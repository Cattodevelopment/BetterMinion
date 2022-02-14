<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions\entities;

use Mcbeany\BetterMinion\events\minions\MinionCollectResourcesEvent;
use Mcbeany\BetterMinion\minions\informations\MinionInformation;
use Mcbeany\BetterMinion\minions\informations\MinionInventory;
use Mcbeany\BetterMinion\minions\informations\MinionNBT;
use Mcbeany\BetterMinion\utils\Configuration;
use pocketmine\block\Block;
use pocketmine\block\BlockToolType;
use pocketmine\entity\Human;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\Player;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class BaseMinion extends Human{
	public const MAX_TICKDIFF = 20;

	protected UuidInterface $owner;
	protected string $ownerName;
	protected MinionInformation $minionInformation;
	protected MinionInventory $minionInventory;

	protected int $tickWait = 0;
	protected bool $isWorking = true;

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$this->owner = Uuid::uuid3(Uuid::NIL, $nbt->getString(MinionNBT::OWNER));
		$this->ownerName = $nbt->getString(MinionNBT::OWNER_NAME);
		$infoNBT = $nbt->getCompoundTag(MinionNBT::INFORMATION);
		if($infoNBT === null){
			$this->flagForDespawn();
			return;
		}
		$this->minionInformation = MinionInformation::deserializeTag($infoNBT);
		$this->minionInventory = MinionInventory::deserializeTag(
			$nbt->getListTag(MinionNBT::INVENTORY) ??
			new ListTag([], NBT::TAG_Compound)
		);
		$this->minionInventory->setSize($this->minionInformation->getLevel());
		$this->getInventory()->setItemInHand($this->getTool());
		$this->setScale(Configuration::getInstance()->minion_scale());
		$this->setNameTagAlwaysVisible(false);
	}

	public function saveNBT() : CompoundTag{
		return parent::saveNBT()
			->setString(MinionNBT::OWNER, $this->owner->toString())
			->setString(MinionNBT::OWNER_NAME, $this->ownerName)
			->setTag(MinionNBT::INFORMATION, $this->minionInformation->serializeTag())
			->setTag(MinionNBT::INVENTORY, $this->minionInventory->serializeTag());
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->isWorking()){
			$lastItem = $this->minionInventory->getItem($this->minionInventory->getSize() - 1);
			if(!$lastItem->isNull() && $lastItem->getCount() == $lastItem->getMaxStackSize()){
				$this->stopWorking();
				$this->setNameTag("My inventory is full :<");
				return true;
			}
		}else{
			$this->continueWorking();
		}
		$this->setNameTag();
		return parent::onUpdate($currentTick);
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		if(!$this->isWorking()){
			return parent::entityBaseTick($tickDiff);
		}
		$this->minionAnimationTick($tickDiff);
		$this->tickWait += $tickDiff;
		$actionTime = $this->getActionTime();
		if($this->tickWait >= $actionTime){
			$times = (int) ($this->tickWait / $actionTime);
			$this->tickWait -= $actionTime * $times;
			if($this->tickWait < self::MAX_TICKDIFF){
				if($times > 1){
					$this->doOfflineAction($times - 1);
				}
				$this->onAction();
			}else{
				$this->doOfflineAction($times);
			}
		}
		return parent::entityBaseTick($tickDiff);
	}

	public function setNameTag(string $name = "") : void{
		if(empty($name)){
			$this->setNameTagVisible(false);
		}
		$this->setNameTagVisible();
		parent::setNameTag($name);
	}

	public function isWorking() : bool{
		return $this->isWorking;
	}

	public function stopWorking() : void{
		$this->isWorking = false;
	}

	public function continueWorking() : void{
		$this->isWorking = true;
	}

	/**
	 * @return \Generator|Block[]
	 */
	public function getWorkingTargets() : \Generator{
		yield;
	}

	public function getActionTime() : int{
		return 20; // TODO: Time based on level
	}

	/**
	 * @param Item[] $drops
	 */
	protected function addStuff(array $drops) : void{
		foreach($drops as $drop){
			$event = new MinionCollectResourcesEvent($this, $drop);
			$event->call();
			if($event->isCancelled()){
				continue;
			}
			$this->minionInventory->addItem($drop);
		}
	}

	public function takeStuff(int $slot, Player $player) : bool{
		$item = $this->minionInventory->getItem($slot);
		$addable = $player->getInventory()->getAddableItemQuantity($item);
		$player->getInventory()->addItem((clone $item)->setCount($addable));
		$this->minionInventory->setItem($slot, $item->setCount($item->getCount() - $addable));
		return $item->isNull();
	}

	/*protected function getAirBlock() : ?Block{
		foreach($this->getWorkingTargets() as $target){
			if($target instanceof Block){
				if($target->asItem()->isNull()){
					return $target;
				}
			}
		}
		return null;

	}

	protected function isContainInvalidBlock() : bool{
		foreach($this->getWorkingTargets() as $target){
			if($target instanceof Block){
				if($target->isSameType($this->minionInformation->getRealTarget()) && !$target->asItem()->isNull()){
					return true;
				}
			}
		}
		return false;
	}*/

	protected function onAction() : void{
	}

	/**
	 * As @NgLamVN explained, onOfflineAction will be executed if there is no viewer or minion is not loaded, the thing onOfflineAction
	 * will do is just adding drops to the inventory instead of sending block breaking animation, thus can reduce server laggy.
	 * Very cool :ayyyy:
	 *
	 * @pararm int $times Number of break time
	 */
	protected function doOfflineAction(int $times) : void{
	}

	protected function minionAnimationTick(int $tickDiff = 1) : void{
	}

	public function getOwner() : UuidInterface{
		return $this->owner;
	}

	public function getOwnerName() : string{
		return $this->ownerName;
	}

	public function getOriginalNameTag() : string{
		return $this->ownerName . "'s Minion";
	}

	public function getWorkingRadius() : int{
		return $this->minionInformation->getUpgrade()->hasExpander() ? 3 : 2;
	}

	public function getMinionInformation() : MinionInformation{
		return $this->minionInformation;
	}

	public function getMinionInventory() : MinionInventory{
		return $this->minionInventory;
	}

	public function getTool() : Item{
		return match($this->minionInformation->getRealTarget()->getBreakInfo()->getToolType()){
			BlockToolType::AXE => VanillaItems::IRON_AXE(),
			BlockToolType::HOE => VanillaItems::IRON_HOE(),
			BlockToolType::PICKAXE => VanillaItems::IRON_PICKAXE(),
			BlockToolType::SHOVEL => VanillaItems::IRON_SHOVEL(),
			BlockToolType::SWORD => VanillaItems::IRON_SWORD(),
			BlockToolType::SHEARS => VanillaItems::SHEARS(),
			default => VanillaItems::AIR()
		};
	}
}
