<?php

class Pawn extends Figure {
    protected int $row; // индекс строки положения пешки

    public function __construct(GameState $game_state, int $position_index) {
        parent::__construct($game_state, $position_index);
        $this->row = Functions::positionToRow($position_index);
    }

    public function getCandidateMoves() {
        $moves = array();
        if ($this->color == COLOR_WHITE) {
            $direction = -1;
            $first_row = 6;
        } else {
            $direction = 1;
            $first_row = 1;
        }

        // проверим поле перед пешкой
        $to_index = $this->position_index + $direction * 16;
        $to_figure = $this->game_state->position[$to_index];
        if ($to_figure === FG_NONE) {
            $moves[] = $to_index;
            if ($this->row === $first_row) {
                // пешка находится на начальной позиции, проверим ещё одно поле "вперёд"
                $to_index += $direction * 16;
                $to_figure = $this->game_state->position[$to_index];
                if ($to_figure === FG_NONE) {
                    $moves[] = $to_index;
                }
            }
        }
        // теперь проверим возможность взятий, сначала - влево
        $to_index = $this->position_index + $direction * 16 - 1;
        $to_figure = $this->game_state->position[$to_index];
        if (Functions::color($to_figure) === $this->enemy_color || $to_index === $this->game_state->crossed_field) {
            $moves[] = $to_index;
        }
        // теперь взятие вправо
        $to_index = $this->position_index + $direction * 16 + 1;
        $to_figure = $this->game_state->position[$to_index];
        if (Functions::color($to_figure) === $this->enemy_color || $to_index === $this->game_state->crossed_field) {
            $moves[] = $to_index;
        }
        return $moves;
    }

    public function getAvailableMoves() {
        $moves = array();
        $board_position = new BoardPosition();
        $our_king_position = $this->game_state->getKingPosition($this->color);
        $candidate_moves = $this->getCandidateMoves();
        $pawn_figure_weight = self::FIGURE_WEIGHTS[FG_PAWN];
        foreach($candidate_moves as $to_index) {
            $to_position = $this->game_state->position; // копируем позицию
            if ($to_index === $this->game_state->crossed_field) {
                // это "взятие на проходе". Надо убрать с позиции проскочившую пешку.
                $beat_col = Functions::positionToCol($to_index);
                $beat_cell_index = Functions::colRowToPositionIndex($beat_col, $this->row);
                $to_position[$beat_cell_index] = FG_NONE;
                $beat_figure_weight = $pawn_figure_weight;
            } else {
                $beat_figure_type = Functions::figureType($to_position[$to_index]);
                $beat_figure_weight = self::FIGURE_WEIGHTS[$beat_figure_type];
            }
            $to_position[$this->position_index] = FG_NONE; // убираем фигуру из текущей позиции
            $to_position[$to_index] = $this->figure; // перемещаем фигуру на выбранное поле. Если на том поле что-то стояло - оно "убирается"
            $board_position->setPosition($to_position);
            if ($board_position->isFieldUnderAttack($our_king_position, $this->enemy_color)) {
                // ход недопустим, т.к. после него наш король оказывается под атакой
                continue;
            }
            $moves[] = array($this->position_index, $to_index, $pawn_figure_weight, $beat_figure_weight);
        }
        return $moves;
    }

    public function makeMove($to_cell_index, $validate_move=true, $to_figure=FG_QUEEN) {
        $from_cell_index = $this->position_index;
        $crossed_field = $this->game_state->crossed_field;
        if (!parent::makeMove($to_cell_index, $validate_move)) {
            return false;
        }
        $row = $this->row;
        $this->game_state->non_action_semimove_counter = 0;
        $direction = $this->color === COLOR_WHITE ? -1 : 1;
        $to_row = Functions::positionToRow($to_cell_index);
        if ($row + 2*$direction == $to_row) {
            // пешка перескочила поле, пометим его как проходное, т.е. доступное для взятия пешкой оппонента
            $this->game_state->crossed_field = $from_cell_index + $direction * 16;
        } elseif ($crossed_field === $to_cell_index) {
            // этот ход - взятие "на проходе", надо убрать взятую пешку
            $beat_col = Functions::positionToCol($to_cell_index);
            $beat_cell_index = Functions::colRowToPositionIndex($beat_col, $row);
            $this->game_state->position[$beat_cell_index] = FG_NONE;
        } elseif (($direction == 1 && $to_row == BOARD_SIZE-1) || ($direction == -1 && $to_row == 0)) {
            // пешка достигла последней горизонтали - она превращается в другую фигуру
            $to_figure = Functions::figureType($to_figure) + $this->color;
            $this->game_state->position[$to_cell_index] = $to_figure;
        }
        return true;
    }
}