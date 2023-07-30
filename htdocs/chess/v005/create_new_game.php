<?php
require_once('engine/chess_game.php');

$human_color = ($_POST['human_color'] == 'b' ? COLOR_BLACK : COLOR_WHITE);

$game = new ChessGame($human_color);
$game->createNewGame($human_color);
$data = $game->getClientJsonGameState();
header('Content-Type: application/json');
echo json_encode($data);