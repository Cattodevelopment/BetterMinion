<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\entities\types;

use Mcbeany\BetterMinion\entities\BaseMinion;
use function array_rand;

class FarmingMinion extends BaseMinion{
	public function getWorkingBlocks() : array{
		$blocks = [];
		$x = (int) $this->getPosition()->getX();
		$y = (int) $this->getPosition()->getY();
		$z = (int) $this->getPosition()->getZ();
		for($i = $x - $this->getWorkingRadius(); $i <= $x + $this->getWorkingRadius(); $i++){
			for($j = $z - $this->getWorkingRadius(); $j <= $z + $this->getWorkingRadius(); $z++){
				if(($i !== $x) && ($j !== $z)){
					$blocks[] = $this->getPosition()->getWorld()->getBlockAt($i, $y, $j);
				}
			}
		}
		return $blocks;
	}

	protected function onAction() : void{
		$working_blocks = $this->getWorkingBlocks();
		$target = $working_blocks[array_rand($working_blocks)];
		$this->lookAt($target->getPosition());
		//TODO: Harvest Crops Action
	}

	protected function doOfflineAction(int $times) : void{
		//TODO: Offline action (just add stuff to inventory)
	}
}