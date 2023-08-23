<?php

class ComputerAI {
    protected $game_state;

    public function __construct(GameState $game_state) {
        $this->game_state = $game_state;
    }

    public function getGenerateMove() {
        $move_generator = new MoveGenerator($this->game_state);
        $available_moves = $move_generator->generateAllMoves();
        if (count($available_moves) === 0) {
            return false;
        }
        $cell_from = array_rand($available_moves);
        $figure_moves = $available_moves[$cell_from];
        $cell_to = $figure_moves[array_rand($figure_moves)];
        return array('from' => $cell_from, 'to' => $cell_to);
    }
}