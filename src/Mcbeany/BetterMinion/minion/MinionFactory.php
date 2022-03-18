<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion;

use Exception;
use InvalidArgumentException;
use Mcbeany\BetterMinion\event\player\PlayerSpawnMinionEvent;
use Mcbeany\BetterMinion\minion\entity\BaseMinion;
use Mcbeany\BetterMinion\minion\entity\types\MiningMinion;
use Mcbeany\BetterMinion\minion\information\MinionInformation;
use Mcbeany\BetterMinion\minion\information\MinionNBT;
use Mcbeany\BetterMinion\minion\information\MinionType;
use Mcbeany\BetterMinion\minion\information\MinionUpgrade;
use Mcbeany\BetterMinion\minion\information\targets\BlockTargetType;
use Mcbeany\BetterMinion\minion\information\upgrades\TogglableUpgrade;
use Mcbeany\BetterMinion\utils\Configuration;
use Mcbeany\BetterMinion\utils\SingletonTrait;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\World;
use function basename;
use function fmod;
use function is_a;
use function is_subclass_of;

final class MinionFactory{
	use SingletonTrait;

	/** @phpstan-var array<string, class-string<MinionType>> $defaultTypes */
	protected array $defaultTypes = [];
	/** @phpstan-var array<string, array<string, class-string<BaseMinion>>> $registeredMinions */
	protected array $registeredMinions = [];
	/** @phpstan-var array<string, MinionUpgrade> $defaultUpgrades */
	protected array $defaultUpgrades = [];

	/**
	 * @phpstan-return array<string, class-string<MinionType>>
	 */
	public function getDefaultTypes() : array{
		return $this->defaultTypes;
	}

	/**
	 * @phpstan-return array<string, array<string, class-string<BaseMinion>>>
	 */
	public function getRegisteredMinions() : array{
		return $this->registeredMinions;
	}

	/**
	 * @phpstan-return array<string, MinionUpgrade>
	 */
	public function getDefaultUpgrades() : array{
		return $this->defaultUpgrades;
	}

	public function getType(string $type, string $target) : ?MinionType{
		try{
			$defaultType = $this->defaultTypes[$type] ?? null;
			if($defaultType === null){
				return null;
			}
			$target = $defaultType::parseTarget($target);
			return new $defaultType($type, $target);
		}catch(Exception){
			return null;
		}
	}

	public function getMinion(MinionType $type) : ?string{
		return $this->registeredMinions[$type->getName()][$type->stringifyTarget()] ??
			$this->registeredMinions[$type->getName()][""] ??
			null;
	}

	protected function onInit() : void{
		$this->registerDefaultMinion(MiningMinion::class, MinionType::MINING_MINION, BlockTargetType::class);
		$this->registerUpgrade(TogglableUpgrade::AUTO_SMELTER());
		$this->registerUpgrade(TogglableUpgrade::AUTO_SELLER());
		$this->registerUpgrade(TogglableUpgrade::COMPACTOR());
		$this->registerUpgrade(TogglableUpgrade::EXPANDER());
	}

	/**
	 * @template E of BaseMinion
	 * @template T of MinionType
	 *
	 * @phpstan-param class-string<E> $entityClass
	 * @phpstan-param class-string<T> $typeClass
	 * @param array<string> $saveNames
	 */
	public function registerDefaultMinion(string $entityClass, string $typeName, string $typeClass, array $saveNames = []) : void{
		$this->defaultTypes[$typeName] = $typeClass;
		$this->registeredMinions[$typeName][""] = $entityClass;
		$this->registerMinion($entityClass, $saveNames);
	}

	/**
	 * @template E of BaseMinion
	 *
	 * @phpstan-param class-string<E> $entityClass
	 * @param array<string> $saveNames
	 */
	public function registerCustomMinion(string $entityClass, MinionType $type, array $saveNames = []) : void{
		$typeName = $type->getName();
		/** @phpstan-var class-string<MinionType> $typeClass */
		$typeClass = $this->defaultTypes[$typeName] ?? null;
		if(!is_a($typeClass, MinionType::class, true) || !$type instanceof $typeClass){
			throw new InvalidArgumentException("Cannot register custom minion with default unregistered type");
		}
		$this->registeredMinions[$typeName][$type->stringifyTarget()] = $entityClass;
		$this->registerMinion($entityClass, $saveNames);
	}

	/**
	 * @template E of BaseMinion
	 *
	 * @phpstan-param class-string<E> $entityClass
	 * @param array<string> $saveNames
	 */
	protected function registerMinion(string $entityClass, array $saveNames = []) : void{
		if(!is_subclass_of($entityClass, BaseMinion::class, true)){
			throw new InvalidArgumentException("$entityClass is not a subclass of BaseMinion");
		}
		/** @var EntityFactory $factory */
		$factory = EntityFactory::getInstance();
		$factory->register(
			$entityClass,
			function(World $world, CompoundTag $nbt) use ($entityClass) : BaseMinion{
				return new $entityClass(
					location: EntityDataHelper::parseLocation($nbt, $world),
					skin: Human::parseSkinNBT($nbt),
					owner: $nbt->getString(MinionNBT::MINION_OWNER),
					minionInformation: MinionInformation::nbtDeserialize(
						$nbt->getCompoundTag(MinionNBT::MINION_INFORMATION) ??
						CompoundTag::create()
					),
					nbt: $nbt
				);
			},
			empty($saveNames) ? [basename($entityClass)] : $saveNames
		);
	}

	public function registerUpgrade(MinionUpgrade $upgrade) : void{
		$this->defaultUpgrades[$upgrade->getName()] = $upgrade;
	}

	/**
	 * @param array<MinionUpgrade> $upgrades
	 */
	public function newSpawner(MinionType $type, int $level = 1, ?array $upgrades = null) : Item{
		$item = Configuration::getInstance()->minion_spawner();
		$item->setNamedTag($item->getNamedTag()->setTag(
			MinionNBT::MINION_INFORMATION,
			(new MinionInformation(
				type: $type,
				level: $level,
				upgrades: $upgrades ?? $this->getDefaultUpgrades()
			))->nbtSerialize())
		);
		return $item;
	}

	public function spawnMinion(MinionInformation $information, Player $player) : bool{
		$className = $this->getMinion($information->getType());
		if($className === null){
			return false;
		}
		/** @var BaseMinion $minion */
		$minion = new $className(
			location: Location::fromObject(
				$player->getPosition()->floor()->add(0.5, 0, 0.5),
				$player->getWorld(),
				fmod($player->getLocation()->getYaw(), 360)
			),
			skin: $player->getSkin(),
			owner: $player->getName(),
			minionInformation: $information
		);
		$event = new PlayerSpawnMinionEvent($player, $minion);
		$event->call();
		if($event->isCancelled()){
			return false;
		}
		$minion->spawnToAll();
		return true;
	}
}