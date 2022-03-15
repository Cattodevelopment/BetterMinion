<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\command\argument;

use CortexPE\Commando\args\StringEnumArgument;
use Mcbeany\BetterMinion\minion\information\MinionType;
use pocketmine\command\CommandSender;

class TypeArgument extends StringEnumArgument {
	public function parse(string $argument, CommandSender $sender) : string{
		return $argument;
	}

	public function getTypeName() : string{
		return "string";
	}

	/**
	 * @return array<string>
	 */
	public function getEnumValues() : array{
		return MinionType::getAll();
	}
}