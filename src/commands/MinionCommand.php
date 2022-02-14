<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\commands;

use CortexPE\Commando\BaseCommand;
use Mcbeany\BetterMinion\commands\subcommands\GiveCommand;
use pocketmine\command\CommandSender;

final class MinionCommand extends BaseCommand{
	protected function prepare() : void{
		$this->registerSubCommand(new GiveCommand("give", "Give Minion to Player"));
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
	}
}
