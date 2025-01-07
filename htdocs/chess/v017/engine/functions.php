<?php

class Functions {
    static public $pos8To16convert = array(
        68,   69,  70,  71, 72,  73,   74,  75,  
        84,   85,  86,  87, 88,  89,   90,  91,  
        100, 101, 102, 103, 104, 105, 106, 107,  
        116, 117, 118, 119, 120, 121, 122, 123,  
        132, 133, 134, 135, 136, 137, 138, 139,  
        148, 149, 150, 151, 152, 153, 154, 155,  
        164, 165, 166, 167, 168, 169, 170, 171,  
        180, 181, 182, 183, 184, 185, 186, 187
    );

    static public function color(int $figure) {
        return $figure & 0b11;
    }

    static public function figureType(int $figure) {
        return $figure & 0b11100;
    }

    static public function positionToCol(int $position_index) {
        return ($position_index & 0b1111) - 4;
    }

    static public function positionToRow(int $position_index) {
        return ($position_index >> 4) - 4;
    }

    static public function colRowToPositionIndex($col, $row) {
        return (($row + 4) << 4) + $col + 4;
    }

    // Преобразование "индекс поля на доске 16*16" -> "индекс поля на доске 8*8"
    static public function pos16ToPos8($pos16) {
        $row = ($pos16 >> 4) - 4;
        $col = ($pos16 & 0b1111) - 4;
        return ($row << 3) + $col;
    }

    // Преобразование "индекс поля на доске 8*8" -> "индекс поля на доске 16*16"
    static public function pos8ToPos16($pos8) {
        return self::$pos8To16convert[$pos8];
    }

    // Преобразование позиции на доске 16*16 в позицию на доске 8*8
    static public function convertPosition16To8($position16) {
        $result = array();
        for ($row = 0; $row < 8; $row++) {
            $i = (($row + 4) << 4) + 4;
            $result[] = $position16[$i];
            $result[] = $position16[$i+1];
            $result[] = $position16[$i+2];
            $result[] = $position16[$i+3];
            $result[] = $position16[$i+4];
            $result[] = $position16[$i+5];
            $result[] = $position16[$i+6];
            $result[] = $position16[$i+7];
        }
        return $result;
    }

    // Преобразование позиции на доске 8*8 в позицию на доске 16*16
    static public function convertPosition8To16($position8) {
        $result = array_fill(0, 256, FG_NONE + COLOR_OVER);
        foreach(self::$pos8To16convert as $index8 => $index16) {
            $result[$index16] = $position8[$index8];
        }
        return $result;
    }

    // Преобразование структуры допустимых ходов из формата с индексами полей на доске 16*16 в формат с индексами полей на доске 8*8 
    static public function convertMoves16To8($moves) {
        $result = array();
        foreach ($moves as $pos_from_16 => $moves_16) {
            $pos_from_8 = self::pos16ToPos8($pos_from_16);
            $result[$pos_from_8] = array();
            foreach($moves_16 as $to_16) {
                $result[$pos_from_8][] = self::pos16ToPos8($to_16);
            }
        }
        return $result;
    }
}