<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions\entities;

use Mcbeany\BetterMinion\events\minions\MinionCollectResourcesEvent;
use Mcbeany\BetterMinion\events\players\PlayerTakeMinionEvent;
use Mcbeany\BetterMinion\minions\informations\MinionInformation;
use Mcbeany\BetterMinion\minions\informations\MinionInventory;
use Mcbeany\BetterMinion\minions\informations\MinionNBT;
use Mcbeany\BetterMinion\minions\MinionFactory;
use Mcbeany\BetterMinion\utils\Configuration;
use Mcbeany\BetterMinion\utils\Utils;
use pocketmine\block\Block;
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

	protected int $tickWait = 0, $tickWork = 0;
	protected bool $isWorking = true;
	protected mixed $target = null;

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
		$this->setNameTagVisible();
		$this->setNameTagAlwaysVisible();
	}

	public function saveNBT() : CompoundTag{
		return parent::saveNBT()
			->setString(MinionNBT::OWNER, $this->owner->toString())
			->setString(MinionNBT::OWNER_NAME, $this->ownerName)
			->setTag(MinionNBT::INFORMATION, $this->minionInformation->serializeTag())
			->setTag(MinionNBT::INVENTORY, $this->minionInventory->serializeTag());
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->minionInventory->isFull()){
			if($this->isWorking){
				$this->stopWorking();
				$this->setNameTag("My inventory is full :<");
				return true;
			}
		}
		if(!$this->isWorking){
			$this->continueWorking();
			return true;
		}
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

	public function setNameTag(?string $name = null) : void{
		if($name === null){
			$this->setNameTagVisible(false);
		}
		$this->setNameTagVisible();
		parent::setNameTag($name ?? "");
	}

	public function isWorking() : bool{
		return $this->isWorking;
	}

	public function stopWorking() : void{
		$this->isWorking = false;
		$this->clearTarget();
	}

	public function continueWorking() : void{
		$this->isWorking = true;
		$this->setNameTag();
	}

	public function clearTarget() : void{
		$this->tickWork = 0;
		$this->target = null;
	}

	/**
	 * @return \Generator|Block[]
	 */
	protected function getWorkingTargets() : \Generator{
		/** @phpstan-ignore-next-line */
		yield;
	}

	public function getActionTime() : int{
		return 100; // TODO: Time based on level
	}

	/**
	 * @param Item[] $drops
	 */
	protected function addStuff(array $drops) : void{
		$event = new MinionCollectResourcesEvent($this, $drops);
		$event->call();
		if($event->isCancelled()){
			return;
		}
		$this->minionInventory->addItem(...$event->getDrops());
	}

	public function addDrops() : void{
		$this->addStuff($this->minionInformation->getTarget()->getDrops($this->getTool()));
	}

	public function takeStuff(int $slot, Player $player) : bool{
		$item = $this->minionInventory->getItem($slot);
		$addable = $player->getInventory()->getAddableItemQuantity($item);
		$player->getInventory()->addItem((clone $item)->setCount($addable));
		$this->minionInventory->setItem($slot, $item->setCount($item->getCount() - $addable));
		$this->minionInventory->reorder();
		return $item->isNull();
	}

	public function takeMinion(Player $player) : void{
		(new PlayerTakeMinionEvent($player, $this))->call();
		$info = $this->minionInformation;
		Utils::giveItem($player, MinionFactory::getInstance()->getSpawner(
			$info->getType(),
			$info->getTarget(),
			$info->getLevel(),
			$info->getUpgrade()
		));
		foreach($this->minionInventory->getContents() as $item){
			$this->getWorld()->dropItem($this->getPosition(), $item);
		}
		$this->flagForDespawn();
	}

	protected function onAction() : void{
	}

	/**
	 * As @NgLamVN explained, onOfflineAction will be executed if there is no viewer or minion is not loaded, the thing onOfflineAction
	 * will do is just adding drops to the inventory instead of sending block breaking animation, thus can reduce server laggy.
	 * Very cool :ayyyy:
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
		return VanillaItems::AIR();
	}
}
