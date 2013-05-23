<?php //assumes dbconnect has already been called, and player name was passed in GET request

global $mysqli;

//make sure this client is listed in the player table
$player = $_GET['player'];
if($player == '') $player = $_POST['player'];
$playerID = -1;
$gameID = NULL;
$client = $_SERVER['REMOTE_ADDR'];
$query = 'SELECT ID,NickName,GameID FROM RummyPlayer WHERE ClientIP="' . $client . '"';
if(($result = $mysqli->query($query)) !== FALSE) {
	$match = -1;
	while($row = $result->fetch_row()) { //see if any of the players from this IP is the player who was sent
		if($row[1] === $player) {
			$match = 1;
			$playerID = $row[0];
			$gameID = $row[2];
			break;
		}
		$match = 0;
	}
	if($match === 0) fail('IP ' . $client . ' is not player ' . $player);
	if($playerID < 0) fail("Couldn't get valid playerID for player " . $player);
}
else fail("Couldn't query player table for IP " . $client);

//check if this player is the owner of this game - store in $isOwner for use by clients
$isOwner = false;
if($gameID !== NULL) {
	if(($result = $mysqli->query('SELECT PlayerID FROM RummyRole WHERE GameID='.$gameID.' AND Role="OWNER"')) !== FALSE
		and ($row = $result->fetch_row())) $isOwner = $row[0] === $player;
	else fail('Could not determine owner of this game');
	if($isOwner) addResponse('You are the game owner');
}
else addResponse('Not in a game yet');

?>
