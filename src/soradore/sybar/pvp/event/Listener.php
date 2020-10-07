<?php

namespace soradore\sybar\pvp\event;

use pocketmine\event\Listener as EventListener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Entity;
use soradore\sybar\pvp\entity\Skeleton;
use soradore\sybar\pvp\entity\Missile;
use soradore\sybar\pvp\entity\Car;
use pocketmine\Player;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\Server;
use pocketmine\event\player\{
    PlayerJumpEvent,
    PlayerToggleSneakEvent
};
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\types\GameRuleType;
use pocketmine\scheduler\Task;
use pocketmine\block\Block;

class Listener implements EventListener {

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $ev){
        $player = $ev->getPlayer();
        /*$nbt = Entity::createBaseNBT($player);
        $skeleton = Entity::createEntity("Missile", $player->getLevel(), $nbt);
        $skeleton->spawnToAll();*/
        //var_dump(base64_encode($player->getSkin()->getSkinData()));
        $nbt = Entity::createBaseNBT($player);
        $skeleton = Entity::createEntity("Car2", $player->getLevel(), $nbt);
        $skeleton->spawnToAll();
        /*$gpk = new GameRulesChangedPacket;
        $gpk->gameRules = [
            "showcoordinates" => [GameRuleType::BOOL,true]
        ];
        $player->dataPacket($gpk);*/
    }


    public function stopRiding(Player $player){
        $car = $this->plugin->getRiding($player);
        if($car != null){
            //echo "debug 1\n";
            if($car->isDriver($player)){
                //echo "debug 2\n";
                $car->setDriver();
            }elseif($car->isPassenger($player)){
                //echo "debug 3\n";
                $car->setPassenger();
            }
        }
    }

    public function onReceive(DataPacketReceiveEvent $ev){
        $pk = $ev->getPacket();
        $player = $ev->getPlayer();
        if($pk instanceof PlayerInputPacket){
            $car = $this->plugin->getRiding($player);
            if($pk->jumping || $pk->sneaking){
                $this->stopRiding($player);
                return;
            }
            if($car != null && $car->isDriver($player)){
                //$car->setCurrentSpeed($pk->motionY * 1.8);
                $car->addSpeed($pk->motionY * 0.08);
            }
            return;
        }

        if($pk instanceof InteractPacket){
            //var_dump($pk);
            $targetEntity = $player->getLevel()->getEntity($pk->target);
            if($pk->action == InteractPacket::ACTION_LEAVE_VEHICLE){
                $this->stopRiding($player);
                return;
            }
            if($pk->action == InteractPacket::ACTION_OPEN_INVENTORY){
                $windowId = Player::HARDCODED_INVENTORY_WINDOW_ID;
                if($pk->target != $player->getId() && (!isset($player->openHardcodedWindows[$windowId]))){
                    $player->openHardcodedWindows[$windowId] = true;
					$pk1 = new ContainerOpenPacket();
					$pk1->windowId = $windowId;
					$pk1->type = WindowTypes::INVENTORY;
					$pk1->x = $pk1->y = $pk1->z = 0;
					$pk1->entityUniqueId = $player->getId();
					$player->sendDataPacket($pk1);
                }
                return;
            }
        }
    }
}