<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information;

use InvalidArgumentException;
use Mcbeany\BetterMinion\minion\MinionFactory;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use function array_map;

class MinionInformation implements MinionNBT{
	public const MIN_LEVEL = 1;
	public const MAX_LEVEL = 2;

	/**
	 * @param array<MinionUpgrade> $upgrades
	 */
	public function __construct(
		protected MinionType $type,
		protected int $level = self::MIN_LEVEL,
		protected array $upgrades = []
	){
	}

	public function getType() : MinionType{
		return $this->type;
	}

	public function getLevel() : int{
		return $this->level;
	}

	/**
	 * @return array<MinionUpgrade>
	 */
	public function getUpgrades() : array{
		return $this->upgrades;
	}

	public function increaseLevel() : void{
		$this->level++;
	}

	public function nbtSerialize() : CompoundTag{
		return CompoundTag::create()
			->setTag(MinionNBT::MINION_TYPE, $this->type->nbtSerialize())
			->setInt(MinionNBT::MINION_LEVEL, $this->level)
			->setTag(MinionNBT::MINION_UPGRADES, new ListTag(
				array_map(
					fn(MinionUpgrade $upgrade) => $upgrade->nbtSerialize(),
					$this->upgrades
				),
				NBT::TAG_Compound
			));
	}

	public static function nbtDeserialize(CompoundTag $nbt) : self{
		$typeTag = $nbt->getCompoundTag(MinionNBT::MINION_TYPE) ?? CompoundTag::create();
		$typeName = $typeTag->getString(MinionNBT::TYPE_NAME);
		$type = MinionFactory::getInstance()->getType(
			$typeName,
			$typeTag->getString(MinionNBT::TYPE_TARGET)
		);
		if($type === null){
			throw new InvalidArgumentException("Invalid minion type $typeName");
		}
		/** @var array<CompoundTag> $upgradeTags */
		$upgradeTags = $nbt->getListTag(MinionNBT::MINION_UPGRADES)?->getValue() ?? [];
		return new self(
			$type,
			$nbt->getInt(MinionNBT::MINION_LEVEL),
			array_map(
				function(CompoundTag $upgradeTag) : MinionUpgrade{
					/** @phpstan-var class-string<MinionUpgrade> $upgradeClass */
					$upgradeClass = $upgradeTag->getString(MinionNBT::UPGRADE_CLASS);
					/** @var MinionUpgrade $upgrade */
					$upgrade = $upgradeClass::nbtDeserialize($upgradeTag);
					return $upgrade;
				},
				$upgradeTags
			)
		);
	}
}