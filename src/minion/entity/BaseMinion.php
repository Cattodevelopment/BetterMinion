<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\entity;

use Generator;
use Mcbeany\BetterMinion\minion\entity\objects\MinionInventory;
use Mcbeany\BetterMinion\minion\information\MinionInformation;
use Mcbeany\BetterMinion\minion\information\MinionNBT;
use Mcbeany\BetterMinion\utils\Configuration;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\Player;

use function array_map;

abstract class BaseMinion extends Human {
	public const MAX_TICKDIFF = 20;

	protected string $owner;
	protected MinionInventory $minionInventory;
	protected UpgradeManager $upgradeManager;

	protected int $tickWait = 0, $tickWork = 0;
	protected bool $isWorking = true;

	public function __construct(
		Location $location,
		Skin $skin,
		protected MinionInformation $minionInformation,
		?CompoundTag $nbt = null
	) {
		parent::__construct($location, $skin, $nbt);
	}

	public function getOwner() : string{
		return $this->owner;
	}

	public function getOriginalNameTag() : string{
		return $this->owner . "'s Minion";
	}

	public function getWorkingRadius() : int{
		return /*$this->minionInformation->getUpgrade()->hasExpander() ? 3 :*/ 2;
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

	public function isOwner(Player $player) : bool{
		return $this->owner === $player->getName();
	}

	public function isWorking() : bool{
		return $this->isWorking;
	}

	public function stopWorking() : void{
		$this->isWorking = false;
		$this->tickWork = 0;
	}

	public function continueWorking() : void{
		$this->isWorking = true;
		$this->setNameTag();
	}

	public function getActionTime() : int{
		return 100; // TODO: Time based on level
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

	protected function getWorkingTargets() : Generator{
		yield;
	}

	public function setNameTag(?string $name = null) : void{
		if($name === null){
			$this->setNameTagVisible(false);
		}
		$this->setNameTagVisible();
		parent::setNameTag($name ?? "");
	}

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$this->owner = $nbt->getString(MinionNBT::OWNER);
		if(!$this->getWorld()->getServer()->getOfflinePlayer($this->owner)->hasPlayedBefore()){
			$this->flagForDespawn();
			return;
		}
		if(!isset($this->minionInformation)){
			$this->minionInformation = MinionInformation::nbtDeserialize($nbt->getCompoundTag(MinionNBT::INFORMATION) ?? CompoundTag::create());
		}
		$this->minionInventory = new MinionInventory($this->minionInformation->getLevel());
		$this->minionInventory->setContents(array_map(static function(CompoundTag $tag) : Item{
				return Item::nbtDeserialize($tag);
			}, $nbt->getListTag(MinionNBT::INVENTORY)?->getValue() ?? []
		));
		$this->minionInventory->reorder();
		$this->upgradeManager = new UpgradeManager($this);

		$this->setScale(Configuration::getInstance()->minion_scale());
	}

	public function saveNBT() : CompoundTag{
		return parent::saveNBT()
			->setString(MinionNBT::OWNER, $this->owner)
			->setTag(MinionNBT::INFORMATION, $this->minionInformation->nbtSerialize())
			->setTag(MinionNBT::INVENTORY, new ListTag(array_map(static function(Item $item) : CompoundTag{
					return $item->nbtSerialize();
				}, $this->minionInventory->getContents()
			), NBT::TAG_Compound));
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
		if(!$this->isWorking){
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
}