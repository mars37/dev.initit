<?php

class Functions {
    static public function color(int $figure) {
        return $figure & 0b11;
    }

    static public function figureType(int $figure) {
        return $figure & 0b11100;
    }

    static public function positionToCol(int $position_index) {
        return $position_index & 0b111;
    }

    static public function positionToRow(int $position_index) {
        return $position_index >> 3;
    }

    static public function colRowToPositionIndex($col, $row) {
        return ($row << 3) + $col;
    }
}