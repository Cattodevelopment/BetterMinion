<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minions\entities\types;

use Mcbeany\BetterMinion\events\minions\MinionStartWorkEvent;
use Mcbeany\BetterMinion\events\minions\MinionWorkEvent;
use Mcbeany\BetterMinion\minions\entities\BaseMinion;
use pocketmine\block\Block;
use pocketmine\block\BlockToolType;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\particle\BlockPunchParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\BlockBreakSound;
use pocketmine\world\sound\BlockPlaceSound;
use pocketmine\world\sound\BlockPunchSound;
use function iterator_to_array;
use function shuffle;

class MiningMinion extends BaseMinion{
	/** @var ?Block $target */
	protected mixed $target = null;

	/**
	 * @return \Generator|Block[]
	 */
	protected function getWorkingTargets() : \Generator{
		$radius = $this->getWorkingRadius();
		for($x = -$radius; $x <= $radius; ++$x){
			for($z = -$radius; $z <= $radius; ++$z){
				if($x == 0 and $z == 0){
					continue;
				}
				yield $this->getWorld()->getBlock($this->getPosition()->add($x, -1, $z));
			}
		}
	}

	protected function getAirPosition() : ?Position{
		/** @var Block $target */
		foreach($this->getWorkingTargets() as $target){
			if($target->asItem()->isNull()){
				return $target->getPosition();
			}
		}
		return null;

	}

	protected function containInvalidBlock() : bool{
		/** @var Block $target */
		foreach($this->getWorkingTargets() as $target){
			if(!$target->isSameType($this->minionInformation->getTarget()) and !$target->asItem()->isNull()){
				return true;
			}
		}
		return false;
	}

	protected function place(Position $pos) : void{
		$world = $pos->getWorld();
		$this->lookAt($pos);
		$block = $this->minionInformation->getTarget();
		$this->getInventory()->setItemInHand($block->asItem());
		$this->broadcastAnimation(new ArmSwingAnimation($this), $this->getViewers());
		$world->setBlock($pos, $block);
		$world->addSound($pos, new BlockPlaceSound($block));
	}

	protected function startMine(Block $block) : void{
		$this->getInventory()->setItemInHand($this->getTool());
		$this->target = $block;
		$breakTime = $block->getBreakInfo()->getBreakTime($this->getTool());
		$breakSpeed = $breakTime * 20; // 20 ticks = 1 sec
		$this->tickWork = (int) $breakSpeed;
		if($this->tickWork > $this->getActionTime()) { //When mining time > action time will cause spaming breaking block ...
			$this->stopWorking();
			$this->setNameTag("Block break time is too long :(");
			return;
		}
		if($breakSpeed > 0){
			$breakSpeed = 1 / $breakSpeed;
		}else{
			$breakSpeed = 1;
		}
		$pos = $block->getPosition();
		$this->lookAt($block->getPosition());
		$pos->getWorld()->broadcastPacketToViewers($pos, LevelEventPacket::create(LevelEvent::BLOCK_START_BREAK, (int) (65535 * $breakSpeed), $pos));
	}

	protected function mine() : void{
		$block = $this->target;
		if(!$block instanceof Block){
			return;
		}
		$pos = $block->getPosition();
		$world = $pos->getWorld();
		$event = new MinionWorkEvent($this, $block->getPosition());
		$event->call();
		if($event->isCancelled()){
			return;
		}
		$this->broadcastAnimation(new ArmSwingAnimation($this), $this->getViewers());
		$world->addParticle($pos, new BlockPunchParticle($block, Facing::opposite($this->getHorizontalFacing())));
		$this->broadcastSound(new BlockPunchSound($block), $this->getViewers());
		$world->broadcastPacketToViewers($pos, LevelEventPacket::create(LevelEvent::BLOCK_STOP_BREAK, 0, $pos));
		$world->addSound($pos, new BlockBreakSound($block));
	}

	protected function onAction() : void{
		if($this->containInvalidBlock()){
			$this->setNameTag("This place is not perfect :(");
			return;
		}
		$air = $this->getAirPosition();
		if($air !== null){
			$event = new MinionWorkEvent($this, $air);
			$event->call();
			if($event->isCancelled()){
				return;
			}
			$this->place($air);
			return;
		}
		if($this->target === null){
			$area = iterator_to_array($this->getWorkingTargets());
			shuffle($area);
			/** @var Block $block */
			foreach($area as $block){
				$event = new MinionStartWorkEvent($this, $block->getPosition());
				$event->call();
				if($event->isCancelled()){
					continue;
				}
				$this->startMine($block);
				break;
			}
		}
	}

	protected function doOfflineAction(int $times) : void{
		for($i = 0; $i < $times; $i++){
			$this->addDrops();
		}
	}

	protected function minionAnimationTick(int $tickDiff = 1) : void{
		// TODO: Mining speed bug
		$target = $this->target;
		if($target === null){
			return;
		}
		$remainTick = $this->tickWork - $tickDiff;
		$maxTick = -BaseMinion::MAX_TICKDIFF;
		if($remainTick > 0){
			$this->tickWork -= $tickDiff;
			$this->mine();
			return;
		}
		if($remainTick > $maxTick){
			$this->tickWork = 0;
			if($this->target === null){
				return;
			}
			$block = clone $this->target;
			$this->target = null;
			if(!$block instanceof Block){
				return;
			}
			$pos = $block->getPosition();
			$world = $pos->getWorld();
			$world->addParticle($pos->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));
			$world->setBlock($pos, VanillaBlocks::AIR());
			$this->addDrops();
			return;
		}
		if($remainTick < $maxTick){
			$this->tickWork = 0;
			// TODO: Hacks... Skip and just add stuff like offline action
			$this->target = null;
			$this->doOfflineAction(1);
		}
	}

	public function getTool() : Item{
		return match($this->minionInformation->getTarget()->getBreakInfo()->getToolType()){
			BlockToolType::AXE => VanillaItems::DIAMOND_AXE(),
			BlockToolType::PICKAXE => VanillaItems::DIAMOND_PICKAXE(),
			BlockToolType::SHOVEL => VanillaItems::DIAMOND_SHOVEL(),
			BlockToolType::HOE => VanillaItems::DIAMOND_HOE(),
			BlockToolType::SHEARS => VanillaItems::SHEARS(),
			default => parent::getTool()
		};
	}
}
