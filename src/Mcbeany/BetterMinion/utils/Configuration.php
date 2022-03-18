<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\utils;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

final class Configuration{
	use SingletonTrait;

	protected function onInit() : void{
		$this->plugin->saveDefaultConfig();
	}

	public function minion_spawner() : Item{
		/** @var string $input */
		$input = $this->plugin->getConfig()->get("minion-spawner");
		return Utils::parseItem($input) ?? VanillaItems::NETHER_STAR();
	}
}