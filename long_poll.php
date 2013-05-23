<?php

include 'dbconnect.php';
include 'validate_client.php';
global $mysqli, $response, $player, $playerID, $gameID, $isOwner;

$start_time = microtime(true);
$time_limit = 5; //seconds

//a change in the game is registered by a change in the role table (either someone wins, or the turn advances)
$currentTurn = $_POST['turn']; //whose turn the client thinks it is - tell them when it changes
$currentWinner = $_POST['winner'];
$currentGames = $_POST['games'];
if($currentGames === NULL) $currentGames = array();
$currentRequests = $_POST['requests'];
if($currentRequests === NULL) $currentRequests = array();
addResponse('current requests: ' . implode(',', $currentRequests));
addResponse('current games: ' . implode(',', $currentGames));
$games = array(); //list of newly created game rooms
$requests = array(); //list of new players requesting to join the game
$data = array(); //values to be returned to client

do{
	//update the list of existing games
	if(($result = $mysqli->query('SELECT Name,InProgress FROM RummyGame')) !== FALSE) {
		while($row = $result->fetch_row()) {
			if($row[0] !== NULL and array_search($row[0], $currentGames, TRUE) === FALSE) $games[] = $row;
		}
		if(count($games) > 0) $data['games'] = $games;
	}
	//getting turn/win/join request updates only applies if we are part of a game already
	if($gameID !== NULL) {
		if(($result = $mysqli->query('SELECT * FROM RummyRole WHERE GameID=' . $gameID)) !== FALSE) {
			while($row = $result->fetch_row()) {
				if($row[0] === 'WINNER' and $row[1] !== NULL and $row[1] !== $currentWinner) {
					succeed(array('winner' => $row[1])); //if someone won, forget all other updates
				}
				if($row[0] === 'TURN' and $row[1] !== $currentTurn) {
					$currentTurn = $row[1];
				}
			}
			if($currentTurn !== $_POST['turn']) {
				$response .= 'Turn change from ' . $_POST['turn'] . ' to ' . $currentTurn . PHP_EOL;
				$data['turn'] = $currentTurn;
			}
		}
		if($isOwner) { //send any new join requests to the game owner
			if(($result = $mysqli->query('SELECT NickName FROM RummyPlayer WHERE ISNULL(GameID) AND GameRequest=' . $gameID)) !== FALSE) {
				while($row = $result->fetch_row()) {
					if($row[0] !== NULL and array_search($row[0], $currentRequests, TRUE) === FALSE) $requests[] = $row[0];
				}
				if(count($requests) > 0) {
					$data['requests'] = $requests;
				}
			}
		}
	}
	if(count($data) > 0) succeed($data); //there were changes to the game state, so send them immediately

	usleep(250000); //hang for 250ms

}while(microtime(true) - $start_time < $time_limit);

$mysqli->close();
$response .= 'long_poll: no change' . PHP_EOL;
echo json_encode(array('response' => $response));

?>
