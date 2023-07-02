<?php

class ChessGame {
    function makeMove($cell_index_from, $cell_index_to) {

    }

    function getGameState() {
        return [
            'is_our_move' => true,
            'prev_move_from' => 1,
            'prev_move_to' => 18,
            'position' => [
                6,  0, 8,  4,  2,  8,  10, 6,
                12, 12, 12, 12, 12, 12, 12, 12,
                0,  0,  10,  0,  0,  0,  0,  0,
                0,  0,  0,  0,  0,  0,  0,  0,
                0,  0,  0,  0,  0,  0,  0,  0,
                0,  0,  0,  0,  0,  0,  0,  0,
                11, 11, 11, 11, 11, 11, 11, 11,
                5,  9,  7,  3,  1,  7,  9,  5
            ],
            'available_moves' => [
                48 => [40, 32],
                49 => [41, 33],
                50 => [42, 34],
                51 => [43, 35],
                52 => [44, 36],
                53 => [45, 37],
                54 => [46, 38],
                55 => [47, 39],
                57 => [40, 42],
                62 => [45, 47]
            ]
        ];
    }
}