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
require_once('computer_ai_v1.php');

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

        $this->game_state->savePositionHash();
        if ($this->isHumanMove()) {
            $this->game_state->savePositionHistory();
        }
        $this->saveGame();
    }

    private function getInitPosition() {
        # FG_NONE+COLOR_OVER = 3 - поле за доской
        # FG_PAWN+COLOR_BLACK = 24 + 2 = 26 - чёрная пешка
        # FG_PAWN+COLOR_WHITE = 24 + 1 = 25 - белая пешка
        # Аналогично FG_ROOK = 12 - ладья без цвета, к ней прибавляется цвет: 1 - белый, 2 - чёрный
        # FG_KNIGHT = 20 - конь, FG_BISHOP = 16 - слон, FG_QUEEN = 8 - ферзь, FG_KING = 4 - король
        return array(
            3, 3, 3, 3,     3,  3,  3,  3,  3,  3,  3,  3,    3, 3, 3, 3,
            3, 3, 3, 3,     3,  3,  3,  3,  3,  3,  3,  3,    3, 3, 3, 3,
            3, 3, 3, 3,     3,  3,  3,  3,  3,  3,  3,  3,    3, 3, 3, 3,
            3, 3, 3, 3,     3,  3,  3,  3,  3,  3,  3,  3,    3, 3, 3, 3,

            3, 3, 3, 3,    14, 22, 18, 10,  6, 18, 22, 14,    3, 3, 3, 3,
            3, 3, 3, 3,    26, 26, 26, 26, 26, 26, 26, 26,    3, 3, 3, 3,
            3, 3, 3, 3,     0,  0,  0,  0,  0,  0,  0,  0,    3, 3, 3, 3,
            3, 3, 3, 3,     0,  0,  0,  0,  0,  0,  0,  0,    3, 3, 3, 3,
            3, 3, 3, 3,     0,  0,  0,  0,  0,  0,  0,  0,    3, 3, 3, 3,
            3, 3, 3, 3,     0,  0,  0,  0,  0,  0,  0,  0,    3, 3, 3, 3,
            3, 3, 3, 3,    25, 25, 25, 25, 25, 25, 25, 25,    3, 3, 3, 3,
            3, 3, 3, 3,    13, 21, 17,  9,  5, 17, 21, 13,    3, 3, 3, 3,

            3, 3, 3, 3,     3,  3,  3,  3,  3,  3,  3,  3,    3, 3, 3, 3,
            3, 3, 3, 3,     3,  3,  3,  3,  3,  3,  3,  3,    3, 3, 3, 3,
            3, 3, 3, 3,     3,  3,  3,  3,  3,  3,  3,  3,    3, 3, 3, 3,
            3, 3, 3, 3,     3,  3,  3,  3,  3,  3,  3,  3,    3, 3, 3, 3
        );
    }

    public function isHumanMove() {
        return $this->game_state->isHumanMove();
    }

    public function makeMove($cell_index_from, $cell_index_to, $validate_move=true, $to_figure_string=null) {
        if ($validate_move) {
            $color = $this->game_state->current_player_color;
            $figure_code = $this->game_state->position[$cell_index_from];
            if (Functions::color($figure_code) != $color) {
                $this->game_state->text_state = 'Недопустимый ход: Вы можете ходить только фигурами своего цвета';
                return;
            }
        }
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
        if ($this->game_state->makeMove($cell_index_from, $cell_index_to, $validate_move, $to_figure)) {
            if ($this->isHumanMove()) {
                $this->game_state->savePositionHistory();
            }
            $this->saveGame();
        }
    }

    public function getClientJsonGameState() {
        if (!$this->game_state) {
            return null;
        }
        $is_human_move = $this->game_state->current_player_color == $this->game_state->human_color;
        return array(
            'position' => Functions::convertPosition16To8($this->game_state->position),
            'is_human_move' => $is_human_move,
            'human_color' => ($this->game_state->human_color == COLOR_BLACK ? 'b' : 'w'),
            'move_number' => $this->game_state->move_number,
            'prev_move_from' => is_null($this->game_state->prev_move_from) ? null : Functions::pos16ToPos8($this->game_state->prev_move_from),
            'prev_move_to' => is_null($this->game_state->prev_move_to) ? null : Functions::pos16ToPos8($this->game_state->prev_move_to),
            'text_state' => $this->game_state->text_state,
            'available_moves' => $is_human_move ? Functions::convertMoves16To8($this->generateAvailableMoves()) : array()
        );
    }

    public function generateAvailableMoves() {
        if ($this->game_state->checkDraw()) {
            return array();
        }
        $move_generator = new MoveGenerator($this->game_state);
        return $move_generator->generateAllMoves();
    }

    public function makeComputerMove() {
        $ai = new ComputerAIV1($this->game_state);
        $move = $ai->getGenerateMove();
        if (!$move) {
            if ($this->game_state->isKingUnderAttack()) {
                $text_state = 'Мат. Вы выиграли!';
            } else {
                $text_state = 'Пат. Ничья.';
            }
            $this->game_state->text_state = $text_state;
            if ($this->game_state->positionRepeatCount() === 0) {
                $this->game_state->savePositionHistory();
            }
            $this->saveGame();
            return;
        }
        if (!isset($move['from']) || !isset($move['to'])) {
            $this->game_state->text_state = 'Ошибка! Компьютер возвратил ход в неверном формате';
            return;
        }

        $this->makeMove($move['from'], $move['to'], false);
        if ($this->game_state->checkDraw()) {
            $this->saveGame();
            return;
        }

        // проверим - есть ли ходы у человека?
        $available_moves = $this->generateAvailableMoves();
        if (count($available_moves) === 0) {
            if ($this->game_state->isKingUnderAttack()) {
                $text_state = 'Мат. Компьютер выиграл';
            } else {
                $text_state = 'Пат. Ничья.';
            }
            $this->game_state->text_state = $text_state;
            $this->saveGame();
        }
    }

    public function moveBack() {
        $this->game_state->moveBack();
        $this->saveGame();
    }
}
