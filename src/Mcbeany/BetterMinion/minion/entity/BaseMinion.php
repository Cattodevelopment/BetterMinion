<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\entity;

use Mcbeany\BetterMinion\minion\entity\objects\MinionInventory;
use Mcbeany\BetterMinion\minion\information\MinionInformation;
use Mcbeany\BetterMinion\minion\information\MinionNBT;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Server;
use function array_map;

abstract class BaseMinion extends Human{
	protected MinionInventory $minionInventory;

	public function __construct(
		Location $location,
		Skin $skin,
		protected string $owner,
		protected MinionInformation $minionInformation,
		?CompoundTag $nbt = null
	){
		parent::__construct(
			location: $location,
			skin: $skin,
			nbt: $nbt
		);
	}

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		if(!Server::getInstance()->getOfflinePlayer($this->owner)->hasPlayedBefore()){
			$this->flagForDespawn();
			return;
		}
		$this->minionInventory = new MinionInventory($this->minionInformation->getLevel());
		/** @var array<CompoundTag> $itemTags */
		$itemTags = $nbt->getListTag(MinionNBT::MINION_INVENTORY)?->getValue() ?? [];
		$this->minionInventory->setContents(array_map(
			fn(CompoundTag $itemTag) => Item::nbtDeserialize($itemTag),
			$itemTags
		));
		$this->minionInventory->reorder();
	}

	public function saveNBT() : CompoundTag{
		return parent::saveNBT()
			->setString(MinionNBT::MINION_OWNER, $this->owner)
			->setTag(MinionNBT::MINION_INFORMATION, $this->minionInformation->nbtSerialize())
			->setTag(MinionNBT::MINION_INVENTORY, new ListTag(
				array_map(
					fn(Item $item) => $item->nbtSerialize(),
					$this->minionInventory->getContents()
				),
				NBT::TAG_Compound
			));
	}
}