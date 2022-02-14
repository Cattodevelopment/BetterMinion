<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\entities;

use Mcbeany\BetterMinion\BetterMinion;
use Mcbeany\BetterMinion\entities\inventory\MinionInventory;
use Mcbeany\BetterMinion\minions\MinionInformation;
use Mcbeany\BetterMinion\utils\MinionLimiter;
use Mcbeany\BetterMinion\utils\Utils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\MenuIds;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use onebone\economyapi\EconomyAPI;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use function array_fill;
use function array_map;
use function array_reverse;
use function explode;
use function file_get_contents;
use function floor;
use function intval;
use function json_decode;

abstract class MinionEntity extends Human
{
    public const ACTION_CANT_WORK = -1;
    public const ACTION_IDLE = 0;
    public const ACTION_TURNING = 1;
    public const ACTION_WORKING = 2;

    /** @var MinionInformation */
    protected $minionInformation;
    /** @var MinionInventory */
    protected $minionInventory;
    /** @var int */
    protected $currentAction = self::ACTION_IDLE;
    /** @var int */
    protected $currentActionTicks = 0;
    /** @var null|Block|Living */
    protected $target;
    /** @var float */
    protected $gravity = 0;
    /** @var float */
    private $money;

    public function saveNBT(): void
    {
        parent::saveNBT();
        $this->namedtag->setTag(new ListTag('MinionInventory', array_map(function (Item $item): CompoundTag {
            return $item->nbtSerialize();
        }, $this->minionInventory->getContents())));
        $this->namedtag->setTag($this->minionInformation->nbtSerialize());
        $this->namedtag->setFloat('Money', $this->money);
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            if ($damager instanceof Player) {
                if (isset(BetterMinion::getInstance()->isRemove[$damager->getName()])) {
                    unset(BetterMinion::getInstance()->isRemove[$damager->getName()]);
                    $damager->sendMessage('Successfully removed ' . $this->getMinionInformation()->getOwner() . "'s minion");
                    $this->destroy();

                    return;
                }
                if ($damager->getName() === $this->getMinionInformation()->getOwner()) {
                    $menu = InvMenu::create(MenuIds::TYPE_DOUBLE_CHEST);
                    $menu->setName($this->getMinionInformation()->getOwner() . "'s Minion " . Utils::getRomanNumeral($this->getMinionInformation()->getLevel()));
                    $menu->getInventory()->setContents(array_fill(0, 54, Item::get(BlockIds::INVISIBLE_BEDROCK, 7)->setCustomName(TextFormat::RESET)));
                    if ($this->canUseAutoSmelter()) {
                        $menu->getInventory()->setItem(10, Item::get(BlockIds::FURNACE)->setCustomName('Auto Smelter (' . ($this->getMinionInformation()->getUpgrade()->isAutoSmelt() ? 'Enabled' : 'Disabled') . ')')->setLore(['Automatically smelts items that the minion produces.', 'Result: ' . $this->getSmeltedTarget()->getVanillaName() . '.']));
                    } else {
                        $menu->getInventory()->setItem(10, Item::get(BlockIds::STAINED_GLASS, 14)->setCustomName(TextFormat::RED . 'Your minion cannot use this upgrade!'));
                    }
                    if ($this->canUseCompacter()) {
                        $menu->getInventory()->setItem(19, Item::get(BlockIds::DISPENSER)->setCustomName('Compacter (' . ($this->getMinionInformation()->getUpgrade()->isCompact() ? 'Enabled' : 'Disabled') . ')'));
                    } else {
                        $menu->getInventory()->setItem(19, Item::get(BlockIds::STAINED_GLASS, 14)->setCustomName(TextFormat::RED . 'Your minion cannot use this upgrade!'));
                    }
                    if ($this->canUseExpander()) {
                        $menu->getInventory()->setItem(37, Item::get(BlockIds::COMMAND_BLOCK)->setCustomName('Expander (' . ($this->getMinionInformation()->getUpgrade()->isExpand() ? 'Enabled' : 'Disabled') . ')')->setLore(['Increases the minion range by one block.']));
                    } else {
                        $menu->getInventory()->setItem(37, Item::get(BlockIds::STAINED_GLASS, 14)->setCustomName(TextFormat::RED . 'Your minion cannot use this upgrade!'));
                    }
                    $menu->getInventory()->setItem(48, Item::get(BlockIds::CHEST)->setCustomName(TextFormat::GREEN . 'Retrieve all results'));
                    $menu->getInventory()->setItem(50, Item::get(ItemIds::BOTTLE_O_ENCHANTING)->setCustomName(TextFormat::AQUA . 'Level up your minion')->setLore([$this->getMinionInformation()->getLevel() < 15 ? TextFormat::YELLOW . 'Cost: ' . TextFormat::GREEN . $this->getLevelUpCost() : TextFormat::RED . 'Reached max level!']));
                    $menu->getInventory()->setItem(53, Item::get(BlockIds::BEDROCK)->setCustomName(TextFormat::RED . 'Remove your minion'));
                    $taskId = BetterMinion::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (int $currentTick) use ($menu): void {
                        for ($i = 0; $i < 15; ++$i) {
                            $menu->getInventory()->setItem((int) (12 + ($i % 5) + (9 * floor($i / 5))), $this->getMinionInventory()->slotExists($i) ? $this->getMinionInventory()->getItem($i) : Item::get(BlockIds::STAINED_GLASS, 14)->setCustomName(TextFormat::YELLOW . 'Unlock at level ' . TextFormat::GOLD . Utils::getRomanNumeral(($i + 1))));
                        }
                        $menu->getInventory()->setItem(28, Item::get(ItemIds::HOPPER)->setCustomName('Auto Seller (' . ($this->getMinionInformation()->getUpgrade()->isAutoSell() ? 'Enabled' : 'Disabled') . ')')->setLore(["Sells resources when the minion's storage is full.", 'Held money: ' . $this->money . '.']));
                        $types = ['Mining', 'Farming', 'Lumberjack', 'Slaying', 'Fishing'];
                        $menu->getInventory()->setItem(45, Item::fromString((string) BetterMinion::getInstance()->getConfig()->get('minion-item'), false)->setCustomName(TextFormat::BLUE . $this->getMinionInformation()->getType()->getTargetName() . ' Minion ' . Utils::getRomanNumeral($this->getMinionInformation()->getLevel()))->setLore([
                            'Type: ' . $types[$this->getMinionInformation()->getType()->getActionType()],
                            'Target: ' . $this->getMinionInformation()->getType()->getTargetName(),
                            'Level: ' . $this->getMinionInformation()->getLevel(),
                            'Resources Collected: ' . $this->getMinionInformation()->getResourcesCollected(),
                        ]));
                    }), 1)->getTaskId();
                    $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction): void {
                        $player = $transaction->getPlayer();
                        $itemClicked = $transaction->getItemClicked();
                        $action = $transaction->getAction();

                        switch ($action->getSlot()) {
                            case 10:
                                if ($this->canUseAutoSmelter()) {
                                    $player->removeWindow($action->getInventory());
                                    if (EconomyAPI::getInstance()->myMoney($player) - $this->getAutoSmeltCost() >= 0) {
                                        EconomyAPI::getInstance()->reduceMoney($player, $this->getAutoSmeltCost());
                                        $this->getMinionInformation()->getUpgrade()->setAutoSmelt();
                                        $this->stopWorking();
                                    }
                                }

                                break;

                            case 19:
                                if ($this->canUseCompacter()) {
                                    $player->removeWindow($action->getInventory());
                                    if (EconomyAPI::getInstance()->myMoney($player) - $this->getCompactCost() >= 0) {
                                        EconomyAPI::getInstance()->reduceMoney($player, $this->getCompactCost());
                                        $this->getMinionInformation()->getUpgrade()->setCompact();
                                        $this->stopWorking();
                                    }
                                }

                                break;

                            case 28:
                                $player->removeWindow($action->getInventory());
                                if (!$this->getMinionInformation()->getUpgrade()->isAutoSell()) {
                                    if (EconomyAPI::getInstance()->myMoney($player) - $this->getAutoSellCost() >= 0) {
                                        EconomyAPI::getInstance()->reduceMoney($player, $this->getAutoSellCost());
                                        $this->getMinionInformation()->getUpgrade()->setAutoSell();
                                        $this->stopWorking();
                                    }
                                } else {
                                    EconomyAPI::getInstance()->addMoney($player, $this->money);
                                    $this->money = 0;
                                }

                                break;

                            case 37:
                                $player->removeWindow($action->getInventory());
                                if ($this->canUseExpander()) {
                                    if (!$this->getMinionInformation()->getUpgrade()->isExpand()) {
                                        if (EconomyAPI::getInstance()->myMoney($player) - $this->getExpandCost() >= 0) {
                                            EconomyAPI::getInstance()->reduceMoney($player, $this->getExpandCost());
                                            $this->getMinionInformation()->getUpgrade()->setExpand();
                                            $this->stopWorking();
                                        }
                                    }
                                }

                                break;

                            case 48:
                                $player->removeWindow($action->getInventory());
                                foreach (array_reverse($this->getMinionInventory()->getContents(), true) as $slot => $item) {
                                    if ($player->getInventory()->canAddItem($item)) {
                                        $player->getInventory()->addItem($item);
                                        $this->getMinionInventory()->setItem($slot, Item::get(BlockIds::AIR));
                                    } else {
                                        $player->sendMessage(TextFormat::RED . 'Your inventory is full, empty it before making a transaction');
                                    }
                                }

                                break;

                            case 50:
                                $player->removeWindow($action->getInventory());
                                if ($this->getMinionInformation()->getLevel() < 15) {
                                    if (EconomyAPI::getInstance()->myMoney($player) - $this->getLevelUpCost() >= 0) {
                                        EconomyAPI::getInstance()->reduceMoney($player, $this->getLevelUpCost());
                                        $this->getMinionInformation()->incrementLevel();
                                        $player->sendMessage(TextFormat::GREEN . 'Your minion has been upgraded to level ' . TextFormat::GOLD . Utils::getRomanNumeral($this->getMinionInformation()->getLevel()));
                                        $this->getMinionInventory()->setSize($this->getMinionInformation()->getLevel());
                                        $this->stopWorking();
                                    } else {
                                        $player->sendMessage(TextFormat::RED . "You don't have enough economy to level up!");
                                    }
                                } else {
                                    $player->sendMessage(TextFormat::RED . 'Your minion has reached the maximum level!');
                                }

                                break;

                            case 53:
                                $player->removeWindow($action->getInventory());
                                $this->destroy();

                                break;

                            default:
                                for ($i = 0; $i <= 15; ++$i) {
                                    if ($i > $this->getMinionInformation()->getLevel() - 1) {
                                        continue;
                                    }
                                    $slot = (int) (12 + ($i % 5) + (9 * floor($i / 5)));
                                    if ($action->getSlot() === $slot) {
                                        if ($player->getInventory()->canAddItem($itemClicked)) {
                                            $player->getInventory()->addItem($itemClicked);
                                            $remaining = $itemClicked->getCount();
                                            /** @var Item $item */
                                            foreach (array_reverse($this->getMinionInventory()->all($itemClicked), true) as $slot => $item) {
                                                $itemCount = $item->getCount();
                                                $this->getMinionInventory()->setItem($slot, $item->setCount($itemCount - $remaining > 0 ? $itemCount - $remaining : 0));
                                                $remaining -= $itemCount;
                                                if ($remaining === 0) {
                                                    break;
                                                }
                                            }
                                        } else {
                                            $player->removeWindow($action->getInventory());
                                            $player->sendMessage(TextFormat::RED . 'Your inventory is full, empty it before making a transaction');
                                        }
                                    }
                                }

                                break;
                        }
                        for ($i = 0; $i < 15; ++$i) {
                            $action->getInventory()->setItem((int) (12 + ($i % 5) + (9 * floor($i / 5))), $this->getMinionInventory()->slotExists($i) ? $this->getMinionInventory()->getItem($i) : Item::get(BlockIds::STAINED_GLASS, 14)->setCustomName(TextFormat::YELLOW . 'Unlock at level ' . TextFormat::GOLD . Utils::getRomanNumeral(($i + 1))));
                        }
                    }));
                    $menu->send($damager);
                    $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use ($taskId): void {
                        BetterMinion::getInstance()->getScheduler()->cancelTask($taskId);
                    });
                }
            }
        }
        $source->setCancelled();
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);
        if (!$this->closed && !$this->isFlaggedForDespawn()) {
            if ($this->ticksLived % 60 === 0) {
                $this->updateTarget();
            }
            if (!$this->checkFull()) {
                return $hasUpdate;
            }
            if ($this->target === null) {
                $this->getTarget();
            }
            ++$this->currentActionTicks;
            if ($this->target instanceof Block) {
                $this->target = $this->level->getBlock($this->target);
                if (!$this->checkTarget()) {
                    $this->stopWorking();

                    return $hasUpdate;
                }
            }

            switch ($this->currentAction) {
                case self::ACTION_IDLE:
                    if ($this->currentActionTicks >= 60 && $this->target !== null) { //TODO: Customize
                        $this->currentAction = self::ACTION_TURNING;
                        $this->currentActionTicks = 0;
                    }

                    break;

                case self::ACTION_TURNING:
                    $this->lookAt($this->target->multiply($this->currentActionTicks / 5));
                    if ($this->currentActionTicks === 5) {
                        $this->currentAction = self::ACTION_WORKING;
                        $this->currentActionTicks = 0;
                    }

                    break;

                case self::ACTION_WORKING:
                    $isPlacing = $this->target->getId() === BlockIds::AIR;
                    if (!$isPlacing) {
                        if ($this->currentActionTicks === 1) {
                            $this->level->broadcastLevelEvent($this->target, LevelEventPacket::EVENT_BLOCK_START_BREAK, (int) (65535 / 60));
                        }
                        if ($this->isWorkFast() && $this->currentActionTicks === 2) {
                            $this->startWorking();
                        }
                        $pk = new AnimatePacket();
                        $pk->action = AnimatePacket::ACTION_SWING_ARM;
                        $pk->entityRuntimeId = $this->getId();
                        $this->level->broadcastPacketToViewers($this, $pk);
                    } else {
                        $this->level->broadcastLevelEvent($this->target, LevelEventPacket::EVENT_BLOCK_STOP_BREAK);
                    }
                    if ($this->currentActionTicks === 60) {
                        $this->startWorking();
                        $this->stopWorking();
                        if (!$this->checkFull()) {
                            return $hasUpdate;
                        }
                    }

                    break;

                case self::ACTION_CANT_WORK:
                    if (!$this->isInventoryFull()) {
                        $this->currentAction = self::ACTION_IDLE;
                        $this->setNameTag($this->getMinionInformation()->getOwner() . "'s Minion");
                    }

                    break;
            }
        }

        return $hasUpdate;
    }

    public function canBeCollidedWith(): bool
    {
        return false;
    }

    public function addEffect(EffectInstance $effect): bool
    {
        return false;
    }

    public function getMinionInformation(): MinionInformation
    {
        return $this->minionInformation;
    }

    public function getMinionInventory(): MinionInventory
    {
        return $this->minionInventory;
    }

    protected function initEntity(): void
    {
        parent::initEntity();
        $this->setScale(0.5);
        $this->setImmobile();
        $this->setNameTagAlwaysVisible();
        $this->minionInformation = MinionInformation::nbtDeserialize($this->namedtag->getCompoundTag('MinionInformation'));
        $this->minionInventory = new MinionInventory();
        $this->minionInventory->setSize($this->minionInformation->getLevel());
        $this->money = $this->namedtag->getFloat('Money', 0);
        $invTag = $this->namedtag->getListTag('MinionInventory');
        if ($invTag !== null) {
            $this->minionInventory->setContents(array_map(function (CompoundTag $tag): Item {
                return Item::nbtDeserialize($tag);
            }, $invTag->getValue()));
        }
        $tool = BetterMinion::getInstance()->getConfig()->getNested('tool.tier', 'diamond');
        $isNetheriteTool = $tool === 'Netherite';
        $this->getInventory()->setItemInHand($this->getTool($tool, $isNetheriteTool));
        if ($this->isInventoryFull()) {
            $this->stopWorking();
            $this->currentAction = self::ACTION_CANT_WORK;
            $this->setNameTag($this->getMinionInformation()->getOwner() . "'s Minion\n" . TextFormat::RED . 'My inventory is now full');
        } else {
            $this->setNameTag($this->getMinionInformation()->getOwner() . "'s Minion");
        }
        MinionLimiter::addCount($this->getMinionInformation()->getOwner(), $this->getLevelNonNull()->getFolderName());
    }

    protected function getSmeltedTarget(): ?Item
    {
        $smeltedItems = json_decode(file_get_contents(BetterMinion::getInstance()->getDataFolder() . 'smelts.json'), true);
        foreach ($smeltedItems as $input => $output) {
            $realInput = Item::fromString($input, false);
            $realOutput = Item::fromString($output, false);
            foreach ($this->getRealDrops() as $drop) {
                if ($realInput->equals($drop, true)) {
                    return $realOutput;
                }
            }
        }

        return null;
    }

    protected function canUseAutoSmelter(): bool
    {
        return $this->getSmeltedTarget() !== null;
    }

    /**
     * @return null|Item|Item[]
     */
    protected function getCompactedTarget(Item $item = null)
    {
        $compactedItems = json_decode(file_get_contents(BetterMinion::getInstance()->getDataFolder() . 'compacts.json'), true);
        foreach ($compactedItems as $input => $output) {
            $realInput = Item::fromString($input, false);
            $realOutput = Item::fromString($output, false);
            if ($item === null) {
                foreach ($this->getTargetDrops() as $drop) {
                    if ($realInput->equals($drop, true)) {
                        return $realOutput;
                    }
                }
            } else {
                $item = clone $item;
                $data = explode(':', $input);
                $realInput->setCount((int) ($data[2] ?? 1));
                if ($realInput->equals($item, true) && $item->getCount() >= $realInput->getCount()) {
                    return [$realOutput->setCount(intval($item->getCount() / $realInput->getCount())), $item->setCount($item->getCount() % $realInput->getCount())];
                }
            }
        }

        return null;
    }

    protected function canUseCompacter(): bool
    {
        return $this->getCompactedTarget() !== null;
    }

    protected function canUseExpander(): bool
    {
        return true;
    }

    protected function isWorkFast(): bool
    {
        return false;
    }

    protected function getTargetDrops(): array
    {
        $drops = $this->getRealDrops();
        if ($this->getMinionInformation()->getUpgrade()->isAutoSmelt()) {
            $drops = [$this->getSmeltedTarget()];
        }

        return $drops;
    }

    protected function updateTarget(): void
    {
    }

    abstract protected function getTarget();

    protected function checkTarget(): bool
    {
        return $this->target->getId() === BlockIds::AIR || ($this->target->getId() === $this->getMinionInformation()->getType()->getTargetId() && $this->target->getDamage() === $this->getMinionInformation()->getType()->getTargetMeta());
    }

    protected function stopWorking(): void
    {
        $this->currentAction = self::ACTION_IDLE;
        $this->currentActionTicks = 0;
        $this->target = null;
    }

    protected function isInventoryFull(): bool
    {
        $full = true;
        $drops = $this->getTargetDrops();
        if ($this->getMinionInformation()->getUpgrade()->isCompact()) {
            $drop[] = $this->getCompactedTarget()->setCount(1);
        }
        foreach ($drops as $item) {
            if ($this->getMinionInventory()->canAddItem($item->setCount(1))) {
                $full = false;
            }
        }

        return $full;
    }

    abstract protected function getTool(string $tool, bool $isNetheriteTool): Item;

    protected function getMinionRange(): int
    {
        return $this->getMinionInformation()->getUpgrade()->isExpand() ? 3 : 2;
    }

    protected function startWorking(): void
    {
        $this->level->addParticle(new DestroyBlockParticle($this->target->add(0.5, 0.5, 0.5), $this->target));
        $this->level->setBlock($this->target, $this->target->getId() === BlockIds::AIR ? $this->getMinionInformation()->getType()->toBlock() : Block::get(BlockIds::AIR));
        if ($this->target->getId() !== BlockIds::AIR) {
            $drops = $this->getTargetDrops();
            foreach ($drops as $drop) {
                for ($i = 1; $i <= $drop->getCount(); ++$i) {
                    $thing = Item::get($drop->getId(), $drop->getDamage());
                    if ($this->getMinionInventory()->canAddItem($thing)) {
                        $this->getMinionInventory()->addItem($thing);
                        $this->getMinionInformation()->incrementResourcesCollected();
                    }
                }
            }
        }
        if ($this->getMinionInformation()->getUpgrade()->isCompact()) {
            foreach ($this->getMinionInventory()->getContents() as $item) {
                if ($item->getCount() !== $item->getMaxStackSize()) {
                    continue;
                }
                $results = $this->getCompactedTarget($item);
                if ($results === null) {
                    continue;
                }
                $result = $results[0];
                $extra = $results[1];
                $this->getMinionInventory()->removeItem($item);
                $this->getMinionInventory()->addItem($result);
                if (!$extra->isNull()) {
                    $this->getMinionInventory()->addItem($extra);
                }
            }
        }
    }

    private function checkFull(): bool
    {
        if ($this->isInventoryFull()) {
            if ($this->getMinionInformation()->getUpgrade()->isAutoSell()) {
                $this->sellItems();
            }
            $this->currentAction = self::ACTION_CANT_WORK;
            $this->setNameTag($this->getMinionInformation()->getOwner() . "'s Minion\n" . TextFormat::RED . 'My inventory is now full');

            return false;
        }

        return true;
    }

    private function getRealDrops(): array
    {
        $block = $this->getMinionInformation()->getType()->toBlock();
        $drops = $block->getDropsForCompatibleTool(Item::get(BlockIds::AIR));
        if (empty($drops)) {
            $drops = $block->getSilkTouchDrops(Item::get(BlockIds::AIR));
        }

        return $drops;
    }

    private function destroy(): void
    {
        if ($this->target instanceof Block) {
            $this->level->broadcastLevelEvent($this->target, LevelEventPacket::EVENT_BLOCK_STOP_BREAK);
        }
        MinionLimiter::reduceCount($this->getMinionInformation()->getOwner(), $this->getLevelNonNull()->getFolderName());
        $this->minionInventory->dropContents($this->level, $this);
        $minionItem = Item::fromString((string) BetterMinion::getInstance()->getConfig()->get('minion-item'), false);
        $minionItem->setCustomName($this->getMinionInformation()->getType()->getTargetName() . ' Minion ' . Utils::getRomanNumeral($this->getMinionInformation()->getLevel()));
        $minionItem->setNamedTagEntry($this->minionInformation->nbtSerialize());
        $this->level->dropItem($this, $minionItem);
        $this->flagForDespawn();
    }

    private function sellItems(): void
    {
        $sellAll = BetterMinion::getInstance()->getServer()->getPluginManager()->getPlugin('SellAll');
        $sellPrices = $sellAll->getConfig()->getAll();
        $item = $this->getMinionInventory()->getItem($this->getMinionInventory()->getSize() - 1);
        if (isset($sellPrices[$item->getId()])) {
            $this->money += $sellPrices[$item->getId()] * $item->getCount();
            $this->getMinionInventory()->remove($item);
        } elseif (isset($sellPrices[$item->getId() . ':' . $item->getDamage()])) {
            $this->money += $sellPrices[$item->getId() . ':' . $item->getDamage()] * $item->getCount();
            $this->getMinionInventory()->remove($item);
        }
    }

    private function getLevelUpCost(): int
    {
        $costs = (array) BetterMinion::getInstance()->getConfig()->get('levelup-costs');

        return (int) $costs[$this->getMinionInformation()->getLevel()];
    }

    private function getAutoSmeltCost(): int
    {
        return (int) BetterMinion::getInstance()->getConfig()->getNested('upgrade-costs.autosmelt');
    }

    private function getAutoSellCost(): int
    {
        return (int) BetterMinion::getInstance()->getConfig()->getNested('upgrade-costs.autosell');
    }

    private function getCompactCost(): int
    {
        return (int) BetterMinion::getInstance()->getConfig()->getNested('upgrade-costs.compact');
    }

    private function getExpandCost(): int
    {
        return (int) BetterMinion::getInstance()->getConfig()->getNested('upgrade-costs.expand');
    }
}
