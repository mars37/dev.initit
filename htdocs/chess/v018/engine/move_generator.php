<?php

class MoveGenerator {
    private $game_state;
    private $beat_pawns;
    private $beat_knights;
    private $beat_bishops;
    private $beat_rooks;
    private $beat_queens;
    private $beat_king;
    private $move_pawns;
    private $move_knights;
    private $move_bishops;
    private $move_rooks;
    private $move_queens;
    private $move_king;

    private $all_figures_mask;

    public function __construct(GameState $game_state) {
        $this->game_state = $game_state;
    }

    public function generateAllMoves() {
        $moves = array();
        $flat_moves = $this->generateAllMovesFlat();
        foreach($flat_moves as $move) {
            $field_from = (int)(63 - log($move[0], 2));
            $field_to = (int)(63 - log($move[1], 2));
            if (!isset($moves[$field_from])) {
                $moves[$field_from] = array($field_to);
            } else {
                $moves[$field_from][] = $field_to;
            }
        }
        return $moves;
    }

    public function generateAllMovesMasks() {
        $this->beat_pawns = array();
        $this->beat_knights = array();
        $this->beat_bishops = array();
        $this->beat_rooks = array();
        $this->beat_queens = array();
        $this->beat_king = array();
        $this->move_pawns = array();
        $this->move_knights = array();
        $this->move_bishops = array();
        $this->move_rooks = array();
        $this->move_queens = array();
        $this->move_king = array();
        $this->all_figures_mask = (int)($this->game_state->m_all_white_figures | $this->game_state->m_all_black_figures);
        if ($this->game_state->current_player_color == COLOR_WHITE) {
            $this->generateWhiteMoves();
        } else {
            $this->generateBlackMoves();
        }
    }

    public function generateAllMovesFlat() {
        $this->generateAllMovesMasks();
        return array_merge(
            $this->beat_pawns, $this->beat_knights, $this->beat_bishops, $this->beat_rooks, $this->beat_queens, $this->beat_king,
            $this->move_queens, $this->move_rooks, $this->move_bishops, $this->move_knights, $this->move_pawns, $this->move_king
        );
    }

    public function generateAllBeatsFlat() {
        $this->generateAllMovesMasks();
        return array_merge($this->beat_pawns, $this->beat_knights, $this->beat_bishops, $this->beat_rooks, $this->beat_queens, $this->beat_king);
    }

    private function generateWhiteMoves() {
        $this->addWhiteQueenMoves();
        $this->addWhiteRookMoves();
        $this->addWhiteBishopMoves();
        $this->addWhiteKhightMoves();
        $this->addWhitePawnMoves();
        $this->addWhiteKingMoves();
    }

    private function generateBlackMoves() {
        $this->addBlackQueenMoves();
        $this->addBlackRookMoves();
        $this->addBlackBishopMoves();
        $this->addBlackKhightMoves();
        $this->addBlackPawnMoves();
        $this->addBlackKingMoves();
    }

    private function addWhiteQueenMoves() {
        $m_queens_set = (int)$this->game_state->m_w_queen;
        while ($m_queens_set) {
            $m_queen = $m_queens_set & (int)-$m_queens_set;
            $pos_from = (int)(63 - log($m_queen, 2));

            // луч вверх
            $m_move = (int)Masks::VERT_UP_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой, атакованная ферзём (может быть и своя)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - чёрная фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ($m_ray_end & FIELD_A8) ? NOT_A8 : $m_ray_end - 1; // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вверх"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч влево вверх 
            $m_move = (int)Masks::DIAG_UP_LEFT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой, атакованной ферзём (может быть и своя)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - чёрная фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ($m_ray_end & FIELD_A8) ? NOT_A8 : $m_ray_end - 1; // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "влево вверх"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вправо вверх
            $m_move = (int)Masks::DIAG_UP_RIGHT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - чёрная фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= (int)($m_ray_end - 1); // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вправо вверх"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вправо
            $m_move = (int)Masks::HOR_RIGHT_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - чёрная фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вправо"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч влево
            $m_move = (int)Masks::HOR_LEFT_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - чёрная фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ($m_ray_end & FIELD_A8) ? NOT_A8 : $m_ray_end - 1; // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "влево"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вниз
            $m_move = (int)Masks::VERT_DOWN_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - чёрная фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вниз"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вниз вправо
            $m_move = (int)Masks::DIAG_DOWN_RIGHT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - чёрная фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вниз вправо"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вниз влево
            $m_move = (int)Masks::DIAG_DOWN_LEFT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - чёрная фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вниз влево"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            $m_queens_set &= ~$m_queen;
        }
    }

