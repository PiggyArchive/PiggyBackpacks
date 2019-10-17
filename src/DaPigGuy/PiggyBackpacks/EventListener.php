<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyBackpacks;

use muqsit\invmenu\InvMenu;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;

/**
 * Class EventListener
 * @package DaPigGuy\PiggyBackpacks
 */
class EventListener implements Listener
{
    /** @var PiggyBackpacks */
    public $plugin;

    /**
     * EventListener constructor.
     * @param PiggyBackpacks $plugin
     */
    public function __construct(PiggyBackpacks $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if ($item->getId() === Item::CHEST && ($size = $item->getNamedTagEntry("Size")) !== null) {
            if ($item->getNamedTagEntry("Contents") === null) $item->setNamedTagEntry(new ListTag("Contents"));

            $backpack = InvMenu::create($size->getValue() > 27 ? InvMenu::TYPE_DOUBLE_CHEST : InvMenu::TYPE_CHEST);
            $backpack->setName($item->getName());
            $backpack->getInventory()->setContents(array_map(function (CompoundTag $serializedItem): Item {
                return Item::nbtDeserialize($serializedItem);
            }, $item->getNamedTagEntry("Contents")->getValue()));
            for ($i = $backpack->getInventory()->getSize() - 1; $i >= $size->getValue(); $i--) {
                $slot = Item::get(Item::INVISIBLE_BEDROCK)->setCustomName(" ");
                $slot->setNamedTagEntry(new ByteTag("BackpackSlot", 1));
                $backpack->getInventory()->setItem($i, $slot);
            }

            $backpack->setListener(function (Player $player) use ($backpack): bool {
                $backpackItem = $player->getInventory()->getItemInHand();
                $backpackItem->setNamedTagEntry(new ListTag("Contents", array_map(function (Item $item) {
                    return $item->nbtSerialize();
                }, array_filter($backpack->getInventory()->getContents(), function (Item $item) {
                    return $item->getId() !== Item::INVISIBLE_BEDROCK || $item->getNamedTagEntry("BackpackSlot") === null;
                }))));
                $player->getInventory()->setItemInHand($backpackItem);
                return true;
            });

            $backpack->setInventoryCloseListener(function (Player $player) use ($backpack): void {
                $backpackItem = $player->getInventory()->getItemInHand();
                $backpackItem->removeNamedTagEntry("Opened");
                $backpackItem->setNamedTagEntry(new ListTag("Contents", array_map(function (Item $item) {
                    return $item->nbtSerialize();
                }, array_filter($backpack->getInventory()->getContents(), function (Item $item) {
                    return $item->getId() !== Item::INVISIBLE_BEDROCK || $item->getNamedTagEntry("BackpackSlot") === null;
                }))));
                $player->getInventory()->setItemInHand($backpackItem);
            });

            $item->setNamedTagEntry(new ByteTag("Opened", 1));
            $player->getInventory()->setItemInHand($item);

            $backpack->send($player);
            $event->setCancelled();
        }
    }

    /**
     * @param InventoryTransactionEvent $event
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $actions = $transaction->getActions();
        foreach ($actions as $action) {
            if (
                ($action->getSourceItem()->getId() === Item::CHEST && $action->getSourceItem()->getNamedTagEntry("Opened") !== null) ||
                ($action->getTargetItem()->getId() === Item::INVISIBLE_BEDROCK && $action->getTargetItem()->getNamedTagEntry("BackpackSlot") !== null)
            ) {
                $event->setCancelled();
            }
        }
    }
}