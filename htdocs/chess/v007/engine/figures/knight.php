<?php

class Knight extends Figure {
    public function getCandidateMoves() {
        $moves = array();
        $shifts = array(
            array(-2, -1), array(-2, 1), array(-1, -2), array(-1, 2), array(1, -2), array(1, 2), array(2, -1),array(2, 1)
        );
        foreach($shifts as $shift) {
            $col = $this->col + $shift[0];
            if ($col < 0 || $col >= BOARD_SIZE) {
                continue;
            }
            $row = $this->row + $shift[1];
            if ($row < 0 || $row >= BOARD_SIZE) {
                continue;
            }
            $to_index = Functions::colRowToPositionIndex($col, $row);
            $to_figure = $this->game_state->position[$to_index];
            if ($this->color == Functions::color($to_figure)) {
                continue;
            }
            $moves[] = $to_index;
        }
        return $moves;
    }
}
