<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\events\minions;

use Mcbeany\BetterMinion\minions\entities\BaseMinion;
use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\world\Position;

class MinionStartWorkEvent extends MinionEvent implements Cancellable {
	use CancellableTrait;

	public function __construct(
		BaseMinion $minion,
		private Position $position
	) {
		parent::__construct($minion);
	}

	public function getPosition() : Position{
		return $this->position;
	}

	public function getBlock() : Block{
		return $this->position->getWorld()->getBlock($this->position);
	}
}