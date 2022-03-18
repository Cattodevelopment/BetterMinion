<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information\targets;

use InvalidArgumentException;
use Mcbeany\BetterMinion\minion\information\MinionType;
use Mcbeany\BetterMinion\utils\Utils;
use pocketmine\block\Block;

class BlockTargetType extends MinionType{
	public function __construct(
		string $name,
		protected Block $target
	){
		parent::__construct(name: $name);
	}

	public function getTarget() : Block{
		return $this->target;
	}

	public function stringifyTarget() : string{
		return Utils::parseToString($this->target);
	}

	public static function parseTarget(string $input) : Block{
		$block = Utils::parseBlock($input);
		if($block === null){
			throw new InvalidArgumentException("Invalid block target: $input");
		}
		return $block;
	}
}