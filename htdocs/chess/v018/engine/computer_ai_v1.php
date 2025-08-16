<?php

class ComputerAIV1 {
    const MAX_FORCE_DEPTH = 8;
    protected $search_depth = 0;
    protected GameState $game_state;
    private $position_counter = 0;
    private $init_search_depth = 0;

    public function __construct(GameState $game_state, $search_depth = 2.4) {
        $this->game_state = $game_state;
        $this->search_depth = max($search_depth, 1);
        $this->init_search_depth = $this->search_depth;
    }

    public function getGenerateMove() {
        set_time_limit(90);

        //$t1 = microtime(true);

        $search_best_move_result = $this->searchBestMove($this->game_state, $this->search_depth, PHP_INT_MIN, PHP_INT_MAX, 0);

        //$t2 = microtime(true);
        //$delta = round($t2 - $t1, 1);
        //$speed = $t2>$t1 ? round($this->position_counter / ($t2 - $t1)) : 1000000;
        //error_log('Оценено позиций: '.$this->position_counter.' Score='.$search_best_move_result['score']." Time=$delta sec. Speed = $speed\r\n", 3, 'log.txt');
        $move = $search_best_move_result['best_move'];
        if (!$move) {
            return false;
        }
        return array(
            'from' => (int)(63 - log($move[0], 2)),
            'to' => (int)(63 - log($move[1], 2))
        );        
    }

    /**
     * Метод оценивает позицию делая только ходы - взятия с ограничением до MAX_FORCE_DEPTH
     */
    private function getForceScore(GameState $game_state, $alpha, $beta, $current_depth) {
        $move_generator = new MoveGenerator($game_state);
        $available_moves = $move_generator->generateAllMovesFlat();
        $no_available_moves = (count($available_moves) === 0);
        
        $this->position_counter += 1;
        $score = $game_state->calcStaticScoreEx($no_available_moves, $current_depth);
        if ($no_available_moves || $current_depth > self::MAX_FORCE_DEPTH) {
            return $score;
        }

        if ($score > $alpha) {
            $alpha = $score;
        }
        if ($alpha > $beta) {
            return $alpha;
        }
        foreach ($available_moves as $move) {
            if (($move[2] & MOVE_TYPE_BEAT) == 0) {
                continue;
            }
            $next_game_state = clone $game_state;
            $next_game_state->makeMove($move);
            $tmp_score = - $this->getForceScore($next_game_state, -$beta, -$alpha, $current_depth+1);
            if ($tmp_score > $alpha) {
                $alpha = $tmp_score;
            }
            if ($alpha >= $beta) {
                return $alpha;
            }
        }
        return $alpha;
    }

    /**
     * Метод ищет "лучший ход" и возвращает оценку позиции
     * $game_state - позиция, для которой ищется лучший ход
     * $depth - глубина, на которую надо делать перебор позиций
     * $alpha, $beta - альфа, бета границы отсечения
     * Возвращается массив с двумя ключами:
     * score - оценка позиции
     * best_move - найденный "лучший ход" (массив с двумя элементами - индексы полей "откуда" и "куда") или null
     */
    private function searchBestMove(GameState $game_state, $depth, $alpha, $beta, $current_depth=0) {
        if ($game_state->checkDraw()) {
            return array('score' => 0, 'best_move' => null);
        }
        if ($depth < 0) {
            return array('score' => $this->getForceScore($game_state, $alpha, $beta, $current_depth+1), 'best_move' => null);
        }

        $move_generator = new MoveGenerator($game_state);
        $available_moves = $move_generator->generateAllMovesFlat();
        $no_available_moves = (count($available_moves) === 0);
        if ($no_available_moves) {
            $this->position_counter += 1;
            return array('score' => $game_state->calcStaticScoreEx($no_available_moves, $current_depth), 'best_move' => null);
        }

        $delta_depth = 1;
        $moves_qty= count($available_moves);
        switch ($moves_qty) {
            case 1:
                if ($depth == $this->init_search_depth) {
                    $best_move = reset($available_moves);
                    return array('score' => 0, 'best_move' => $best_move);
                }
                $delta_depth = 0;
                break;
            case 2:
                $delta_depth = 0.4;
                break;
            case 3:
                $delta_depth = 0.55;
                break;
            case 4:
                $delta_depth = 0.8;
                break;
        }

        $best_move = reset($available_moves);
        foreach ($available_moves as $move) {
            $next_game_state = clone $game_state;
            $next_game_state->makeMove($move);

            if ($next_game_state->isKingUnderAttack()) {
                $new_depth = $depth - min(0.30, $delta_depth);
            } elseif ($move[2] & MOVE_TYPE_BEAT) {
                $new_depth = $depth - min(0.45, $delta_depth);
            } else {
                $new_depth = $depth - $delta_depth;
            }
            $search_best_move_result = $this->searchBestMove($next_game_state, $new_depth, -$beta, -$alpha, $current_depth+1);
            $tmp_score = - $search_best_move_result['score'];

            if ($tmp_score > $alpha) {
                $alpha = $tmp_score;
                $best_move = $move;
            }
            if ($alpha >= $beta) {
                break;
            }
        }
        return array('score' => $alpha, 'best_move' => $best_move);
    }
}