<?php

//connect to the database
include 'dbconnect.php';
global $response;

//ensure RummyDeck and RummyPlayer tables are initialized
include 'init_tables.php';

$player = $_GET['player'];

//add the new player
$query = 'INSERT INTO RummyPlayer (NickName, ClientIP) VALUES ("' . $player . '", "' . $_SERVER['REMOTE_ADDR'] .'")';
if($mysqli->query($query) === FALSE) $response .= 'Player ' . $player . ' could not be added' . PHP_EOL;

//get the updated player list
$query = 'SELECT * FROM RummyPlayer';
if(($players = $mysqli->query($query)) !== FALSE) {
	//echo the list of players
	$response .= 'There are ' . $players->num_rows . ' players with ' . $players->field_count . ' columns:<br />';
	$cols = $players->fetch_fields();
	$response .= '<table border="1"><tr>';
	foreach($cols as $col) $response .= '<td>' . $col->name . '</td>';
	$response .= '</tr>';
	$playerID = -1;
	while(($row = $players->fetch_row()) !== NULL) {
		$response .= '<tr>';
		foreach($row as $val) $response .= '<td>' . ($val !== NULL ? $val : 'NULL') . '</td>';
		$playerID = $row[0];
		$response .= '</tr>';
	}
	$response .= '</table>';
	
	//if this is the first player, make it their turn
	if(($result = $mysqli->query('SELECT PlayerID FROM RummyRoles WHERE Role="TURN"')) !== FALSE and ($row = $result->fetch_row()))
		$currentTurn = $row[0];
	else fail("Couldn't get current turn");
	if($currentTurn === NULL and $mysqli->query('UPDATE RummyRoles SET PlayerID="' . $player . '" WHERE Role="TURN"') === FALSE)
		fail('Could not set turn to ' . $player);
}

//deal the new player a hand
$tbl = 'RummyDeck';
$query = 'CALL deal_cards("' . $player . '", ' . HAND_SIZE . ')';
$hand = array();
if(($cards = $mysqli->query($query)) !== FALSE) {
	while(($row = $cards->fetch_row()) !== NULL) $hand[] = $row[0];
}
else fail('Could not get hand to deal');

$mysqli->close();
echo json_encode(array('response' => $response, 'hand' => $hand));

?>
