<?php

namespace soradore\sybar\pvp\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\{
	ActorEventPacket, SetActorLinkPacket, AnimatePacket, AddActorPacket
};
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\Player;
use pocketmine\utils\UUID;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\math\Vector3;
use pocketmine\Server;
use soradore\sybar\pvp\entity\sound\Sound;
use soradore\sybar\pvp\main;
use soradore\sybar\pvp\inventory\CarInventory;

class Car2 extends Human implements Car {
    private $plugin;
    public $width = 1.6;
    public $height = 0.7;
    public $eyeHeight = 0.3;
    public $driver = null;
    public $passenger = null;
    public $currentSpeed = 0;
    public $maxSpeed = 3.0;
    public $inventory;

    public function __construct(Level $level, CompoundTag $nbt){
        $this->plugin = main::getInstance();
        $this->server = $this->plugin->getServer();
        $this->uuid = UUID::fromRandom();
        @$this->iniSkin();
        parent::__construct($level, $nbt);
        $this->setScale(2.0);
        $this->setNameTag("§e車");
        $this->setNameTagAlwaysVisible();
    }

    public function iniSkin(){
        $img = @imagecreatefrompng($this->plugin->getDataFolder() . "images/texture.car.ver2.png");
        $skinbytes = "";
        $s = (int)@getimagesize($this->plugin->getDataFolder() . "images/texture.car.ver2.png")[1];
        for($y = 0; $y < $s; $y++){
            for($x = 0; $x < 64; $x++){
                $colorat = @imagecolorat($img, $x, $y);
                $a = ((~((int)($colorat >> 24))) << 1) & 0xff;
                $r = ($colorat >> 16) & 0xff;
                $g = ($colorat >> 8) & 0xff;
                $b = $colorat & 0xff;
                $skinbytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
        $this->setSkin(new Skin("Standard_CustomSlim", $skinbytes, "", "geometry.car2", file_get_contents($this->plugin->getDataFolder() . "images/car.geo.ver2.json")));
        $this->sendSkin();
    }


    /*public function initEntity(): void{
        parent::initEntity();

        $this->inventory = new CarInventory($this);
    }*/

    public function hasMovement(): bool{
        return $this->hasMovement;
    }


    public function entityBaseTick(int $tickDiff = 1): bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);
        $driver = $this->getDriver();
        $passenger = $this->getPassenger();
        if($this->getCurrentSpeed() <= 0){
            //Sound::stop("music.engine_sound", $this);
        }
        if($driver == null){
            return false;
        }
        if($this->getId() != $this->plugin->getRiding($driver)->getId()){
            return false;
        }
        if(!$this->isAlive()){
            $this->setDriver();
            $this->setPassenger();
            return false;
        }
        if(!$driver->isOnline()){
            $this->setDriver();
            return false;
        }
        if($passenger != null && !$passenger->isOnline()){
            $this->setPassenger();
            return false;
        }
        $this->yaw = $driver->yaw;
        $speed = $this->getCurrentSpeed();
        $this->doSpeedTick($tickDiff);
        $moveX = sin(-deg2rad($this->yaw)) * $speed;
        $moveZ = cos(-deg2rad($this->yaw)) * $speed;
        $this->checkFront();
        $this->motion->x = $moveX;
        $this->motion->z = $moveZ;

        return $hasUpdate;
    }


    public function getCurrentSpeed(){
        return $this->currentSpeed;
    }


    /*public function onInteract(Player $player){
        if($this->getDriver() == null){
            $this->setDriver($player);
        }elseif($this->getPassenger() == null){
            $this->setPassenger($player);
        }
    }*/

    public function addSpeed(float $val){
        $speed = $this->getCurrentSpeed();
        $speed+=$val;
        $this->setCurrentSpeed($speed);
    }


    public function setCurrentSpeed(float $speed){
        if($speed <= 0){
            return;
        }
        if($speed > $this->maxSpeed){
            $speed = $this->maxSpeed;
        }
        $this->currentSpeed = $speed;
        //Sound::play("music.engine_sound", $this, 3, 5.0);
    }


