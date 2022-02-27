<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\utils;

use pocketmine\item\Item;
use pocketmine\utils\Config;
use function is_string;

class Configuration{
	use SingletonTrait;

	protected function onInit() : void{
		$this->getOwningPlugin()->saveDefaultConfig();
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
		/** @var float $scale */
		$scale = $this->get("scale");
		return $scale;
	}

	public function getConfig() : Config{
		return $this->getOwningPlugin()->getConfig();
	}

	public function get(string $key) : mixed{
		$default = $this->defaults()[$key];
		$set = $this->getConfig()->get($key, null);
		if(gettype($default) !== gettype($set)){
			$this->setDefault($key);
			return $default;
		}
		return $set;
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

	/**
	 * @throws \JsonException
	 */
	public function setDefault(string $key) : void{
		$this->getConfig()->set($key, $this->defaults()[$key]);
		$this->getConfig()->save();
	}
}
