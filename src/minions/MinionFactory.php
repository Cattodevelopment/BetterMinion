<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions;

use Mcbeany\BetterMinion\events\players\PlayerSpawnMinionEvent;
use Mcbeany\BetterMinion\minions\entities\BaseMinion;
use Mcbeany\BetterMinion\minions\entities\types\MiningMinion;
use Mcbeany\BetterMinion\minions\informations\MinionInformation;
use Mcbeany\BetterMinion\minions\informations\MinionNBT;
use Mcbeany\BetterMinion\minions\informations\MinionType;
use Mcbeany\BetterMinion\minions\informations\MinionUpgrade;
use Mcbeany\BetterMinion\utils\Configuration;
use Mcbeany\BetterMinion\utils\SingletonTrait;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
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

final class MinionFactory {
	use SingletonTrait;

	/** @var array<string, string> $minions */
	private array $minions = [];

	protected function onInit() : void{
		$this->register(MiningMinion::class, MinionType::MINING());
	}

	public function getSpawner(MinionType $type, Block $target, int $level = 1, ?MinionUpgrade $upgrade = null) : Item{
		$item = Configuration::getInstance()->minion_spawner();
		$item->setNamedTag($item->getNamedTag()->setTag(
			MinionNBT::INFORMATION,
			(new MinionInformation($type, $target, $level, $upgrade ?? new MinionUpgrade))->nbtSerialize()
		));
		return $item;
	}

	public function spawnMinion(MinionInformation $information, Player $player) : bool{
		$class = $this->getMinion($information->getType());
		if($class === null){
			return false;
		}
		$nbt = CompoundTag::create()
			->setString(MinionNBT::OWNER, $player->getUniqueId()->toString())
			->setString(MinionNBT::OWNER_NAME, $player->getName())
			->setTag(MinionNBT::INFORMATION, $information->nbtSerialize());
		/** @var BaseMinion $entity */
		$entity = new $class(Location::fromObject(
			$player->getPosition()->floor()->add(0.5, 0, 0.5),
			$player->getWorld(),
			fmod($player->getLocation()->getYaw(), 360)
		), $player->getSkin(), $nbt);
		$event = new PlayerSpawnMinionEvent($player, $entity);
		$event->call();
		if($event->isCancelled()){
			return false;
		}
		$entity->spawnToAll();
		return true;
	}

	public function register(string $className, MinionType $type) : void{
		if(!is_a($className, BaseMinion::class, true)){
			throw new \InvalidArgumentException("$className is not a valid minion class");
		}
		/** @var EntityFactory $factory */
		$factory = EntityFactory::getInstance();
		$factory->register(
			$className,
			function(World $world, CompoundTag $nbt) use ($className) : Entity{
				return new $className(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
			},
			[basename($className)]
		);
		$this->minions[$type->name()] = $className;
	}

	public function getMinion(MinionType $type) : ?string{
		return $this->minions[$type->name()] ?? null;
	}
}
