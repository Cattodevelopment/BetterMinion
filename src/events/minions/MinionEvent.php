<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\events\minions;

use Mcbeany\BetterMinion\minions\entities\BaseMinion;
use pocketmine\event\Event;

abstract class MinionEvent extends Event{
	public function __construct(
		protected BaseMinion $minion
	) {
	}

	public function getMinion() : BaseMinion{
		return $this->minion;
	}
}
