<?php

include 'dbconnect.php';
include 'init_tables.php';
global $mysqli, $response;

$player = $_GET['name'];
$client = $_SERVER['REMOTE_ADDR'];

addResponse('attempting to register ' . $player . ' at ' . $client);

if(($result = $mysqli->query('SELECT ID FROM RummyPlayer WHERE NickName="' . $player . '"')) !== FALSE) {
	if(($row = $result->fetch_row()) and $row[0] !== NULL) fail('Name ' . $player . ' is already taken');
	if($mysqli->query('INSERT INTO RummyPlayer(NickName,ClientIP) VALUES ("' . $player . '","' . $client . '")') === FALSE)
		fail('Could not add player ' . $player . ' at IP ' . $client);
	else addResponse('Player ' . $player . ' added');
	succeed(array('success' => true));
}
else fail('Could not query player table for name ' . $player);

?>
