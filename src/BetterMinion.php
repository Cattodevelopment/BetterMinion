<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion;

use Mcbeany\BetterMinion\minion\MinionFactory;
use pocketmine\plugin\PluginBase;

final class BetterMinion extends PluginBase {
	protected function onEnable() : void{
		MinionFactory::init($this);
	}
}