<?php

namespace soradore\sybar\pvp\;

use pocketmine\plugin\PluginBase;
use pocketmine\entity\Entity;
use soradore\sybar\pvp\event\Listener;
use soradore\sybar\pvp\entity\Skeleton;
use soradore\sybar\pvp\entity\Missile;
use soradore\sybar\pvp\entity\Miner;
use soradore\sybar\pvp\entity\Car;
use soradore\sybar\pvp\entity\Car1;
use soradore\sybar\pvp\entity\Car2;
use soradore\sybar\pvp\entity\projectile\Rocket;
use pocketmine\Player;

class main extends PluginBase {

    public static $instance = null;
    public $riding = [];

    public function onEnable(){
        $this->eventListener = new Listener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->eventListener, $this);
        Entity::registerEntity(Car1::class, true);
        Entity::registerEntity(Car2::class, true);
        //Entity::registerEntity(Rocket::class, false, ["FireworksRocket"]);
    }

    public static function getInstance(): PluginBase
    {
        return self::$instance;
    }

    public function setRiding(Player $player, ?Car $car = null){
        $name = $player->getName();
        if($this->getRiding($player) != null){
            $this->eventListener->stopRiding($player);
        }
        $this->riding[$name] = $car;
    }

    public function getRidings(){
        return $this->riding;
    }

    public function getRiding(Player $player){
        $name = $player->getName();
        return isset($this->riding[$name]) ? $this->riding[$name] : null;
    }

    public function onLoad(){
        self::$instance = $this;
    }
    
}

