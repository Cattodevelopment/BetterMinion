<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information;

use Mcbeany\BetterMinion\utils\Utils;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use function assert;
use function class_exists;
use function in_array;
use function is_a;

class MinionType implements MinionNBT {
	/** @var array<string> $types */
	private static array $types = [];

	public function __construct(
		private string $name,
		private Block|string|null $target = null,
		private Block|string|null $youngTarget = null,
		private ?Block $condition = null
	) {
		if(!in_array($name, self::$types, true)){
			self::$types[] = $name;
		}
	}

	public function getName() : string{
		return $this->name;
	}

	public function getBlockTarget() : Block{
		assert($this->target instanceof Block);
		return $this->target;
	}

	public function getEntityTarget() : string{
		assert($this->target !== null and is_a($this->target, Entity::class));
		return $this->target;
	}

	public function getYoungBlockTarget() : ?Block{
		assert($this->youngTarget instanceof Block);
		return $this->youngTarget;
	}

	public function getYoungEntityTarget() : string{
		assert($this->youngTarget !== null and is_a($this->youngTarget, Entity::class));
		return $this->youngTarget;
	}

	public function getBlockCondition() : ?Block{
		return $this->condition;
	}

	public static function MINING(?Block $target = null) : self{
		return new MinionType("mining", $target);
	}

	public static function FARMING(
		?Block $target = null,
		?Block $youngTarget = null,
		?Block $condition = null
	) : self{
		return new MinionType("farming", $target, $youngTarget, $condition);
	}

	public static function LUMBERJACK(
		?Block $target = null,
		?Block $youngTarget = null,
		?Block $condition = null
	) : self{
		return new MinionType("lumberjack", $target, $youngTarget, $condition);
	}

	public function nbtSerialize() : CompoundTag{
		return CompoundTag::create()
			->setString(MinionNBT::TYPE_NAME, $this->name)
			->setString(MinionNBT::TYPE_TARGET, $this->target instanceof Block ?
				Utils::blockToString($this->target) :
				($this->target ?? ""))
			->setString(MinionNBT::TYPE_YOUNG_TARGET, $this->youngTarget instanceof Block ?
				Utils::blockToString($this->youngTarget) :
				($this->youngTarget ?? ""))
			->setString(MinionNBT::TYPE_CONDITION, $this->condition === null ?
				"" :
				Utils::blockToString($this->condition));
	}

	public static function nbtDeserialize(CompoundTag $tag) : self{
		$target = $tag->getString(MinionNBT::TYPE_TARGET);
		if(!class_exists($target)){
			$target = Utils::parseBlock($target);
		}
		$youngTarget = $tag->getString(MinionNBT::TYPE_YOUNG_TARGET);
		if(!class_exists($youngTarget)){
			$youngTarget = Utils::parseBlock($youngTarget);
		}
		return new self(
			$tag->getString(MinionNBT::TYPE_NAME),
			$target,
			$youngTarget,
			Utils::parseBlock($tag->getString(MinionNBT::TYPE_CONDITION))
		);
	}

	/**
	 * @return array<string>
	 */
	public static function getAll() : array{
		return self::$types;
	}
}