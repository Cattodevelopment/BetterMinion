<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\events;

use Mcbeany\BetterMinion\minions\informations\MinionInformation;
use Mcbeany\BetterMinion\minions\informations\MinionNBT;
use Mcbeany\BetterMinion\minions\MinionFactory;
use Mcbeany\BetterMinion\utils\Configuration;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;

final class EventListener implements Listener{
	public function handleItemUse(PlayerItemUseEvent $event) : void{
		$player = $event->getPlayer();
		$item = $event->getItem();
		if($item->equals(Configuration::getInstance()->minion_spawner(), true, false)){
			$nbt = $item->getNamedTag()->getCompoundTag(MinionNBT::INFORMATION);
			if($nbt !== null){
				if(MinionFactory::getInstance()->spawnMinion(MinionInformation::deserializeTag($nbt), $player)){
                    $item->pop();
					$player->getInventory()->setItemInHand($item);
					$event->cancel();
				}
			}
		}
	}
}