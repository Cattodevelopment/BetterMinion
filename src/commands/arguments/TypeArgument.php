<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\commands\arguments;

use CortexPE\Commando\args\StringEnumArgument;
use Mcbeany\BetterMinion\minions\informations\MinionType;
use pocketmine\command\CommandSender;
use function array_map;
use function array_values;

class TypeArgument extends StringEnumArgument {
	public function getTypeName() : string{
		return "string";
	}

	public function parse(string $argument, CommandSender $sender) : ?MinionType{
		return MinionType::fromString($argument);
	}

	/**
	 * @return string[]
	 */
	public function getEnumValues() : array{
		return array_map(
			fn (MinionType $type) : string => $type->name(),
			array_values(MinionType::getAll())
		);
	}
}
