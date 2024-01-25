<?php

class ComputerAIV1 {
    protected $search_depth = 0;
    protected GameState $game_state;
    private $position_counter = 0;

    public function __construct(GameState $game_state, $search_depth = 3) {
        $this->game_state = $game_state;
        $this->search_depth = max($search_depth, 1);
    }

    public function getGenerateMove() {
        $search_best_move_result = $this->searchBestMove($this->game_state, $this->search_depth);
        // error_log('Оценено позиций: '.$this->position_counter.' Score='.$search_best_move_result['score']."\r\n", 3, 'log.txt');
        $move = $search_best_move_result['best_move'];
        if (!$move) {
            return false;
        }
        return array('from' => $move[0], 'to' => $move[1]);        
    }

    /**
     * Метод ищет "лучший ход" и возвращает оценку позиции
     * $game_state - позиция, для которой ищется лучший ход
     * $depth - глубина, на которую надо делать перебор позиций
     * Возвращается массив с двумя ключами:
     * score - оценка позиции
     * best_move - найденный "лучший ход" (массив с двумя элементами - индексы полей "откуда" и "куда") или null
     */
    private function searchBestMove(GameState $game_state, $depth) {
        $move_generator = new MoveGenerator($game_state);
        $available_moves = $move_generator->generateAllMovesFlat();
        $no_available_moves = (count($available_moves) === 0);
        if ($depth === 0 || $no_available_moves) {
            $this->position_counter += 1;
            return array('score' => $game_state->calcStaticScore($no_available_moves), 'best_move' => null);
        }

        $score = PHP_INT_MIN;
        $best_move = reset($available_moves);
        foreach ($available_moves as $move) {
            $cell_from = $move[0];
            $cell_to = $move[1];
            $next_game_state = clone $game_state;
            $next_game_state->makeMove($cell_from, $cell_to, false);
            $search_best_move_result = $this->searchBestMove($next_game_state, $depth-1);
            $tmp_score = - $search_best_move_result['score'];
            if ($tmp_score > $score) {
                $score = $tmp_score;
                $best_move = $move;
            }
        }
        return array('score' => $score, 'best_move' => $best_move);
    }
}