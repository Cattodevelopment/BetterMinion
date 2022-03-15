<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information;

use pocketmine\nbt\tag\CompoundTag;

class MinionUpgrade implements MinionNBT {
	public function __construct(
		private bool $autoSmelter = false,
		private bool $autoSeller = false,
		private bool $compactor = false,
		private bool $expander = false,
		private bool $chestLinker = false,
		private bool $customSkin = false,
	) {
	}

	public function hasAutoSmelter() : bool{
		return $this->autoSmelter;
	}

	public function hasAutoSeller() : bool{
		return $this->autoSeller;
	}

	public function hasCompactor() : bool{
		return $this->compactor;
	}

	public function hasExpander() : bool{
		return $this->expander;
	}

	public function hasChestLinker() : bool{
		return $this->chestLinker;
	}

	public function hasCustomSkin() : bool{
		return $this->customSkin;
	}

	public function setAutoSmelter(bool $autoSmelter = true) : void{
		$this->autoSmelter = $autoSmelter;
	}

	public function setAutoSeller(bool $autoSeller = true) : void{
		$this->autoSeller = $autoSeller;
	}

	public function setCompactor(bool $compactor = true) : void{
		$this->compactor = $compactor;
	}

	public function setExpander(bool $expander = true) : void{
		$this->expander = $expander;
	}

	public function setChestLinker(bool $chestLinker = true) : void{
		$this->chestLinker = $chestLinker;
	}

	public function setCustomSkin(bool $customSkin = true) : void{
		$this->customSkin = $customSkin;
	}

	public function nbtSerialize() : CompoundTag{
		return CompoundTag::create()
			->setByte(MinionNBT::AUTO_SMELTER, (int) $this->autoSmelter)
			->setByte(MinionNBT::AUTO_SELLER, (int) $this->autoSeller)
			->setByte(MinionNBT::COMPACTOR, (int) $this->compactor)
			->setByte(MinionNBT::EXPANDER, (int) $this->expander)
			->setByte(MinionNBT::CHEST_LINKER, (int) $this->chestLinker)
			->setByte(MinionNBT::CUSTOM_SKIN, (int) $this->customSkin);
	}

	public static function nbtDeserialize(CompoundTag $tag) : self{
		return new self(
			(bool) $tag->getByte(MinionNBT::AUTO_SMELTER),
			(bool) $tag->getByte(MinionNBT::AUTO_SELLER),
			(bool) $tag->getByte(MinionNBT::COMPACTOR),
			(bool) $tag->getByte(MinionNBT::EXPANDER),
			(bool) $tag->getByte(MinionNBT::CHEST_LINKER),
			(bool) $tag->getByte(MinionNBT::CUSTOM_SKIN)
		);
	}
}