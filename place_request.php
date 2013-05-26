<?php

include 'dbconnect.php';
include 'validate_client.php';
global $mysqli, $response, $player, $playerID;

$game = $_GET['game'];
$gameID = single('ID', 'RummyGame', 'Name="'.$game.'"');

if($mysqli->query('UPDATE RummyPlayer SET GameRequest=' . $gameID . ' WHERE ID=' . $playerID) === FALSE)
	fail('Could not update request for player ' . $playerID . '=' . $player . ' to game ' . $gameID . '=' . $game);
succeed(array('success' => true));

?>
