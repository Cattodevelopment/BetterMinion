<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion;

use Mcbeany\BetterMinion\minion\entity\BaseMinion;
use Mcbeany\BetterMinion\minion\entity\types\MiningMinion;
use Mcbeany\BetterMinion\minion\information\MinionType;
use Mcbeany\BetterMinion\utils\SingletonTrait;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use function basename;
use function strval;

final class MinionFactory {
	use SingletonTrait;

	/** @var array<string, class-string<BaseMinion>> $minionClasses */
	private array $minionClasses = [];

	protected function onInit() : void{
		$this->register(MiningMinion::class, MinionType::MINING());
	}

	/**
	 * @phpstan-param class-string<BaseMinion> $className
	 * @phpstan-param MinionType      $type
	 */
	public function register(string $className, MinionType $type) : void{
		/** @var EntityFactory $factory */
		$factory = EntityFactory::getInstance();
		$factory->register(
			$className,
			function(World $world, CompoundTag $nbt) use ($className) : BaseMinion{
				return new $className(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
			},
			[basename($className)]
		);
		$this->minionClasses[strval($type)] = $className;
	}

	public function getMinionClass(MinionType $type) : ?string{
		return $this->minionClasses[strval($type)] ??
			$this->minionClasses[$type->getName()] ??
			null;
	}
}