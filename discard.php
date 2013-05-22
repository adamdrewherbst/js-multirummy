<?php

include 'dbconnect.php';
include 'validate_client.php';
global $mysqli, $response, $player, $playerID;

//make sure it is this player's turn
$query = 'SELECT PlayerID FROM RummyRoles WHERE Role="TURN"';
if(!($result = $mysqli->query($query)) or !($row = $result->fetch_row()) or $row[0] !== $playerID)
	fail('It is not your turn, ' . $player);
$result->free();

//and that they actually have the card they want to discard
$card = $_GET['card'];
$hasCard = false;
$query = 'SELECT Pile FROM RummyDeck WHERE CardID=' . $card;
if(!($result = $mysqli->query($query)) or !($row = $result->fetch_row()) or $row[0] !== $player)
	fail('Player ' . $player . ' does not have card ' . $card);
$result->free();

//determine the next card from the stack to deal
$newCard = -1;
$query = 'UPDATE RummyDeck SET Pile="<discard>" WHERE CardID=' . $card;
if(!$mysqli->query($query)) fail('Discard operation failed');
$query = 'CALL deal_cards("' . $player . '", 1)';
if(($result = $mysqli->query($query)) !== FALSE) {
	if(($row = $result->fetch_row())) $newCard = $row[0];
	while($mysqli->next_result()); //the deal_cards procedure has additional queries in it - need to get past those results
}
else fail('Could not get card to deal');
$result->free();
$response .= 'Dealing card ' . $newCard . PHP_EOL;

//get the next player in the table, or first if there is no next
$nextID = -1;
$nextPlayer = '';
$query = 'SELECT MIN(ID) FROM RummyPlayer WHERE ID > ' . $playerID . '; SELECT ID FROM RummyPlayer ORDER BY ID LIMIT 1';
if($mysqli->multi_query($query) !== FALSE) {
	do {
		if(($result = $mysqli->store_result()) !== FALSE and ($row = $result->fetch_row()) !== NULL) {
			if(!$nextID or $nextID < 0) $nextID = $row[0];
			$response .= 'Using next ID = ' . $nextID . PHP_EOL;
			$result->free();
		}
	}while($mysqli->next_result());
}
else fail('Could not get next player index');

//get the name of the next player
if($nextID >= 0 and ($result = $mysqli->query('SELECT NickName FROM RummyPlayer WHERE ID=' . $nextID)) !== FALSE) {
	if(($row = $result->fetch_row()) !== NULL) $nextPlayer = $row[0];
}
else fail('Could not retrieve next player for ID = ' . $nextID);
$result->free();

if($nextID < 0 or $nextPlayer == '') fail('Could not retrieve next player: ' . $nextID . ' = ' . $nextPlayer);//*/
$response .= 'Next player = ' . $nextID . ' = ' . $nextPlayer . PHP_EOL;

//set the role table so it is their turn
if($mysqli->query('UPDATE RummyRoles SET PlayerID="' . $nextPlayer . '" WHERE Role="TURN"') === FALSE) fail("Couldn't advance turn");

$mysqli->close();
echo json_encode(array('response' => $response, 'card' => $newCard));

?>

