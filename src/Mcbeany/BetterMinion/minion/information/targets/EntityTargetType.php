<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information\targets;

use InvalidArgumentException;
use Mcbeany\BetterMinion\minion\information\MinionType;
use pocketmine\entity\Entity;
use function is_a;

class EntityTargetType extends MinionType{
	public function __construct(
		string $name,
		protected string $target
	){
		parent::__construct(name: $name);
	}

	public function getTarget() : string{
		return $this->target;
	}

	public function stringifyTarget() : string{
		return $this->target;
	}

	public static function parseTarget(string $input) : string{
		if(!is_a($input, Entity::class, true)){
			throw new InvalidArgumentException("Invalid entity target: $input");
		}
		return $input;
	}
}