    public function doSpeedTick(int $tickDiff){
        $now = $this->getCurrentSpeed();
        $after = $now - $tickDiff * 0.06;
        if($after < 0){
            $after = 0;
        }
        $this->currentSpeed = $after;
    }

    public function jump(): void
    {
        if($this->onGround)
            $this->motion->y = 0.5;
    }

    public function checkFront(): void
    {
        $dv = $this->getDirectionVector()->multiply(2.4);
        $checkPos = $this->add($dv->x, 0, $dv->z)->floor();
        if($this->level->getBlockAt($checkPos->x, $this->y+1, $checkPos->z)->isSolid())
        {
            $this->currentSpeed = 0;
            return;
        }
        if($this->level->getBlockAt($checkPos->x, $this->y, $checkPos->z)->isSolid())
        {
            $this->jump();
        }
    }

    // public function attack(EntityDamageEvent $ev): void{
    //     if($ev instanceof EntityDamageByEntityEvent){
    //         $this->fire();
    //         $ev->setCancelled();
    //         $this->yaw += 10;
    //     }
    // }

    public function attack(EntityDamageEvent $ev): void{
        if($ev instanceof EntityDamageByEntityEvent && !($ev instanceof EntityDamageByChildEntityEvent)){
            $damager = $ev->getDamager();
            if($damager instanceof Player){
                if($this->getDriver() == null){
                    $this->setDriver($damager);
                }elseif($this->getPassenger() == null){
                    $this->setPassenger($damager);
                }
            }
            $ev->setCancelled();
            return;
        }
        parent::attack($ev);
    }

    public function setDriver(Player $driver = null){
        $nowDriver = $this->getDriver();
        if($driver == null){
            if($nowDriver != null){
                $nowDriver->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, false);
                $nowDriver->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 0, 0));
                $this->sendLinkPacket($this->getId(), $nowDriver->getId(), EntityLink::TYPE_REMOVE);
                $this->driver = null;
                $this->plugin->setRiding($nowDriver);
            }
            return;
        }
        if($nowDriver != null && $this->getPassenger() != null){
            $driver->sendMessage("この乗り物は満員だ!");
            return;
        }
        $driver->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, true);
        $driver->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 0.3, 0.1));
        $this->sendLinkPacket($this->getId(), $driver->getId(), EntityLink::TYPE_RIDER);
        $this->driver = $driver;
        $this->plugin->setRiding($driver, $this);
        return;
    }

    public function sendLinkPacket(int $to, int $from, int $type, $target = null){
        $pk = new SetActorLinkPacket();
        $pk->link = new EntityLink($to, $from, $type, true, true);
        if($target == null){
            $target = $this->getViewers();
        }
        if(is_array($target)){
            Server::getInstance()->broadcastPacket($target, $pk);
            return;
        }
        if($target instanceof Player){
            $target->dataPacket($pk);
        }
    }


    public function setPassenger(Player $passenger = null){
        $nowPassenger = $this->getPassenger();
        if($passenger == null){
            if($nowPassenger != null){
                $nowPassenger->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, false);
                $nowPassenger->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 0, 0));
                $this->sendLinkPacket($this->getId(), $nowPassenger->getId(), EntityLink::TYPE_REMOVE);
                $this->passenger = null;
                $this->plugin->setRiding($nowPassenger);
            }
            return;
        }
        if($nowPassenger != null && $this->getDriver() != null){
            $passenger->sendMessage("この乗り物は満員だ!");
            return;
        }
        $passenger->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, true);
        $passenger->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(1, 0, -1.1));
        $this->sendLinkPacket($this->getId(), $passenger->getId(), EntityLink::TYPE_PASSENGER);
        $this->passenger = $passenger;
        $this->plugin->setRiding($passenger, $this);
        return;
    }

    public function getDriver(): ?Player{
        return $this->driver;
    }

    public function getPassenger(): ?Player{
        return $this->passenger;
    }

    public function isDriver(Player $player){
        $driver = $this->getDriver();
        if($driver != null){
            return $driver->getName() == $player->getName();
        }
        return false;
    }


    public function isPassenger(Player $player){
        $passenger = $this->getPassenger();
        if($passenger != null){
            return $passenger->getName() == $player->getName();
        }
        return false;
    }
}