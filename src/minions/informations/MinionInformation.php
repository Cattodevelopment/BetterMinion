<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions\informations;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use function get_class;

class MinionInformation implements MinionNBT{
	public const MIN_LEVEL = 1;
	public const MAX_LEVEL = 15;
	public function __construct(
		private MinionType $type,
		private Block $target,
		private int $level,
		private MinionUpgrade $upgrade
		// TODO
	) {
	}

	public function getType() : MinionType{
		return $this->type;
	}

	public function getTarget() : Block{
		return $this->target;
	}

	public function getLevel() : int{
		return $this->level;
	}

	public function increaseLevel() : void{
		$this->level++;
	}

	public function getUpgrade() : MinionUpgrade{
		return $this->upgrade;
	}

	protected function targetSerialize() : CompoundTag{
		$info = $this->target->getIdInfo();
		return CompoundTag::create()
			->setInt(MinionNBT::BLOCK_ID, $info->getBlockId())
			->setInt(MinionNBT::VARIANT, $info->getVariant());
	}

	protected static function targetDeserialize(CompoundTag $tag) : Block{
		/** @var BlockFactory $factory */
		$factory = BlockFactory::getInstance();
		return $factory->get(
			$tag->getInt(MinionNBT::BLOCK_ID),
			$tag->getInt(MinionNBT::VARIANT)
		);
	}

	public function nbtSerialize() : CompoundTag{
		return CompoundTag::create()
			->setTag(MinionNBT::TYPE, $this->type->nbtSerialize())
			->setTag(MinionNBT::TARGET, $this->targetSerialize())
			->setInt(MinionNBT::LEVEL, $this->level)
			->setTag(MinionNBT::UPGRADE, $this->upgrade->nbtSerialize());
	}

	/**
	 * @param CompoundTag $tag
	 */
	public static function nbtDeserialize(Tag $tag) : self{
		if(!$tag instanceof CompoundTag){
			throw new \InvalidArgumentException("Expected " . CompoundTag::class . ", got " . get_class($tag));
		}
		$type = $tag->getTag(MinionNBT::TYPE);
		$target = $tag->getTag(MinionNBT::TARGET);
		$upgrade = $tag->getTag(MinionNBT::UPGRADE);
		if(!$type instanceof StringTag){
			throw new \InvalidArgumentException("Expected " . CompoundTag::class . ", got " . ($type === null ? "null" : get_class($type)));
		}
		if(!$target instanceof CompoundTag){
			throw new \InvalidArgumentException("Expected " . CompoundTag::class . ", got " . ($target === null ? "null" : get_class($target)));
		}
		if(!$upgrade instanceof CompoundTag){
			throw new \InvalidArgumentException("Expected " . CompoundTag::class . ", got " . ($upgrade === null ? "null" :get_class($upgrade)));
		}
		return new self(
			MinionType::nbtDeserialize($type),
			self::targetDeserialize($target),
			$tag->getInt(MinionNBT::LEVEL),
			MinionUpgrade::nbtDeserialize($upgrade)
		);
	}
}
