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
 * @method static MinionType FARMING()
 */

class MinionType implements MinionNBT{
	use EnumTrait;

	protected static function setup() : void{
		self::registerAll(
			new self("mining"),
			new self("farming")
		);
	}

	/**
	 * @return MinionType|null 
	 */
	public static function fromString(string $typeName) : ?self{
		self::checkInit();
		return self::$members[mb_strtoupper($typeName)] ?? null;
	}

	public function typeName() : string{
		return ucfirst($this->name());
	}

	public function serializeTag() : StringTag{
		return new StringTag($this->name());
	}

	/**
	 * @param StringTag $tag
	 */
	public static function deserializeTag(Tag $tag) : self{
		if(!$tag instanceof StringTag){
			throw new \InvalidArgumentException("Expected " . StringTag::class . ", got " . get_class($tag));
		}
		return self::fromString($tag->getValue());
	}
}
