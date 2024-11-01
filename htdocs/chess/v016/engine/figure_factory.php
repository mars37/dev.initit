<?php

class FigureFactory {
    public function create(GameState $game_state, int $position_index) {
        $figure_code = $game_state->position[$position_index];
        $figure_type = Functions::figureType($figure_code);
        switch($figure_type) {
            case FG_KING:
                $class_name = 'King';
                break;
            case FG_QUEEN:
                $class_name = 'Queen';
                break;
            case FG_ROOK:
                $class_name = 'Rook';
                break;
            case FG_BISHOP:
                $class_name = 'Bishop';
                break;
            case  FG_KNIGHT:
                $class_name = 'Knight';
                break;
            case FG_PAWN:
                $class_name = 'Pawn';
                break;
            default:
                throw new Exception('Unknown figure type');
        }
        return new $class_name($game_state, $position_index);
    }
}
