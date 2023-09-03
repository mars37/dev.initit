<?php

class SessionStorage {
    const SESSION_KEY = 'chess_game_state_v2';

    public function __construct() {
        session_start();
    }

    public function saveGameState(GameState $game_state) {
        $_SESSION[self::SESSION_KEY] = $game_state->serializeState();
    }

    public function loadGameState() {
        if (empty($_SESSION[self::SESSION_KEY])) {
            return null;
        }

        $game_state = new GameState();
        $game_state->unserializeState($_SESSION[self::SESSION_KEY]);
        return $game_state;
    }
}