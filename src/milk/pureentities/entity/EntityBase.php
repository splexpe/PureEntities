<?php

declare(strict_types=1);

namespace milk\pureentities\entity;

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\timings\Timings;

abstract class EntityBase extends Creature{

    private $speed = 1.0;

    protected $stayTime = 0;
    protected $moveTime = 0;

    /** @var Vector3|Entity */
    protected $target = \null;

    /** @var Vector3|Entity */
    protected $followTarget = \null;

    private $movement = \true;
    private $friendly = \false;
    private $wallcheck = \true;

    public function __destruct(){}

    public abstract function updateMove($tickDiff);

    public abstract function targetOption(Creature $creature, $distance);

    public function isMovement() : bool{
        return $this->movement;
    }

    public function isFriendly() : bool{
        return $this->friendly;
    }

    public function isWallCheck() : bool{
        return $this->wallcheck;
    }

    public function setMovement(bool $value) : void{
        $this->movement = $value;
    }

    public function setFriendly(bool $value) : void{
        $this->friendly = $value;
    }

    public function setWallCheck(bool $value) : void{
        $this->wallcheck = $value;
    }

    public function getSpeed() : float{
        return $this->speed;
    }

    public function setSpeed(float $speed) : void{
        $this->speed = $speed;
    }

    public function getTarget() : ?Vector3{
        return $this->followTarget != null ? $this->followTarget : ($this->target instanceof Entity ? $this->target : null);
    }

    public function setTarget(Entity $target) : void{
        $this->followTarget = $target;
        
        $this->moveTime = 0;
        $this->stayTime = 0;
        $this->target = \null;
    }
    
    public function initEntity(CompoundTag $tag) : void{
        parent::initEntity($tag);

        if($tag->hasTag('Movement', ByteTag::class)){
            $this->setMovement($tag->getByte('Movement') !== 0);
        }
        if($tag->hasTag('Friendly', ByteTag::class)){
            $this->setFriendly($tag->getByte('Friendly') !== 0);
        }
        if($tag->hasTag('WallCheck', ByteTag::class)){
            $this->setWallCheck($tag->getByte('WallCheck') !== 0);
        }
        $this->setImmobile(\true);
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setByte('Movement', $this->isMovement() ? 1 : 0);
        $nbt->setByte('Friendly', $this->isFriendly() ? 1 : 0);
        $nbt->setByte('WallCheck', $this->isWallCheck() ? 1 : 0);

        return $nbt;
    }

    public function updateMovement(bool $teleport = \false) : void{
        $send = \false;
        if(
            $this->lastLocation->x !== $this->x
            || $this->lastLocation->y !== $this->y
            || $this->lastLocation->z !== $this->z
            || $this->lastLocation->yaw !== $this->yaw
            || $this->lastLocation->pitch !== $this->pitch
        ){
            $send = \true;
            $this->lastLocation = $this->asLocation();
        }

        if(
            $this->lastMotion->x !== $this->motion->x
            || $this->lastMotion->y !== $this->motion->y
            || $this->lastMotion->z !== $this->motion->z
        ){
            $this->lastMotion = clone $this->motion;
        }

        if($send){
            $this->broadcastMovement($teleport);
        }
    }

    public function attack(EntityDamageEvent $source) : void{
        if($this->attackTime > 0) return;

        parent::attack($source);

        if($source->isCancelled() || !($source instanceof EntityDamageByEntityEvent)){
            return;
        }

        $this->stayTime = 0;
        $this->moveTime = 0;

        //TODO: Implement FlyingEntity
        $damager = $source->getDamager();
        $motion = (new Vector3($this->x - $damager->x, $this->y - $damager->y, $this->z - $damager->z))->normalize();
        $motion->y = 0.6;
        $this->setMotion($motion);
    }

    public function knockBack(Entity $attacker, float $damage, float $x, float $z, float $base = 0.4) : void{

    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = Entity::entityBaseTick($tickDiff);

        if($this->isInsideOfSolid()){
            $hasUpdate = \true;
            $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_SUFFOCATION, 1);
            $this->attack($ev);
        }

        if($this->moveTime > 0){
            $this->moveTime -= $tickDiff;
        }
        if($this->attackTime > 0){
            $this->attackTime -= $tickDiff;
        }
        return $hasUpdate;
    }

    public function move(float $dx, float $dy, float $dz) : void{
        $this->blocksAround = \null;

        Timings::$entityMoveTimer->startTiming();

        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;

        $list = $this->level->getCollisionCubes($this, $this->level->getTickRate() > 1 ? $this->boundingBox->offsetCopy($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz));
        foreach($list as $bb){
            $dy = $bb->calculateYOffset($this->boundingBox, $dy);
        }
        $this->boundingBox->offset(0, $dy, 0);

        if($this->isWallCheck()){
            foreach($list as $bb){
                $dx = $bb->calculateXOffset($this->boundingBox, $dx);
            }
            $this->boundingBox->offset($dx, 0, 0);

            foreach($list as $bb){
                $dz = $bb->calculateZOffset($this->boundingBox, $dz);
            }
            $this->boundingBox->offset(0, 0, $dz);
        }else{
            $this->boundingBox->offset($dx, 0, $dz);
        }

        $this->x += $dx;
        $this->y += $dy;
        $this->z += $dz;

        $this->checkChunks();
        $this->checkBlockCollision();
        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);

        Timings::$entityMoveTimer->stopTiming();
    }

}
