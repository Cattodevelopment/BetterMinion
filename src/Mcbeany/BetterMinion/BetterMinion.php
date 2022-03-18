<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion;

use CortexPE\Commando\PacketHooker;
use Mcbeany\BetterMinion\command\MinionCommand;
use Mcbeany\BetterMinion\event\EventHandler;
use Mcbeany\BetterMinion\minion\MinionFactory;
use Mcbeany\BetterMinion\session\SessionManager;
use Mcbeany\BetterMinion\utils\Configuration;
use pocketmine\plugin\PluginBase;

final class BetterMinion extends PluginBase{
	protected function onEnable() : void{
		if(!PacketHooker::isRegistered()){
			PacketHooker::register($this);
		}
		Configuration::init($this);
		MinionFactory::init($this);
		SessionManager::init($this);
		$this->getServer()->getCommandMap()->register("minion", new MinionCommand($this));
		$this->getServer()->getPluginManager()->registerEvents(new EventHandler, $this);
	}
}