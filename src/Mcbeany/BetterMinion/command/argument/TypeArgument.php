<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\command\argument;

use CortexPE\Commando\args\StringEnumArgument;
use Mcbeany\BetterMinion\minion\MinionFactory;
use pocketmine\command\CommandSender;
use function array_keys;

class TypeArgument extends StringEnumArgument{
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
		return array_keys(MinionFactory::getInstance()->getDefaultTypes());
	}
}