<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\events\minions;

use Mcbeany\BetterMinion\minions\entities\BaseMinion;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\item\Item;

class MinionCollectResourcesEvent extends MinionEvent implements Cancellable {
	use CancellableTrait;

	/**
	 * @param Item[] $drops
	 */
	public function __construct(
		BaseMinion $minion,
		private array $drops
	) {
		parent::__construct($minion);
	}

	/**
	 * @return Item[]
	 */
	public function getDrops() : array{
		return $this->drops;
	}

	/**
	 * @param Item[] $drops
	 */
	public function setDrops(array $drops) : void{
		$this->drops = $drops;
	}
}
