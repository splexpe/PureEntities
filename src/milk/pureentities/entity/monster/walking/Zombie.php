<?php

namespace milk\pureentities\entity\monster\walking;

use milk\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Ageable;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\EntityEventPacket;

class Zombie extends WalkingMonster implements Ageable{
    const NETWORK_ID = 32;

    public $width = 0.5;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    public function initEntity(CompoundTag $tag) : void{
        parent::initEntity($tag);

        $this->setSpeed(1.1);
        $this->setDamages([0, 3, 4, 6]);
    }

    public function getName() : string{
        return 'Zombie';
    }

    public function isBaby() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_BABY);
    }

    public function setHealth(float $amount) : void{
        parent::setHealth($amount);

        if($this->isAlive()){
            if(15 < $this->getHealth()){
                $this->setDamages([0, 2, 3, 4]);
            }else if(10 < $this->getHealth()){
                $this->setDamages([0, 3, 4, 6]);
            }else if(5 < $this->getHealth()){
                $this->setDamages([0, 3, 5, 7]);
            }else{
                $this->setDamages([0, 4, 6, 9]);
            }
        }
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 2){
            $this->attackDelay = 0;

            $pk = new EntityEventPacket();
            $pk->entityRuntimeId = $this->id;
            $pk->event = EntityEventPacket::ARM_SWING;
            $this->server->broadcastPacket($this->hasSpawned, $pk);

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getResultDamage());
            $player->attack($ev);
        }
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);

        //TODO: 밝기 측정 추후수정
        /*$time = $this->getLevel()->getTime() % Level::TIME_FULL;
        if(
            !$this->isOnFire()
            && ($time < Level::TIME_NIGHT || $time > Level::TIME_SUNRISE)
        ){
            $this->setOnFire(1);
        }*/
        return $hasUpdate;
    }

    public function getDrops() : array{
        $drops = [
            ItemFactory::get(Item::ROTTEN_FLESH, 0, \mt_rand(0, 2))
        ];

        if(\mt_rand(0, 199) < 5){
            switch(\mt_rand(0, 2)){
                case 0:
                    $drops[] = ItemFactory::get(Item::IRON_INGOT, 0, 1);
                    break;
                case 1:
                    $drops[] = ItemFactory::get(Item::CARROT, 0, 1);
                    break;
                case 2:
                    $drops[] = ItemFactory::get(Item::POTATO, 0, 1);
                    break;
            }
        }

        return $drops;
    }

}
