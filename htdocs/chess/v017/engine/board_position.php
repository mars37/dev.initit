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
        return $this->underKnightAttack($cell_index, $color) || $this->underPawnAttack($cell_index, $color) || $this->underLinearAttack($cell_index, $color);
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

    private function underKnightAttack($cell_index, $color) {
        $danger_figure = FG_KNIGHT + $color;
        foreach(Knight::SHIFTS as $shift) {
            $to_index = $cell_index + $shift;
            $to_figure = $this->position[$to_index];
            if ($danger_figure == $to_figure) {
                return true;
            }
        }
        return false;
    }

    private function underPawnAttack($cell_index, $color) {
        $danger_figure = FG_PAWN + $color;
        $direction = ($color == COLOR_WHITE ? 1 : -1);
        $position_index = $cell_index + $direction * 16 - 1;
        if ($this->position[$position_index] == $danger_figure) {
            return true;
        }
        $position_index = $cell_index + $direction * 16 + 1;
        if ($this->position[$position_index] == $danger_figure) {
            return true;
        }
        return false;
    }

    private function underLinearAttack($cell_index, $color) {
        return (
            $this->underAttackByShifts($cell_index, $color, Bishop::SHIFTS, array(FG_BISHOP, FG_QUEEN)) ||
            $this->underAttackByShifts($cell_index, $color, Rook::SHIFTS, array(FG_ROOK, FG_QUEEN))
        );
    }

    private function underAttackByShifts($cell_index, $color, $shifts, $dangerous_figures) {
        foreach($shifts as $shift) {
            $continue_shift = true;
            $to_index = $cell_index;
            $distance = 0; // расстояние, на сколько мы отошли от рассматриваемой клетки - нужно для атак короля
            while ($continue_shift) {
                $distance++;
                $to_index += $shift;
                $figure = $this->position[$to_index];
                if ($figure == FG_NONE) {
                    // поле пустое, можно дальше двигаться вдоль текущего направления
                    continue;
                }
                // наткнулись на поле, не являющееся пустым, дальше смещаться по текущему направлению нельзя
                $continue_shift = false;
                if (($figure & 0b11) == $color) {
                    // наткнулись на чужую фигуру, надо узнать, представляет ли она опасность
                    $figure_type = $figure & 0b11100;
                    if (in_array($figure_type, $dangerous_figures) || ($distance == 1 && $figure_type == FG_KING)) {
                        return true;
                    }
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
        foreach(Functions::$pos8To16convert as $i) {
            $figure_code = $this->position[$i];
            if ($figure_code == FG_NONE) {
                continue;
            }
            // нашли поле с фигурой
            $figure_type = $figure_code & 0b11100;
            if (($figure_code & 0b11) == COLOR_WHITE) {
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
        return $this->weighedCountKnightAttack($field_idx, $attack_color) + $this->weighedCountLinearAttack($field_idx, $attack_color);
    }

    private function weighedCountKnightAttack($field_idx, $attack_color) {
        $attack_count = 0;
        foreach(Knight::SHIFTS as $shift) {
            $to_index = $field_idx + $shift;
            $to_figure = $this->position[$to_index];
            if (FG_KNIGHT + $attack_color == $to_figure) {
                $attack_count++;
            }
        }
        return $attack_count * (self::FIGURE_FROM_ATTACK_FACTOR[FG_KNIGHT]);
    }

    private function weighedCountLinearAttack($field_idx, $attack_color) {
        $attack_count = $this->weighedCountAttackByShifts($field_idx, $attack_color, Rook::SHIFTS, array(FG_ROOK, FG_QUEEN));
        $attack_count += $this->weighedCountAttackByShifts($field_idx, $attack_color, Bishop::SHIFTS, array(FG_BISHOP, FG_QUEEN));
        $attack_count += $this->weighedCountAttackByPawn($field_idx, $attack_color);
        return $attack_count;
    }

    private function weighedCountAttackByShifts($field_idx, $attack_color, $shifts, $dangerous_figures) {
        $weighted_count = 0;
        foreach($shifts as $shift) {
            $continue_shift = true;
            $to_index = $field_idx;
            $distance = 0; // расстояние, на сколько мы отошли от рассматриваемой клетки - нужно для атак короля
            while ($continue_shift) {
                $distance++;
                $to_index += $shift;
                $figure = $this->position[$to_index];
                if ($figure == FG_NONE) {
                    // поле пустое, можно дальше двигаться вдоль текущего направления
                    continue;
                }
                // наткнулись на какую-то фигуру
                $to_figure_color = $figure & 0b11;
                if ($to_figure_color != $attack_color) {
                    // наткнулись или на свою фигуру, или вышли за поле, дальше направление можно не рассматривать
                    $continue_shift = false;
                    continue;
                }
                // наткнулись на чужую фигуру, надо узнать, представляет ли она опасность
                $figure_type = $figure & 0b11100;
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

    private function weighedCountAttackByPawn($field_idx, $attack_color) {
        $weighted_count = 0;
        $direction = ($attack_color == COLOR_WHITE ? 1 : -1);
        // сначала рассмотрим луч влево от пешки
        $to_index = $field_idx + $direction * 16 - 1;
        if ($this->position[$to_index] == FG_PAWN + $attack_color) {
            $weighted_count += self::FIGURE_FROM_ATTACK_FACTOR[FG_PAWN];
            // продолжим двигаться по лучу влево, возможно там "рентгеновская" атака от слона или ферзя
            while (true) {
                $to_index += $direction * 16 - 1;
                $figure = $this->position[$to_index];
                if ($figure == FG_NONE) {
                    continue;
                }
                if (($figure & 0b11) != $attack_color) {
                    break;
                }
                $figure_type = $figure & 0b11100;
                if ($figure_type == FG_BISHOP || $figure_type == FG_QUEEN) {
                    $weighted_count += self::FIGURE_FROM_ATTACK_FACTOR[$figure_type];
                }
            }
        }

        // тепeрь рассмотрим луч вправо от пешки
        $to_index = $field_idx + $direction * 16 + 1;
        if ($this->position[$to_index] == FG_PAWN + $attack_color) {
            $weighted_count += self::FIGURE_FROM_ATTACK_FACTOR[FG_PAWN];
            // продолжим двигаться по лучу влево, возможно там "рентгеновская" атака от слона или ферзя
            while (true) {
                $to_index += $direction * 16 + 1;
                $figure = $this->position[$to_index];
                if ($figure == FG_NONE) {
                    continue;
                }
                if (($figure & 0b11) != $attack_color) {
                    break;
                }
                $figure_type = $figure & 0b11100;
                if ($figure_type == FG_BISHOP || $figure_type == FG_QUEEN) {
                    $weighted_count += self::FIGURE_FROM_ATTACK_FACTOR[$figure_type];
                }
            }
        }
        return $weighted_count;
    }
}
