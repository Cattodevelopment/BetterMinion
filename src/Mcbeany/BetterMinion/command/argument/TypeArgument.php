<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\commands\arguments;

use CortexPE\Commando\args\StringEnumArgument;
use Mcbeany\BetterMinion\minions\MinionType;
use pocketmine\command\CommandSender;
use function array_map;
use function array_values;
use function strtolower;

class TypeArgument extends StringEnumArgument{

	public function __construct(){ parent::__construct("type", true); }

	public function parse(string $argument, CommandSender $sender) : string{
		return strtolower($argument);
	}

	public function getTypeName() : string{
		return "string";
	}

	public function getEnumValues() : array{
		return array_values(array_map(fn(MinionType $type) => $type->name(), MinionType::getAll()));
	}

}