<?php
declare(strict_types=1);

namespace Warship;

class Game {
    const BOARD_WATER = 1;
    const BOARD_BOAT = 2;
    const BOARD_SHOT = 4;

    private array $boards;
    private array $lifes;

    public function __construct()
    {
        $this->reset();
        $this->setup();
    }

    public function getCoord(int $x, int $y): string {
        return chr(65 + $x) . ($y + 1);
    }

    public function reset(): void
    {
        $this->boards = [
            'my' => [],
            'ennemy' => []
        ];
        $this->lifes = [
            'my' => 0,
            'ennemy' => 0
        ];
        
        for($x = 0; $x < 10; $x++) {
            for($y = 0; $y < 10; $y++) {
                $coord = $this->getCoord($x, $y);
                $this->boards['my'][$coord] = self::BOARD_WATER;
                $this->boards['ennemy'][$coord] = self::BOARD_WATER;
            }
        }
    }

    public function setup(): void {
        $boatLengths = array(5, 4, 3, 3, 2);
        foreach($boatLengths as $boatLength) {
            do {
                $x = mt_rand(0, 9);
                $y = mt_rand(0, 9);
                $isHorizontal = mt_rand(0, 1) === 0;
            } while($this->canPlaceBoat($x, $y, $boatLength, $isHorizontal));

            $this->placeBoat($x, $y, $boatLength, $isHorizontal);
        }
    }

    public function canPlaceBoat(int $x, int $y, int $length, bool $isHorizontal): bool {
        for($i = 0; $i < $length; $i++) {
            $coord = $isHorizontal ? $this->getCoord($x + $length, $y) : $this->getCoord($x, $y + $length);
            if(!isset($this->boards['my'][$coord]) || $this->boards['my'][$coord] !== self::BOARD_WATER) {
                return false;
            }
        }

        return true;
    }

    public function placeBoat(int $x, int $y, int $length, bool $isHorizontal) {
        for($i = 0; $i < $length; $i++) {
            $coord = $isHorizontal ? $this->getCoord($x + $length, $y) : $this->getCoord($x, $y + $length);
            $this->boards['my'][$coord] = self::BOARD_BOAT;
            $this->lifes['my']++;
            $this->lifes['ennemy']++;
        }
    }

    public function shot(): string {
        do {
            $x = mt_rand(0, 9);
            $y = mt_rand(0, 9);
            $coord = $this->getCoord($x, $y);
        } while($this->boards['ennemy'][$coord] !== self::BOARD_WATER);
        $this->boards['ennemy'][$coord] &= self::BOARD_SHOT;

        return $coord;
    }

    public function ennemyShot($coord): string {
        $this->boards['my'][$coord] &= self::BOARD_SHOT;

        if($this->boards['my'][$coord] | self::BOARD_WATER) {
            return 'miss';
        }

        $this->lifes['my']--;
        if($this->lifes['my'] > 0) {
            return 'hit';
        }

        return 'won';
    }

    public function handleCommand($command): void {
        $shotCoord = null;
        if ($command === 'your turn') {
            $shotCoord = $this->shot();
            echo $shotCoord . "\n";
        } elseif ($m = preg_match('`^([A-J])?:([1-9]|10))$`x', $command)) {
            echo $this->ennemyShot($m[1].$m[2]) . "\n";
        } elseif ($command === 'miss') {
            echo "ok\n";
        } elseif (preg_match('`^hit|sunk|won$`x', $command)) {
            $this->boards['ennemy'][$shotCoord] &= self::BOARD_BOAT;
            $this->lifes['ennemy']--;
            echo "ok\n";
        } else {
            die("Can't understand '$command'\n");
        }
    }
}