<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion;

use CortexPE\Commando\PacketHooker;
use Mcbeany\BetterMinion\commands\MinionCommand;
use Mcbeany\BetterMinion\minions\MinionFactory;
use Mcbeany\BetterMinion\utils\Configuration;
use pocketmine\plugin\PluginBase;

final class BetterMinion extends PluginBase{
	protected function onEnable() : void{
		Configuration::init($this);
		if(!PacketHooker::isRegistered()){
			PacketHooker::register($this);
		}
		$this->getServer()->getCommandMap()->register("minion", new MinionCommand(
			$this,
			"minion",
			"Minion Command"
		));
		MinionFactory::init($this);
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
	}
}
