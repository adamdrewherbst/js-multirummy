<?php

//since all server events will include this module, set up game-wide globals here
define('HAND_SIZE', 7);
define('DECK_SIZE', 52);
$suits = array('Clubs', 'Spades', 'Diamonds', 'Hearts');
$numbers = array('Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Jack', 'Queen', 'King', 'Ace');

//to be used by clients who hit an error
function fail($msg) {
	global $mysqli, $response;
	$response .= $msg . PHP_EOL;
	echo json_encode(array('response' => $response));
	if(get_class($result) === 'mysqli_result') $result->free();
	$mysqli->close();
	exit();
}
$response = '';

//now connect to the MySQL 'games' database
$user = 'root';
$pass = 'tsup**dl3';
//*/
/*$user = '1380903';
$pass = 'playtime';
//*/

$db = 'games';
$mysqli = new mysqli('localhost', $user, $pass, $db);
if($mysqli->connect_errno) {
	fail('Could not connect to database');
}

?>
