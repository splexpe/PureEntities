<?php

declare(strict_types=1);

namespace milk\pureentities\entity;

use milk\pureentities\entity\animal\Animal;
use pocketmine\math\Vector3;
use pocketmine\entity\Creature;

abstract class FlyingEntity extends EntityBase{

    protected function checkTarget(){
        if($this->attackTime > 0){
            return;
        }

        if($this->followTarget !== \null && !$this->followTarget->closed && $this->followTarget->isAlive()){
            return;
        }

        $option = \true;
        $target = $this->target;
        if(!($target instanceof Creature) or !($option = $this->targetOption($target, $this->distanceSquared($target)))){
            if(!$option) $this->target = \null;

            $near = PHP_INT_MAX;
            foreach ($this->getLevel()->getEntities() as $creature){
                if(
                    $creature === $this
                    || !($creature instanceof Creature)
                    || $creature instanceof Animal
                    || $creature instanceof EntityBase && $creature->isFriendly() === $this->isFriendly()
                    || ($distance = $this->distanceSquared($creature)) > $near or !$this->targetOption($creature, $distance)
                ){
                    continue;
                }

                $near = $distance;
                $this->target = $creature;
            }
        }

        if(
            $this->target instanceof Creature
            && $this->target->isAlive()
        ){
            return;
        }

        $maxY = max($this->getLevel()->getHighestBlockAt((int) $this->x, (int) $this->z) + 15, 120);
        if($this->moveTime <= 0 or !$this->target instanceof Vector3){
            $x = \mt_rand(20, 100);
            $z = \mt_rand(20, 100);
            if($this->y > $maxY){
                $y = \mt_rand(-12, -4);
            }else{
                $y = \mt_rand(-10, 10);
            }
            $this->moveTime = \mt_rand(300, 1200);
            $this->target = $this->add(\mt_rand(0, 1) ? $x : -$x, $y, \mt_rand(0, 1) ? $z : -$z);
        }
    }

    public function updateMove($tickDiff){
        if(!$this->isMovement()){
            return \null;
        }

        if($this->attackTime > 0){
            $this->move($this->motion->x * $tickDiff, $this->motion->y * $tickDiff, $this->motion->z * $tickDiff);
            $this->updateMovement();
            return \null;
        }

        //TODO: 재설계중...
        /*$before = $this->target;
        $this->checkTarget();
        if($this->target instanceof Player or $before !== $this->target){
            $x = $this->target->x - $this->x;
            $y = $this->target->y - $this->y;
            $z = $this->target->z - $this->z;

            $diff = \abs($x) + \abs($z);
            if($x ** 2 + $z ** 2 < 0.5){
                $this->motion->x = 0;
                $this->motion->z = 0;
            }else{
                $this->motion->x = $this->getSpeed() * 0.15 * ($x / $diff);
                $this->motion->z = $this->getSpeed() * 0.15 * ($z / $diff);

                $this->motion->y = $this->getSpeed() * 0.27 * ($y / $diff);
            }
            $this->yaw = \rad2deg(-\atan2($x / $diff, $z / $diff));
            $this->pitch = $y === 0 ? 0 : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)));
        }

        $target = $this->target;
        $dx = $this->motion->x * $tickDiff;
        $dy = $this->motion->y * $tickDiff;
        $dz = $this->motion->z * $tickDiff;

        $be = new Vector2($this->x + $dx, $this->z + $dz);
        $this->move($dx, $dy, $dz);
        $af = new Vector2($this->x, $this->z);

        if($be->x !== $af->x || $be->y !== $af->y){
            $this->moveTime -= 90 * $tickDiff;
        }

        $this->updateMovement();*/
        return \null;
    }

    public function fall(float $fallDistance) : void{

    }

}