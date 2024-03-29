<?php

class MoveGenerator {
    private $game_state;

    public function __construct(GameState $game_state) {
        $this->game_state = $game_state;
        $this->game_state->setFigures();
    }

    public function generateAllMoves() {
        $moves = array();
        $color = $this->game_state->current_player_color;
        for($i = 0; $i < BOARD_SIZE * BOARD_SIZE; $i++) {
            $figure_code = $this->game_state->position[$i];
            if ($figure_code === FG_NONE || Functions::color($figure_code) != $color) {
                continue;
            }
            $figure_factory = new FigureFactory();
            $figure = $figure_factory->create($this->game_state, $i);
            $figure_moves = $figure->getAvailableMoves();
            if (count($figure_moves) > 0) {
                $moves[$i] = $figure_moves;
            }
        }
        return $moves;
    }
    
    public function generateAllMovesFlat() {
        $moves = $this->generateAllMoves();
        if (count($moves) === 0) {
            return $moves;
        }
        $flat_moves = array();
        foreach($moves as $index_from => $indexes_to) {
            foreach($indexes_to as $index_to) {
                $flat_moves[] = array($index_from, $index_to);
            }
        }
        return $flat_moves;
    }
}
