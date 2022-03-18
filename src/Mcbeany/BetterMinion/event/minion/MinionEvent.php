<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\event\minion;

use Mcbeany\BetterMinion\minion\entity\BaseMinion;
use pocketmine\event\Event;

abstract class MinionEvent extends Event {
	public function __construct(
		protected BaseMinion $minion
	){
	}

	public function getMinion() : BaseMinion{
		return $this->minion;
	}
}