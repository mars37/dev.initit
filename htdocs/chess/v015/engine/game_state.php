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
        FG_QUEEN => 90,
        FG_ROOK => 50,
        FG_BISHOP => 30,
        FG_KNIGHT => 27,
        FG_PAWN => 10
    );

    public $position = null; // массив из 64 элементов - положение фигур на доске
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
        $data = array(
            'state' => $this->getHash(),
            'history' => $this->state_history
        );
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function unserializeState(string $serialized_state) {
        try {
            $data = json_decode($serialized_state, true);
            $this->state_history = $data['history'];
            foreach (self::PROPERTY_NAMES as $key) {
                $this->$key = $data['state'][$key];
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
        for($i = 0; $i < BOARD_SIZE*BOARD_SIZE; $i++) {
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
        for($i = 0; $i < BOARD_SIZE*BOARD_SIZE; $i++) {
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
}
