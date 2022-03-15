<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\entity;

class UpgradeManager {
	public function __construct(
		private BaseMinion $minion
	) {
	}
}