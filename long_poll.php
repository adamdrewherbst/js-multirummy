<?php

include 'dbconnect.php';
global $mysqli, $response;

//a change in the game is registered by a change in the role table (either someone wins, or the turn advances)
$currentTurn = $_GET['turn']; //whose turn the client thinks it is - tell them when it changes
$query = 'SELECT * FROM RummyRoles';
do{
	if(($result = $mysqli->query($query)) !== FALSE) {
		while($row = $result->fetch_row()) {
			if($row[0] === 'WINNER' and $row[1] !== NULL) {
				
			}
			if($row[0] === 'TURN' and $row[1] !== $currentTurn)
		}
	}
	usleep(250000); //hang for 250ms
}while();

$mysqli->close();

?>
