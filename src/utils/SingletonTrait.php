<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\utils;

use Mcbeany\BetterMinion\BetterMinion;

trait SingletonTrait {
    /** @var static $instance */
	private static $instance;

    final private function __construct(
        private BetterMinion $plugin
    ) {
    }

    public function getPlugin() : BetterMinion{
        return $this->plugin;
    }

    protected function onInit() : void{
    }

    public static function init(BetterMinion $plugin) : void{
		(self::$instance = new static($plugin))->onInit();
	}

	public static function getInstance() : static{
		return self::$instance;
	}
}