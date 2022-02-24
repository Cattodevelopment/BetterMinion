<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\utils;

use pocketmine\item\Item;
use pocketmine\utils\Config;
use function is_float;
use function is_string;

class Configuration{
	use SingletonTrait;

	protected function onInit() : void{
		$this->getPlugin()->saveDefaultConfig();
		$this->getConfig()->setDefaults($this->defaults());
	}

	final public function minion_spawner() : Item{
		$name = $this->get("spawner");
		$item = Utils::parseItem(is_string($name) ? $name : "");
		if($item === null){
			$this->setDefault("spawner");
			return $this->minion_spawner();
		}
		return $item;
	}

	final public function minion_scale() : float{
		$scale = $this->get("scale");
		if(!is_float($scale)){
			$this->setDefault("scale");
			return $this->minion_scale();
		}
		return $scale;
	}

	public function getConfig() : Config{
		return $this->getPlugin()->getConfig();
	}

	public function get(string $key) : mixed{
		return $this->getConfig()->get($key, null);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function defaults() : array{
		return [
			"spawner" => "nether_star",
			"scale" => 0.5
		];
	}

	public function setDefault(string $key) : void{
		$this->getConfig()->set($key, $this->defaults()[$key]);
		$this->getConfig()->save();
	}
}
