<?php

require_once('figures/king.php');
require_once('figures/queen.php');
require_once('figures/rook.php');
require_once('figures/bishop.php');
require_once('figures/knight.php');
require_once('figures/pawn.php');

abstract class Figure {
    protected GameState $game_state;
    protected int $position_index;

    public function __construct(GameState $game_state, int $position_index) {
        $this->game_state = $game_state;
        $this->position_index = $position_index;
    }

    abstract public function getAvailableMoves();
}
