<?php

namespace soradore\sybar\pvp\inventory;

use pocketmine\inventory\PlayerInventory;

class CarInventory extends PlayerInventory {

    public function __construct($holder){
        $this->holder = $holder;
        parent::__construct($holder);
    }

    public function getNetworkType(): int{
        return -1;
    }

    public function getDefaultSize(): int{
        return 32;
    }
    
    public function getName(): string{
        return "CarInventory";
    }
}