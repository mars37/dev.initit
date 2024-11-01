<?php

class King extends Figure {
    // индексы полей между королём и ладьёй. Порядок важен! От короля - до ладьи
    const FIELDS_CASTLING_WHITE_KING = array(61, 62);
    const FIELDS_CASTLING_WHITE_QUEEN = array(59, 58, 57);
    const FIELDS_CASTLING_BLACK_KING = array(5, 6);
    const FIELDS_CASTLING_BLACK_QUEEN = array(3, 2, 1);

    const FIELD_INIT_WHITE = 60; // начальная позиция белого короля
    const FIELD_INIT_BLACK = 4; // начальная позиция чёрного короля
    const TO_COLUMN_CASTLING_KING = 6; // индекс вертикали на которую встаёт король при короткой рокировке

    protected BoardPosition $board_position;

    // Метод отдаёт только поля простых перемещений. Возможность рокировок будет проверена в методе getAvailableMoves
    public function getCandidateMoves() {
        return $this->getShortRangeCandidateMoves(Queen::SHIFTS);
    }

    public function getAvailableMoves() {
        $moves = array();
        $this->board_position = new BoardPosition();
        $king_figure_weight = self::FIGURE_WEIGHTS[FG_KING];

        // проверим "обычные" перемещения
        $candidate_moves = $this->getCandidateMoves();
        foreach($candidate_moves as $to_index) {
            $to_position = $this->game_state->position; // копируем позицию
            $beat_figure = $to_position[$to_index];
            $to_position[$this->position_index] = FG_NONE; // убираем фигуру из текущей позиции
            $to_position[$to_index] = $this->figure;
            $this->board_position->setPosition($to_position);
            if ($this->board_position->isFieldUnderAttack($to_index, $this->enemy_color)) {
                // ход недопустим, т.к. после него наш король оказывается под атакой
                continue;
            }
            $beat_figure_weight = self::FIGURE_WEIGHTS[ Functions::figureType($beat_figure) ];
            $moves[] = array($this->position_index, $to_index, $king_figure_weight, $beat_figure_weight);
        }

        // теперь проверим возможность рокировок
        $none_figure_weight = self::FIGURE_WEIGHTS[FG_NONE];
        $castling_weight = self::FIGURE_WEIGHTS[FG_KING] + self::FIGURE_WEIGHTS[FG_ROOK];
        $this->board_position->setPosition($this->game_state->position);
        if ($this->color == COLOR_WHITE) {
            if ($this->game_state->enable_castling_white_king && $this->checkCastlingConditions(self::FIELD_INIT_WHITE, self::FIELDS_CASTLING_WHITE_KING)) {
                $moves[] = array($this->position_index, self::FIELDS_CASTLING_WHITE_KING[1], $castling_weight, $none_figure_weight);
            }
            if ($this->game_state->enable_castling_white_queen && $this->checkCastlingConditions(self::FIELD_INIT_WHITE, self::FIELDS_CASTLING_WHITE_QUEEN)) {
                $moves[] = array($this->position_index, self::FIELDS_CASTLING_WHITE_QUEEN[1], $castling_weight, $none_figure_weight);
            }
        } else {
            if ($this->game_state->enable_castling_black_king && $this->checkCastlingConditions(self::FIELD_INIT_BLACK, self::FIELDS_CASTLING_BLACK_KING)) {
                $moves[] = array($this->position_index, self::FIELDS_CASTLING_BLACK_KING[1], $castling_weight, $none_figure_weight);
            }
            if ($this->game_state->enable_castling_black_queen && $this->checkCastlingConditions(self::FIELD_INIT_BLACK, self::FIELDS_CASTLING_BLACK_QUEEN)) {
                $moves[] = array($this->position_index, self::FIELDS_CASTLING_BLACK_QUEEN[1], $castling_weight, $none_figure_weight);
            }
        }
        return $moves;
    }

    // Проверка условий возможности рокировки c поля $init_position, поля до ладьи - $fields_to_rook, порядок полей важен!
    protected function checkCastlingConditions(int $init_position, array $fields_to_rook) {
        // поля должны быть свободны
        foreach ($fields_to_rook as $cell) {
            if ($this->game_state->position[$cell] !== FG_NONE) {
                return false;
            }
        }
        // поле, на котором стоит король, и поля, которые он пройдёт, не должны быть под атакой
        if (
            $this->board_position->isFieldUnderAttack($init_position, $this->enemy_color) ||
            $this->board_position->isFieldUnderAttack($fields_to_rook[0], $this->enemy_color) ||
            $this->board_position->isFieldUnderAttack($fields_to_rook[1], $this->enemy_color)
        ) {
            return false;
        }
        return true;
    }

    public function makeMove($to_cell_index, $validate_move=true) {
        $col = $this->col;
        if (!parent::makeMove($to_cell_index, $validate_move)) {
            return false;
        }

        // король делает ход, пометим что теперь рокировки невозможны
        if ($this->color == COLOR_WHITE) {
            $this->game_state->enable_castling_white_king = false;
            $this->game_state->enable_castling_white_queen = false;
        } else {
            $this->game_state->enable_castling_black_king = false;
            $this->game_state->enable_castling_black_queen = false;
        }
        
        $to_col = Functions::positionToCol($to_cell_index);
        if (abs($col - $to_col) == 2) {
            // вертикаль изменилась на 2, значит это рокировка, надо передвинуть ладью
            if ($to_col == self::TO_COLUMN_CASTLING_KING) {
                // короткая рокировка (на королевский фланг)
                $rook_from_position = Functions::colRowToPositionIndex(BOARD_SIZE-1, $this->row);
                $rook_to_position = $to_cell_index - 1;
            } else {
                // длинная рокировка (на ферзевый фланг)
                $rook_from_position = Functions::colRowToPositionIndex(0, $this->row);
                $rook_to_position = $to_cell_index + 1;
            }
            $this->game_state->position[$rook_from_position] = FG_NONE;
            $this->game_state->position[$rook_to_position] = FG_ROOK + $this->color;
        }
        return true;
    }
}
