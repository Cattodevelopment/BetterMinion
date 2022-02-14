<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions\entities\types;

use Mcbeany\BetterMinion\minions\entities\BaseMinion;
use pocketmine\block\BlockToolType;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class MiningMinion extends BaseMinion{
	protected function getWorkingTargets() : \Generator{
		$radius = $this->getWorkingRadius();
		for($x = -$radius; $x <= $radius; ++$x){
			for($z = -$radius; $z <= $radius; ++$z){
				if($x == 0 and $z == 0){
					continue;
				}
				yield $this->getWorld()->getBlock($this->getPosition()->add($x, -1, $z));
			}
		}
	}

	public function getTool() : Item{
		return match($this->getMinionInformation()->getTarget()->getBreakInfo()->getToolType()){
			BlockToolType::AXE => VanillaItems::IRON_AXE(),
			BlockToolType::PICKAXE => VanillaItems::IRON_PICKAXE(),
			BlockToolType::SHOVEL => VanillaItems::IRON_SHOVEL(),
			BlockToolType::HOE => VanillaItems::IRON_HOE(),
			BlockToolType::SHEARS => VanillaItems::SHEARS(),
			default => parent::getTool()
		};
	}
}
