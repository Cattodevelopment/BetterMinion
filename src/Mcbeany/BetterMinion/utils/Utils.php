<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\utils;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use function explode;
use function is_numeric;

final class Utils{
	public static function parseItem(string $input) : ?Item{
		/** @var StringToItemParser $parser */
		$parser = StringToItemParser::getInstance();
		/** @var LegacyStringToItemParser $legacyParser */
		$legacyParser = LegacyStringToItemParser::getInstance();
		try {
			return $parser->parse($input) ?? self::parseIdMeta($input) ?? $legacyParser->parse($input);
		} catch (LegacyStringToItemParserException) {
			return null;
		}
	}

	public static function parseIdMeta(string $input) : ?Item{
		$parts = explode(":", $input);
		if(!is_numeric($parts[0])){
			return null;
		}
		$id = (int) $parts[0];
		$meta = 0;
		if(isset($parts[1])){
			$meta = $parts[1];
			if(!is_numeric($meta)){
				return null;
			}
			$meta = (int) $meta;
		}
		/** @var ItemFactory $factory */
		$factory = ItemFactory::getInstance();
		$item = $factory->get($id, $meta);
		return $item->isNull() && $item->equals(VanillaItems::AIR()) ? null : $item;
	}

	public static function parseBlock(string $input) : ?Block{
		$block = self::parseItem($input)?->getBlock();
		return $block?->asItem()->isNull() ? null : $block;
	}

	public static function parseToString(Block|Item $input) : string{
		return $input->getId() . ":" . $input->getMeta();
	}
}