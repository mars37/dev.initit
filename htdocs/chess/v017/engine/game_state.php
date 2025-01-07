<?php

class GameState {
    const PROPERTY_NAMES = array(
        'position', 'current_player_color', 'enable_castling_white_king', 'enable_castling_black_king', 'enable_castling_white_queen', 'enable_castling_black_queen',
        'crossed_field', 'non_action_semimove_counter', 'move_number', 'human_color', 'prev_move_from', 'prev_move_to', 'text_state'
    );
    const FIELDS_FOR_RULE_REPEAT = array(
        'position', 'current_player_color', 'enable_castling_white_king', 'enable_castling_black_king', 'enable_castling_white_queen', 'enable_castling_black_queen',
        'crossed_field'
    );

    const FIGURE_VALUES = array(
        FG_KING => 1000000,
        FG_QUEEN => 9000,
        FG_ROOK => 5000,
        FG_BISHOP => 3000,
        FG_KNIGHT => 2800,
        FG_PAWN => 1000
    );

    public $position = null; // массив из 256 элементов (64 "полезных") - положение фигур на доске
    public $current_player_color = null; // текущий игрок, чья очередь хода - белые или чёрные
    public $enable_castling_white_king = null; // возможность рокировки белого короля на королевский фланг (короткая рокировка)
    public $enable_castling_white_queen = null; // возможность рокировки белого короля на ферзевый фланг (длинная рокировка)
    public $enable_castling_black_king = null; // возможность рокировки чёрного короля на королевский фланг (короткая рокировка)
    public $enable_castling_black_queen = null; // возможность рокировки чёрного короля на ферзевый фланг (длинная рокировка)
    public $crossed_field = null; // проходимое поле (поле которое пешка перескочила на предыдущем ходу) - для взятия "на проходе"
    public $non_action_semimove_counter = null; // число полуходов, после последнего взятия фигуры или движения пешки (для правил 50 и 75 ходов)
    public $move_number = null; // номер хода

    public $human_color = null; // цвет фигур, которыми играет человек
    public $prev_move_from = null; // индекс поля откуда был предыдущий ход
    public $prev_move_to = null; // индекс поля куда пошли в предыдущий ход
    public $text_state = null; // текстовое описание состояния

    private $position_hashes = array();
    private $state_history = array();

    public $figures = null; // ассоциативный массив "фигура => массив с индексами полей, на которых эта фигура находится"

    private function getHash() {
        $result = array();
        foreach (self::PROPERTY_NAMES as $key) {
            $result[$key] = $this->$key;
        }
        return $result;
    }

    private function getStateForRuleRepeat($state_hash) {
        $result = array();
        foreach (self::FIELDS_FOR_RULE_REPEAT as $key) {
            $result[$key] = $state_hash[$key];
        }
        return $result;
    }

