<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\utils;

use Mcbeany\BetterMinion\BetterMinion;

trait SingletonTrait{
	/** @var static $instance */
	private static $instance;

	final public function __construct(
		protected BetterMinion $plugin
	){
	}

	protected function onInit() : void{
	}

	public static function init(BetterMinion $plugin) : void{
		(static::$instance = new self(plugin: $plugin))->onInit();
	}

	public static function getInstance() : static{
		return static::$instance;
	}
}