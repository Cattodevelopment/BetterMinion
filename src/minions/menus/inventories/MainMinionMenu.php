<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions\menus\inventories;

use Mcbeany\BetterMinion\events\minions\MinionCollectResourcesEvent;
use Mcbeany\BetterMinion\minions\informations\MinionInformation;
use Mcbeany\BetterMinion\minions\menus\BaseInvMenu;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use function array_fill;
use function array_map;
use function array_search;
use function floor;
use function range;

class MainMinionMenu extends BaseInvMenu{
	public const INV_TYPE = InvMenuTypeIds::TYPE_DOUBLE_CHEST;

	/** @var int[] $invSlots */
	private array $invSlots = [];

	public function sendToPlayer() : void{
		$this->invSlots = array_map(
			fn (int $i) => (int) (21 + ($i % 5) + (9 * (floor($i / 5)))),
			range(0, MinionInformation::MAX_LEVEL)
		);
		$this->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) : void{
			$this->render();
			$player = $this->getPlayer();
			$minion = $this->getMinion();
			$slot = $transaction->getAction()->getSlot();
			switch($slot){
				case 48:
					for($i = $minion->getMinionInventory()->getSize() - 1; $i >= 0; $i--){
						if(!$minion->takeStuff($i, $player)){
							break;
						}
					}
					break;
				case 53:
					$minion->takeMinion($player);
					break;
				default:
					if(($clickedSlot = array_search($slot, $this->invSlots, true)) !== false){
						if($minion->getMinionInventory()->slotExists($clickedSlot)){
							$minion->takeStuff($clickedSlot, $player);
						}
					}
					break;
			}
		}));
		parent::sendToPlayer();
	}

	/**
	 * @priority HIGHEST
	 * @handleCancelled FALSE
	 */
	public function handleCollectResource(MinionCollectResourcesEvent $event) : void{
		$this->render();
	}

	protected function render() : void{
		$this->setName($this->getMinion()->getOriginalNameTag());
		$inventory = $this->inventory;
		$inventory->setContents(array_fill(0, $inventory->getSize() - 1, VanillaBlocks::INVISIBLE_BEDROCK()->asItem()));
		foreach($this->invSlots as $i => $slot){
			$invItem = $this->getMinion()->getMinionInventory()->slotExists($i) ?
				$this->getMinion()->getMinionInventory()->getItem($i) :
				VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem();
			$inventory->setItem($slot, $invItem);
		}
		$inventory->setItem(48, VanillaBlocks::CHEST()->asItem());
		$inventory->setItem(53, VanillaBlocks::BEDROCK()->asItem());
	}
}
