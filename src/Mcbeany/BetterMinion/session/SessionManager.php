<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\session;

use Mcbeany\BetterMinion\utils\SingletonTrait;
use pocketmine\player\Player;

final class SessionManager{
	use SingletonTrait;

	/** @var Session[] $sessions */
	protected array $sessions = [];

	public function createSession(Player $player) : void{
		$this->sessions[$player->getId()] = new Session;
	}

	public function getSession(Player $player) : ?Session{
		return $this->sessions[$player->getId()] ?? null;
	}

	public function destroySession(Player $player) : void{
		unset($this->sessions[$player->getId()]);
	}
}