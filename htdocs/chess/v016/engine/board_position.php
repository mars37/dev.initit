<?php

class BoardPosition {
    // чем более ценную фигуру атакуем, тем лучше
    const FIGURE_ATTACK_FACTOR = array(
        FG_KING => 80,
        FG_QUEEN => 60,
        FG_ROOK => 35,
        FG_BISHOP => 22,
        FG_KNIGHT => 20,
        FG_PAWN => 10
    );

    // чем менее ценной фигурой атакуем, тем лучше
    const FIGURE_FROM_ATTACK_FACTOR = array(
        FG_KING => 20,
        FG_QUEEN => 40,
        FG_ROOK => 65,
        FG_BISHOP => 78,
        FG_KNIGHT => 80,
        FG_PAWN => 90
    );

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

    /**
     * "Фактор атаки" - сумма взвешенных количеств атак на фигры с учётом цвета. Вычисляется для белых.
     * Учитываются "рентгеновские" атаки, когда атакующие фигуры стоят друг за другом
     */
    public function getAttackFactor() {
        $attacks_factor = 0;
        for($i = 0; $i < BOARD_SIZE*BOARD_SIZE; $i++) {
            $figure_code = $this->position[$i];
            if ($figure_code == FG_NONE) {
                continue;
            }
            // нашли поле с фигурой
            $figure_type = Functions::figureType($figure_code);
            $figure_color = Functions::color($figure_code);
            if ($figure_color == COLOR_WHITE) {
                $opponent_color = COLOR_BLACK;
                $multiplier = -1;
            } else {
                $opponent_color = COLOR_WHITE;
                $multiplier = 1;
            }
            $attacks_factor += $multiplier * self::FIGURE_ATTACK_FACTOR[$figure_type] * $this->getWeighedAttackCountToField($i, $opponent_color);
        }
        return $attacks_factor;
    }

    /**
     * Метод возвращает взвешенное количество атак на поле с индексом $field_idx со стороны фигур цвета $attack_color
     */
    private function getWeighedAttackCountToField($field_idx, $attack_color) {
        $figure_col = Functions::positionToCol($field_idx);
        $figure_row = Functions::positionToRow($field_idx);
        return $this->weighedCountKnightAttack($figure_col, $figure_row, $attack_color) + $this->weighedCountLinearAttack($figure_col, $figure_row, $attack_color);
    }

    private function weighedCountKnightAttack($figure_col, $figure_row, $attack_color) {
        $attack_count = 0;
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
            if (FG_KNIGHT + $attack_color == $to_figure) {
                $attack_count++;
            }
        }
        return $attack_count * (self::FIGURE_FROM_ATTACK_FACTOR[FG_KNIGHT]);
    }

    private function weighedCountLinearAttack($figure_col, $figure_row, $attack_color) {
        $attack_count = $this->weighedCountAttackByShifts($figure_col, $figure_row, $attack_color, Rook::SHIFTS, array(FG_ROOK, FG_QUEEN));
        $attack_count += $this->weighedCountAttackByShifts($figure_col, $figure_row, $attack_color, Bishop::SHIFTS, array(FG_BISHOP, FG_QUEEN));
        $attack_count += $this->weighedCountAttackByPawn($figure_col, $figure_row, $attack_color);
        return $attack_count;
    }

    private function weighedCountAttackByShifts($figure_col, $figure_row, $attack_color, $shifts, $dangerous_figures) {
        $weighted_count = 0;
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
                // наткнулись на какую-то фигуру
                $to_figure_color = Functions::color($figure);
                if ($to_figure_color != $attack_color) {
                    // наткнулись на свою фигуру, дальше направление можно не рассматривать
                    $continue_shift = false;
                    continue;
                }
                // наткнулись на чужую фигуру, надо узнать, представляет ли она опасность
                $figure_type = Functions::figureType($figure);
                if (in_array($figure_type, $dangerous_figures)) {
                    $weighted_count += self::FIGURE_FROM_ATTACK_FACTOR[$figure_type];
                    // не останавливаемся, продолжим двигаться по направлению - учитываем "рентгеновкое" нападение
                } elseif ($distance == 1 && $figure_type == FG_KING) {
                    $weighted_count += self::FIGURE_FROM_ATTACK_FACTOR[FG_KING];
                    $continue_shift = false; // после короля двигаться по направлению не будем
                }
            }
        }
        return $weighted_count;
    }

    private function weighedCountAttackByPawn($figure_col, $figure_row, $attack_color) {
        $weighted_count = 0;
        $direction = ($attack_color == COLOR_WHITE ? 1 : -1);
        $row = $figure_row+ $direction;
        if ($row < 0 || $row >= BOARD_SIZE) {
            return $weighted_count;
        }
        // сначала рассмотрим луч влево от пешки
        $col = $figure_col - 1;
        if ($col >= 0) {
            $position_index = Functions::colRowToPositionIndex($col, $row);
            if ($this->position[$position_index] == FG_PAWN + $attack_color) {
                $weighted_count += self::FIGURE_FROM_ATTACK_FACTOR[FG_PAWN];
                // продолжим двигаться по лучу влево, возможно там "рентгеновская" атака от слона или ферзя
                $continue_shift = true;
                while ($continue_shift) {
                    $row += $direction;
                    if ($row < 0 || $row >= BOARD_SIZE) {
                        break;
                    }
                    $col -= 1;
                    if ($col < 0) {
                        break;
                    }
                    $to_index = Functions::colRowToPositionIndex($col, $row);
                    $figure = $this->position[$to_index];
                    if ($figure == FG_NONE) {
                        continue;
                    }
                    $to_figure_color = Functions::color($figure);
                    if ($to_figure_color != $attack_color) {
                        break;
                    }
                    $figure_type = Functions::figureType($figure);
                    if ($figure_type == FG_BISHOP || $figure_type == FG_QUEEN) {
                        $weighted_count += self::FIGURE_FROM_ATTACK_FACTOR[$figure_type];
                    }
                }
            }
        }

        // тепeрь рассмотрим луч вправо от пешки
        $row = $figure_row+ $direction;
        $col = $figure_col + 1;
        if ($col < BOARD_SIZE) {
            $position_index = Functions::colRowToPositionIndex($col, $row);
            if ($this->position[$position_index] == FG_PAWN + $attack_color) {
                $weighted_count += self::FIGURE_FROM_ATTACK_FACTOR[FG_PAWN];
                // продолжим двигаться по лучу вправо, возможно там "рентгеновская" атака от слона или ферзя
                $continue_shift = true;
                while ($continue_shift) {
                    $row += $direction;
                    if ($row < 0 || $row >= BOARD_SIZE) {
                        break;
                    }
                    $col += 1;
                    if ($col >= BOARD_SIZE) {
                        break;
                    }
                    $to_index = Functions::colRowToPositionIndex($col, $row);
                    $figure = $this->position[$to_index];
                    if ($figure == FG_NONE) {
                        continue;
                    }
                    $to_figure_color = Functions::color($figure);
                    if ($to_figure_color != $attack_color) {
                        break;
                    }
                    $figure_type = Functions::figureType($figure);
                    if ($figure_type == FG_BISHOP || $figure_type == FG_QUEEN) {
                        $weighted_count += self::FIGURE_FROM_ATTACK_FACTOR[$figure_type];
                    }
                }
            }
        }

        return $weighted_count;
    }
}
