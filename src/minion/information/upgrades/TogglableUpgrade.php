<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information\upgrades;

use Mcbeany\BetterMinion\minion\information\MinionNBT;
use Mcbeany\BetterMinion\minion\information\MinionUpgrade;
use pocketmine\nbt\tag\CompoundTag;

class TogglableUpgrade extends MinionUpgrade {
	public function __construct(
		string $name,
		private bool $enabled = false
	) {
		parent::__construct($name);
	}

	public function isEnabled() : bool{
		return $this->enabled;
	}

	public function setEnabled(bool $enabled = true) : void{
		$this->enabled = $enabled;
	}

	public static function AUTO_SMELTER(bool $enabled = false) : self{
		return new self("autoSmelter", $enabled);
	}

	public static function AUTO_SELLER(bool $enabled = false) : self{
		return new self("autoSeller", $enabled);
	}

	public static function COMPACTOR(bool $enabled = false) : self{
		return new self("compactor", $enabled);
	}

	public static function EXPANDER(bool $enabled = false) : self{
		return new self("expander", $enabled);
	}

	public function nbtSerialize() : CompoundTag{
		return parent::nbtSerialize()
			->setByte(MinionNBT::UPGRADE_TOGGLE, (int) $this->enabled);
	}

	public static function nbtDeserialize(CompoundTag $tag) : MinionUpgrade{
		return new self(
			$tag->getString(MinionNBT::UPGRADE_NAME),
			(bool) $tag->getByte(MinionNBT::UPGRADE_TOGGLE)
		);
	}
}