<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information;

use Mcbeany\BetterMinion\minion\MinionFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use function array_combine;
use function array_keys;
use function array_map;
use function assert;

class MinionInformation implements MinionNBT {
	public const MIN_LEVEL = 1;
	public const MAX_LEVEL = 15;
	//TODO: Add lock feature

	/**
	 * @param array<string, MinionUpgrade> $upgrades
	 */
	public function __construct(
		private MinionType $type,
		private array $upgrades,
		private int $level = self::MIN_LEVEL
	) {
	}

	public function getType() : MinionType{
		return $this->type;
	}

	/**
	 * @return array<string, MinionUpgrade>
	 */
	public function getUpgrades() : array{
		return $this->upgrades;
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
			->setTag(MinionNBT::UPGRADES, new ListTag(array_map(static function(MinionUpgrade $upgrade) : CompoundTag{
				return $upgrade->nbtSerialize();
			}, $this->upgrades)))
			->setInt(MinionNBT::LEVEL, $this->level);
	}

	public static function nbtDeserialize(CompoundTag $tag) : self{
		return new self(
			MinionType::nbtDeserialize($tag->getCompoundTag(MinionNBT::TYPE) ?? CompoundTag::create()),
			array_combine(
				array_keys(MinionFactory::getInstance()->getDefaultUpgrades()),
				array_map(static function(CompoundTag $upgradeTag) : MinionUpgrade{
					$name = $upgradeTag->getString(MinionNBT::UPGRADE_NAME);
					$default = MinionFactory::getInstance()->getDefaultUpgrade($name);
					assert($default instanceof MinionUpgrade, "Minion upgrade $name is not registered");
					/** @var MinionUpgrade $upgrade */
					$upgrade = $default::class::nbtDeserialize($upgradeTag);
					return $upgrade;
				}, $tag->getListTag(MinionNBT::UPGRADES)?->getValue() ?? [])
			),
			$tag->getInt(MinionNBT::LEVEL)
		);
	}
}