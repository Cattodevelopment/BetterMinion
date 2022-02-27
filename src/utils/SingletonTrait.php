<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\utils;

use Mcbeany\BetterMinion\BetterMinion;
use pocketmine\plugin\PluginOwnedTrait;

trait SingletonTrait{
	use PluginOwnedTrait {
		__construct as private __pluginConstruct;
	}

	/** @var static $instance */
	private static $instance;

	final public function __construct(BetterMinion $owningPlugin) {
		$this->__pluginConstruct($owningPlugin);
	}

	public static function init(BetterMinion $plugin) : void{
		(self::$instance = new static($plugin))->onInit();
	}

	protected function onInit() : void{}

	public static function getInstance() : static{
		return self::$instance;
	}

	public function getOwningPlugin() : BetterMinion{
		/** @var BetterMinion $plugin */
		$plugin = $this->owningPlugin;
		return $plugin;
	}
}
