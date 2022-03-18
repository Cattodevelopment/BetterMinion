<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\command\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Mcbeany\BetterMinion\command\argument\TypeArgument;
use Mcbeany\BetterMinion\minion\MinionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class GiveCommand extends BaseSubCommand{
	public function __construct() {
		parent::__construct("give", "Give player a minion spawner");
	}

	protected function prepare() : void{
		$this->registerArgument(0, new TypeArgument("type"));
		$this->registerArgument(1, new RawStringArgument("target"));
		$this->registerArgument(2, new RawStringArgument("player", true));
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		/** @var string $target */
		$target = $args["target"];
		/** @var string $typeName */
		$typeName = $args["type"];
		$type = MinionFactory::getInstance()->getType($typeName, $target);
		if($type === null){
			return;
		}
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
		$extras = $player->getInventory()->addItem(MinionFactory::getInstance()->newSpawner($type));
		if(!empty($extras)){
			$player->dropItem(...$extras);
		}
	}
}