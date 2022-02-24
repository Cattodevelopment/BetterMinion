<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions\informations;

use pocketmine\nbt\tag\Tag;

interface MinionNBT{
	public const INFORMATION = "minionInformation";
	public const TYPE = "minionType";
	public const LEVEL = "minionLevel";

	public const OWNER = "owner";
	public const OWNER_NAME = "ownerName";

	public const TARGET = "minionTarget";
	public const BLOCK_ID = "blockId";
	public const VARIANT = "blockVariant";

	public const INVENTORY = "minionInventory";

	public const UPGRADE = "minionUpgrade";
	public const AUTO_SMELTER = "autoSmelter";
	public const AUTO_SELLER = "autoSeller";
	public const COMPACTOR = "compactor";
	public const EXPANDER = "expander";

	/**
	 * @return mixed
	 */
	public function serializeTag();
	/**
	 * @deprecated
	 *
	 * @return mixed
	 */
	public static function deserializeTag(Tag $tag);
}
