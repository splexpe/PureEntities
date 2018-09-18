<?php

declare(strict_types=1);

namespace milk\pureentities\entity\monster;

use pocketmine\entity\Entity;

interface Monster{

    public function attackEntity(Entity $player);

    public function getDamages(int $difficulty = -1) : array;
    public function getResultDamage(int $difficulty = -1) : int;

    public function getMinDamage(int $difficulty = -1) : int;
    public function getMaxDamage(int $difficulty = -1) : int;

    public function setMinDamage(int $damage, int $difficulty = -1) : void;
    public function setMaxDamage(int $damage, int $difficulty = -1) : void;

    public function setDamage(int $damage, int $difficulty = -1) : void;
    public function setDamages(array $damages) : void;
    public function setMinDamages(array $damages) : void;
    public function setMaxDamages(array $damages) : void;

}