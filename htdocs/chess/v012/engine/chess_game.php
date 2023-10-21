<?php
require_once('constants.php');
require_once('session_storage.php');
require_once('game_state.php');
require_once('functions.php');
require_once('figure_factory.php');
require_once('figure.php');
require_once('move_generator.php');
require_once('board_position.php');
require_once('computer_ai.php');

class ChessGame {
    private $storage;
    private $game_state;

    public function __construct() {
        $this->storage = new SessionStorage();
    }

    public function loadGame() {
        $this->game_state = $this->storage->loadGameState();
    }

    public function saveGame() {
        $this->storage->saveGameState($this->game_state);
    }

    public function createNewGame($human_color) {
        $this->game_state = new GameState();
        $this->game_state->position = $this->getInitPosition();
        $this->game_state->current_player_color = COLOR_WHITE; // белые ходят
        $this->game_state->enable_castling_white_king = true; // возможна короткая рокировка у белых
        $this->game_state->enable_castling_white_queen = true; // возможна длинная рокировка у белых
        $this->game_state->enable_castling_black_king = true; // возможна короткая рокировка у чёрных
        $this->game_state->enable_castling_black_queen = true; // возможна длинная рокировка у чёрных
        $this->game_state->crossed_field = null;
        $this->game_state->non_action_semimove_counter = 0;
        $this->game_state->move_number = 1; // номер хода - 1

        $this->game_state->human_color = ($human_color == COLOR_BLACK ? COLOR_BLACK : COLOR_WHITE);
        $this->game_state->prev_move_from = null;
        $this->game_state->prev_move_to = null;
        $this->game_state->text_state = 'Новая партия создана. '.($this->game_state->human_color == $this->game_state->current_player_color ? 'Ваш ход' : 'Ждите мой ход');

        $this->saveGame();
    }

    private function getInitPosition() {
        return array(
            FG_ROOK+COLOR_BLACK, FG_KNIGHT+COLOR_BLACK, FG_BISHOP+COLOR_BLACK, FG_QUEEN+COLOR_BLACK, FG_KING+COLOR_BLACK, FG_BISHOP+COLOR_BLACK, FG_KNIGHT+COLOR_BLACK, FG_ROOK+COLOR_BLACK,
            FG_PAWN+COLOR_BLACK, FG_PAWN+COLOR_BLACK,   FG_PAWN+COLOR_BLACK,   FG_PAWN+COLOR_BLACK,  FG_PAWN+COLOR_BLACK, FG_PAWN+COLOR_BLACK,   FG_PAWN+COLOR_BLACK,   FG_PAWN+COLOR_BLACK,
            FG_NONE,             FG_NONE,               FG_NONE,               FG_NONE,              FG_NONE,             FG_NONE,               FG_NONE,               FG_NONE,
            FG_NONE,             FG_NONE,               FG_NONE,               FG_NONE,              FG_NONE,             FG_NONE,               FG_NONE,               FG_NONE,
            FG_NONE,             FG_NONE,               FG_NONE,               FG_NONE,              FG_NONE,             FG_NONE,               FG_NONE,               FG_NONE,
            FG_NONE,             FG_NONE,               FG_NONE,               FG_NONE,              FG_NONE,             FG_NONE,               FG_NONE,               FG_NONE,
            FG_PAWN+COLOR_WHITE, FG_PAWN+COLOR_WHITE,   FG_PAWN+COLOR_WHITE,   FG_PAWN+COLOR_WHITE,  FG_PAWN+COLOR_WHITE, FG_PAWN+COLOR_WHITE,    FG_PAWN+COLOR_WHITE,  FG_PAWN+COLOR_WHITE,
            FG_ROOK+COLOR_WHITE, FG_KNIGHT+COLOR_WHITE, FG_BISHOP+COLOR_WHITE, FG_QUEEN+COLOR_WHITE, FG_KING+COLOR_WHITE, FG_BISHOP+COLOR_WHITE, FG_KNIGHT+COLOR_WHITE, FG_ROOK+COLOR_WHITE
        );
    }

    public function makeMove($cell_index_from, $cell_index_to, $validate_move=true, $to_figure_string=null) {
        if ($validate_move) {
            $color = $this->game_state->current_player_color;
            $figure_code = $this->game_state->position[$cell_index_from];
            if ($figure_code === FG_NONE || Functions::color($figure_code) != $color) {
                $this->game_state->text_state = 'Недопустимый ход: Вы можете ходить только фигурами своего цвета';
                return;
            }
        }
        $figure_factory = new FigureFactory();
        $figure = $figure_factory->create($this->game_state, $cell_index_from);
        switch ($to_figure_string) {
            case 'rook':
                $to_figure = FG_ROOK;
                break;
            case 'bishop':
                $to_figure = FG_BISHOP;
                break;
            case 'knight':
                $to_figure = FG_KNIGHT;
                break;
            default:
                $to_figure = FG_QUEEN;
        }
        if ($figure->makeMove($cell_index_to, $validate_move, $to_figure)) {
            $this->saveGame();
        }
    }

    public function getClientJsonGameState() {
        if (!$this->game_state) {
            return null;
        }
        $is_human_move = $this->game_state->current_player_color == $this->game_state->human_color;
        return array(
            'position' =>$this->game_state->position,
            'is_human_move' => $is_human_move,
            'human_color' => ($this->game_state->human_color == COLOR_BLACK ? 'b' : 'w'),
            'move_number' => $this->game_state->move_number,
            'prev_move_from' => $this->game_state->prev_move_from,
            'prev_move_to' => $this->game_state->prev_move_to,
            'text_state' => $this->game_state->text_state,
            'available_moves' => $is_human_move ? $this->generateAvailableMoves() : array()
        );
    }

    public function generateAvailableMoves() {
        $move_generator = new MoveGenerator($this->game_state);
        return $move_generator->generateAllMoves();
    }

    public function makeComputerMove() {
        $ai = new ComputerAI($this->game_state);
        $move = $ai->getGenerateMove();
        if (!$move) {
            if ($this->isKingUnderAttack($this->game_state->current_player_color)) {
                $text_state = 'Мат. Вы выиграли!';
            } else {
                $text_state = 'Пат. Ничья.';
            }
            $this->game_state->text_state = $text_state;
            $this->saveGame();
            return;
        }
        if (!isset($move['from']) || !isset($move['to'])) {
            $this->game_state->text_state = 'Ошибка! Компьютер возвратил ход в неверном формате';
            return;
        }
        $this->makeMove($move['from'], $move['to'], false);

        // проверим - есть ли ходы у человека?
        $available_moves = $this->generateAvailableMoves();
        if (count($available_moves) === 0) {
            if ($this->isKingUnderAttack($this->game_state->current_player_color)) {
                $text_state = 'Мат. Компьютер выиграл';
            } else {
                $text_state = 'Пат. Ничья.';
            }
            $this->game_state->text_state = $text_state;
            $this->saveGame();
        }
    }

    protected function isKingUnderAttack($color) {
        $board_position = new BoardPosition();
        $board_position->setPosition($this->game_state->position);
        return $board_position->isKingUnderAttack($color);
    }
}