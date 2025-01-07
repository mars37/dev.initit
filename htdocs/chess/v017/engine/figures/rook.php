<?php

class Rook extends Figure {
    const WHITE_KING_ROOK_INIT_POSITION = 187;
    const WHITE_QUEEN_ROOK_INIT_POSITION = 180;
    const BLACK_KING_ROOK_INIT_POSITION = 75;
    const BLACK_QUEEN_ROOK_INIT_POSITION = 68;
    const SHIFTS = array(-16, -1, 1, 16); 

    public function getCandidateMoves() {
        return $this->getLongRangeCandidateMoves(self::SHIFTS);
    }

    public function makeMove($to_cell_index, $validate_move=true) {
        $cell_from = $this->position_index;
        if (!parent::makeMove($to_cell_index, $validate_move)) {
            return false;
        }
        if ($this->color == COLOR_WHITE) {
            switch ($cell_from) {
                case self::WHITE_KING_ROOK_INIT_POSITION:
                    $this->game_state->enable_castling_white_king = false;
                    break;
                case self::WHITE_QUEEN_ROOK_INIT_POSITION:
                    $this->game_state->enable_castling_white_queen = false;
                    break;
            }
        } elseif ($this->color == COLOR_BLACK) {
            switch ($cell_from) {
                case self::BLACK_KING_ROOK_INIT_POSITION:
                    $this->game_state->enable_castling_black_king = false;
                    break;
                case self::BLACK_QUEEN_ROOK_INIT_POSITION:
                    $this->game_state->enable_castling_black_queen = false;
                    break;
            }
        }
        return true;
    }
}