    public function serializeState() {
        $state = $this->getHash();
        $state['position'] = Functions::convertPosition16To8($state['position']);
        $fields_to_convert = array('crossed_field', 'prev_move_from', 'prev_move_to');
        foreach($fields_to_convert as $field) {
            if (!is_null($state[$field])) {
                $state[$field] = Functions::pos16ToPos8($state[$field]);
            }
        }

        $state_history = $this->state_history;
        foreach($state_history as $i => $history) {
            $state_history[$i]['position'] = Functions::convertPosition16To8($history['position']);
            foreach($fields_to_convert as $field) {
                if (!is_null($state_history[$i][$field])) {
                    $state_history[$i][$field] = Functions::pos16ToPos8($state_history[$i][$field]);
                }
            }
        }
        $data = array(
            'state' => $state,
            'history' => $state_history
        );
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function unserializeState(string $serialized_state) {
        $fields_to_convert = array('crossed_field', 'prev_move_from', 'prev_move_to');
        try {
            $data = json_decode($serialized_state, true);
            $this->state_history = $data['history'];
            foreach($this->state_history as $i => $history) {
                $this->state_history[$i]['position'] = Functions::convertPosition8To16($history['position']);
                foreach($fields_to_convert as $field) {
                    if (!is_null($this->state_history[$i][$field])) {
                        $this->state_history[$i][$field] = Functions::pos8ToPos16($this->state_history[$i][$field]);
                    }
                }
            }
            foreach (self::PROPERTY_NAMES as $key) {
                $this->$key = $data['state'][$key];
            }
            $this->position = Functions::convertPosition8To16($this->position);
            foreach($fields_to_convert as $field) {
                if (!is_null($this->$field)) {
                    $this->$field = Functions::pos8ToPos16($this->$field);
                }
            }
            $this->recalculatePositionHashes();
            return true;
        } catch(Exception $e) {
            return false;
        }
    }

    public function setFigures() {
        $this->figures = array(
            FG_KING + COLOR_WHITE => array(),
            FG_KING + COLOR_BLACK => array(),
            FG_QUEEN + COLOR_WHITE => array(),
            FG_QUEEN + COLOR_BLACK => array(),
            FG_ROOK + COLOR_WHITE => array(),
            FG_ROOK + COLOR_BLACK => array(),
            FG_BISHOP + COLOR_WHITE => array(),
            FG_BISHOP + COLOR_BLACK => array(),
            FG_KNIGHT + COLOR_WHITE => array(),
            FG_KNIGHT + COLOR_BLACK => array(),
            FG_PAWN + COLOR_WHITE => array(),
            FG_PAWN + COLOR_BLACK => array()
        );
        foreach(Functions::$pos8To16convert as $i) {
            $figure_code = $this->position[$i];
            if ($figure_code !== FG_NONE) {
                $this->figures[$figure_code][] = $i;
            }
        }
    }

    public function isHumanMove() {
        return $this->current_player_color == $this->human_color;
    }

    private function getPositionHash(array $state) {
        $state_for_rule_repeat = $this->getStateForRuleRepeat($state);
        return md5(json_encode($state_for_rule_repeat, JSON_UNESCAPED_UNICODE));
    }

    private function getCurrentPositionHash() {
        return $this->getPositionHash($this->getHash());
    }

    public function positionRepeatCount() {
        $key = $this->getCurrentPositionHash();
        if (!isset($this->position_hashes[$key])) {
            return 0;
        }
        return $this->position_hashes[$key];
    }

    public function savePositionHash() {
        // сохраняем счётчик хешей позиций
        $key = $this->getCurrentPositionHash();
        if (!isset($this->position_hashes[$key])) {
            $this->position_hashes[$key] = 1;
        } else {
            $this->position_hashes[$key] += 1;
        }
    }

    public function savePositionHistory() {
        $this->state_history[] = $this->getHash();
    }

    public function moveBack() {
        array_pop($this->state_history);
        $hash = end($this->state_history);
        foreach (self::PROPERTY_NAMES as $key) {
            $this->$key = $hash[$key];
        }
        $this->recalculatePositionHashes();
    }

    private function recalculatePositionHashes() {
        $this->position_hashes = array();
        foreach ($this->state_history as $state) {
            $hash = $this->getPositionHash($state);
            if (!isset($this->position_hashes[$hash])) {
                $this->position_hashes[$hash] = 1;
            } else {
                $this->position_hashes[$hash] += 1;
            }
        }
    }

    public function makeMove($cell_index_from, $cell_index_to, $validate_move=true, $to_figure=FG_QUEEN) {
        $figure_factory = new FigureFactory();
        $figure = $figure_factory->create($this, $cell_index_from);
        if (!$figure->makeMove($cell_index_to, $validate_move, $to_figure)) {
            return false;
        }
        $this->savePositionHash();
        return true;
    }

    public function isKingUnderAttack() {
        $board_position = new BoardPosition();
        $board_position->setPosition($this->position);
        return $board_position->isKingUnderAttack($this->current_player_color);
    }

    // Проверка ничьи по правилам 50-и ходов и троекратного повторения позиции
    public function checkDraw() {
        if ($this->non_action_semimove_counter >= 100) {
            $this->text_state = 'Ничья. Компьютер потребовал ничью по правилу 50 ходов';
            return true;
        }
        if ($this->positionRepeatCount() >= 3) {
            $this->text_state = 'Ничья. Компьютер потребовал ничью по правилу троекратного повторения позиции';
            return true;
        }
        return false;
    }

    public function getKingPosition($color) {
        $king_positions = $this->figures[FG_KING + $color];
        if (count($king_positions) === 0) {
            throw new Exception('Not found king');
        }
        return $king_positions[0];
    }

    public function calcStaticScore($no_available_moves) {
        if ($no_available_moves) {
            if ($this->isKingUnderAttack()) {
                return PHP_INT_MIN;
            } else {
                return 0;
            }
        }

        $score = 0;
        foreach(Functions::$pos8To16convert as $i) {
            $figure_code = $this->position[$i];
            if ($figure_code !== FG_NONE) {
                $figure = Functions::figureType($figure_code);
                $color = Functions::color($figure_code);
                if ($color == $this->current_player_color) {
                    $score += self::FIGURE_VALUES[$figure];
                } else {
                    $score -= self::FIGURE_VALUES[$figure];
                }
            }
        }
        return $score;
    }

    public function calcStaticScoreEx($no_available_moves, $deep=0) {
        if ($no_available_moves) {
            if ($this->isKingUnderAttack()) {
                return PHP_INT_MIN + $deep;
            } else {
                return 0;
            }
        }
        // Будем строить оценку позиции для белых. Если нужна оценка для чёрных (т.е. если сейчас их ход), то возвратим противоположное число.

        // заполняем массив фигур. Формат: array[figure_code][] - массив номеров полей
        $this->setFigures();

        // считаем материальную ценность фигур
        $figure_types = array(FG_QUEEN, FG_ROOK, FG_BISHOP, FG_KNIGHT, FG_PAWN);
        $score = 0;
        foreach ($figure_types as $figure_type) {
            $score += self::FIGURE_VALUES[$figure_type] * (count($this->figures[$figure_type + COLOR_WHITE]) - count($this->figures[$figure_type + COLOR_BLACK]));
        }

        // ценность продвижения пешек
        $pawn_position_weigths = array(
            0, 0, 0, 0,    0, 0, 0, 0, 0, 0, 0, 0,    0, 0, 0, 0,
            0, 0, 0, 0,    0, 0, 0, 0, 0, 0, 0, 0,    0, 0, 0, 0,
            0, 0, 0, 0,    0, 0, 0, 0, 0, 0, 0, 0,    0, 0, 0, 0,
            0, 0, 0, 0,    0, 0, 0, 0, 0, 0, 0, 0,    0, 0, 0, 0,

            0, 0, 0, 0,    0, 0, 0, 0, 0, 0, 0, 0,    0, 0, 0, 0,
            0, 0, 0, 0,   65,70,70,70,70,70,70,65,    0, 0, 0, 0,
            0, 0, 0, 0,   50,55,60,65,65,60,55,50,    0, 0, 0, 0,
            0, 0, 0, 0,   36,40,48,50,50,48,40,36,    0, 0, 0, 0,
            0, 0, 0, 0,   18,20,24,36,36,24,20,18,    0, 0, 0, 0,
            0, 0, 0, 0,   10,10,10,10,10,10,10,10,    0, 0, 0, 0,
            0, 0, 0, 0,    0, 0, 0, 0, 0, 0, 0, 0,    0, 0, 0, 0,
            0, 0, 0, 0,    0, 0, 0, 0, 0, 0, 0, 0,    0, 0, 0, 0,

            0, 0, 0, 0,    0, 0, 0, 0, 0, 0, 0, 0,    0, 0, 0, 0,
            0, 0, 0, 0,    0, 0, 0, 0, 0, 0, 0, 0,    0, 0, 0, 0,
            0, 0, 0, 0,    0, 0, 0, 0, 0, 0, 0, 0,    0, 0, 0, 0,
            0, 0, 0, 0,    0, 0, 0, 0, 0, 0, 0, 0,    0, 0, 0, 0
        );
        // считаем ценность продвижения для белых пешек
        foreach ($this->figures[FG_PAWN + COLOR_WHITE] as $field_idx) {
            $score += $pawn_position_weigths[$field_idx];
        }
        // считаем ценность продвижения для чёрных пешек
        $pawn_position_weigths = array_reverse($pawn_position_weigths);
        foreach ($this->figures[FG_PAWN + COLOR_BLACK] as $field_idx) {
            $score -= $pawn_position_weigths[$field_idx];
        }

        // Наши фигуры (белые) хотим расположить ближе к королю противника
        $square_distance = 0;
        $figure_types_without_pawn = array(FG_QUEEN, FG_ROOK, FG_BISHOP, FG_KNIGHT);
        $black_king_position = $this->getKingPosition(COLOR_BLACK);
        $black_king_row_norm = $black_king_position >> 4;
        $black_king_col_norm = $black_king_position & 0b1111;
        foreach ($figure_types_without_pawn as $figure_type) {
            foreach ($this->figures[$figure_type + COLOR_WHITE] as $position) {
                $row_norm = $position >> 4;
                $col_norm = $position & 0b1111;
                $square_distance -= pow($black_king_row_norm - $row_norm, 2) + pow($black_king_col_norm - $col_norm, 2); // минус - т.к. чем больше дистанция, тем нам (белым) хуже
            }
        }

        // А нашего (белого) короля хотим расположить подальше от фигур противника
        $white_king_position = $this->getKingPosition(COLOR_WHITE);
        $white_king_row_norm = $white_king_position >> 4;
        $white_king_col_norm = $white_king_position & 0b1111;
        foreach ($figure_types_without_pawn as $figure_type) {
            foreach ($this->figures[$figure_type + COLOR_BLACK] as $position) {
                $row_norm = $position >> 4;
                $col_norm = $position & 0b1111;
                $square_distance += pow($white_king_row_norm - $row_norm, 2) + pow($white_king_col_norm - $col_norm, 2); // чем больше дистанция тем нам (белым) лучше
            }
        }
        $score += $this->move_number * 0.2 * $square_distance;

        // учитывем "фактор атак"
        $score += $this->getAttackFactor() / 100;

        if ($this->current_player_color == COLOR_BLACK) {
            $score = -$score;
        }
        return $score;
    }

    public function getAttackFactor() {
        // Вычисляем "фактор атаки" для белых: сумму "взвешенных количеств атак" на чёрные фигуры минус сумма взвешенных количеств атак на белые фигуры.
        // Вес определяется достоинствами атакуемой и атакующей фигур
        $board_position = new BoardPosition();
        $board_position->setPosition($this->position);
        return $board_position->getAttackFactor();
    }
}
