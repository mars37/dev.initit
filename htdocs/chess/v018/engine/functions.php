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

    static public function getHighestBitOld($num) {
        return (int)(1 << (int)log($num, 2));
    }

    /**
     * Не надо вызывать функцию с нулевым аргументом, надо проверять этот случай снаружи.
     * Проверку здесь я удалил для ускорения - в некоторых случая проверка уже есть снаружи (вызов внутри цикла, где проверка есть в условии цикла)
     * Это одно из тяжёлых мест для производительности
    */
    static public function getHighestBit($num) {
        $num |= ($num >> 1);
        $num |= ($num >> 2);
        $num |= ($num >> 4);
        $num |= ($num >> 8);
        $num |= ($num >> 16);
        $num |= ($num >> 32);
        return ($num ^ ($num >> 1));
    }
}