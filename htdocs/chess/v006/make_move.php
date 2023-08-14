<?php
require_once('engine/chess_game.php');
$cell_index_from = $_POST['cell_index_from'];
$cell_index_to = $_POST['cell_index_to'];
$game = new ChessGame();
$game->makeMove($cell_index_from, $cell_index_to);
$data = $game->getClientJsonGameState();
header('Content-Type: application/json');
echo json_encode($data, JSON_UNESCAPED_UNICODE);