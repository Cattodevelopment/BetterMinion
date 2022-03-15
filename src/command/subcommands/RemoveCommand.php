<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\command\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Mcbeany\BetterMinion\session\SessionManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class RemoveCommand extends BaseSubCommand {
	public function __construct() {
		parent::__construct("remove", "Toggle remove mode");
	}

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
		SessionManager::getInstance()->getSession($player)?->toggleRemoveMode();
	}
}