<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\utils;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Config;

final class Configuration {
	use SingletonTrait;

	private Config $pmConfig;

	protected function onInit() : void{
		$this->getPlugin()->saveDefaultConfig();
		$this->pmConfig = $this->getPlugin()->getConfig();
	}

	public function minion_spawner() : Item{
		/** @var string $input */
		$input = $this->pmConfig->get("minion-spawner");
		return Utils::parseItem($input) ?? VanillaItems::NETHER_STAR();
	}

	public function minion_scale() : float{
		/** @var float|int $scale */
		$scale = $this->pmConfig->get("minion-scale");
		return (float) $scale;
	}
}