<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions\menus;

use Mcbeany\BetterMinion\minions\entities\BaseMinion;
use pocketmine\player\Player;

interface IMinionMenu {
	public function sendToPlayer() : void;

	public function getMinion() : BaseMinion;

	public function getPlayer() : Player;
}