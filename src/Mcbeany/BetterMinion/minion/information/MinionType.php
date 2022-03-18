<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information;

use LogicException;
use pocketmine\nbt\tag\CompoundTag;

abstract class MinionType implements MinionNBT{
	public const MINING_MINION = "mining";
	public const FARMING_MINION = "farming";
	public const LUMBERJACK_MINION = "lumberjack";
	public const SLAYING_MINION = "slaying";

	public function __construct(
		protected string $name
	){
	}

	/**
	 * @return mixed
	 */
	abstract public function getTarget();

	abstract public function stringifyTarget() : string;

	/**
	 * @return mixed
	 */
	abstract public static function parseTarget(string $input);

	public function getName() : string{
		return $this->name;
	}

	public function nbtSerialize() : CompoundTag{
		return CompoundTag::create()
			->setString(MinionNBT::TYPE_NAME, $this->name)
			->setString(MinionNBT::TYPE_TARGET, $this->stringifyTarget());
	}

	/**
	 * @throws LogicException
	 *
	 * @return static
	 */
	public static function nbtDeserialize(CompoundTag $nbt){
		throw new LogicException("Cannot deserialize default MinionType from NBT");
	}
}