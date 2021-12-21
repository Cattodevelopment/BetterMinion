<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\commands;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\BaseSubCommand;
use Mcbeany\BetterMinion\commands\subcommands\GiveCommand;
use pocketmine\command\CommandSender;

class MinionCommand extends BaseCommand{


	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		$sender->sendMessage("Usage: " . "/minion <" . implode("|",
				array_map(fn(BaseSubCommand $subCommand) => $subCommand->getName(), $this->getSubCommands())) . "> [options...]");
	}

	protected function prepare() : void{
		$this->setPermission("betterminion.commands");
		$this->registerSubCommand(new GiveCommand("give", "Give player a minion spawner"));
	}

}
