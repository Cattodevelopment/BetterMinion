<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions;

use pocketmine\nbt\tag\CompoundTag;

final class MinionInfo implements MinionNBT{

	public function __construct(
		private MinionType $type,
		private MinionUpgrade $upgrade,
		private int $level,
		private float $moneyHeld,
		private int $collectedResources
	){
	}

	public static function nbtDeserialize(CompoundTag $nbt) : self{
		return new self(
			MinionType::fromString($nbt->getString(MinionNBT::TYPE)),
			MinionUpgrade::nbtDeserialize($nbt->getCompoundTag(MinionNBT::UPGRADE)),
			$nbt->getInt(MinionNBT::LEVEL),
			$nbt->getFloat(MinionNBT::MONEY_HELD),
			$nbt->getInt(MinionNBT::COLLECTED_RESOURCES)
		);
	}

	public function incrementLevel() : void{
		$this->level++;
	}

	public function incrementMoneyHeld(float $moneyHeld) : void{
		$this->moneyHeld += $moneyHeld;
	}

	public function incrementCollectedResources(int $collectedResources) : void{
		$this->collectedResources += $collectedResources;
	}

	public function nbtSerialize() : CompoundTag{
		return CompoundTag::create()
			->setString(MinionNBT::TYPE, $this->getType()->name())
			->setTag(MinionNBT::UPGRADE, $this->getUpgrade()->nbtSerialize())
			->setInt(MinionNBT::LEVEL, $this->getLevel())
			->setFloat(MinionNBT::MONEY_HELD, $this->getMoneyHeld())
			->setInt(MinionNBT::COLLECTED_RESOURCES, $this->getCollectedResources());
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

	public function getMoneyHeld() : float{
		return $this->moneyHeld;
	}

	public function getCollectedResources() : int{
		return $this->collectedResources;
	}

}