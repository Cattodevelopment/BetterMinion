<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information;

use pocketmine\nbt\tag\CompoundTag;

interface MinionNBT{
	public const MINION_OWNER = "minionOwner";
	public const MINION_INFORMATION = "minionInformation";
	public const MINION_LEVEL = "minionLevel";
	public const MINION_INVENTORY = "minionInventory";

	public const MINION_TYPE = "minionType";
	public const TYPE_NAME = "typeName";
	public const TYPE_TARGET = "typeTarget";

	public const MINION_UPGRADES = "minionUpgrades";
	public const UPGRADE_NAME = "upgradeName";
	public const UPGRADE_CLASS = "upgradeClass";
	public const UPGRADE_TOGGLE = "upgradeToggle";

	public function nbtSerialize() : CompoundTag;

	/**
	 * @return mixed
	 */
	public static function nbtDeserialize(CompoundTag $nbt);
}