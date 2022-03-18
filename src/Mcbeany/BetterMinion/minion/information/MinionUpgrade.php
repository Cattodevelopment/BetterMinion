<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information;

use pocketmine\nbt\tag\CompoundTag;

abstract class MinionUpgrade implements MinionNBT{
	public function __construct(
		protected string $name
	) {
	}

	public function getName() : string{
		return $this->name;
	}

	public function nbtSerialize() : CompoundTag{
		return CompoundTag::create()
			->setString(MinionNBT::UPGRADE_NAME, $this->name)
			->setString(MinionNBT::UPGRADE_CLASS, static::class);
	}
}