<?php
declare(strict_types=1);

namespace skywars\arena;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\scheduler\Task;
use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\Item;
use pocketmine\tile\Sign;
use skywars\math\Time;
use skywars\math\Vector3;
use pocketmine\Player;
use pocketmine\inventory\Inventory;

/**
 * Class ArenaScheduler
 * @package skywars\arena
 */
class ArenaScheduler extends Task {

    /** @var Arena $plugin */
    protected $plugin;

    /** @var int $startTime */
    public $startTime = 30;

    /** @var float|int $gameTime */
    public $gameTime = 20 * 60;

    /** @var int $restartTime */
    public $restartTime = 10;

    /** @var array $restartData */
    public $restartData = [];

    /**
     * ArenaScheduler constructor.
     * @param Arena $plugin
     */
    public function __construct(Arena $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick) {
        $this->reloadSign();

        if($this->plugin->setup) return;

        switch ($this->plugin->phase) {
            case Arena::PHASE_LOBBY:
                if(count($this->plugin->players) >= 2) {
					$this->plugin->broadcastMessage("§eStarting in §c" . Time::calculateTime($this->startTime) . "", Arena::MSG_TIP);
					if($this->startTime == 10){
					$this->plugin->broadcastMessage("§l§eSky§6Wars\n§r§cInsane§f Mode", Arena::MSG_TITLE);
					$this->plugin->broadcastMessage("§eThe game will started in §610§e seconds");
					}
					if($this->startTime == 5){
					$this->plugin->broadcastMessage("§l§c5\n§r§ePrepare to fight", Arena::MSG_TITLE);
					$this->plugin->broadcastMessage("§eThe game will started in §c5§e seconds");
					}
					if($this->startTime == 4){
					$this->plugin->broadcastMessage("§l§c4\n§r§ePrepare to fight", Arena::MSG_TITLE);
					$this->plugin->broadcastMessage("§eThe game will started in §c4§e seconds");
					}
					if($this->startTime == 3){
					$this->plugin->broadcastMessage("§l§c3\n§r§ePrepare to fight", Arena::MSG_TITLE);
					$this->plugin->broadcastMessage("§eThe game will started in §c3§e seconds");
					}
					if($this->startTime == 2){
					$this->plugin->broadcastMessage("§l§c2\n§r§ePrepare to fight", Arena::MSG_TITLE);
					$this->plugin->broadcastMessage("§eThe game will started in §c2§e seconds");
					}
					if($this->startTime == 1){
					$this->plugin->broadcastMessage("§l§c1\n§r§ePrepare to fight", Arena::MSG_TITLE);
					$this->plugin->broadcastMessage("§eThe game will started in §c1§e seconds");
					}
                    $this->startTime--;
                    if($this->startTime == 0){
                        $this->plugin->startGame();
                        foreach ($this->plugin->players as $player) {
                            $this->plugin->level->addSound(new AnvilUseSound($player->asVector3()));
							$player->getInventory()->removeItem(Item::get(261, 0, 1));
                        }
                    }
                    else {
                        foreach ($this->plugin->players as $player) {
                            $this->plugin->level->addSound(new ClickSound($player->asVector3()));
                        }
                    }
                }
                else {
					$t = str_repeat(" ", 65);
                    $this->plugin->broadcastMessage($t. "§l§CSky§Bwars §cInsane §r§7\n" .$t. "§cWaiting for players..." .str_repeat("\n", 20), Arena::MSG_TIP);
                    $this->startTime = 30;
                }
                break;
            case Arena::PHASE_GAME:
			    $t = str_repeat(" ", 65);
                $this->plugin->broadcastMessage($t. "§l§CSky§Bwars §cInsane §r§7| §l§eStar-SkyWars§r\n" .$t. "§fAlive: §a" .count($this->plugin->players). "§r\n" .$t. "§fEnd in: §a" .Time::calculateTime($this->gameTime)."§r\n". $t. "§fMap: §a{$this->plugin->level->getFolderName()}\n". $t. "§fMode: §cInsane\n". str_repeat("\n", 20), Arena::MSG_TIP);
                switch ($this->gameTime) {
                    case 15 * 60:
                        $this->plugin->broadcastMessage("§eAll chest will being refill in 5 minutes!");
                        break;
                    case 11 * 60:
                        $this->plugin->broadcastMessage("§eAll chest will being refill in 1 minutes!");
                        break;
                    case 10 * 60:
                        $this->plugin->broadcastMessage("§eAll chest has been refilled!");
                        break;
                }
                if($this->plugin->checkEnd()) $this->plugin->startRestart();
                $this->gameTime--;
                break;
            case Arena::PHASE_RESTART:
                $this->plugin->broadcastMessage("§eTeleport to lobby in §c{$this->restartTime}§e seconds", Arena::MSG_TIP);
                $this->restartTime--;

                switch ($this->restartTime) {
                    case 0:

                        foreach ($this->plugin->players as $player) {
                            $player->teleport($this->plugin->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
                            $player->getInventory()->clearAll();
                            $player->getArmorInventory()->clearAll();
                            $player->getCursorInventory()->clearAll();
                            $player->setFood(20);
                            $player->setHealth(20);
                            $player->getInventory()->setItem(0, Item::get(345, 0, 1)->setCustomName("§l§aConnect §r§7(§fTap to use§7)"));
	                        $player->getInventory()->setItem(1, Item::get(399, 0, 1)->setCustomName("§l§eLinks §r§7(§fTap to use§7)"));
                            $player->getInventory()->setItem(2, Item::get(144, 3, 1)->setCustomName("§l§bProfile §r§7(§fTap to use§7)"));
	                        $player->getInventory()->setItem(4, Item::get(54, 0, 1)->setCustomName("§l§6Collectibles §r§7(§fTap to use§7)"));
                            $player->setGamemode($this->plugin->plugin->getServer()->getDefaultGamemode());
                        }
                        $this->plugin->loadArena(true);
                        $this->reloadTimer();
                        break;
                }
                break;
        }
    }

    public function reloadSign() {
        if(!is_array($this->plugin->data["joinsign"]) || empty($this->plugin->data["joinsign"])) return;

        $signPos = Position::fromObject(Vector3::fromString($this->plugin->data["joinsign"][0]), $this->plugin->plugin->getServer()->getLevelByName($this->plugin->data["joinsign"][1]));

        if(!$signPos->getLevel() instanceof Level) return;

        $signText = [
            "§l§CSky§Bwars",
            "§9[ §b? / ? §9]",
            "§6Setup",
            "§6Wait few sec..."
        ];

        if($signPos->getLevel()->getTile($signPos) === null) return;

        if($this->plugin->setup) {
            /** @var Sign $sign */
            $sign = $signPos->getLevel()->getTile($signPos);
            $sign->setText($signText[0], $signText[1], $signText[2], $signText[3]);
            return;
        }

        $signText[1] = "§b" . count($this->plugin->players) . " / " . $this->plugin->data["slots"] . "";

        switch ($this->plugin->phase) {
            case Arena::PHASE_LOBBY:
                if(count($this->plugin->players) >= $this->plugin->data["slots"]) {
                    $signText[2] = "§cFULL";
                    $signText[3] = "§9Map§7: §e{$this->plugin->level->getFolderName()}";
                }
                else {
                    $signText[2] = "§aJOIN";
                    $signText[3] = "§9Map§7: §e{$this->plugin->level->getFolderName()}";
                }
                break;
            case Arena::PHASE_GAME:
                $signText[2] = "§cIn-Game";
                $signText[3] = "§9Map§7: §e{$this->plugin->level->getFolderName()}";
                break;
            case Arena::PHASE_RESTART:
                $signText[2] = "§cRESET";
                $signText[3] = "§9Map§7: §e{$this->plugin->level->getFolderName()}";
                break;
        }

        /** @var Sign $sign */
        $sign = $signPos->getLevel()->getTile($signPos);
        $sign->setText($signText[0], $signText[1], $signText[2], $signText[3]);
    }

    public function reloadTimer() {
        $this->startTime = 15;
        $this->gameTime = 20 * 60;
        $this->restartTime = 10;
    }
}
