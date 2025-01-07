<?php

require_once('figures/king.php');
require_once('figures/queen.php');
require_once('figures/rook.php');
require_once('figures/bishop.php');
require_once('figures/knight.php');
require_once('figures/pawn.php');

abstract class Figure {
    const FIGURE_WEIGHTS = array(
        FG_KING => 2,
        FG_QUEEN => 6,
        FG_ROOK => 5,
        FG_BISHOP => 4,
        FG_KNIGHT => 3,
        FG_PAWN => 1,
        FG_NONE => 0
    );

    protected GameState $game_state;
    protected int $position_index;
    protected int $figure; // фигура, для которой создан экземпляр класса
    protected int $figure_weight; // "вес" фигуры, для которой создан экземпляр класса
    protected int $color; // цвет фигуры
    protected int $enemy_color; // цвет фигур противника

    public function __construct(GameState $game_state, int $position_index) {
        $this->game_state = $game_state;
        $this->position_index = $position_index;
        $this->figure = $game_state->position[$position_index];
        $this->figure_weight = self::FIGURE_WEIGHTS[ Functions::figureType($this->figure) ];
        $this->color = Functions::color($this->figure);
        $this->enemy_color = $this->color == COLOR_BLACK ? COLOR_WHITE : COLOR_BLACK;
    }

    abstract public function getCandidateMoves();

    public function getAvailableMoves() {
        /**
         * допустимые ходы - числовой массив, элементы которого - числовые массивы с такой структурой (индекс - информация):
         * 0 - индекс поля "откуда"
         * 1 - индекс поля "куда"
         * 2 - вес фигуры, которая ходит
         * 3 - вес фигуры, которую берут
         */
        $moves = array();
        $board_position = new BoardPosition();
        $our_king_position = $this->game_state->getKingPosition($this->color);

        $candidate_moves = $this->getCandidateMoves();
        foreach($candidate_moves as $to_index) {
            $to_position = $this->game_state->position; // копируем позицию
            $to_position[$this->position_index] = FG_NONE; // убираем фигуру из текущей позиции
            $beat_figure = $to_position[$to_index];
            $beat_figure_weight = self::FIGURE_WEIGHTS[ Functions::figureType($beat_figure) ];
            $to_position[$to_index] = $this->figure; // перемещаем фигуру на выбранное поле. Если на том поле что-то стояло - оно "убирается"
            $board_position->setPosition($to_position);
            if ($board_position->isFieldUnderAttack($our_king_position, $this->enemy_color)) {
                // ход недопустим, т.к. после него наш король оказывается под атакой
                continue;
            }
            $moves[] = array($this->position_index, $to_index, $this->figure_weight, $beat_figure_weight);
        }
        return $moves;
    }

    public function makeMove($to_cell_index, $validate_move=true) {
        // проверяем - а корректный ли ход нам передали?
        $this->game_state->setFigures();
        if ($validate_move && !$this->isValidMove($to_cell_index)) {
            $this->game_state->text_state = 'Недопустимый ход. Сделайте корректный ход';
            return false;
        }

        $to_figure = $this->game_state->position[$to_cell_index];

        // делаем изменение в позиции - двигаем фигуру
        $this->game_state->position[$this->position_index] = FG_NONE;
        $this->game_state->position[$to_cell_index] = $this->figure;

        // Обnull-яем "пересекаемое поле". Для хода пешки это поведение изменим
        $this->game_state->crossed_field = null;
        
        // изменяем счётчик числа полуходов, после последнего взятия фигуры или движения пешки
        if ($to_figure !== FG_NONE) {
            $this->game_state->non_action_semimove_counter = 0;
        } else {
            $this->game_state->non_action_semimove_counter++;
        }

        // изменяем счётчик ходов и очередь хода
        if ($this->color == COLOR_BLACK) {
            $this->game_state->move_number++;
            $this->game_state->current_player_color = COLOR_WHITE;
        } else {
            $this->game_state->current_player_color = COLOR_BLACK;
        }

        // изменяем информацию о предыдущих полях "откуда" и "куда"
        $this->game_state->prev_move_from = $this->position_index;
        $this->game_state->prev_move_to = $to_cell_index;

        // изменяем текстовое описание состояния игры
        if ($this->game_state->human_color == $this->color) {
            $this->game_state->text_state = "#{$this->game_state->move_number} Ход компьютера, подождите";
        } else {
            $this->game_state->text_state = "#{$this->game_state->move_number} Ваш ход.";
        }

        // если пошли на начальное поле какой-либо ладьи ("съели" например), то надо снять флаг возможности соответствующей рокировки
        switch ($to_cell_index) {
            case Rook::WHITE_KING_ROOK_INIT_POSITION:
                $this->game_state->enable_castling_white_king = false;
                break;
            case Rook::WHITE_QUEEN_ROOK_INIT_POSITION:
                $this->game_state->enable_castling_white_queen = false;
                break;
            case Rook::BLACK_KING_ROOK_INIT_POSITION:
                $this->game_state->enable_castling_black_king = false;
                break;
            case Rook::BLACK_QUEEN_ROOK_INIT_POSITION:
                $this->game_state->enable_castling_black_queen = false;
                break;
        }
        return true;
    }

    public function getLongRangeCandidateMoves(array $shifts) {
        $moves = array();
        foreach ($shifts as $shift) {
            $continue_shift = true;
            $to_index = $this->position_index;
            while ($continue_shift) {
                $to_index += $shift;
                $to_figure = $this->game_state->position[$to_index];
                if ($to_figure === FG_NONE) {
                    $moves[] = $to_index;
                    continue;
                }
                $to_figure_color = Functions::color($to_figure);
                if ($to_figure_color == $this->enemy_color) {
                    $moves[] = $to_index;
                }
                $continue_shift = false;
            }
        }
        return $moves;
    }

    public function getShortRangeCandidateMoves(array $shifts) {
        $moves = array();
        foreach($shifts as $shift) {
            $to_index = $this->position_index + $shift;
            $to_color = Functions::color($this->game_state->position[$to_index]);
            if ($to_color == COLOR_NONE || $to_color == $this->enemy_color) {
                $moves[] = $to_index;
            }
        }
        return $moves;
    }

    protected function isValidMove($to_cell_index) {
        $available_moves = $this->getAvailableMoves();
        foreach($available_moves as $move) {
            if ($to_cell_index === $move[1]) {
                return true;
            }
        }
        return false;
    }
}
