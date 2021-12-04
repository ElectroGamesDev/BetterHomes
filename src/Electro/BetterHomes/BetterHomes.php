<?php

namespace Electro\BetterHomes;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\MenuOption;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\Config;

use pocketmine\player\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\event\Listener;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\world\Position;

class BetterHomes extends PluginBase implements Listener{

    public array $homes = [];

    public function onEnable() : void{
        if (!file_exists($this->getDataFolder() . "Players")){
            mkdir($this->getDataFolder() . "Players");
        }
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if (!file_exists($this->getDataFolder() . "Players/" . $player->getName() . ".yml")) {
            new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML, array(
                "Homes" => [],
            ));
        }
        $this->loadData($player);
    }

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $this->saveData($player);
        unset($this->homes[$player->getName()]);
    }

    public function onDisable() : void
    {
        $this->saveAllData();
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        if (!$sender instanceof Player)
        {
            $sender->sendMessage("§cYou must be in-game to run this command");
            return true;
        }
        switch($cmd->getName()) {
            case "home":
                if(!$sender instanceof Player) {
                    $sender->sendMessage("§l§cERROR: §r§aYou must be in-game to execute this command");
                    return true;
                }
                $sender->sendForm($this->homeForm());
                break;
        }
        return true;
    }

    private function homeForm() : MenuForm{
        return new MenuForm(
            "§lHomes Form",
            "",
            [
                new MenuOption("§lTeleport To Home"),
                new MenuOption("§lCreate Home"),
                new MenuOption("§lRemove Home")
            ],

            function(Player $submitter, int $selected) : void{

                if ($selected == 1)
                {
                    $submitter->sendForm($this->homeCreationForm());
                }
                if ($selected == 2)
                {
                    $submitter->sendForm($this->homeRemoveForm($submitter));
                }
                if ($selected == 0)
                {
                    $submitter->sendForm($this->homeTeleportForm($submitter));
                }
            }
        );
    }

    private function homeCreationForm() : CustomForm{
        return new CustomForm(
            "§lCreate a Home",
            [
                new Input("name", '§rEnter Home Name', "Base"),
            ],
            function(Player $submitter, CustomFormResponse $response) : void{
                $homeName = $response->getString("name");
                if ($homeName == null)
                {
                    $submitter->sendMessage("§l§cERROR: §r§aYou have entered an invalid home name");
                    return;
                }
                if (array_key_exists($homeName, $this->homes[$submitter->getName()])){
                    $submitter->sendMessage("§l§cERROR: §r§aA home with that name already exists");
                    return;
                }
                $this->homes[$submitter->getName()][$homeName] = [$submitter->getPosition()->getX(), $submitter->getPosition()->getY(), $submitter->getPosition()->getZ(), $submitter->getWorld()->getFolderName()];
                $submitter->sendMessage("§aYou have successfully created the home " . $homeName . "!");
            },
        );
    }

    private function homeRemoveForm(Player $player) : CustomForm{
        $list = [];
        foreach ($this->homes[$player->getName()] as $homeName => $home)
        {
            $list[] = $homeName;
        }
        return new CustomForm(
            "§lRemove a Home",
            [
                new Dropdown("homes", "Select A Home To Remove", $list),
            ],
            function(Player $submitter, CustomFormResponse $response) use ($list) : void{
                $homeName = $response->getInt("homes");

                if (!is_numeric($homeName)){
                    $submitter->sendMessage("§l§cERROR: §r§aYou selected an invalid home");
                    return;
                }
                $homeName = $list[$response->getInt("homes")];
                unset($this->homes[$submitter->getName()][$homeName]);
                $submitter->sendMessage("§aYou have successfully removed the home " . $homeName);
            },
        );
    }

    private function homeTeleportForm(Player $player) : CustomForm{
        $list = [];
        foreach ($this->homes[$player->getName()] as $homeName => $home)
        {
            $list[] = $homeName;
        }
        return new CustomForm(
            "§lTeleport To A Home",
            [
                new Dropdown("homes", "Select A Home To Teleport To", $list),
            ],
            function(Player $submitter, CustomFormResponse $response) use ($list) : void{
                $homeName = $response->getInt("homes");
                if (!is_numeric($homeName)){
                    $submitter->sendMessage("§l§cERROR: §r§aYou selected an invalid home");
                    return;
                }
                $homeName = $list[$response->getInt("homes")];
                $submitter->teleport(new Position($this->homes[$submitter->getName()][$homeName][0], $this->homes[$submitter->getName()][$homeName][1], $this->homes[$submitter->getName()][$homeName][2], $this->getServer()->getWorldManager()->getWorldByName($this->homes[$submitter->getName()][$homeName][3])));
                $submitter->sendMessage("§aYou have successfully teleported to " . $homeName);
            },
        );
    }

    public function saveData(Player $player)
    {
        $playerHomes = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
        $playerHomes->set("Homes", $this->homes[$player->getName()]);
        $playerHomes->save();
    }

    public function saveAllData()
    {
        foreach ($this->homes as $player => $home) {
            $playerHomes = new Config($this->getDataFolder() . "Players/" . $player . ".yml", Config::YAML);
            $playerHomes->set("Homes", $home);
            $playerHomes->save();
        }
    }

    public function loadData(Player $player)
    {
        $playerHomes = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
        $this->homes[$player->getName()] = $playerHomes->get("Homes");
    }

}
