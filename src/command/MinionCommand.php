<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\command;

use CortexPE\Commando\BaseCommand;
use Mcbeany\BetterMinion\BetterMinion;
use Mcbeany\BetterMinion\command\subcommands\GiveCommand;
use pocketmine\command\CommandSender;

final class MinionCommand extends BaseCommand {
	public function __construct(BetterMinion $plugin) {
		parent::__construct($plugin, "minion", "Minion Command");
	}

	protected function prepare() : void{
		$this->registerSubCommand(new GiveCommand);
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		$this->sendUsage();
	}
}