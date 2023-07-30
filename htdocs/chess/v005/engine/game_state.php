<?php

class GameState {
    const PROPERTY_NAMES = array(
        'position', 'current_player_color', 'enable_castling_white_king', 'enable_castling_black_king', 'enable_castling_white_queen', 'enable_castling_black_queen',
        'crossed_field', 'non_action_semimove_counter', 'move_number', 'human_color', 'prev_move_from', 'prev_move_to', 'text_state'
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

    private function getHash() {
        $result = array();
        foreach (self::PROPERTY_NAMES as $key) {
            $result[$key] = $this->$key;
        }
        return $result;
    }

    public function serializeState() {
        return json_encode($this->getHash(), JSON_UNESCAPED_UNICODE);
    }

    public function unserializeState(string $serialized_state) {
        try {
            $data = json_decode($serialized_state, true);
            foreach (self::PROPERTY_NAMES as $key) {
                $this->$key = $data[$key];
            }
            return true;
        } catch(Exception $e) {
            return false;
        }
    }
}
