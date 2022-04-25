<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\commands\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Mcbeany\BetterMinion\BetterMinion;
use Mcbeany\BetterMinion\commands\arguments\PlayerArgument;
use Mcbeany\BetterMinion\commands\arguments\TypeArgument;
use Mcbeany\BetterMinion\minions\MinionType;
use Mcbeany\BetterMinion\utils\Language;
use Mcbeany\BetterMinion\utils\Utils;
use pocketmine\block\BlockLegacyIds;
use pocketmine\command\CommandSender;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\player\Player;
use function count;

class GiveCommand extends BaseSubCommand{

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		if(count($args) < 2){
			$sender->sendMessage("Usage: /minion give <type> <target> <player>");
			return;
		}
		$type = MinionType::fromString($args["type"]);
		if($type === null){
			$sender->sendMessage(Language::type_not_found($args["type"]));
		}
		try{
			$target = Utils::parseItem($args["target"])->getBlock();
			if($target->getId() !== BlockLegacyIds::AIR){
				/** @var Player|null $player */
				$player = $sender;
				if(!$sender instanceof Player){
					if(!isset($args["player"])){
						$sender->sendMessage(Language::no_selected_player());
						return;
					}
					$player = $sender->getServer()->getPlayerByPrefix($args["player"]);
				}
				if($player === null){
					$sender->sendMessage(Language::player_not_found($args["player"]));
					return;
				}
				$item = BetterMinion::getInstance()->createSpawner($type, $target->getIdInfo());
				$player->sendMessage(Language::received_minion_spawner($type, $target));
				if(!empty($player->getInventory()->addItem($item))){
					$player->sendMessage(Language::inventory_is_full());
				}
				$sender->sendMessage(Language::gave_player_spawner($player, $type, $target));
			}
			return;
		}catch(LegacyStringToItemParserException){
		}

		$sender->sendMessage(Language::target_not_found($args["target"]));
	}

	/**
	 * @throws ArgumentOrderException
	 */
	protected function prepare() : void{
		$this->setPermission("betterminion.commands.give");
		$this->registerArgument(0, new TypeArgument());
		$this->registerArgument(1, new RawStringArgument("target", true));
		$this->registerArgument(2, new PlayerArgument());
	}
}