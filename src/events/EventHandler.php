<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\events;

use Mcbeany\BetterMinion\events\player\PlayerInteractMinionEvent;
use Mcbeany\BetterMinion\minions\entities\BaseMinion;
use Mcbeany\BetterMinion\minions\informations\MinionInformation;
use Mcbeany\BetterMinion\minions\informations\MinionNBT;
use Mcbeany\BetterMinion\minions\menus\inventories\MainMinionMenu;
use Mcbeany\BetterMinion\minions\MinionFactory;
use Mcbeany\BetterMinion\sessions\SessionManager;
use Mcbeany\BetterMinion\utils\Configuration;
use Mcbeany\BetterMinion\utils\SingletonTrait;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class EventHandler implements Listener{
	use SingletonTrait;

	protected function onInit() : void{
		$plugin = $this->getOwningPlugin();
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	public function handleJoin(PlayerJoinEvent $event) : void{
		SessionManager::getInstance()->createSession($event->getPlayer());
	}

	public function handleQuit(PlayerQuitEvent $event) : void{
		SessionManager::getInstance()->destroySession($event->getPlayer());
	}

	public function handleItemUse(PlayerItemUseEvent $event) : void{
		$player = $event->getPlayer();
		$item = $event->getItem();
		if($this->useSpawner($player, $item)){
			$event->cancel();
		}
	}

	public function handleInteractEntity(PlayerEntityInteractEvent $event) : void{
		$minion = $event->getEntity();
		if(!$minion instanceof BaseMinion){
			return;
		}
		$this->handleClickMinion($event->getPlayer(), $minion);
	}

	public function handleDamageEntity(EntityDamageByEntityEvent $event) : void{
		$player = $event->getDamager();
		$minion = $event->getEntity();
		if(!$player instanceof Player or !$minion instanceof BaseMinion){
			return;
		}
		$this->handleClickMinion($player, $minion);
		$event->cancel();
	}

	private function useSpawner(Player $player, Item $item) : bool{
		if($item->equals(Configuration::getInstance()->minion_spawner(), true, false)){
			$nbt = $item->getNamedTag()->getCompoundTag(MinionNBT::INFORMATION);
			if($nbt !== null){
				if(MinionFactory::getInstance()->spawnMinion(MinionInformation::deserializeTag($nbt), $player)){
					$item->pop();
					$player->getInventory()->setItemInHand($item);
					return true;
				}
			}
		}
		return false;
	}

	private function handleClickMinion(Player $player, BaseMinion $minion) : void{
		$session = SessionManager::getInstance()->getSession($player);
		if($session === null){
			return;
		}
		if($session->inRemoveMode()) {
			$minion->flagForDespawn();
			return;
		}
		$event = new PlayerInteractMinionEvent($player, $minion);
		$event->call();
		if($event->isCancelled()){
			return;
		}
		(new MainMinionMenu($minion, $player))->sendToPlayer();
	}
}