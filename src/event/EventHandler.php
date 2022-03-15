<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\event;

use Mcbeany\BetterMinion\event\player\PlayerInteractMinionEvent;
use Mcbeany\BetterMinion\minion\entity\BaseMinion;
use Mcbeany\BetterMinion\minion\information\MinionInformation;
use Mcbeany\BetterMinion\minion\information\MinionNBT;
use Mcbeany\BetterMinion\minion\MinionFactory;
use Mcbeany\BetterMinion\session\SessionManager;
use Mcbeany\BetterMinion\utils\Configuration;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class EventHandler implements Listener {
	public function handleJoin(PlayerJoinEvent $event) : void{
		SessionManager::getInstance()->createSession($event->getPlayer());
	}

	public function handleQuit(PlayerQuitEvent $event) : void{
		SessionManager::getInstance()->destroySession($event->getPlayer());
	}

	public function handleItemUse(PlayerItemUseEvent $event) : void{
		if($this->useSpawner($event->getPlayer(), $event->getItem())){
			$event->cancel();
		}
	}

	public function useSpawner(Player $player, Item $item) : bool{
		if($item->equals(Configuration::getInstance()->minion_spawner(), true, false)){
			$nbt = $item->getNamedTag()->getCompoundTag(MinionNBT::INFORMATION);
			if($nbt !== null){
				if(MinionFactory::getInstance()->spawnMinion(MinionInformation::nbtDeserialize($nbt), $player)){
					$item->pop();
					$player->getInventory()->setItemInHand($item);
					return true;
				}
			}
		}
		return false;
	}

	public function handleInteractEntity(PlayerEntityInteractEvent $event) : void{
		$minion = $event->getEntity();
		if(!$minion instanceof BaseMinion){
			return;
		}
		$this->clickMinion($event->getPlayer(), $minion);
	}

	public function handleDamageEntity(EntityDamageByEntityEvent $event) : void{
		$player = $event->getDamager();
		$minion = $event->getEntity();
		if(!$player instanceof Player or !$minion instanceof BaseMinion){
			return;
		}
		$this->clickMinion($player, $minion);
		$event->cancel();
	}

	public function clickMinion(Player $player, BaseMinion $minion) : void{
		if(SessionManager::getInstance()->getSession($player)?->inRemoveMode() ?? false){
			$minion->flagForDespawn();
			return;
		}
		$event = new PlayerInteractMinionEvent($player, $minion);
		$event->call();
		if($event->isCancelled()){
			return;
		}
	}
}