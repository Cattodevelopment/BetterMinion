<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\commands\subcommands;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Mcbeany\BetterMinion\commands\arguments\PlayerArgument;
use Mcbeany\BetterMinion\sessions\SessionManager;
use Mcbeany\BetterMinion\utils\Language;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class RemoveCommand extends BaseSubCommand{

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		$player = $sender;
		if(!$sender instanceof Player){
			if (!isset($args["player"])) {
				$sender->sendMessage("Usage: /minion remove <player>");
				return;
			}
			$player = $sender->getServer()->getPlayerByPrefix($args["player"]);
		}
		if($player === null){
			$sender->sendMessage(Language::player_not_found($args["player"]));
			return;
		}
		$session = SessionManager::getSession($player);
		$sender->sendMessage(Language::toggled_remove_mode($session->toggleRemoveMode()));
	}

	/**
	 * @throws ArgumentOrderException
	 */
	protected function prepare() : void{
		$this->setPermission("betterminion.commands.remove");
		$this->registerArgument(0, new PlayerArgument());
	}

}