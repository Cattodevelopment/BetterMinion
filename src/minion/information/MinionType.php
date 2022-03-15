<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\minion\information;

use Mcbeany\BetterMinion\utils\Utils;
use pocketmine\block\Block;
use pocketmine\nbt\tag\CompoundTag;

class MinionType implements MinionNBT {
    public function __construct(
        private string $name,
        private Block|string|null $target = null,
        private Block|string|null $youngTarget = null,
        private ?Block $condition = null
    ) {
    }

    public function getName() : string{
        return $this->name;
    }

    public function getBlockTarget() : Block{
        return $this->target;
    }

    public function getEntityTarget() : string{
        return $this->target;
    }

    public function getYoungEntityTarget() : string{
        return $this->youngTarget;
    }

    public function getYoungBlockTarget() : ?Block{
        return $this->youngTarget;
    }

    public function getBlockCondition() : ?Block{
        return $this->condition;
    }

    public static function MINING(?Block $target = null) : self{
        return new MinionType("mining", $target);
    }

    public static function FARMING(
        ?Block $target = null,
        ?Block $youngTarget = null,
        ?Block $condition = null
    ) : self{
        return new MinionType("farming", $target, $youngTarget, $condition);
    }

    public static function LUMBERJACK(
        ?Block $target = null,
        ?Block $youngTarget = null,
        ?Block $condition = null
    ) : self{
        return new MinionType("lumberjack", $target, $youngTarget, $condition);
    }

    public function nbtSerialize() : CompoundTag{
        return CompoundTag::create()
            ->setString(MinionNBT::TYPE_NAME, $this->name)
            ->setString(MinionNBT::TYPE_TARGET, $this->target instanceof Block ?
                Utils::blockToString($this->target) :
                $this->target)
            ->setString(MinionNBT::TYPE_YOUNG_TARGET, $this->youngTarget instanceof Block ?
                Utils::blockToString($this->youngTarget) :
                $this->youngTarget ?? "")
            ->setString(MinionNBT::TYPE_CONDITION, $this->condition === null ?
                "" :
                Utils::blockToString($this->condition));
    }

    public static function nbtDeserialize(CompoundTag $tag) : self{
        $target = $tag->getString(MinionNBT::TYPE_TARGET);
        if(!class_exists($target)){
            $target = Utils::parseBlock($target);
        }
        $youngTarget = $tag->getString(MinionNBT::TYPE_YOUNG_TARGET);
        if(!class_exists($youngTarget)){
            $youngTarget = Utils::parseBlock($youngTarget);
        }
        return new self(
            $tag->getString(MinionNBT::TYPE_NAME),
            $target,
            $youngTarget,
            Utils::parseBlock($tag->getString(MinionNBT::TYPE_CONDITION))
        );
    }
}