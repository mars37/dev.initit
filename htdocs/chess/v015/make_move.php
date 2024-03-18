<?php
require_once('engine/chess_game.php');
$cell_index_from = intval($_POST['cell_index_from']);
$cell_index_to = intval($_POST['cell_index_to']);
$to_figure_string = empty($_POST['transform_to']) ? null : $_POST['transform_to'];
$game = new ChessGame();
$game->loadGame();
$game->makeMove($cell_index_from, $cell_index_to, true, $to_figure_string);
$data = $game->getClientJsonGameState();
header('Content-Type: application/json');
echo json_encode($data, JSON_UNESCAPED_UNICODE);