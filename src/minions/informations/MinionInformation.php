<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions\informations;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIdentifier;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;
use function get_class;

class MinionInformation implements MinionNBT{
	public const MIN_LEVEL = 1;
	public const MAX_LEVEL = 15;
	public function __construct(
		private MinionType $type,
		private BlockIdentifier $target,
		private MinionUpgrade $upgrade,
		private int $level = self::MIN_LEVEL
		// TODO
	) {
	}

	public function getType() : MinionType{
		return $this->type;
	}

	public function getTarget() : BlockIdentifier{
		return $this->target;
	}

	public function getRealTarget() : Block{
		/** @var Block $block */
		$block = BlockFactory::getInstance()->get(
			$this->target->getBlockId(),
			$this->target->getVariant()
		);
		return $block;
	}

	public function getUpgrade() : MinionUpgrade{
		return $this->upgrade;
	}

	public function getLevel() : int{
		return $this->level;
	}

	public function increaseLevel() : void{
		$this->level++;
	}

	protected function targetSerialize() : CompoundTag{
		return CompoundTag::create()
			->setInt(MinionNBT::BLOCK_ID, $this->target->getBlockId())
			->setInt(MinionNBT::VARIANT, $this->target->getVariant());
	}

	protected static function targetDeserialize(CompoundTag $tag) : BlockIdentifier{
		return new BlockIdentifier(
			$tag->getInt(MinionNBT::BLOCK_ID),
			$tag->getInt(MinionNBT::VARIANT)
		);
	}

	public function serializeTag() : CompoundTag{
		return CompoundTag::create()
			->setTag(MinionNBT::TYPE, $this->type->serializeTag())
			->setTag(MinionNBT::TARGET, $this->targetSerialize())
			->setTag(MinionNBT::UPGRADE, $this->upgrade->serializeTag())
			->setInt(MinionNBT::LEVEL, $this->level);
	}

	/**
	 * @param CompoundTag $tag
	 */
	public static function deserializeTag(Tag $tag) : self{
		if(!$tag instanceof CompoundTag){
			throw new \InvalidArgumentException("Expected " . CompoundTag::class . ", got " . get_class($tag));
		}
		return new self(
			MinionType::deserializeTag($tag->getTag(MinionNBT::TYPE)),
			self::targetDeserialize($tag->getTag(MinionNBT::TARGET)),
			MinionUpgrade::deserializeTag($tag->getTag(MinionNBT::UPGRADE)),
			$tag->getInt(MinionNBT::LEVEL)
		);
	}
}
