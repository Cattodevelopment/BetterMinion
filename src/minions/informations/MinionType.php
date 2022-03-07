<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions\informations;

use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\utils\EnumTrait;
use function get_class;
use function mb_strtoupper;
use function ucfirst;

/**
 * This doc-block is generated automatically, do not modify it manually.
 * This must be regenerated whenever registry members are added, removed or changed.
 * @see build/generate-registry-annotations.php
 * @generate-registry-docblock
 *
 * @method static MinionType MINING()
 */

class MinionType implements MinionNBT{
	use EnumTrait;

	protected static function setup() : void{
		self::registerAll(
			new self("mining")
		);
	}

	public static function fromString(string $typeName) : ?self{
		self::checkInit();
		/** @var self $type */
		$type = self::$members[mb_strtoupper($typeName)] ?? null;
		return $type;
	}

	public function typeName() : string{
		return ucfirst($this->name());
	}

	public function nbtSerialize() : StringTag{
		return new StringTag($this->name());
	}

	/**
	 * @param StringTag $tag
	 */
	public static function nbtDeserialize(Tag $tag) : self{
		if(!$tag instanceof StringTag){
			throw new \InvalidArgumentException("Expected " . StringTag::class . ", got " . get_class($tag));
		}
		$type = self::fromString($tag->getValue());
		if($type === null){
			throw new \InvalidArgumentException("Expected " . self::class . ", got null");
		}
		return $type;
	}
}
