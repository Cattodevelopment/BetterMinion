<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\entity\objects;

use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use function array_map;
use function array_values;

class MinionInventory extends SimpleInventory {
	public function setSize(int $size) : void{
		$this->slots->setSize($size);
	}

	public function reorder() : void{
		$this->setContents(array_map([self::class, "realItem"], array_values($this->getContents())));
	}

	private static function realItem(?Item $item) : Item{
		return $item ?? VanillaItems::AIR();
	}

	public function isFull() : bool{
		$lastItem = $this->getItem($this->getSize() - 1);
		return !$lastItem->isNull() and $lastItem->getCount() == $lastItem->getMaxStackSize();
	}
}