<?php

class BoardPosition {
    private $position;

    public function setPosition(array $position) {
        $this->position = $position;
    }

    // Метод возвращает булево значение - атаковано-ли поле с индексом $cell_index фигурами цвета $color
    public function isFieldUnderAttack($cell_index, $color) {
        $col = Functions::positionToCol($cell_index);
        $row = Functions::positionToRow($cell_index);
        return $this->underKnightAttack($col, $row, $color) || $this->underPawnAttack($col, $row, $color) || $this->underLinearAttack($col, $row, $color);
    }

    // Метод возвращает булево значение - атакован ли король цвета $color
    public function isKingUnderAttack($color) {
        $cell_index = array_search(FG_KING + $color, $this->position);
        if ($cell_index === false) {
            return false;
        }
        $enemy_color = $color == COLOR_WHITE ? COLOR_BLACK : COLOR_WHITE;
        return $this->isFieldUnderAttack($cell_index, $enemy_color);
    }

    private function underKnightAttack($figure_col, $figure_row, $color) {
        foreach(Knight::SHIFTS as $shift) {
            $col = $figure_col + $shift[0];
            if ($col < 0 || $col >= BOARD_SIZE) {
                continue;
            }
            $row = $figure_row + $shift[1];
            if ($row < 0 || $row >= BOARD_SIZE) {
                continue;
            }
            $to_index = Functions::colRowToPositionIndex($col, $row);
            $to_figure = $this->position[$to_index];
            if (FG_KNIGHT + $color == $to_figure) {
                return true;
            }
        }
        return false;
    }

    private function underPawnAttack($figure_col, $figure_row, $color) {
        $direction = ($color == COLOR_WHITE ? 1 : -1);
        $row = $figure_row+ $direction;
        if ($row < 0 || $row >= BOARD_SIZE) {
            return false;
        }
        $col = $figure_col - 1;
        if ($col >= 0) {
            $position_index = Functions::colRowToPositionIndex($col, $row);
            if ($this->position[$position_index] == FG_PAWN + $color) {
                return true;
            }
        }
        $col = $figure_col + 1;
        if ($col < BOARD_SIZE) {
            $position_index = Functions::colRowToPositionIndex($col, $row);
            if ($this->position[$position_index] == FG_PAWN + $color) {
                return true;
            }
        }
        return false;
    }

    private function underLinearAttack($figure_col, $figure_row, $color) {
        return (
            $this->underAttackByShifts($figure_col, $figure_row, $color, Bishop::SHIFTS, array(FG_BISHOP, FG_QUEEN)) ||
            $this->underAttackByShifts($figure_col, $figure_row, $color, Rook::SHIFTS, array(FG_ROOK, FG_QUEEN))
        );
    }

    private function underAttackByShifts($figure_col, $figure_row, $color, $shifts, $dangerous_figures) {
        foreach($shifts as $shift) {
            list($shift_col, $shift_row) = $shift;
            $continue_shift = true;
            $col = $figure_col;
            $row = $figure_row;
            $distance = 0; // расстояние, на сколько мы отошли от рассматриваемой клетки - нужно для атак короля
            while ($continue_shift) {
                $col = $col + $shift_col;
                if ($col < 0 || $col >= BOARD_SIZE) {
                    $continue_shift = false;
                    continue;
                }
                $row = $row + $shift_row;
                if ($row < 0 || $row >= BOARD_SIZE) {
                    $continue_shift = false;
                    continue;
                }
                $distance++;
                $to_index = Functions::colRowToPositionIndex($col, $row);
                $figure = $this->position[$to_index];
                if ($figure == FG_NONE) {
                    // поле пустое, можно дальше двигаться вдоль текущего направления
                    continue;
                }
                // наткнулись на какую-то фигуру, дальше смещаться по текущему направлению нельзя
                $continue_shift = false;
                $to_figure_color = Functions::color($figure);
                if ($to_figure_color != $color) {
                    // наткнулись на свою фигуру
                    continue;
                }
                // наткнулись на чужую фигуру, надо узнать, представляет ли она опасность
                $figure_type = Functions::figureType($figure);
                if (in_array($figure_type, $dangerous_figures) || ($distance == 1 && $figure_type == FG_KING)) {
                    return true;
                }
            }
        }
        // ни на что "опасное" не наткнулись, возвращаем false, т.е. поле не под атакой
        return false;
    }
}
