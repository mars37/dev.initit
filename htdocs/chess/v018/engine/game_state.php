<?php

use LDAP\Result;

class GameState {
    const PROPERTY_NAMES = array(
        'position', 'current_player_color', 'enable_castling_white_king', 'enable_castling_black_king', 'enable_castling_white_queen', 'enable_castling_black_queen',
        'm_crossed_field', 'non_action_semimove_counter', 'move_number', 'human_color', 'prev_move_from', 'prev_move_to', 'text_state'
    );
    const FIELDS_FOR_RULE_REPEAT = array(
        'position', 'current_player_color', 'enable_castling_white_king', 'enable_castling_black_king', 'enable_castling_white_queen', 'enable_castling_black_queen',
        'm_crossed_field'
    );

    const FIGURE_VALUES = array(
        FG_KING => 1000000,
        FG_QUEEN => 9000,
        FG_ROOK => 5000,
        FG_BISHOP => 3000,
        FG_KNIGHT => 2800,
        FG_PAWN => 1000
    );

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

    // ценность продвижения белых пешек
    const W_PAWN_POS_BEGIN_WEIGHTS = array(
        0, 0, 0,   0,   0, 0, 0, 0,
        0, 0, 0, 280, 280, 0, 0, 0,
        0, 0, 0, 260, 260, 0, 0, 0,
        0, 0, 0, 200, 200, 0, 0, 0,
        0, 0, 0, 110, 160, 0, 0, 0,
        0, 0, 0,  80,  80, 0, 0, 0,
        0, 0, 0,   0,   0, 0, 0, 0,
        0, 0, 0,   0,   0, 0, 0, 0
    );
    const W_PAWN_POS_WEIGHTS = array(
          0,   0,   0,   0,   0,   0,   0,   0,
        260, 280, 280, 280, 280, 280, 280, 260,
        200, 220, 240, 260, 260, 240, 220, 200,
        144, 160, 193, 200, 200, 193, 160, 144,
         40,  40,  96, 160, 160,  36,  40,  40,
         20,  20,  40,  40,  40,  10,  20,  20,
          0,   0,   0,   0,   0,   0,   0,   0,
          0,   0,   0,   0,   0,   0,   0,   0
    );
    // ценность продвижения чёрных пешек
    const B_PAWN_POS_BEGIN_WEIGHTS = array(
        0, 0, 0,   0,   0, 0, 0, 0,
        0, 0, 0,   0,   0, 0, 0, 0,
        0, 0, 0,  80,  80, 0, 0, 0,
        0, 0, 0, 110, 160, 0, 0, 0,
        0, 0, 0, 200, 200, 0, 0, 0,
        0, 0, 0, 260, 260, 0, 0, 0,
        0, 0, 0, 280, 280, 0, 0, 0,
        0, 0, 0,   0,   0, 0, 0, 0
    );
    const B_PAWN_POS_WEIGHTS = array(
          0,   0,   0,   0,   0,   0,   0,   0,
          0,   0,   0,   0,   0,   0,   0,   0,
         20,  20,  40,  40,  40,  10,  20,  20,
         40,  40,  96, 160, 160,  36,  40,  40,
        144, 160, 193, 200, 200, 193, 160, 144,
        200, 220, 240, 260, 260, 240, 220, 200,
        260, 280, 280, 280, 280, 280, 280, 260,
          0,   0,   0,   0,   0,   0,   0,   0,
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

    // bitboard для всех типов фигур для обоих цветов
    public $m_w_pawn = null;
    public $m_b_pawn = null;
    public $m_w_king = null;
    public $m_b_king = null;
    public $m_w_queen = null;
    public $m_b_queen = null;
    public $m_w_knight = null;
    public $m_b_knight = null;
    public $m_w_bishop = null;
    public $m_b_bishop = null;
    public $m_w_rook = null;
    public $m_b_rook = null;

    public $m_all_white_figures = null;
    public $m_all_black_figures = null;
    public $all_figures_mask = null;
    public $m_crossed_field = 0;

    public function setPosition($position) {
        $this->m_w_pawn = 0;
        $this->m_b_pawn = 0;
        $this->m_w_king = 0;
        $this->m_b_king = 0;
        $this->m_w_queen = 0;
        $this->m_b_queen = 0;
        $this->m_w_knight = 0;
        $this->m_b_knight = 0;
        $this->m_w_bishop = 0;
        $this->m_b_bishop = 0;
        $this->m_w_rook = 0;
        $this->m_b_rook = 0;
        $this->position = $position;
        $position_bit = 0b1;
        for($i = 63; $i >= 0; $i--) {
            switch ($position[$i]) {
                case FG_NONE:
                    break;
                case FG_PAWN + COLOR_WHITE:
                    $this->m_w_pawn |= $position_bit; break;
                case FG_PAWN + COLOR_BLACK:
                    $this->m_b_pawn |= $position_bit; break;
                case FG_KING + COLOR_WHITE:
                    $this->m_w_king |= $position_bit; break;
                case FG_KING + COLOR_BLACK:
                    $this->m_b_king |=  $position_bit; break;
                case FG_QUEEN + COLOR_WHITE:
                    $this->m_w_queen |= $position_bit; break;
                case FG_QUEEN + COLOR_BLACK:
                    $this->m_b_queen |= $position_bit; break;
                case FG_ROOK + COLOR_WHITE:
                    $this->m_w_rook |= $position_bit; break;
                case FG_ROOK + COLOR_BLACK:
                    $this->m_b_rook |= $position_bit; break;
                case FG_BISHOP + COLOR_WHITE:
                    $this->m_w_bishop |= $position_bit; break;
                case FG_BISHOP + COLOR_BLACK:
                    $this->m_b_bishop |= $position_bit; break;
                case FG_KNIGHT + COLOR_WHITE:
                    $this->m_w_knight |= $position_bit; break;
                case FG_KNIGHT + COLOR_BLACK:
                    $this->m_b_knight |= $position_bit; break;
            }
            $position_bit = $position_bit << 1;
        }
        $this->m_all_white_figures = $this->m_w_pawn | $this->m_w_king | $this->m_w_queen | $this->m_w_rook | $this->m_w_bishop | $this->m_w_knight;
        $this->m_all_black_figures = $this->m_b_pawn | $this->m_b_king | $this->m_b_queen | $this->m_b_rook | $this->m_b_bishop | $this->m_b_knight;
    }

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
        $state['crossed_field'] = $state['m_crossed_field'] ? (int)(63 - log($state['m_crossed_field'], 2)) : null;
        unset($state['m_crossed_field']);
        
        $state_history = $this->state_history;
        foreach ($state_history as $i => $sh) {
            $state_history[$i]['crossed_field'] = $sh['m_crossed_field'] ? (int)(63 - log($sh['m_crossed_field'], 2)) : null;
            unset($state_history[$i]['m_crossed_field']);
        }
        $data = array(
            'state' => $state,
            'history' => $state_history
        );
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function unserializeState(string $serialized_state) {
        try {
            $data = json_decode($serialized_state, true);
            $crossed_field = empty($data['state']['crossed_field']) ? null : $data['state']['crossed_field'];
            $this->crossed_field = $crossed_field;
            $data['state']['m_crossed_field'] = $crossed_field ? (int)(1 << (63 - (int)$crossed_field)) : 0;
            $this->state_history = $data['history'];
            foreach ($this->state_history as $i => $sh) {
                $this->state_history[$i]['m_crossed_field'] = empty($sh['crossed_field']) ? 0 : (int)(1 << (63 - (int)$sh['crossed_field']));
                unset($this->state_history[$i]['crossed_field']);
            }
            foreach (self::PROPERTY_NAMES as $key) {
                $this->$key = $data['state'][$key];
            }
            $this->setPosition($data['state']['position']);
            $this->recalculatePositionHashes();
            return true;
        } catch(Exception $e) {
            return false;
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
        $this->crossed_field = $this->m_crossed_field ? (int)(63 - log($this->m_crossed_field, 2)) : null;
        $this->setPosition($this->position);
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

    public function makeMove($move, $to_figure=FG_QUEEN) {
        list($m_from, $m_to, $m_type_move) = $move;

        // делаем изменение в position, этот массив по идее можно выпилить, по пока так, он сейчас много где участвует в вычислениях хешей состояний
        $pos_from = (int)(63 - log($m_from, 2));
        $pos_to = (int)(63 - log($m_to, 2));
        $this->position[$pos_to] = $this->position[$pos_from];
        $this->position[$pos_from] = FG_NONE;

        if ($this->current_player_color == COLOR_WHITE) {
            // ход белой фигурой
            $this->m_all_white_figures = ($this->m_all_white_figures & ~$m_from) | $m_to;
            if ($m_type_move & MOVE_TYPE_PAWN) {
                // ход пешкой
                $this->m_w_pawn = ($this->m_w_pawn & ~$m_from) | $m_to;
                if ($m_type_move & MOVE_TYPE_BEAT) {
                    if ($m_type_move & MOVE_TYPE_CROSS_BEAT) {
                        $m_beat = $m_to >> 8;
                        $this->m_all_black_figures &= ~$m_beat;
                        $this->m_b_pawn &= ~$m_beat;
                        $pos_beat = (int)(63 - log($m_beat, 2));
                        $this->position[$pos_beat] = FG_NONE;
                    } else {
                        $this->m_all_black_figures &= ~$m_to;
                        $this->m_b_pawn &= ~$m_to;
                        $this->m_b_bishop &= ~$m_to;
                        $this->m_b_knight &= ~$m_to;
                        $this->m_b_queen &= ~$m_to;
                        $this->m_b_rook &= ~$m_to;
                        if ($m_to & FIELD_A8) {
                            $this->enable_castling_black_queen = false;
                        } elseif ($m_to & FIELD_H8) {
                            $this->enable_castling_black_king = false;
                        }
                    }
                }
                if ($m_to & HOR_8) {
                    // превращение пешки
                    $this->m_w_pawn &= ~$m_to;
                    switch ($to_figure) {
                        case FG_QUEEN: $this->m_w_queen |= $m_to; break;
                        case FG_ROOK: $this->m_w_rook |= $m_to; break;
                        case FG_BISHOP: $this->m_w_bishop |= $m_to; break;
                        case FG_KNIGHT: $this->m_w_knight |= $m_to; break;
                        default: $to_figure = FG_QUEEN; $this->m_w_queen |= $m_to;
                    }
                    $this->position[$pos_to] = $to_figure + COLOR_WHITE;
                }
            } elseif ($m_type_move & MOVE_TYPE_KING) {
                // ход королём (в т.ч. рокировка)
                $this->m_w_king = ($this->m_w_king & ~$m_from) | $m_to; // перемещаем короля
                $this->enable_castling_white_king = $this->enable_castling_white_queen = false; // запрещаем рокировки белым
                if ($m_type_move & MOVE_TYPE_BEAT) {
                    $this->m_all_black_figures &= ~$m_to;
                    $this->m_b_pawn &= ~$m_to;
                    $this->m_b_bishop &= ~$m_to;
                    $this->m_b_knight &= ~$m_to;
                    $this->m_b_queen &= ~$m_to;
                    $this->m_b_rook &= ~$m_to;
                    if ($m_to & FIELD_A8) {
                        $this->enable_castling_black_queen = false;
                    } elseif ($m_to & FIELD_H8) {
                        $this->enable_castling_black_king = false;
                    }
                } elseif ($m_type_move & MOVE_TYPE_KING_CASTLING) {
                    $this->m_w_rook = ($this->m_w_rook & ~FIELD_H1) | FIELD_F1;
                    $this->position[POS_H1] = FG_NONE;
                    $this->position[POS_F1] = FG_ROOK + COLOR_WHITE;
                } elseif ($m_type_move & MOVE_TYPE_QUEEN_CASTLING) {
                    $this->m_w_rook = ($this->m_w_rook & ~FIELD_A1) | FIELD_D1;
                    $this->position[POS_A1] = FG_NONE;
                    $this->position[POS_D1] = FG_ROOK + COLOR_WHITE;
                }
            } else {
                // ход остальными фигурами (не пешкой и не королём)
                if ($this->m_w_queen & $m_from) {
                    $this->m_w_queen = ($this->m_w_queen & ~$m_from) | $m_to;
                } elseif ($this->m_w_rook & $m_from) {
                    $this->m_w_rook = ($this->m_w_rook & ~$m_from) | $m_to;
                    if ($m_from & FIELD_A1) {
                        $this->enable_castling_white_queen = false;
                    } elseif ($m_from & FIELD_H1) {
                        $this->enable_castling_white_king = false;
                    }
                } elseif ($this->m_w_bishop & $m_from) {
                    $this->m_w_bishop = ($this->m_w_bishop & ~$m_from) | $m_to;
                } else {
                    $this->m_w_knight = ($this->m_w_knight & ~$m_from) | $m_to;
                }
                if ($m_type_move & MOVE_TYPE_BEAT) {
                    $this->m_all_black_figures &= ~$m_to;
                    $this->m_b_pawn &= ~$m_to;
                    $this->m_b_bishop &= ~$m_to;
                    $this->m_b_knight &= ~$m_to;
                    $this->m_b_queen &= ~$m_to;
                    $this->m_b_rook &= ~$m_to;
                    if ($m_to & FIELD_A8) {
                        $this->enable_castling_black_queen = false;
                    } elseif ($m_to & FIELD_H8) {
                        $this->enable_castling_black_king = false;
                    }
                }
            }
            $this->m_crossed_field = ($m_type_move & MOVE_TYPE_PAWN2) ? ($m_to >> 8) : 0;
            $this->current_player_color = COLOR_BLACK;
        } else {
            // ход чёрной фигурой
            $this->m_all_black_figures = ($this->m_all_black_figures & ~$m_from) | $m_to;
            if ($m_type_move & MOVE_TYPE_PAWN) {
                // ход пешкой
                $this->m_b_pawn = ($this->m_b_pawn & ~$m_from) | $m_to;
                if ($m_type_move & MOVE_TYPE_BEAT) {
                    if ($m_type_move & MOVE_TYPE_CROSS_BEAT) {
                        $m_beat = $m_to << 8;
                        $this->m_all_white_figures &= ~$m_beat;
                        $this->m_w_pawn &= ~$m_beat;
                        $pos_beat = (int)(63 - log($m_beat, 2));
                        $this->position[$pos_beat] = FG_NONE;
                    } else {
                        $this->m_all_white_figures &= ~$m_to;
                        $this->m_w_pawn &= ~$m_to;
                        $this->m_w_bishop &= ~$m_to;
                        $this->m_w_knight &= ~$m_to;
                        $this->m_w_queen &= ~$m_to;
                        $this->m_w_rook &= ~$m_to;
                        if ($m_to & FIELD_A1) {
                            $this->enable_castling_white_queen = false;
                        } elseif ($m_to & FIELD_H1) {
                            $this->enable_castling_white_king = false;
                        }
                    }
                }
                if ($m_to & HOR_1) {
                    // превращение пешки
                    $this->m_b_pawn &= ~$m_to;
                    switch ($to_figure) {
                        case FG_QUEEN: $this->m_b_queen |= $m_to; break;
                        case FG_ROOK: $this->m_b_rook |= $m_to; break;
                        case FG_BISHOP: $this->m_b_bishop |= $m_to; break;
                        case FG_KNIGHT: $this->m_b_knight |= $m_to; break;
                        default: $to_figure = FG_QUEEN; $this->m_b_queen |= $m_to;
                    }
                    $this->position[$pos_to] = $to_figure + COLOR_BLACK;
                }
            } elseif ($m_type_move & MOVE_TYPE_KING) {
                // ход королём (в т.ч. рокировка)
                $this->m_b_king = ($this->m_b_king & ~$m_from) | $m_to; // перемещаем короля
                $this->enable_castling_black_king = $this->enable_castling_black_queen = false; // запрещаем рокировки белым
                if ($m_type_move & MOVE_TYPE_BEAT) {
                    $this->m_all_white_figures &= ~$m_to;
                    $this->m_w_pawn &= ~$m_to;
                    $this->m_w_bishop &= ~$m_to;
                    $this->m_w_knight &= ~$m_to;
                    $this->m_w_queen &= ~$m_to;
                    $this->m_w_rook &= ~$m_to;
                    if ($m_to & FIELD_A1) {
                        $this->enable_castling_white_queen = false;
                    } elseif ($m_to & FIELD_H1) {
                        $this->enable_castling_white_king = false;
                    }
                } elseif ($m_type_move & MOVE_TYPE_KING_CASTLING) {
                    $this->m_b_rook = ($this->m_b_rook & ~FIELD_H8) | FIELD_F8;
                    $this->position[POS_H8] = FG_NONE;
                    $this->position[POS_F8] = FG_ROOK + COLOR_BLACK;
                } elseif ($m_type_move & MOVE_TYPE_QUEEN_CASTLING) {
                    $this->m_b_rook = ($this->m_b_rook & ~FIELD_A8) | FIELD_D8;
                    $this->position[POS_A8] = FG_NONE;
                    $this->position[POS_D8] = FG_ROOK + COLOR_BLACK;
                }
            } else {
                // ход остальными фигурами (не пешкой и не королём)
                if ($this->m_b_queen & $m_from) {
                    $this->m_b_queen = ($this->m_b_queen & ~$m_from) | $m_to;
                } elseif ($this->m_b_rook & $m_from) {
                    $this->m_b_rook = ($this->m_b_rook & ~$m_from) | $m_to;
                    if ($m_from & FIELD_A8) {
                        $this->enable_castling_black_queen = false;
                    } elseif ($m_from & FIELD_H8) {
                        $this->enable_castling_black_king = false;
                    }
                } elseif ($this->m_b_bishop & $m_from) {
                    $this->m_b_bishop = ($this->m_b_bishop & ~$m_from) | $m_to;
                } else {
                    $this->m_b_knight = ($this->m_b_knight & ~$m_from) | $m_to;
                }
                if ($m_type_move & MOVE_TYPE_BEAT) {
                    $this->m_all_white_figures &= ~$m_to;
                    $this->m_w_pawn &= ~$m_to;
                    $this->m_w_bishop &= ~$m_to;
                    $this->m_w_knight &= ~$m_to;
                    $this->m_w_queen &= ~$m_to;
                    $this->m_w_rook &= ~$m_to;
                    if ($m_to & FIELD_A1) {
                        $this->enable_castling_white_queen = false;
                    } elseif ($m_to & FIELD_H1) {
                        $this->enable_castling_white_king = false;
                    }
                }
            }
            $this->m_crossed_field = ($m_type_move & MOVE_TYPE_PAWN2) ? ($m_to << 8) : 0;
            // изменяем очередь хода и счётчик ходов
            $this->current_player_color = COLOR_WHITE;
            $this->move_number++;
        }

        $this->non_action_semimove_counter = ($m_type_move & (MOVE_TYPE_PAWN | MOVE_TYPE_BEAT)) ? 0 : $this->non_action_semimove_counter + 1;
        $this->savePositionHash();
        return true;
    }

    public function isKingUnderAttack() {
        return ($this->current_player_color == COLOR_WHITE) ? $this->isWhiteKingUnderAttack() : $this->isBlackKingUnderAttack();
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

    private function countBits($num) {
        $qty = 0;
        while ($num) {
            $qty++;
            $num &= ~($num & (int)-$num);
        }
        return $qty;
    }

    public function calcStaticScore($no_available_moves, $deep=0) {
        if ($no_available_moves) {
            if ($this->isKingUnderAttack()) {
                return PHP_INT_MIN + $deep;
            } else {
                return 0;
            }
        }
        // Будем строить оценку позиции для белых. Если нужна оценка для чёрных (т.е. если сейчас их ход), то возвратим противоположное число.
        // считаем материальную ценность фигур
        $score = self::FIGURE_VALUES[FG_QUEEN] * ($this->countBits($this->m_w_queen) - $this->countBits($this->m_b_queen)) +
            self::FIGURE_VALUES[FG_ROOK] * ($this->countBits($this->m_w_rook) - $this->countBits($this->m_b_rook)) +
            self::FIGURE_VALUES[FG_BISHOP] * ($this->countBits($this->m_w_bishop) - $this->countBits($this->m_b_bishop)) +
            self::FIGURE_VALUES[FG_KNIGHT] * ($this->countBits($this->m_w_knight) - $this->countBits($this->m_b_knight)) +
            self::FIGURE_VALUES[FG_PAWN] * ($this->countBits($this->m_w_pawn) - $this->countBits($this->m_b_pawn));
        return $this->current_player_color == COLOR_WHITE ? $score : -$score;
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
        // считаем материальную ценность фигур
        $score = self::FIGURE_VALUES[FG_QUEEN] * ($this->countBits($this->m_w_queen) - $this->countBits($this->m_b_queen)) +
            self::FIGURE_VALUES[FG_ROOK] * ($this->countBits($this->m_w_rook) - $this->countBits($this->m_b_rook)) +
            self::FIGURE_VALUES[FG_BISHOP] * ($this->countBits($this->m_w_bishop) - $this->countBits($this->m_b_bishop)) +
            self::FIGURE_VALUES[FG_KNIGHT] * ($this->countBits($this->m_w_knight) - $this->countBits($this->m_b_knight)) +
            self::FIGURE_VALUES[FG_PAWN] * ($this->countBits($this->m_w_pawn) - $this->countBits($this->m_b_pawn));
        
        // считаем ценность продвижения для белых пешек
        if ($this->move_number > 3) {
            $w_pawn_pos_weigth = self::W_PAWN_POS_WEIGHTS;
            $b_pawn_pos_weigth = self::B_PAWN_POS_WEIGHTS;
        } else {
            $w_pawn_pos_weigth = self::W_PAWN_POS_BEGIN_WEIGHTS;
            $b_pawn_pos_weigth = self::B_PAWN_POS_BEGIN_WEIGHTS;
        }
        $m_pawns = $this->m_w_pawn;
        while ($m_pawns) {
            $m_pawn = $m_pawns & (int)-$m_pawns;
            $pos = (int)(63 - log($m_pawn, 2));
            $score += $w_pawn_pos_weigth[$pos];
            $m_pawns &= ~$m_pawn;
        }
        // считаем ценность продвижения для чёрных пешек
        $m_pawns = $this->m_b_pawn;
        while ($m_pawns) {
            $m_pawn = $m_pawns & (int)-$m_pawns;
            $pos = (int)(63 - log($m_pawn, 2));
            $score -= $b_pawn_pos_weigth[$pos];
            $m_pawns &= ~$m_pawn;
        }

        // штрафы за ранние ходы тяжёлыми фигурами
        if ($this->move_number <= 10) {
            $score += 500 * (10 - $this->move_number) * (
                $this->countBits( ($this->m_w_queen | $this->m_w_rook) & (POS_A1 | POS_D1 | POS_H1) ) -
                $this->countBits( ($this->m_b_queen | $this->m_b_rook) & (int)(POS_A8 | POS_D8 | POS_H8) )
            );
        }

        // Наши фигуры (белые) хотим расположить ближе к королю противника
        $square_distance = 0;
        $pos_king = (int)(63 - log($this->m_b_king, 2));
        $king_row = $pos_king >> 3;
        $king_col = $pos_king & 0b111;
        $m_set = $this->m_w_queen;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $pos = (int)(63 - log($m_fig, 2));
            $row = $pos >> 3;
            $col = $pos & 0b111;
            $square_distance -= ($king_row - $row)**2 + ($king_col - $col)**2; // минус - т.к. чем больше дистанция, тем нам (белым) хуже
            $m_set &= ~$m_fig;
        }
        $m_set = $this->m_w_rook;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $pos = (int)(63 - log($m_fig, 2));
            $row = $pos >> 3;
            $col = $pos & 0b111;
            $square_distance -= ($king_row - $row)**2 + ($king_col - $col)**2;
            $m_set &= ~$m_fig;
        }
        $m_set = $this->m_w_bishop;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $pos = (int)(63 - log($m_fig, 2));
            $row = $pos >> 3;
            $col = $pos & 0b111;
            $square_distance -= ($king_row - $row)**2 + ($king_col - $col)**2;
            $m_set &= ~$m_fig;
        }
        $m_set = $this->m_w_knight;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $pos = (int)(63 - log($m_fig, 2));
            $row = $pos >> 3;
            $col = $pos & 0b111;
            $square_distance -= ($king_row - $row)**2 + ($king_col - $col)**2;
            $m_set &= ~$m_fig;
        }

        // А нашего (белого) короля хотим расположить подальше от фигур противника
        $pos_king = (int)(63 - log($this->m_w_king, 2));
        $king_row = $pos_king >> 3;
        $king_col = $pos_king & 0b111;
        $m_set = $this->m_b_queen;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $pos = (int)(63 - log($m_fig, 2));
            $row = $pos >> 3;
            $col = $pos & 0b111;
            $square_distance += ($king_row - $row)**2 + ($king_col - $col)**2; // чем больше дистанция тем нам (белым) лучше
            $m_set &= ~$m_fig;
        }
        $m_set = $this->m_b_rook;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $pos = (int)(63 - log($m_fig, 2));
            $row = $pos >> 3;
            $col = $pos & 0b111;
            $square_distance += ($king_row - $row)**2 + ($king_col - $col)**2;
            $m_set &= ~$m_fig;
        }
        $m_set = $this->m_b_bishop;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $pos = (int)(63 - log($m_fig, 2));
            $row = $pos >> 3;
            $col = $pos & 0b111;
            $square_distance += ($king_row - $row)**2 + ($king_col - $col)**2;
            $m_set &= ~$m_fig;
        }
        $m_set = $this->m_b_knight;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $pos = (int)(63 - log($m_fig, 2));
            $row = $pos >> 3;
            $col = $pos & 0b111;
            $square_distance += ($king_row - $row)**2 + ($king_col - $col)**2;
            $m_set &= ~$m_fig;
        }
        
        $score += 0.8 * $this->move_number * $square_distance;

        // учитывем "фактор атак"
        $score += $this->getAttackFactor() / 80;

        return $this->current_player_color == COLOR_WHITE ? $score : -$score;
    }

    public function getAttackFactor() {
        // Вычисляем "фактор атаки" для белых: сумму "взвешенных количеств атак" на чёрные фигуры минус сумма взвешенных количеств атак на белые фигуры.
        // Вес определяется достоинствами атакуемой и атакующей фигур
        $this->all_figures_mask = $this->m_all_white_figures | $this->m_all_black_figures;
        // начнём с атак на белые фигуры
        $attacks_factor = -self::FIGURE_ATTACK_FACTOR[FG_KING] * $this->getWeighedAttackCountByBlack($this->m_w_king);
        $m_set = (int)$this->m_w_queen;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $attacks_factor -= self::FIGURE_ATTACK_FACTOR[FG_QUEEN] * $this->getWeighedAttackCountByBlack($m_fig);
            $m_set &= ~$m_fig;
        }
        $m_set = (int)$this->m_w_rook;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $attacks_factor -= self::FIGURE_ATTACK_FACTOR[FG_ROOK] * $this->getWeighedAttackCountByBlack($m_fig);
            $m_set &= ~$m_fig;
        }
        $m_set = (int)$this->m_w_bishop;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $attacks_factor -= self::FIGURE_ATTACK_FACTOR[FG_BISHOP] * $this->getWeighedAttackCountByBlack($m_fig);
            $m_set &= ~$m_fig;
        }
        $m_set = (int)$this->m_w_knight;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $attacks_factor -= self::FIGURE_ATTACK_FACTOR[FG_KNIGHT] * $this->getWeighedAttackCountByBlack($m_fig);
            $m_set &= ~$m_fig;
        }
        $m_set = (int)$this->m_w_pawn;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $attacks_factor -= self::FIGURE_ATTACK_FACTOR[FG_PAWN] * $this->getWeighedAttackCountByBlack($m_fig);
            $m_set &= ~$m_fig;
        }
        // теперь - атаки на чёрные фигуры
        $attacks_factor += self::FIGURE_ATTACK_FACTOR[FG_KING] * $this->getWeighedAttackCountByWhite($this->m_b_king);
        $m_set = (int)$this->m_b_queen;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $attacks_factor += self::FIGURE_ATTACK_FACTOR[FG_QUEEN] * $this->getWeighedAttackCountByWhite($m_fig);
            $m_set &= ~$m_fig;
        }
        $m_set = (int)$this->m_b_rook;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $attacks_factor += self::FIGURE_ATTACK_FACTOR[FG_ROOK] * $this->getWeighedAttackCountByWhite($m_fig);
            $m_set &= ~$m_fig;
        }
        $m_set = (int)$this->m_b_bishop;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $attacks_factor += self::FIGURE_ATTACK_FACTOR[FG_BISHOP] * $this->getWeighedAttackCountByWhite($m_fig);
            $m_set &= ~$m_fig;
        }
        $m_set = (int)$this->m_b_knight;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $attacks_factor += self::FIGURE_ATTACK_FACTOR[FG_KNIGHT] * $this->getWeighedAttackCountByWhite($m_fig);
            $m_set &= ~$m_fig;
        }
        $m_set = (int)$this->m_b_pawn;
        while ($m_set) {
            $m_fig = $m_set & (int)-$m_set;
            $attacks_factor += self::FIGURE_ATTACK_FACTOR[FG_PAWN] * $this->getWeighedAttackCountByWhite($m_fig);
            $m_set &= ~$m_fig;
        }
        return $attacks_factor;
    }

    // рассчёт взвешенного количества атак со стороны чёрных фигур на поле $m_fileld с учётом "рентгеновских атак"
    private function getWeighedAttackCountByBlack($m_fileld) {
        $pos = (int)(63 - log($m_fileld, 2));

        // атаки со стороны коней
        $m_cross = $this->m_b_knight & (int)Masks::KHIGHT_MASK[$pos];
        $result = $this->countBits($m_cross) * self::FIGURE_FROM_ATTACK_FACTOR[FG_KNIGHT];
        
        // атака со стороны короля
        $m_cross = $this->m_b_king & ((int)Masks::KING_HV_MASK[$pos] | (int)Masks::KING_DIAG_MASK[$pos]);
        $result += $this->countBits($m_cross) * self::FIGURE_FROM_ATTACK_FACTOR[FG_KING];

        // атака со стороны пешки слева сверху
        if ($m_fileld & (int)($this->m_b_pawn >> 9) & ~COL_A) {
            $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_PAWN];
            // смотрим что за пешкой на луче вверх-влево
            if (($pos & 0b111) >= 2) {
                $cross_mask = (int)Masks::DIAG_UP_LEFT[$pos - 9] & $this->all_figures_mask;
                while ($cross_mask) {
                    $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой
                    if ($m_ray_end & $this->m_b_bishop) {
                        $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_BISHOP];
                    } elseif ($m_ray_end & $this->m_b_queen) {
                        $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                    } else {
                        break;
                    }
                    $cross_mask &= ~$m_ray_end;
                }
            }
        } else {
            // дальнобойные атаки по лучу слева сверху
            $cross_mask = (int)Masks::DIAG_UP_LEFT[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_b_bishop) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_BISHOP];
                } elseif ($m_ray_end & $this->m_b_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
        }

        // атака со стороны пешки справа сверху
        if ($m_fileld & (int)($this->m_b_pawn >> 7) & ~COL_H) {
            $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_PAWN];
            // смотрим что за пешкой на луче вверх-вправо
            if (($pos & 0b111) <= 5) {
                $cross_mask = (int)Masks::DIAG_UP_RIGHT[$pos - 7] & $this->all_figures_mask;
                while ($cross_mask) {
                    $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой
                    if ($m_ray_end & $this->m_b_bishop) {
                        $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_BISHOP];
                    } elseif ($m_ray_end & $this->m_b_queen) {
                        $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                    } else {
                        break;
                    }
                    $cross_mask &= ~$m_ray_end;
                }
            }
        } else {
            // дальнобойные атаки по лучу справа сверху
            $cross_mask = (int)Masks::DIAG_UP_RIGHT[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_b_bishop) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_BISHOP];
                } elseif ($m_ray_end & $this->m_b_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
        }

        // дальнобойные рентгеновские атаки по направлениям
        if ($this->m_b_rook | $this->m_b_queen) {
            // луч вверх
            $cross_mask = (int)Masks::VERT_UP_MASK[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_b_rook) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_ROOK];
                } elseif ($m_ray_end & $this->m_b_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
            // луч влево
            $cross_mask = (int)Masks::HOR_LEFT_MASK[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_b_rook) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_ROOK];
                } elseif ($m_ray_end & $this->m_b_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
            // луч вправо
            $cross_mask = (int)Masks::HOR_RIGHT_MASK[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_b_rook) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_ROOK];
                } elseif ($m_ray_end & $this->m_b_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
            // луч вниз
            $cross_mask = (int)Masks::VERT_DOWN_MASK[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_b_rook) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_ROOK];
                } elseif ($m_ray_end & $this->m_b_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
        }
        if ($this->m_b_bishop | $this->m_b_queen) {
            // луч вниз влево
            $cross_mask = (int)Masks::DIAG_DOWN_LEFT[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_b_bishop) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_BISHOP];
                } elseif ($m_ray_end & $this->m_b_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
            // луч вниз вправо
            $cross_mask = (int)Masks::DIAG_DOWN_RIGHT[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_b_bishop) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_BISHOP];
                } elseif ($m_ray_end & $this->m_b_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
        }
        return $result;
    }

    // рассчёт взвешенного количества атак со стороны белых фигур на поле $m_fileld с учётом "рентгеновских атак"
    private function getWeighedAttackCountByWhite($m_fileld) {
        $pos = (int)(63 - log($m_fileld, 2));

        // атаки со стороны коней
        $m_cross = $this->m_w_knight & (int)Masks::KHIGHT_MASK[$pos];
        $result = $this->countBits($m_cross) * self::FIGURE_FROM_ATTACK_FACTOR[FG_KNIGHT];
        
        // атака со стороны короля
        $m_cross = $this->m_w_king & ((int)Masks::KING_HV_MASK[$pos] | (int)Masks::KING_DIAG_MASK[$pos]);
        $result += $this->countBits($m_cross) * self::FIGURE_FROM_ATTACK_FACTOR[FG_KING];

        // атака со стороны пешки слева снизу
        if ($m_fileld & (int)($this->m_w_pawn << 7) & ~COL_A) {
            $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_PAWN];
            // смотрим что за пешкой на луче вниз-влево
            if (($pos & 0b111) >= 2) {
                $cross_mask = (int)Masks::DIAG_DOWN_LEFT[$pos + 7] & $this->all_figures_mask;
                while ($cross_mask) {
                    $m_ray_end = Functions::getHighestBit($cross_mask); // маска с самым ближним полем с фигурой
                    if ($m_ray_end & $this->m_w_bishop) {
                        $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_BISHOP];
                    } elseif ($m_ray_end & $this->m_w_queen) {
                        $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                    } else {
                        break;
                    }
                    $cross_mask &= ~$m_ray_end;
                }
            }
        } else {
            // дальнобойные атаки по лучу влево-вниз
            $cross_mask = (int)Masks::DIAG_DOWN_LEFT[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_w_bishop) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_BISHOP];
                } elseif ($m_ray_end & $this->m_w_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
        }

        // атака со стороны пешки справа снизу
        if ($m_fileld & (int)($this->m_w_pawn << 9) & ~COL_H) {
            $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_PAWN];
            // смотрим что за пешкой на луче вниз-вправо
            if (($pos & 0b111) <= 5) {
                $cross_mask = (int)Masks::DIAG_DOWN_RIGHT[$pos + 9] & $this->all_figures_mask;
                while ($cross_mask) {
                    $m_ray_end = Functions::getHighestBit($cross_mask); // маска с самым ближним полем с фигурой
                    if ($m_ray_end & $this->m_w_bishop) {
                        $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_BISHOP];
                    } elseif ($m_ray_end & $this->m_w_queen) {
                        $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                    } else {
                        break;
                    }
                    $cross_mask &= ~$m_ray_end;
                }
            }
        } else {
            // дальнобойные атаки по лучу вниз-вправо
            $cross_mask = (int)Masks::DIAG_DOWN_RIGHT[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_w_bishop) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_BISHOP];
                } elseif ($m_ray_end & $this->m_w_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
        }

        // дальнобойные рентгеновские атаки по направлениям
        if ($this->m_w_rook | $this->m_w_queen) {
            // луч вверх
            $cross_mask = (int)Masks::VERT_UP_MASK[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_w_rook) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_ROOK];
                } elseif ($m_ray_end & $this->m_w_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
            // луч влево
            $cross_mask = (int)Masks::HOR_LEFT_MASK[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_w_rook) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_ROOK];
                } elseif ($m_ray_end & $this->m_w_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
            // луч вправо
            $cross_mask = (int)Masks::HOR_RIGHT_MASK[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_w_rook) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_ROOK];
                } elseif ($m_ray_end & $this->m_w_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
            // луч вниз
            $cross_mask = (int)Masks::VERT_DOWN_MASK[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_w_rook) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_ROOK];
                } elseif ($m_ray_end & $this->m_w_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
        }
        // луч вверх влево
        if ($this->m_w_bishop | $this->m_w_queen) {
            $cross_mask = (int)Masks::DIAG_UP_LEFT[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_w_bishop) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_BISHOP];
                } elseif ($m_ray_end & $this->m_w_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
            // луч вверх вправо
            $cross_mask = (int)Masks::DIAG_UP_RIGHT[$pos] & $this->all_figures_mask;
            while ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой
                if ($m_ray_end & $this->m_w_bishop) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_BISHOP];
                } elseif ($m_ray_end & $this->m_w_queen) {
                    $result += self::FIGURE_FROM_ATTACK_FACTOR[FG_QUEEN];
                } else {
                    break;
                }
                $cross_mask &= ~$m_ray_end;
            }
        }
        return $result;
    }

    /**
     * Удаление чёрной фигуры (фигур) из битовых досок по маске
     */
    public function removeBlackFigureByMask($remmask) {
        $this->m_all_black_figures &= $remmask;
        $this->m_b_pawn &= $remmask;
        $this->m_b_knight &= $remmask;
        $this->m_b_bishop &= $remmask;
        $this->m_b_rook &= $remmask;
        $this->m_b_queen &= $remmask;
    }

    /**
     * Удаление белой фигуры (фигур) из битовых досок по маске
     */
    public function removeWhiteFigureByMask($remmask) {
        $this->m_all_white_figures &= $remmask;
        $this->m_w_pawn &= $remmask;
        $this->m_w_knight &= $remmask;
        $this->m_w_bishop &= $remmask;
        $this->m_w_rook &= $remmask;
        $this->m_w_queen &= $remmask;
    }

    /********************* Проверки для белых фигур *********************/

    /**
     * Проверка - будет ли белый король под атакой при взятии белым конём фигуры, стоящей в поле, определённом маской $mask_to
     */
    public function checkWhiteKnightBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белого коня
        $to_state->m_w_knight = ($to_state->m_w_knight & ~$mask_from) | $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        $to_state->removeBlackFigureByMask(~$mask_to);
        return !$to_state->isWhiteKingUnderAttack();
    }

    /**
     * Проверка - будет ли белый король под атакой при ходе (без взятия) белым конём на поле, определённом маской $mask_to
     */
    public function checkWhiteKnightMove($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белого коня
        $to_state->m_w_knight = ($to_state->m_w_knight & ~$mask_from) | $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        return !$to_state->isWhiteKingUnderAttack();
    }

    /**
     * Проверка - будет ли белый король под атакой при взятии белым слоном фигуры, стоящей в поле, определённом маской $mask_to
     */
    public function checkWhiteBishopBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белого слона
        $to_state->m_w_bishop = ($to_state->m_w_bishop & ~$mask_from) | $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        $to_state->removeBlackFigureByMask(~$mask_to);
        return !$to_state->isWhiteKingUnderAttack();
    }

    /**
     * Проверка - будет ли белый король под атакой при ходе (без взятия) белым слоном на поле, определённом маской $mask_to
     */
    public function checkWhiteBishopMove($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белого слона
        $to_state->m_w_bishop = ($to_state->m_w_bishop & ~$mask_from) | $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        return !$to_state->isWhiteKingUnderAttack();
    }

    /**
     * Проверка - будет ли белый король под атакой при взятии белой ладьёй фигуры, стоящей в поле, определённом маской $mask_to
     */
    public function checkWhiteRookBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белую ладью
        $to_state->m_w_rook = ($to_state->m_w_rook & ~$mask_from) | $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        $to_state->removeBlackFigureByMask(~$mask_to);
        return !$to_state->isWhiteKingUnderAttack();
    }

    /**
     * Проверка - будет ли белый король под атакой при ходе (без взятия) белой ладьёй на поле, определённом маской $mask_to
     */
    public function checkWhiteRookMove($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белую ладью
        $to_state->m_w_rook = ($to_state->m_w_rook & ~$mask_from) | $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        return !$to_state->isWhiteKingUnderAttack();
    }

    /**
     * Проверка - будет ли белый король под атакой при взятии белым ферзём, стоящим на поле, определённом маской $mask_to
     */
    public function checkWhiteQueenBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белого ферзя
        $to_state->m_w_queen = ($to_state->m_w_queen & ~$mask_from) | $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        $to_state->removeBlackFigureByMask(~$mask_to);
        return !$to_state->isWhiteKingUnderAttack();
    }

    /**
     * Проверка - будет ли белый король под атакой при ходе (без взятия) белым ферзём на поле, определённом маской $mask_to
     */
    public function checkWhiteQueenMove($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белого ферзя
        $to_state->m_w_queen = ($to_state->m_w_queen & ~$mask_from) | $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        return !$to_state->isWhiteKingUnderAttack();
    }

    public function checkWhitePawnBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белую пешку
        $to_state->m_w_pawn = ($to_state->m_w_pawn & ~$mask_from) | $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        $to_state->removeBlackFigureByMask(~$mask_to);
        return !$to_state->isWhiteKingUnderAttack();
    }

    public function checkWhitePawnCrossBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белую пешку
        $to_state->m_w_pawn = ($to_state->m_w_pawn & ~$mask_from) | $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        // убираем чёрную пешку
        $to_state->m_b_pawn &= ~($mask_to >> 8);
        $to_state->m_all_black_figures &= ~($mask_to >> 8);
        return !$to_state->isWhiteKingUnderAttack();
    }

    public function checkWhitePawnMove($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белую пешку
        $to_state->m_w_pawn = ($to_state->m_w_pawn & ~$mask_from) | $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        return !$to_state->isWhiteKingUnderAttack();
    }

    public function checkWhiteKingBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белого короля
        $to_state->m_w_king = ($to_state->m_w_king & ~$mask_from) | $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        $to_state->removeBlackFigureByMask(~$mask_to);
        return !$to_state->isWhiteKingUnderAttack();
    }

    public function checkWhiteKingMove($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белого короля
        $to_state->m_w_king = $mask_to;
        $to_state->m_all_white_figures = ($to_state->m_all_white_figures & ~$mask_from) | $mask_to;
        return !$to_state->isWhiteKingUnderAttack();
    }

    /**
     * Проверка - находится ли белый король под атакой?
     */
    public function isWhiteKingUnderAttack() {
        return $this->isAttackedByBlack($this->m_w_king);
    }

    /**
     * Проверка - находится ли поле под атакой чёрными фигурами?
     */
    public function isAttackedByBlack($field_mask) {
        // поле атаковано конями?
        $pos_from = (int)(63 - log($field_mask , 2));
        if ($this->m_b_knight & (int)Masks::KHIGHT_MASK[$pos_from]) {
            return true;
        }
        // поле атаковано пешками?
        if ( ((($this->m_b_pawn >> 7) & ~COL_H) | (($this->m_b_pawn >> 9) & NOT_COL_A)) & $field_mask ) {
            return true;
        }
        $m_b_queen_rook = $this->m_b_queen | $this->m_b_rook;
        $m_b_queen_bishop = $this->m_b_queen | $this->m_b_bishop;
        // поле атаковано с соседних полей (по вертикалям, горизонталям, диагоналям)? (не пешками, с ними мы разобрались ранее)
        if (
            ((int)Masks::KING_HV_MASK[$pos_from] & ($this->m_b_king | $m_b_queen_rook))
            ||
            ((int)Masks::KING_DIAG_MASK[$pos_from] & ($this->m_b_king | $m_b_queen_bishop))
        ) {
            return true;
        }
        
        $all_figures_mask = $this->m_all_white_figures | $this->m_all_black_figures;
        
        // проверим луч влево
        $cross_mask = (int)Masks::HOR_LEFT_MASK[$pos_from] & $all_figures_mask;
        // выделение самого младшего (правого) бита: $cross_mask & (int)-$cross_mask
        if ( ($cross_mask & (int)-$cross_mask) & $m_b_queen_rook ) {
            return true;
        }

        // проверим луч вправо
        $cross_mask = Masks::HOR_RIGHT_MASK[$pos_from] & $all_figures_mask;
        if ( $cross_mask && $m_b_queen_rook && ($m_b_queen_rook & Functions::getHighestBit($cross_mask)) ) {
            return true;
        }

        // луч вверх
        $cross_mask = (int)Masks::VERT_UP_MASK[$pos_from] & $all_figures_mask;
        if ( ($cross_mask & (int)-$cross_mask) & $m_b_queen_rook ) {
            return true;
        }
        
        // луч вниз
        $cross_mask = Masks::VERT_DOWN_MASK[$pos_from] & $all_figures_mask;
        if ( $cross_mask && $m_b_queen_rook && ($m_b_queen_rook & Functions::getHighestBit($cross_mask)) ) {
            return true;
        }

        // луч вверх влево
        $cross_mask = (int)Masks::DIAG_UP_LEFT[$pos_from] & $all_figures_mask;
        if ( ($cross_mask & (int)-$cross_mask) & $m_b_queen_bishop ) {
            return true;
        }

        // луч вверх вправо
        $cross_mask = Masks::DIAG_UP_RIGHT[$pos_from] & $all_figures_mask;
        if ( ($cross_mask & (int)-$cross_mask) & $m_b_queen_bishop ) {
            return true;
        }

        // луч вниз влево
        $cross_mask = Masks::DIAG_DOWN_LEFT[$pos_from] & $all_figures_mask;
        if ( $cross_mask && $m_b_queen_bishop && ($m_b_queen_bishop & Functions::getHighestBit($cross_mask)) ) {
            return true;
        }

        // луч вниз вправо
        $cross_mask = Masks::DIAG_DOWN_RIGHT[$pos_from] & $all_figures_mask;
        if ( $cross_mask && $m_b_queen_bishop && ($m_b_queen_bishop & Functions::getHighestBit($cross_mask)) ) {
            return true;
        }

        // угроз не обнаружили, возвращаем false
        return false;
    }

    /********************* Проверки для чёрных фигур *********************/

    /**
     * Проверка - будет ли чёрный король под атакой при взятии чёрным конём фигуры, стоящей в поле, определённом маской $mask_to
     */
    public function checkBlackKnightBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем чёрного коня
        $to_state->m_b_knight = ($to_state->m_b_knight & ~$mask_from) | $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        $to_state->removeWhiteFigureByMask(~$mask_to);
        return !$to_state->isBlackKingUnderAttack();
    }

    /**
     * Проверка - будет ли чёрный король под атакой при ходе (без взятия) чёрным конём на поле, определённом маской $mask_to
     */
    public function checkBlackKnightMove($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем чёрного коня
        $to_state->m_b_knight = ($to_state->m_b_knight & ~$mask_from) | $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        return !$to_state->isBlackKingUnderAttack();
    }

    /**
     * Проверка - будет ли чёрный король под атакой при взятии чёрным слоном фигуры, стоящей в поле, определённом маской $mask_to
     */
    public function checkBlackBishopBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем чёрного слона
        $to_state->m_b_bishop = ($to_state->m_b_bishop & ~$mask_from) | $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        $to_state->removeWhiteFigureByMask(~$mask_to);
        return !$to_state->isBlackKingUnderAttack();
    }

    /**
     * Проверка - будет ли чёрный король под атакой при ходе (без взятия) чёрным слоном на поле, определённом маской $mask_to
     */
    public function checkBlackBishopMove($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем чёрного слона
        $to_state->m_b_bishop = ($to_state->m_b_bishop & ~$mask_from) | $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        return !$to_state->isBlackKingUnderAttack();
    }

    /**
     * Проверка - будет ли чёрный король под атакой при взятии белой ладьёй фигуры, стоящей в поле, определённом маской $mask_to
     */
    public function checkBlackRookBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем чёрную ладью
        $to_state->m_b_rook = ($to_state->m_b_rook & ~$mask_from) | $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        $to_state->removeWhiteFigureByMask(~$mask_to);
        return !$to_state->isBlackKingUnderAttack();
    }

    /**
     * Проверка - будет ли чёрный король под атакой при ходе (без взятия) белой ладьёй на поле, определённом маской $mask_to
     */
    public function checkBlackRookMove($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем чёрную ладью
        $to_state->m_b_rook = ($to_state->m_b_rook & ~$mask_from) | $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        return !$to_state->isBlackKingUnderAttack();
    }

    /**
     * Проверка - будет ли чёрный король под атакой при взятии чёрным ферзём, стоящим на поле, определённом маской $mask_to
     */
    public function checkBlackQueenBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем чёрного ферзя
        $to_state->m_b_queen = ($to_state->m_b_queen & ~$mask_from) | $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        $to_state->removeWhiteFigureByMask(~$mask_to);
        return !$to_state->isBlackKingUnderAttack();
    }

    /**
     * Проверка - будет ли чёрный король под атакой при ходе (без взятия) чёрным ферзём на поле, определённом маской $mask_to
     */
    public function checkBlackQueenMove($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем чёрного ферзя
        $to_state->m_b_queen = ($to_state->m_b_queen & ~$mask_from) | $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        return !$to_state->isBlackKingUnderAttack();
    }

    public function checkBlackPawnBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем белую пешку
        $to_state->m_b_pawn = ($to_state->m_b_pawn & ~$mask_from) | $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        $to_state->removeWhiteFigureByMask(~$mask_to);
        return !$to_state->isBlackKingUnderAttack();
    }

    public function checkBlackPawnCrossBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем чёрную пешку
        $to_state->m_b_pawn = ($to_state->m_b_pawn & ~$mask_from) | $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        // убираем белую пешку
        $to_state->m_w_pawn &= ~($mask_to << 8);
        $to_state->m_all_white_figures &= ~($mask_to << 8);
        return !$to_state->isBlackKingUnderAttack();
    }

    public function checkBlackPawnMove($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем чёрную пешку
        $to_state->m_b_pawn = ($to_state->m_b_pawn & ~$mask_from) | $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        return !$to_state->isBlackKingUnderAttack();
    }

    public function checkBlackKingBeat($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем чёрного короля
        $to_state->m_b_king = ($to_state->m_b_king & ~$mask_from) | $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        $to_state->removeWhiteFigureByMask(~$mask_to);
        return !$to_state->isBlackKingUnderAttack();
    }

    public function checkBlackKingMove($mask_from, $mask_to) {
        $to_state = clone $this;
        // перемещаем чёрного короля
        $to_state->m_b_king = $mask_to;
        $to_state->m_all_black_figures = ($to_state->m_all_black_figures & ~$mask_from) | $mask_to;
        return !$to_state->isBlackKingUnderAttack();
    }

    /**
     * Проверка - находится ли чёрный король под атакой?
     */
    public function isBlackKingUnderAttack() {
        return $this->isAttackedByWhite($this->m_b_king);
    }

    /**
     * Проверка - находится ли поле под атакой белыми фигурами?
     */
    public function isAttackedByWhite($field_mask) {
        // поле атаковано конями?
        $pos_from = (int)(63 - log($field_mask , 2));
        if ($this->m_w_knight & (int)Masks::KHIGHT_MASK[$pos_from]) {
            return true;
        }
        // поле атаковано пешками?
        if ( ((($this->m_w_pawn << 7) & NOT_COL_A) | (($this->m_w_pawn << 9) & ~COL_H)) & $field_mask ) {
            return true;
        }
        $m_w_queen_rook = $this->m_w_queen | $this->m_w_rook;
        $m_w_queen_bishop = $this->m_w_queen | $this->m_w_bishop;
        // поле атаковано с соседних полей (по вертикалям, горизонталям, диагоналям)? (не пешками, с ними мы разобрались ранее)
        if (
            ((int)Masks::KING_HV_MASK[$pos_from] & ($this->m_w_king | $m_w_queen_rook))
            ||
            ((int)Masks::KING_DIAG_MASK[$pos_from] & ($this->m_w_king | $m_w_queen_bishop))
        ) {
            return true;
        }
        
        $all_figures_mask = $this->m_all_white_figures | $this->m_all_black_figures;
        
        // проверим луч влево
        $cross_mask = (int)Masks::HOR_LEFT_MASK[$pos_from] & $all_figures_mask;
        // выделение самого младшего (правого) бита: $cross_mask & (int)-$cross_mask
        if ( $m_w_queen_rook & ($cross_mask & (int)-$cross_mask) ) {
            return true;
        }

        // проверим луч вправо
        $cross_mask = Masks::HOR_RIGHT_MASK[$pos_from] & $all_figures_mask;
        if ( $cross_mask && $m_w_queen_rook && ($m_w_queen_rook & Functions::getHighestBit($cross_mask)) ) {
            return true;
        }

        // луч вверх
        $cross_mask = (int)Masks::VERT_UP_MASK[$pos_from] & $all_figures_mask;
        if ( $m_w_queen_rook & ($cross_mask & (int)-$cross_mask) ) {
            return true;
        }
        
        // луч вниз
        $cross_mask = Masks::VERT_DOWN_MASK[$pos_from] & $all_figures_mask;
        if ( $cross_mask && $m_w_queen_rook && ($m_w_queen_rook & Functions::getHighestBit($cross_mask)) ) {
            return true;
        }

        // луч вверх влево
        $cross_mask = (int)Masks::DIAG_UP_LEFT[$pos_from] & $all_figures_mask;
        if ( $m_w_queen_bishop & ($cross_mask & (int)-$cross_mask) ) {
            return true;
        }

        // луч вверх вправо
        $cross_mask = Masks::DIAG_UP_RIGHT[$pos_from] & $all_figures_mask;
        if ( $m_w_queen_bishop & ($cross_mask & (int)-$cross_mask) ) {
            return true;
        }

        // луч вниз влево
        $cross_mask = Masks::DIAG_DOWN_LEFT[$pos_from] & $all_figures_mask;
        if ( $cross_mask  && $m_w_queen_bishop && ($m_w_queen_bishop & Functions::getHighestBit($cross_mask)) ) {
            return true;
        }

        // луч вниз вправо
        $cross_mask = Masks::DIAG_DOWN_RIGHT[$pos_from] & $all_figures_mask;
        if ( $cross_mask && $m_w_queen_bishop && ($m_w_queen_bishop & Functions::getHighestBit($cross_mask)) ) {
            return true;
        }

        // угроз не обнаружили, возвращаем false
        return false;
    }
}
