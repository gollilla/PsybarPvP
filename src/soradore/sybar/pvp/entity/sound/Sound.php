<?php


namespace soradore\sybar\pvp\entity\sound;


use pocketmine\network\mcpe\protocol\{
    PlaySoundPacket,
    StopSoundPacket
};
use pocketmine\entity\Entity;
use pocketmine\Server;

class Sound
{
    /**
     * @var null
     */
    private static $now_play = [];
    private static $timer;

    static function play(string $name, Entity $source, $time = 0, float $volume = 1.0, float $pitch = 1.0)
    {
        $id = $source->getId();
        if(!isset(self::$timer[$id])){
            self::$timer[$id] = time();
        }else{
            if($time <= (time() - self::$timer[$id])){
                self::stop($name, $source);
            }else{
                return;
            }
        }
        $players = Server::getInstance()->getOnlinePlayers();
        $packet = new PlaySoundPacket;
        $packet->soundName = $name;
        $packet->volume = $volume;
        $packet->pitch = $pitch;
        foreach ($players as $player){
            $packet->x = $source->getX();
            $packet->y = $source->getY();
            $packet->z = $source->getZ();
            $player->dataPacket($packet);
        }
        self::$now_play[$id] = $name;
    }

    static function stop(string $name = null, Entity $source)
    {
        $id = $source->getId();
        $packet = new StopSoundPacket;
        if($name == null){
            $packet->stopAll = true;
            $packet->soundName = "dummy.sound";
        }else{
            $packet->soundName = $name;
        }
        Server::getInstance()->broadcastPacket(Server::getInstance()->getOnlinePlayers(), $packet);
        unset(self::$timer[$id]);
    }
}