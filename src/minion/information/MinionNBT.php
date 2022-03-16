<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information;

use pocketmine\nbt\tag\CompoundTag;

interface MinionNBT {
	public const OWNER = "owner";
	public const INFORMATION = "minionInformation";
	public const LEVEL = "minionLevel";
	public const INVENTORY = "minionInventory";

	public const TYPE = "minionType";
	public const TYPE_NAME = "typeName";
	public const TYPE_TARGET = "typeTarget";
	public const TYPE_YOUNG_TARGET = "typeYoungTarget";
	public const TYPE_CONDITION = "typeCondition";

	public const UPGRADES = "minionUpgrades";
	public const UPGRADE_NAME = "upgradeName";
	public const UPGRADE_TOGGLE = "upgradeToggle";

	public function nbtSerialize() : CompoundTag;

	/**
	 * @internal
	 *
	 * @return MinionInformation|MinionType|MinionUpgrade
	 */
	public static function nbtDeserialize(CompoundTag $tag);
}