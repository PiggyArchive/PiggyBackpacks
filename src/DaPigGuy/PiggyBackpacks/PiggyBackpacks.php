<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyBackpacks;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

/**
 * Class PiggyBackpacks
 * @package DaPigGuy\PiggyBackpacks
 */
class PiggyBackpacks extends PluginBase
{
    /** @var array */
    public $backpackSizes;

    public function onEnable(): void
    {
        if (!class_exists(InvMenu::class)) {
            $this->getLogger()->error("InvMenu virion not found. Please download PiggyBackpacks from Poggit-CI or use DEVirion (not recommended).");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        if (!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);

        $this->saveDefaultConfig();
        foreach ($this->getConfig()->get("sizes") as $sizeName => $capacity) {
            $this->backpackSizes[$sizeName] = $capacity;
        }
        foreach ($this->getConfig()->get("crafting") as $size => $recipeData) {
            $backpack = Item::get(Item::CHEST, 0, 1);
            $backpack->setNamedTagEntry(new IntTag("Size", $this->backpackSizes[$size]));
            $backpack->setCustomName(TextFormat::RESET . TextFormat::WHITE . $size . " Backpack");

            $requiredItems = [];
            foreach ($recipeData["materials"] as $materialSymbol => $materialData) {
                $requiredItems[$materialSymbol] = Item::get($materialData["id"], $materialData["meta"], $materialData["count"]);
            }
            $this->getServer()->getCraftingManager()->registerRecipe(new ShapedRecipe($recipeData["shape"], $requiredItems, [$backpack]));
        }
        $this->getServer()->getCraftingManager()->buildCraftingDataCache();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }
}