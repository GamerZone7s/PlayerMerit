<?php

namespace GamerZone7s\playermerit;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\Config;
use pocketmine\player\Player;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\world\World;

class Main extends PluginBase implements Listener {

    private $config;
    private $enabledWorlds = [];
    private $playerMerits = [];

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // Load config.yml or create default
        if (!file_exists($this->getDataFolder() . "config.yml")) {
            $this->saveDefaultConfig();
        }
        $this->config = $this->getConfig();

        // Load the list of enabled worlds from config
        $this->enabledWorlds = $this->config->get("enabled_worlds", []);
        $this->getLogger()->info("Enabled worlds: " . implode(", ", $this->enabledWorlds));

        // Load merit.yml or create it if not exists
        $meritFile = $this->getDataFolder() . "merit.yml";
        if (!file_exists($meritFile)) {
            $meritData = new Config($meritFile, Config::YAML, []);
            $meritData->save();
        }

        $this->getLogger()->info("Player Merit Plugin Enabled!");
    }

    public function onDisable(): void {
        $this->getLogger()->info("Player Merit Plugin Disabled!");
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $worldName = $player->getWorld()->getFolderName();

        // Check if the player's world is in the enabled worlds list
        if (!$this->isWorldEnabled($worldName)) {
            return; // If the world is not enabled, do nothing
        }

        $name = $player->getName();

        // Load merit.yml file
        $meritData = new Config($this->getDataFolder() . "merit.yml", Config::YAML);

        // Check if the player already has merit stored
        if ($meritData->exists($name)) {
            $this->playerMerits[$name] = $meritData->get($name); // Load player's merit from merit.yml
        } else {
            // If player does not have merit stored, set default merit from config
            $defaultMerit = $this->config->get("default_merit", 100); // Default to 100 if not set
            $this->playerMerits[$name] = $defaultMerit;
            $meritData->set($name, $defaultMerit);
            $meritData->save();
        }
    }

    // Event handler for player kills
    public function onEntityDamage(EntityDamageByEntityEvent $event): void {
        $damager = $event->getDamager();
        $entity = $event->getEntity();

        // Check if both damager and entity are players
        if ($damager instanceof Player && $entity instanceof Player) {
            // Check if the world is enabled for merit tracking
            $worldName = $damager->getWorld()->getFolderName();
            if (!$this->isWorldEnabled($worldName)) {
                return; // If the world is not enabled, do nothing
            }

            // If player kills another player, reduce merit
            if ($event->getFinalDamage() >= $entity->getHealth()) {
                $this->reduceMeritOnKill($damager);
            }
        }
    }

    // Function to reduce merit points
    public function reduceMeritOnKill(Player $player): void {
        $name = $player->getName();

        // Check if player has merit points
        if (isset($this->playerMerits[$name])) {
            $this->playerMerits[$name] -= 10; // Reduce 10 points

            // Show remaining merit points to the player
            $player->sendMessage("§eYour remaining merit points: " . $this->playerMerits[$name]);

            // Warn the player if merit reaches 0
            if ($this->playerMerits[$name] === 0) {
                $player->sendMessage("§cWarning! Your merit points are 0. One more offense and you may be banned!");
            }

            // Check if merit points are below 0, and ban the player if necessary
            if ($this->playerMerits[$name] < 0) {
                $player->sendMessage("§4You have been banned due to negative merit points!");
                $player->kick("You have been banned due to negative merit points.", false);
                $this->getServer()->getNameBans()->addBan($player->getName(), "Negative merit points", null, "Console");
            }

            // Update merit.yml file
            $meritData = new Config($this->getDataFolder() . "merit.yml", Config::YAML);
            $meritData->set($name, $this->playerMerits[$name]);
            $meritData->save();

            // Notify player about reduced merit points
            $player->sendMessage("§cYou killed a player! Your merit points are reduced by 10.");
        }
    }

    // Function to check if the current world is enabled for merit system
    private function isWorldEnabled(string $worldName): bool {
        return in_array($worldName, $this->enabledWorlds);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "meritcheck") {
            if (!($sender instanceof Player)) {
                $sender->sendMessage("§cThis command can only be used in-game.");
                return false;
            }

            if (!isset($args[0])) {
                $sender->sendMessage("§cPlease provide a player name.");
                return false;
            }

            $targetName = $args[0];
            $target = $this->getServer()->getPlayerExact($targetName);

            if ($target === null) {
                $sender->sendMessage("§ePlayer not found or not online!");
                return false;
            }

            $targetName = $target->getName();
            if (!isset($this->playerMerits[$targetName])) {
                $sender->sendMessage("§eNo merit points available for this player.");
                return false;
            }

            $merit = $this->playerMerits[$targetName];

            if ($merit >= 50) {
                $sender->sendMessage("§a" . $targetName . " has " . $merit . " merit points. (Good Merit)");
            } else {
                $sender->sendMessage("§c" . $targetName . " has " . $merit . " merit points. (Low Merit)");
            }

            return true;
        }

        return false;
    }
}