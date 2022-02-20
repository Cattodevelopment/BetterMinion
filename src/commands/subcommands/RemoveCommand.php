<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\commands\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Mcbeany\BetterMinion\sessions\SessionManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class RemoveCommand extends BaseSubCommand{
	protected function prepare() : void{
		$this->registerArgument(0, new RawStringArgument("player", true));
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		$player = null;
		if($sender instanceof Player){
			$player = $sender;
		}
		if(isset($args["player"])){
			/** @var string $name */
			$name = $args["player"];
			$player = $sender->getServer()->getPlayerByPrefix($name);
		}
		if($player === null){
			return;
		}
		$session = SessionManager::getInstance()->getSession($player);
		if($session === null){
			return;
		}
		$session->toggleRemoveMode();
	}
}