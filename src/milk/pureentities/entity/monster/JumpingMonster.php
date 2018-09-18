<?php

declare(strict_types=1);

namespace milk\pureentities\entity\monster;

use milk\pureentities\entity\JumpingEntity;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

abstract class JumpingMonster extends JumpingEntity implements Monster{

    protected $attackDelay = 0;

    private $minDamage = [0, 0, 0, 0];
    private $maxDamage = [0, 0, 0, 0];

    public abstract function attackEntity(Entity $player);

    public function getDamages(int $difficulty = -1) : array{
        return [$this->getMinDamage($difficulty), $this->getMaxDamage($difficulty)];
    }

    public function getResultDamage(int $difficulty = -1) : int{
        return \mt_rand(...$this->getDamages($difficulty));// + $this->inventory->getItemInHand()->getAttackPoints();
    }

    public function getMinDamage(int $difficulty = -1) : int{
        if($difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        return \min($this->minDamage[$difficulty], $this->maxDamage[$difficulty]);
    }

    public function getMaxDamage(int $difficulty = -1) : int{
        if($difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        return \max($this->minDamage[$difficulty], $this->maxDamage[$difficulty]);
    }

    public function setMinDamage(int $damage, int $difficulty = -1) : void{
        if($difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        $this->minDamage[$difficulty] = $damage;
    }

    public function setMaxDamage(int $damage, int $difficulty = -1) : void{
        if($difficulty === \null || $difficulty < 1 || $difficulty > 3){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        $this->maxDamage[$difficulty] = $damage;
    }

    public function setDamage(int $damage, int $difficulty = -1) : void{
        if($difficulty === \null || $difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }

        $this->setMinDamage($damage, $difficulty);
        $this->setMaxDamage($damage, $difficulty);
    }

    public function setDamages(array $damages) : void{
        $this->setMinDamages($damages);
        $this->setMaxDamages($damages);
    }

    public function setMinDamages(array $damages) : void{
        if(count($damages) > 3) for ($i = 0; $i < 4; $i++) {
            $this->setMinDamage((int) $damages[$i], $i);
        }
    }

    public function setMaxDamages(array $damages) : void{
        if(count($damages) > 3) for ($i = 0; $i < 4; $i++) {
            $this->setMaxDamage((int) $damages[$i], $i);
        }
    }

    public function onUpdate(int $currentTick) : bool{
        if($this->server->getDifficulty() < 1 || $this->isFlaggedForDespawn()){
            $this->close();
            return \false;
        }

        if(!$this->isAlive()){
            if(++$this->deadTicks >= 23){
                $this->close();
                return \false;
            }
            return \true;
        }

        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
        $this->entityBaseTick($tickDiff);

        $target = $this->updateMove($tickDiff);
        if($this->isFriendly()){
            if(!($target instanceof Player)){
                if($target instanceof Entity){
                    $this->attackEntity($target);
                }elseif(
                    $target instanceof Vector3
                    &&(($this->x - $target->x) ** 2 + ($this->z - $target->z) ** 2) <= 1
                ){
                    $this->moveTime = 0;
                }
            }
        }else{
            if($target instanceof Entity){
                $this->attackEntity($target);
            }elseif(
                $target instanceof Vector3
                &&(($this->x - $target->x) ** 2 + ($this->z - $target->z) ** 2) <= 1
            ){
                $this->moveTime = 0;
            }
        }
        return \true;
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);

        $this->attackDelay += $tickDiff;
        if(!$this->canBreathe()){
            $hasUpdate = \true;
            $this->doAirSupplyTick($tickDiff);
        }else{
            $this->setAirSupplyTicks($this->getMaxAirSupplyTicks());
        }
        return $hasUpdate;
    }

    public function targetOption(Creature $creature, $distance){
        return (!($creature instanceof Player) || ($creature->isSurvival() && $creature->spawned)) && $creature->isAlive() && !$creature->closed && $distance <= 144;
    }

}