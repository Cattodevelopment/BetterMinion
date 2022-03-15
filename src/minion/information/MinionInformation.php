<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information;

use pocketmine\nbt\tag\CompoundTag;

class MinionInformation implements MinionNBT {
	public function __construct(
		private MinionType $type,
		private MinionUpgrade $upgrade,
		private int $level
	) {
	}

	public function getType() : MinionType{
		return $this->type;
	}

	public function getUpgrade() : MinionUpgrade{
		return $this->upgrade;
	}

	public function getLevel() : int{
		return $this->level;
	}

	public function increaseLevel() : int{
		return $this->level++;
	}

	public function nbtSerialize() : CompoundTag{
		return CompoundTag::create()
			->setTag(MinionNBT::TYPE, $this->type->nbtSerialize())
			->setTag(MinionNBT::UPGRADE, $this->upgrade->nbtSerialize())
			->setInt(MinionNBT::LEVEL, $this->level);
	}

	public static function nbtDeserialize(CompoundTag $tag) : self{
		return new self(
			MinionType::nbtDeserialize($tag->getCompoundTag(MinionNBT::TYPE) ?? CompoundTag::create()),
			MinionUpgrade::nbtDeserialize($tag->getCompoundTag(MinionNBT::UPGRADE) ?? CompoundTag::create()),
			$tag->getInt(MinionNBT::LEVEL)
		);
	}
}