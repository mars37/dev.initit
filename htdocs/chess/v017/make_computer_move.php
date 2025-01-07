<?php
require_once('engine/chess_game.php');
$game = new ChessGame();
$game->loadGame();
set_time_limit(45);
$game->makeComputerMove();
$data = $game->getClientJsonGameState();
header('Content-Type: application/json');
echo json_encode($data, JSON_UNESCAPED_UNICODE);