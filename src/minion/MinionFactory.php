<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion;

use Mcbeany\BetterMinion\event\player\PlayerSpawnMinionEvent;
use Mcbeany\BetterMinion\minion\entity\BaseMinion;
use Mcbeany\BetterMinion\minion\entity\types\MiningMinion;
use Mcbeany\BetterMinion\minion\information\MinionInformation;
use Mcbeany\BetterMinion\minion\information\MinionNBT;
use Mcbeany\BetterMinion\minion\information\MinionType;
use Mcbeany\BetterMinion\minion\information\MinionUpgrade;
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
use function serialize;

final class MinionFactory {
	use SingletonTrait;

	/** @phpstan-var array<string, class-string<BaseMinion>> $minionClasses */
	private array $minionClasses = [];
	/** @var array<string, MinionUpgrade> $defaultUpgrades */
	private array $defaultUpgrades = [];

	protected function onInit() : void{
		$this->addDefaultUpgrade(TogglableUpgrade::AUTO_SMELTER());
		$this->registerMinion(MiningMinion::class, MinionType::MINING());
	}

	/**
	 * @phpstan-param class-string<BaseMinion> $className
	 * @phpstan-param MinionType      $type
	 */
	public function registerMinion(string $className, MinionType $type) : void{
		/** @var EntityFactory $factory */
		$factory = EntityFactory::getInstance();
		$factory->register(
			$className,
			function(World $world, CompoundTag $nbt) use ($className) : BaseMinion{
				return new $className(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
			},
			[basename($className)]
		);
		$this->minionClasses[serialize($type)] = $className;
	}

	public function addDefaultUpgrade(MinionUpgrade $upgrade) : void{
		$this->defaultUpgrades[$upgrade->getName()] = $upgrade;
	}

	public function getMinionClass(MinionType $type) : ?string{
		return $this->minionClasses[serialize($type)] ??
			$this->minionClasses[serialize(new MinionType($type->getName()))] ??
			null;
	}

	/**
	 * @return array<string, MinionUpgrade>
	 */
	public function getDefaultUpgrades() : array{
		return $this->defaultUpgrades;
	}

	public function getDefaultUpgrade(string $name) : ?MinionUpgrade{
		return $this->defaultUpgrades[$name] ?? null;
	}

	/**
	 * @param array<MinionUpgrade> $upgrades
	 */
	public function newSpawner(MinionType $type, int $level = 1, ?array $upgrades = null) : Item{
		$item = Configuration::getInstance()->minion_spawner();
		$item->setNamedTag($item->getNamedTag()->setTag(
			MinionNBT::INFORMATION,
			(new MinionInformation($type, $upgrades ?? $this->getDefaultUpgrades(), $level))->nbtSerialize())
		);
		return $item;
	}

	public function spawnMinion(MinionInformation $information, Player $player) : bool{
		$class = $this->getMinionClass($information->getType());
		if($class === null){
			return false;
		}
		/** @var BaseMinion $entity */
		$entity = new $class(Location::fromObject(
			$player->getPosition()->floor()->add(0.5, 0, 0.5),
			$player->getWorld(),
			fmod($player->getLocation()->getYaw(), 360)
		), $player->getSkin(), $information, CompoundTag::create());
		$event = new PlayerSpawnMinionEvent($player, $entity);
		$event->call();
		if($event->isCancelled()){
			return false;
		}
		$entity->spawnToAll();
		return true;
	}
}