<?php //assumes dbconnect has already been called, and player name was passed in GET request

global $mysqli;

//make sure this client is listed in the player table
if(array_key_exists('nickname', $_GET)) $player = $_GET['nickname'];
else $player = $_POST['nickname'];
$playerID = -1;
$gameID = NULL;
$client = $_SERVER['REMOTE_ADDR'];
$rows = multiple_list('ID,NickName,GameID', 'RummyPlayer', 'ClientIP="'.$client.'"');
$match = -1;
foreach($rows as $row) {
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

//check if this player is the owner of this game - store in $isOwner for use by clients
$isOwner = false;
if($gameID !== NULL) {
	$isOwner = single('PlayerID', 'RummyRole', 'GameID='.$gameID.' AND Role="OWNER"') === $player;
	if($isOwner) addResponse('You are the game owner');
}
else addResponse('Not in a game yet');

?>
