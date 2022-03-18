<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\session;

class Session{
	protected bool $removeMode = false;

	public function toggleRemoveMode() : bool{
		return $this->removeMode = !$this->removeMode;
	}

	public function inRemoveMode() : bool{
		return $this->removeMode;
	}
}