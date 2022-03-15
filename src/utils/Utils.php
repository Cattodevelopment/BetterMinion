<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\utils;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;

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

    public static function parseBlock(string $input) : ?Block{
        $block = self::parseItem($input)?->getBlock();
        return $block?->asItem()->isNull() ? null : $block;
    }

	public static function blockToString(Block $block) : string{
		return $block->getId() . ":" . $block->getMeta();
	}
}