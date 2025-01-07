<?php
require_once('engine/chess_game.php');

$game = new ChessGame();
$game->loadGame();
$data = $game->getClientJsonGameState();
header('Content-Type: application/json');
echo json_encode($data, JSON_UNESCAPED_UNICODE);