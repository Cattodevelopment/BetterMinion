<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\utils;

use pocketmine\crafting\FurnaceType;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;
use pocketmine\Server;

final class Utils {
	public static function parseItem(string $input) : ?Item{
		/** @var StringToItemParser $parser */
		$parser = StringToItemParser::getInstance();
		/** @var LegacyStringToItemParser $legacyParser */
		$legacyParser = LegacyStringToItemParser::getInstance();
		try {
			return $parser->parse($input) ?? $legacyParser->parse($input);
		} catch (LegacyStringToItemParserException) {
			return null;
		}
	}

	public static function parseSmeltedItem(Item $input) : ?Item{
		foreach(FurnaceType::getAll() as $type){
			$manager = Server::getInstance()->getCraftingManager()->getFurnaceRecipeManager($type);
			if(($recipe = $manager->match($input)) !== null){
				return $recipe->getResult();
			}
		}
		return null;
	}

	public static function giveItem(Player $player, Item $item) : bool{
		$rest = $player->getInventory()->addItem($item);
		foreach($rest as $drop){
			$player->dropItem($drop);
		}
		return empty($rest);
	}
}
