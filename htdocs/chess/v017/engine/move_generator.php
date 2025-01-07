<?php

class MoveGenerator {
    private $game_state;

    public function __construct(GameState $game_state) {
        $this->game_state = $game_state;
        $this->game_state->setFigures();
    }

    public function generateAllMoves() {
        $moves = array();
        $flat_moves = $this->generateAllMovesFlat();
        foreach($flat_moves as $move) {
            $field_from = $move[0];
            if (!isset($moves[$field_from])) {
                $moves[$field_from] = array($move[1]);
            } else {
                $moves[$field_from][] = $move[1];
            }
        }
        return $moves;
    }

    public function generateSortedMoves() {
        $moves = $this->generateAllMovesFlat();
        if (count($moves) === 0) {
            return $moves;
        }
        usort($moves, [$this, 'cmpMoves']);
        return $moves;
    }

    protected function generateAllMovesFlat() {
        $moves = array();
        $color = $this->game_state->current_player_color;
        foreach (Functions::$pos8To16convert as $i) {
            $figure_code = $this->game_state->position[$i];
            if ($figure_code === FG_NONE || Functions::color($figure_code) != $color) {
                continue;
            }
            $figure_factory = new FigureFactory();
            $figure = $figure_factory->create($this->game_state, $i);
            $figure_moves = $figure->getAvailableMoves();
            $moves = array_merge($moves, $figure_moves);
        }
        return $moves;
    }

    private function cmpMoves($a, $b) {
        /* Структура сравниваемых переменных (индекс - смысл):
         * 0 - поле "откуда"
         * 1 - поле "куда"
         * 2 - вес фигуры, которая ходит
         * 3 - вес фигуры, которую берут
         */
        $taken_figure_weigth_a = $a[3];
        $taken_figure_weigth_b = $b[3];
        if ($taken_figure_weigth_a === $taken_figure_weigth_b) {
            if ($taken_figure_weigth_a === Figure::FIGURE_WEIGHTS[FG_NONE]) {
                // Оба сравниваемых хода - не взятия фигур.
                // Лучший ход будет фигурой с бОльшим весом
                return $b[2] <=> $a[2];
            } else {
                // Оба сравниваемых хода берут фигуру с одинаковым весом (одинаково ценные фигуры)
                // Лучший ход будет более лёгкой фигурой (рискует менее ценная фигура)
                return $a[2] <=> $b[2];
            }
        }
        // Оба сравниваемых хода - взятия фигур с разным весом. Лучший будет тот, в котором берётся более ценная фигура
        return $taken_figure_weigth_b <=> $taken_figure_weigth_a;
    }
}
