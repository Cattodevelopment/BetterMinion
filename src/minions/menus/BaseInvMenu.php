<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions\menus;

use Mcbeany\BetterMinion\minions\entities\BaseMinion;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;
use pocketmine\Server;

abstract class BaseInvMenu extends InvMenu implements IMinionMenu, Listener{

	public const INV_TYPE = "";

	public function __construct(
		private BaseMinion $minion,
		private Player $player
	) {
		parent::__construct(InvMenuHandler::getTypeRegistry()->get(static::INV_TYPE));
		Server::getInstance()->getPluginManager()->registerEvents($this, InvMenuHandler::getRegistrant());
		$this->setInventoryCloseListener(function(Player $player, Inventory $inventory) : void{
			HandlerListManager::global()->unregisterAll($this);
		});
	}

	public function handleEntityDespawn(EntityDespawnEvent $event) : void{
		if($event->getEntity() instanceof BaseMinion){
			$this->getPlayer()->removeCurrentWindow();
		}
	}

	public function sendToPlayer() : void{
		$this->render();
		$this->send($this->getPlayer());
	}

	public function getMinion() : BaseMinion{
		return $this->minion;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	abstract protected function render() : void;
}