<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion;

use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use Mcbeany\BetterMinion\commands\MinionCommand;
use Mcbeany\BetterMinion\events\EventHandler;
use Mcbeany\BetterMinion\minions\MinionFactory;
use Mcbeany\BetterMinion\sessions\SessionManager;
use Mcbeany\BetterMinion\utils\Configuration;
use pocketmine\plugin\PluginBase;

final class BetterMinion extends PluginBase{
	/**
	 * @throws HookAlreadyRegistered
	 */
	protected function onEnable() : void{
		Configuration::init($this);
		SessionManager::init($this);
		if(!PacketHooker::isRegistered()){
			PacketHooker::register($this);
		}
		$this->getServer()->getCommandMap()->register("minion", new MinionCommand(
			$this,
			"minion",
			"Minion Command"
		));
		MinionFactory::init($this);
		$this->getServer()->getPluginManager()->registerEvents(new EventHandler, $this);
	}
}
