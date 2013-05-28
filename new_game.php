<?php

include 'dbconnect.php';
include 'validate_client.php';
global $mysqli, $response, $player, $playerID;

//add the game to the game table
insert('RummyGame', 'Name,InProgress', '("' . $player . '", FALSE)');
$gameID = single('ID', 'RummyGame', 'Name="'.$player.'"');

//update this player to be the owner of this game
update('RummyPlayer', 'GameID='.$gameID, 'ID='.$playerID);
insert('RummyRole', 'GameID,Role,PlayerID',
	'('.$gameID.',"TURN",NULL),('.$gameID.',"WINNER",NULL),('.$gameID.',"OWNER","' . $player . '")');

addResponse('Game ' . $player . ' created');
succeed(array('success' => true));

?>
