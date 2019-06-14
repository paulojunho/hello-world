<?php

/*
  Skywars For HyperGames BE
  Youtube: https://youtube.com/c/BerryMC
  IP Server: mc.hypergamesbe.tk
  Port Server: 19132
  Skywars For HyperGames BE
*/
declare(strict_types=1);

namespace skywars;

use pocketmine\command\Command;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use skywars\arena\Arena;
use skywars\commands\SkyWarsCommand;
use skywars\math\Vector3;
use skywars\provider\YamlDataProvider;
use onebone\economyapi\EconomyAPI;
use AMGM\TOP;

/**
 * Class SkyWars
 * @package skywars
 */
class SkyWars extends PluginBase implements Listener {

    /** @var YamlDataProvider */
    public $dataProvider;

    /** @var Command[] $commands */
    public $commands = [];

    /** @var Arena[] $arenas */
    public $arenas = [];

    /** @var Arena[] $setters */
    public $setters = [];

    /** @var int[] $setupData */
    public $setupData = [];

    public function onEnable() {
        $this->getServer() -> getPluginManager()->registerEvents($this, $this);
        $this->dataProvider = new YamlDataProvider($this);
        $this->getServer()->getCommandMap()->register("SkyWars", $this->commands[] = new SkyWarsCommand($this));
		$this->eco = EconomyAPI::getInstance();
    }

    public function onDisable() {
        $this->dataProvider->saveArenas();
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();

        if(!isset($this->setters[$player->getName()])) {
            return;
        }

        $event->setCancelled(\true);
        $args = explode(" ", $event->getMessage());

        /** @var Arena $arena */
        $arena = $this->setters[$player->getName()];

        switch ($args[0]) {
            case "help":
                $player->sendMessage("§f----------[§l§c SKYWARS §r§f]----------");
				$player->sendMessage("You dont need to know");
				$player->sendMessage("§f----------[§l§c SKYWARS §r§f]----------");
                break;
            case "slots":
                if(!isset($args[1])) {
                    $player->sendMessage("[§l§bSW§r§f]§b Sử dụng§7: §aslots §f<slots>");
                    break;
                }
                $arena->data["slots"] = (int)$args[1];
                $player->sendMessage("[§l§cSW§r§f]§b Slots updated to $args[1]!");
                break;
            case "level":
                if(!isset($args[1])) {
                    $player->sendMessage("[§l§cSW§r§f]§b §7level <levelName>");
                    break;
                }
                if(!$this->getServer()->isLevelGenerated($args[1])) {
                    $player->sendMessage("[§l§cSW§r§f]§b Level $args[1] does not found!");
                    break;
                }
                $player->sendMessage("[§l§cSW§r§f]§b Arena level updated to $args[1]!");
                $arena->data["level"] = $args[1];
                break;
            case "spawn":
                if(!isset($args[1])) {
                    $player->sendMessage("[§l§cSW§r§f]§b §7setspawn <int: spawn>");
                    break;
                }
                if(!is_numeric($args[1])) {
                    $player->sendMessage("[§l§cSW§r§f]§b §cType number!");
                    break;
                }
                if((int)$args[1] > $arena->data["slots"]) {
                    $player->sendMessage("[§l§cSW§r§f]§b §cThere are only {$arena->data["slots"]} slots!");
                    break;
                }

                $arena->data["spawns"]["spawn-{$args[1]}"] = (new Vector3($player->getX(), $player->getY(), $player->getZ()))->__toString();
                $player->sendMessage("[§l§cSW§r§f]§b §a> Spawn $args[1] set to X: " . (string)round($player->getX()) . " Y: " . (string)round($player->getY()) . " Z: " . (string)round($player->getZ()));
                break;
            case "joinsign":
                $player->sendMessage("[§l§cSW§r§f]§b §a> Break block to set joinsign!");
                $this->setupData[$player->getName()] = 0;
                break;
            case "savelevel":
                if(!$arena->level instanceof Level) {
                    $player->sendMessage("[§l§cSW§r§f]§b §c> Error when saving level: world not found.");
                    if($arena->setup) {
                        $player->sendMessage("[§l§cSW§r§f]§b §6> Try save level after enabling the arena.");
                    }
                    break;
                }
                $arena->mapReset->saveMap($arena->level);
                $player->sendMessage("§a> Level saved!");
                break;
            case "enable":
                if(!$arena->setup) {
                    $player->sendMessage("§6> Arena is already enabled!");
                    break;
                }
                if(!$arena->enable()) {
                    $player->sendMessage("§c> Could not load arena, there are missing information!");
                    break;
                }
                $player->sendMessage("§a> Arena enabled!");
                break;
            case "done":
                $player->sendMessage("§a> You are successfully leaved setup mode!");
                unset($this->setters[$player->getName()]);
                if(isset($this->setupData[$player->getName()])) {
                    unset($this->setupData[$player->getName()]);
                }
                break;
            default:
                $player->sendMessage("§6> You are in setup mode.\n".
                    "§7- use §lhelp §r§7to display available commands\n"  .
                    "§7- or §ldone §r§7to leave setup mode");
                break;
        }
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if(isset($this->setupData[$player->getName()])) {
            switch ($this->setupData[$player->getName()]) {
                case 0:
                    $this->setters[$player->getName()]->data["joinsign"] = [(new Vector3($block->getX(), $block->getY(), $block->getZ()))->__toString(), $block->getLevel()->getFolderName()];
                    $player->sendMessage("§a> Join sign updated!");
                    unset($this->setupData[$player->getName()]);
                    $event->setCancelled(\true);
                    break;
            }
        }
    }
}
