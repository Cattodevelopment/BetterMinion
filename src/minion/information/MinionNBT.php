<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information;

use pocketmine\nbt\tag\CompoundTag;

interface MinionNBT {
	public const INFORMATION = "minionInformation";
	public const LEVEL = "minionLevel";

	public const TYPE = "minionType";
	public const TYPE_NAME = "typeName";
	public const TYPE_TARGET = "typeTarget";
	public const TYPE_YOUNG_TARGET = "typeYoungTarget";
	public const TYPE_CONDITION = "typeCondition";

	public const UPGRADE = "minionUpgrade";
	public const AUTO_SMELTER = "autoSmelter";
	public const AUTO_SELLER = "autoSeller";
	public const COMPACTOR = "compactor";
	public const EXPANDER = "expander";

	public function nbtSerialize() : CompoundTag;

	/**
	 * @internal
	 *
	 * @return MinionInformation|MinionType|MinionUpgrade
	 */
	public static function nbtDeserialize(CompoundTag $tag);
}