<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\commands\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Mcbeany\BetterMinion\commands\arguments\TypeArgument;
use Mcbeany\BetterMinion\minions\informations\MinionType;
use Mcbeany\BetterMinion\minions\MinionFactory;
use Mcbeany\BetterMinion\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function count;

final class GiveCommand extends BaseSubCommand {
	protected function prepare() : void{
		$this->registerArgument(0, new TypeArgument("type"));
		$this->registerArgument(1, new RawStringArgument("target"));
		$this->registerArgument(2, new RawStringArgument("player", true));
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		if(count($args) < 2){
			return;
		}
		/** @var MinionType|null $type */
		$type = $args["type"];
		if($type === null){
			return;
		}
		/** @var string $input */
		$input = $args["target"];
		$item = Utils::parseItem($input);
		if($item === null){
			return;
		}
		$target = $item->getBlock();
		if($target->asItem()->isNull()){
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
		Utils::giveItem($player, MinionFactory::getInstance()->getSpawner($type, $target));
	}
}
