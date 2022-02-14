<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions\informations;

use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\Tag;
use function array_map;
use function get_class;

class MinionInventory extends SimpleInventory implements MinionNBT{
	public function setSize(int $size) : void{
		$this->slots->setSize($size);
	}

	public function serializeTag() : ListTag{
		return new ListTag(
			array_map(
				fn (Item $item) => $item->nbtSerialize(),
				$this->getContents()
			),
			NBT::TAG_Compound
		);
	}

	/**
	 * @param ListTag $tag
	 */
	public static function deserializeTag(Tag $tag) : self{
		if(!$tag instanceof ListTag){
			throw new \InvalidArgumentException("Expected " . ListTag::class . ", got " . get_class($tag));
		}
		$inventory = new self(MinionInformation::MAX_LEVEL);
		$inventory->setContents(
			array_map(
				fn (CompoundTag $tag) => Item::nbtDeserialize($tag),
				$tag->getValue()
			)
		);
		return $inventory;
	}
}
