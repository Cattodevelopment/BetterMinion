<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\events\player;

use Mcbeany\BetterMinion\minions\entities\BaseMinion;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

abstract class PlayerMinionEvent extends PlayerEvent{
	public function __construct(
		Player $player,
		protected BaseMinion $minion
	) {
		$this->player = $player;
	}

	public function getMinion() : BaseMinion{
		return $this->minion;
	}
}