    private function addWhiteRookMoves() {
        $m_rooks_set = (int)$this->game_state->m_w_rook;
        while ($m_rooks_set) {
            $m_rook = $m_rooks_set & (int)-$m_rooks_set;
            $pos_from = (int)(63 - log($m_rook, 2));
            
            // луч вверх
            $m_move = (int)Masks::VERT_UP_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой, атакованная ладьёй (может быть и своя)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteRookBeat($m_rook, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ладья - чёрная фигура, значит есть ход - взятие
                    $this->beat_rooks[] = array($m_rook, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ($m_ray_end & FIELD_A8) ? NOT_A8 : $m_ray_end - 1; // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вверх"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteRookMove($m_rook, $m_bit_move)) {
                    $this->move_rooks[] = array($m_rook, $m_bit_move,MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч влево
            $m_move = (int)Masks::HOR_LEFT_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой, атакованная ладьёй (может быть и своя)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteRookBeat($m_rook, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ладья - чёрная фигура, значит есть ход - взятие
                    $this->beat_rooks[] = array($m_rook, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ($m_ray_end & FIELD_A8) ? NOT_A8 : $m_ray_end - 1;
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "влево"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteRookMove($m_rook, $m_bit_move)) {
                    $this->move_rooks[] = array($m_rook, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вправо
            $m_move = (int)Masks::HOR_RIGHT_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteRookBeat($m_rook, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ладья - чёрная фигура, значит есть ход - взятие
                    $this->beat_rooks[] = array($m_rook, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вправо"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteRookMove($m_rook, $m_bit_move)) {
                    $this->move_rooks[] = array($m_rook, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вниз
            $m_move = (int)Masks::VERT_DOWN_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteRookBeat($m_rook, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ладья - чёрная фигура, значит есть ход - взятие
                    $this->beat_rooks[] = array($m_rook, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вниз"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteRookMove($m_rook, $m_bit_move)) {
                    $this->move_rooks[] = array($m_rook, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            $m_rooks_set &= ~$m_rook;
        }
    }

    private function addWhiteBishopMoves() {
        $m_bishops_set = (int)$this->game_state->m_w_bishop;
        while ($m_bishops_set) {
            $m_bishop = $m_bishops_set & (int)-$m_bishops_set;
            $pos_from = (int)(63 - log($m_bishop, 2));
            
            // луч влево вверх 
            $m_move = (int)Masks::DIAG_UP_LEFT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой, атакованная слоном (может быть и своя)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteBishopBeat($m_bishop, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает слон - чёрная фигура, значит есть ход - взятие
                    $this->beat_bishops[] = array($m_bishop, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ($m_ray_end & FIELD_A8) ? NOT_A8 : $m_ray_end - 1; // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "влево вверх"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteBishopMove($m_bishop, $m_bit_move)) {
                    $this->move_bishops[] = array($m_bishop, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вправо вверх
            $m_move = (int)Masks::DIAG_UP_RIGHT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteBishopBeat($m_bishop, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает слон - чёрная фигура, значит есть ход - взятие
                    $this->beat_bishops[] = array($m_bishop, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= (int)($m_ray_end - 1); // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вправо вверх"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteBishopMove($m_bishop, $m_bit_move)) {
                    $this->move_bishops[] = array($m_bishop, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вниз влево
            $m_move = (int)Masks::DIAG_DOWN_LEFT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteBishopBeat($m_bishop, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает слон - чёрная фигура, значит есть ход - взятие
                    $this->beat_bishops[] = array($m_bishop, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вниз влево"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteBishopMove($m_bishop, $m_bit_move)) {
                    $this->move_bishops[] = array($m_bishop, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вниз вправо
            $m_move = (int)Masks::DIAG_DOWN_RIGHT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_black_figures) && $this->game_state->checkWhiteBishopBeat($m_bishop, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает слон - чёрная фигура, значит есть ход - взятие
                    $this->beat_bishops[] = array($m_bishop, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вниз вправо"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkWhiteBishopMove($m_bishop, $m_bit_move)) {
                    $this->move_bishops[] = array($m_bishop, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            $m_bishops_set &= ~$m_bishop;
        }
    }

    private function addWhiteKhightMoves() {
        $m_knights_set = (int)$this->game_state->m_w_knight;
        while ($m_knights_set) {
            $m_knight = $m_knights_set & (int)-$m_knights_set;
            $pos_from = (int)(63 - log($m_knight, 2));
            $moves_mask = (int)Masks::KHIGHT_MASK[$pos_from];
            // взятия конём
            $m_beat_figures = $moves_mask & $this->game_state->m_all_black_figures;
            while ($m_beat_figures) {
                $m_beat = $m_beat_figures & (int)-$m_beat_figures;
                if ($this->game_state->checkWhiteKnightBeat($m_knight, $m_beat)) {
                    $this->beat_knights[] = array($m_knight, $m_beat, MOVE_TYPE_BEAT);
                }
                $m_beat_figures &= ~$m_beat;    
            }

            // простые перемещения конём
            $m_moves_fields = $moves_mask & ~($this->game_state->m_all_black_figures | $this->game_state->m_all_white_figures);
            while ($m_moves_fields) {
                $m_move = $m_moves_fields & (int)-$m_moves_fields;
                if ($this->game_state->checkWhiteKnightMove($m_knight, $m_move)) {
                    $this->move_knights[] = array($m_knight, $m_move, MOVE_TYPE_SIMPLE);
                }
                $m_moves_fields &= ~$m_move;    
            }

            $m_knights_set &= ~$m_knight;
        }
    }

    private function addWhitePawnMoves() {
        $m_pawns_set = (int)$this->game_state->m_w_pawn; // это все белые пешки
        // проверим взятия влево (без взятия на проходе)
        $m_beats = (int)($m_pawns_set << 9) & ~COL_H;
        $m_beat_figs = $m_beats & $this->game_state->m_all_black_figures;
        while ($m_beat_figs) {
            $m_beat = $m_beat_figs & (int)-$m_beat_figs;
            $m_from = (($m_beat >> 1) & NOT_A8) >> 8;
            if ($this->game_state->checkWhitePawnBeat($m_from, $m_beat)) {
                $this->beat_pawns[] = array($m_from, $m_beat, MOVE_TYPE_BEAT | MOVE_TYPE_PAWN);
            }
            $m_beat_figs &= ~$m_beat;
        }

        // проверим взятия вправо (без взятия на проходе)
        $m_beats = (int)($m_pawns_set << 7) & NOT_COL_A;
        $m_beat_figs = $m_beats & $this->game_state->m_all_black_figures;
        while ($m_beat_figs) {
            $m_beat = $m_beat_figs & (int)-$m_beat_figs;
            $m_from = $m_beat >> 7;
            if ($this->game_state->checkWhitePawnBeat($m_from, $m_beat)) {
                $this->beat_pawns[] = array($m_from, $m_beat, MOVE_TYPE_BEAT | MOVE_TYPE_PAWN);
            }
            $m_beat_figs &= ~$m_beat;
        }

        // проверим взятие на проходе
        if (($m_beat = (int)$this->game_state->m_crossed_field) != 0) {
            // получу маску с пешками, которые бьют проходное поле
            $m_pawnscross = $this->game_state->m_w_pawn & ( (($m_beat >> 7) & ~COL_H) | (($m_beat >> 9) & NOT_COL_A) );
            while ($m_pawnscross) {
                $m_pawn = $m_pawnscross & (int)-$m_pawnscross;
                if ($this->game_state->checkWhitePawnCrossBeat($m_pawn, $m_beat)) {
                    $this->beat_pawns[] = array($m_pawn, $m_beat, MOVE_TYPE_CROSS_BEAT | MOVE_TYPE_PAWN | MOVE_TYPE_BEAT);
                }
                $m_pawnscross &= ~$m_pawn;
            }
        }

        // проверим простые ходы вперёд
        $m_to_step1 = ((int)$this->game_state->m_w_pawn << 8) & ~$this->all_figures_mask; // маска - куда могут пойти пешки на одну клетку
        $m_to_step2 = (int)(($m_to_step1 & HOR_3) << 8) & ~$this->all_figures_mask; // маска - куда могут пойти пешки на две клетки
        // заполним ходы на две клетки
        while ($m_to_step2) {
            $m_to = $m_to_step2 & (int)-$m_to_step2;
            if ($this->game_state->checkWhitePawnMove($m_to>>16, $m_to)) {
                $this->move_pawns[] = array($m_to>>16, $m_to, MOVE_TYPE_PAWN2 | MOVE_TYPE_PAWN);
            }
            $m_to_step2 &= ~$m_to;
        }
        // теперь - ходы на одну клетку
        while ($m_to_step1) {
            $m_to = $m_to_step1 & (int)-$m_to_step1;
            $m_from = ($m_to == FIELD_A8) ? FIELD_A7 : $m_to>>8;
            if ($this->game_state->checkWhitePawnMove($m_from, $m_to)) {
                $this->move_pawns[] = array($m_from, $m_to, MOVE_TYPE_PAWN);
            }
            $m_to_step1 &= ~$m_to;
        }
    }

    private function addWhiteKingMoves() {
        $pos_from = (int)(63 - log($this->game_state->m_w_king, 2));
        $m_around = (int)Masks::KING_HV_MASK[$pos_from] | (int)Masks::KING_DIAG_MASK[$pos_from];
        // взятия королём рассмотрим отдельно
        $m_king_moves = $m_around & $this->game_state->m_all_black_figures;
        while ($m_king_moves) {
            $m_to = $m_king_moves & (int)-$m_king_moves;
            if ($this->game_state->checkWhiteKingBeat($this->game_state->m_w_king, $m_to)) {
                $this->beat_king[] = array($this->game_state->m_w_king, $m_to, MOVE_TYPE_BEAT | MOVE_TYPE_KING);
            }
            $m_king_moves &= ~$m_to;
        }
        // теперь - простые ходы без взятия
        $m_king_moves = $m_around & ~$this->all_figures_mask;
        while ($m_king_moves) {
            $m_to = $m_king_moves & (int)-$m_king_moves;
            if ($this->game_state->checkWhiteKingMove($this->game_state->m_w_king, $m_to)) {
                $this->move_king[] = array($this->game_state->m_w_king, $m_to, MOVE_TYPE_KING);
            }
            $m_king_moves &= ~$m_to;
        }
        // теперь рассмотрим возможность рокировки, ходы добавим в список взятий, чтобы они получили приоритет в порядке рассмотрения ходов
        // короткая рокировка
        if (
            $this->game_state->enable_castling_white_king &&
            (((FIELD_F1 | FIELD_G1) & $this->all_figures_mask) === 0) &&
            !$this->game_state->isAttackedByBlack(FIELD_E1) &&
            !$this->game_state->isAttackedByBlack(FIELD_F1) &&
            !$this->game_state->isAttackedByBlack(FIELD_G1)
        ) {
            $this->beat_king[] = array(FIELD_E1, FIELD_G1, MOVE_TYPE_KING_CASTLING | MOVE_TYPE_KING);
        }
        // длинная рокировка
        if (
            $this->game_state->enable_castling_white_queen &&
            (((FIELD_B1 | FIELD_C1 | FIELD_D1) & $this->all_figures_mask) === 0) &&
            !$this->game_state->isAttackedByBlack(FIELD_E1) &&
            !$this->game_state->isAttackedByBlack(FIELD_D1) &&
            !$this->game_state->isAttackedByBlack(FIELD_C1)
        ) {
            $this->beat_king[] = array(FIELD_E1, FIELD_C1, MOVE_TYPE_QUEEN_CASTLING | MOVE_TYPE_KING);
        }
    }

    private function addBlackQueenMoves() {
        $m_queens_set = (int)$this->game_state->m_b_queen;
        while ($m_queens_set) {
            $m_queen = $m_queens_set & (int)-$m_queens_set;
            $pos_from = (int)(63 - log($m_queen, 2));

            // луч вниз
            $m_move = (int)Masks::VERT_DOWN_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - белая фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вниз"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вниз вправо
            $m_move = (int)Masks::DIAG_DOWN_RIGHT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - белая фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вниз вправо"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вниз влево
            $m_move = (int)Masks::DIAG_DOWN_LEFT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - белая фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вниз влево"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч влево
            $m_move = (int)Masks::HOR_LEFT_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - белая фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ($m_ray_end & FIELD_A8) ? NOT_A8 : $m_ray_end - 1; // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "влево"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вправо
            $m_move = (int)Masks::HOR_RIGHT_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - белая фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вправо"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вверх
            $m_move = (int)Masks::VERT_UP_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой, атакованная ферзём (может быть и своя)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - белая фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ($m_ray_end & FIELD_A8) ? NOT_A8 : $m_ray_end - 1; // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вверх"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч влево вверх 
            $m_move = (int)Masks::DIAG_UP_LEFT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой, атакованной ферзём (может быть и своя)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - белая фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ($m_ray_end & FIELD_A8) ? NOT_A8 : $m_ray_end - 1; // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "влево вверх"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вправо вверх
            $m_move = (int)Masks::DIAG_UP_RIGHT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackQueenBeat($m_queen, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ферзь - белая фигура, значит есть ход - взятие
                    $this->beat_queens[] = array($m_queen, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= (int)($m_ray_end - 1); // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вправо вверх"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackQueenMove($m_queen, $m_bit_move)) {
                    $this->move_queens[] = array($m_queen, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            $m_queens_set &= ~$m_queen;
        }
    }

    private function addBlackRookMoves() {
        $m_rooks_set = (int)$this->game_state->m_b_rook;
        while ($m_rooks_set) {
            $m_rook = $m_rooks_set & (int)-$m_rooks_set;
            $pos_from = (int)(63 - log($m_rook, 2));
            
            // луч вниз
            $m_move = (int)Masks::VERT_DOWN_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackRookBeat($m_rook, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ладья - белая фигура, значит есть ход - взятие
                    $this->beat_rooks[] = array($m_rook, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вниз"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackRookMove($m_rook, $m_bit_move)) {
                    $this->move_rooks[] = array($m_rook, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }
            
            // луч вправо
            $m_move = (int)Masks::HOR_RIGHT_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackRookBeat($m_rook, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ладья - белая фигура, значит есть ход - взятие
                    $this->beat_rooks[] = array($m_rook, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вправо"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackRookMove($m_rook, $m_bit_move)) {
                    $this->move_rooks[] = array($m_rook, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч влево
            $m_move = (int)Masks::HOR_LEFT_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой, атакованная ладьёй (может быть и своя)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackRookBeat($m_rook, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ладья - белая фигура, значит есть ход - взятие
                    $this->beat_rooks[] = array($m_rook, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ($m_ray_end & FIELD_A8) ? NOT_A8 : $m_ray_end - 1; // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "влево"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackRookMove($m_rook, $m_bit_move)) {
                    $this->move_rooks[] = array($m_rook, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вверх
            $m_move = (int)Masks::VERT_UP_MASK[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой, атакованная ладьёй (может быть и своя)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackRookBeat($m_rook, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает ладья - белая фигура, значит есть ход - взятие
                    $this->beat_rooks[] = array($m_rook, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ($m_ray_end & FIELD_A8) ? NOT_A8 : $m_ray_end - 1; // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вверх"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackRookMove($m_rook, $m_bit_move)) {
                    $this->move_rooks[] = array($m_rook, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }
            
            $m_rooks_set &= ~$m_rook;
        }
    }

    private function addBlackBishopMoves() {
        $m_bishops_set = (int)$this->game_state->m_b_bishop;
        while ($m_bishops_set) {
            $m_bishop = $m_bishops_set & (int)-$m_bishops_set;
            $pos_from = (int)(63 - log($m_bishop, 2));

            // луч вниз вправо
            $m_move = (int)Masks::DIAG_DOWN_RIGHT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackBishopBeat($m_bishop, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает слон - белая фигура, значит есть ход - взятие
                    $this->beat_bishops[] = array($m_bishop, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вниз вправо"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackBishopMove($m_bishop, $m_bit_move)) {
                    $this->move_bishops[] = array($m_bishop, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вниз влево
            $m_move = (int)Masks::DIAG_DOWN_LEFT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = Functions::getHighestBit($cross_mask); // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackBishopBeat($m_bishop, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает слон - белая фигура, значит есть ход - взятие
                    $this->beat_bishops[] = array($m_bishop, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ~((($m_ray_end - 1) << 1) | 1); // оставляем в $m_move только биты старше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вниз влево"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackBishopMove($m_bishop, $m_bit_move)) {
                    $this->move_bishops[] = array($m_bishop, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч вправо вверх
            $m_move = (int)Masks::DIAG_UP_RIGHT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с ближайшей атакованной фигурой (может и своей)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackBishopBeat($m_bishop, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает слон - белая фигура, значит есть ход - взятие
                    $this->beat_bishops[] = array($m_bishop, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= (int)($m_ray_end - 1); // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "вправо вверх"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackBishopMove($m_bishop, $m_bit_move)) {
                    $this->move_bishops[] = array($m_bishop, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            // луч влево вверх 
            $m_move = (int)Masks::DIAG_UP_LEFT[$pos_from];
            $cross_mask = $m_move & $this->all_figures_mask;
            if ($cross_mask) {
                $m_ray_end = $cross_mask & (int)-$cross_mask; // маска с самым ближним полем с фигурой, атакованная слоном (может быть и своя)
                if (($m_ray_end & $this->game_state->m_all_white_figures) && $this->game_state->checkBlackBishopBeat($m_bishop, $m_ray_end)) {
                    // на самом дальнем поле, куда простреливает слон - белая фигура, значит есть ход - взятие
                    $this->beat_bishops[] = array($m_bishop, $m_ray_end, MOVE_TYPE_BEAT);
                }
                $m_move &= ($m_ray_end & FIELD_A8) ? NOT_A8 : $m_ray_end - 1; // оставляем в $m_move только биты младше $m_ray_end
            }
            // теперь в $m_move - все возможные ходы (без взятия) на луче "влево вверх"
            while ($m_move) {
                $m_bit_move = $m_move & (int)-$m_move;
                if ($this->game_state->checkBlackBishopMove($m_bishop, $m_bit_move)) {
                    $this->move_bishops[] = array($m_bishop, $m_bit_move, MOVE_TYPE_SIMPLE);
                }
                $m_move &= ~$m_bit_move;
            }

            $m_bishops_set &= ~$m_bishop;
        }
    }

    private function addBlackKhightMoves() {
        $m_knights_set = (int)$this->game_state->m_b_knight;
        while ($m_knights_set) {
            $m_knight = $m_knights_set & (int)-$m_knights_set;
            $pos_from = (int)(63 - log($m_knight, 2));
            $moves_mask = (int)Masks::KHIGHT_MASK[$pos_from];
            // взятия конём
            $m_beat_figures = $moves_mask & $this->game_state->m_all_white_figures;
            while ($m_beat_figures) {
                $m_beat = $m_beat_figures & (int)-$m_beat_figures;
                if ($this->game_state->checkBlackKnightBeat($m_knight, $m_beat)) {
                    $this->beat_knights[] = array($m_knight, $m_beat, MOVE_TYPE_BEAT);
                }
                $m_beat_figures &= ~$m_beat;
            }

            // простые перемещения конём
            $m_moves_fields = $moves_mask & ~($this->game_state->m_all_black_figures | $this->game_state->m_all_white_figures);
            while ($m_moves_fields) {
                $m_move = $m_moves_fields & (int)-$m_moves_fields;
                if ($this->game_state->checkBlackKnightMove($m_knight, $m_move)) {
                    $this->move_knights[] = array($m_knight, $m_move, MOVE_TYPE_SIMPLE);
                }
                $m_moves_fields &= ~$m_move;
            }

            $m_knights_set &= ~$m_knight;
        }
    }

    private function addBlackPawnMoves() {
        $m_pawns_set = (int)$this->game_state->m_b_pawn; // это все чёрные пешки
        // проверим взятия вправо (без взятия на проходе)
        $m_beats = ($m_pawns_set >> 9) & NOT_COL_A;
        $m_beat_figs = $m_beats & $this->game_state->m_all_white_figures;
        while ($m_beat_figs) {
            $m_beat = $m_beat_figs & (int)-$m_beat_figs;
            $m_from = $m_beat << 9;
            if ($this->game_state->checkBlackPawnBeat($m_from, $m_beat)) {
                $this->beat_pawns[] = array($m_from, $m_beat, MOVE_TYPE_BEAT | MOVE_TYPE_PAWN);
            }
            $m_beat_figs &= ~$m_beat;
        }

        // проверим взятия влево (без взятия на проходе)
        $m_beats = ($m_pawns_set >> 7) & ~COL_H;
        $m_beat_figs = $m_beats & $this->game_state->m_all_white_figures;
        while ($m_beat_figs) {
            $m_beat = $m_beat_figs & (int)-$m_beat_figs;
            $m_from = $m_beat << 7;
            if ($this->game_state->checkBlackPawnBeat($m_from, $m_beat)) {
                $this->beat_pawns[] = array($m_from, $m_beat, MOVE_TYPE_BEAT | MOVE_TYPE_PAWN);
            }
            $m_beat_figs &= ~$m_beat;
        }

        // проверим взятие на проходе
        if ( ($m_beat = (int)$this->game_state->m_crossed_field) != 0) {
            // получу маску с пешками, которые бьют проходное поле
            $m_pawnscross = $this->game_state->m_b_pawn & ( (($m_beat << 7) & NOT_COL_A) | (($m_beat << 9) & ~COL_H) );
            while ($m_pawnscross) {
                $m_pawn = $m_pawnscross & (int)-$m_pawnscross;
                if ($this->game_state->checkBlackPawnCrossBeat($m_pawn, $m_beat)) {
                    $this->beat_pawns[] = array($m_pawn, $m_beat, MOVE_TYPE_CROSS_BEAT | MOVE_TYPE_PAWN | MOVE_TYPE_BEAT);
                }
                $m_pawnscross &= ~$m_pawn;
            }
        }

        // проверим простые ходы вперёд
        $m_to_step1 = ((int)$this->game_state->m_b_pawn >> 8) & ~$this->all_figures_mask; // маска - куда могут пойти пешки на одну клетку
        $m_to_step2 = (($m_to_step1 & HOR_6) >> 8) & ~$this->all_figures_mask; // маска - куда могут пойти пешки на две клетки
        // заполним ходы на две клетки
        while ($m_to_step2) {
            $m_to = $m_to_step2 & (int)-$m_to_step2;
            if ($this->game_state->checkBlackPawnMove($m_to<<16, $m_to)) {
                $this->move_pawns[] = array($m_to<<16, $m_to, MOVE_TYPE_PAWN2 | MOVE_TYPE_PAWN);
            }
            $m_to_step2 &= ~$m_to;
        }
        // теперь - ходы на одну клетку
        while ($m_to_step1) {
            $m_to = $m_to_step1 & (int)-$m_to_step1;
            if ($this->game_state->checkBlackPawnMove($m_to<<8, $m_to)) {
                $this->move_pawns[] = array($m_to<<8, $m_to, MOVE_TYPE_PAWN);
            }
            $m_to_step1 &= ~$m_to;
        }
    }

    private function addBlackKingMoves() {
        $pos_from = (int)(63 - log($this->game_state->m_b_king, 2));
        $m_around = (int)Masks::KING_HV_MASK[$pos_from] | (int)Masks::KING_DIAG_MASK[$pos_from];
        // взятия королём рассмотрим отдельно
        $m_king_moves = $m_around & $this->game_state->m_all_white_figures;
        while ($m_king_moves) {
            $m_to = $m_king_moves & (int)-$m_king_moves;
            if ($this->game_state->checkBlackKingBeat($this->game_state->m_b_king, $m_to)) {
                $this->beat_king[] = array($this->game_state->m_b_king, $m_to, MOVE_TYPE_BEAT | MOVE_TYPE_KING);
            }
            $m_king_moves &= ~$m_to;
        }
        // теперь - простые ходы без взятия
        $m_king_moves = $m_around & ~$this->all_figures_mask;
        while ($m_king_moves) {
            $m_to = $m_king_moves & (int)-$m_king_moves;
            if ($this->game_state->checkBlackKingMove($this->game_state->m_b_king, $m_to)) {
                $this->move_king[] = array($this->game_state->m_b_king, $m_to, MOVE_TYPE_KING);
            }
            $m_king_moves &= ~$m_to;
        }
        // теперь рассмотрим возможность рокировки, ходы добавим в список взятий, чтобы они получили приоритет в порядке рассмотрения ходов
        // короткая рокировка
        if (
            $this->game_state->enable_castling_black_king &&
            (((FIELD_F8 | FIELD_G8) & $this->all_figures_mask) === 0) &&
            !$this->game_state->isAttackedByWhite(FIELD_E8) &&
            !$this->game_state->isAttackedByWhite(FIELD_F8) &&
            !$this->game_state->isAttackedByWhite(FIELD_G8)
        ) {
            $this->beat_king[] = array(FIELD_E8, FIELD_G8, MOVE_TYPE_KING_CASTLING | MOVE_TYPE_KING);
        }
        // длинная рокировка
        if (
            $this->game_state->enable_castling_black_queen &&
            (((FIELD_B8 | FIELD_C8 | FIELD_D8) & $this->all_figures_mask) === 0) &&
            !$this->game_state->isAttackedByWhite(FIELD_E8) &&
            !$this->game_state->isAttackedByWhite(FIELD_D8) &&
            !$this->game_state->isAttackedByWhite(FIELD_C8)
        ) {
            $this->beat_king[] = array(FIELD_E8, FIELD_C8, MOVE_TYPE_QUEEN_CASTLING | MOVE_TYPE_KING);
        }
    }
}
