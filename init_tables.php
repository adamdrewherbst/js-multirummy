<?php //assumes dbconnect has been called

global $mysqli, $response;

function initTable($tbl, $cols, $keycol, $extra = '') {
	global $mysqli, $response;
	
	$query = 'CREATE TABLE ' . $tbl . '(';
	foreach($cols as $col) {
		$query .= $col['name'] . ' ' . strtoupper($col['type']) . ', ';
	}
	$query .= 'PRIMARY KEY (' . $keycol . ')';
	if(strlen($extra) > 0) $query .= ', ' . $extra;
	$query .= ')';
	if($mysqli->query($query) === TRUE) $response .= sprintf("Table %s successfully created.\n", $tbl);
	else $response .= sprintf("Could not create table %s\n", $tbl);
	
	return $response;
}

//ensure deck table is initialized
$cols = array( //for each card we want its ID and whose hand/which pile it is currently in
	array('name' => 'ID', 'type' => 'TINYINT UNSIGNED NOT NULL AUTO_INCREMENT'), //preserve the deck order for queries
	array('name' => 'CardID', 'type' => 'TINYINT UNSIGNED'),
	array('name' => 'Pile', 'type' => 'VARCHAR(30)'),
);
$tbl = 'RummyDeck';
$keycol = 'CardID';
initTable($tbl, $cols, 'ID');

//...and that it has cards
$query = 'SELECT ' . $keycol . ' FROM ' . $tbl;
if(!($result = $mysqli->query($query)) or $result->num_rows == 0) {
	$cards = range(0, DECK_SIZE-1);
	shuffle($cards);
	foreach($cards as $card) {
		$query = 'INSERT INTO ' . $tbl . ' (' . $keycol . ') VALUES (' . $card . ')';
		if($mysqli->query($query) === TRUE) $response .= sprintf("Card %d successfully added.\n", $card);
		else $response .= sprintf("Could not add card %d\n", $card);
	}
}

//ensure player table is initialized
$cols = array(
	array('name' => 'ID', 'type' => 'SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT'),
	array('name' => 'NickName', 'type' => 'VARCHAR(30)'),
	array('name' => 'ClientIP', 'type' => 'VARCHAR(45)'), //allow for IPv6
	array('name' => 'GameID', 'type' => 'SMALLINT UNSIGNED'),
	array('name' => 'GameRequest', 'type' => 'SMALLINT UNSIGNED'),
);
initTable('RummyPlayer', $cols, 'ID', 'UNIQUE(NickName)');

//ensure role table is initialized (stores the player corresponding to each of several roles in the game)
$cols = array(
	array('name' => 'Role', 'type' => 'VARCHAR(30)'),
	array('name' => 'PlayerID', 'type' => 'VARCHAR(30)'),
);
initTable('RummyRoles', $cols, 'Role');
$mysqli->query('INSERT INTO RummyRoles(Role) VALUES ("TURN"),("WINNER")');

//create the stored procedure needed to deal a specified number of cards and return the card values
$query = 'CREATE PROCEDURE deal_cards(IN player VARCHAR(30), IN number TINYINT UNSIGNED) BEGIN'
//			. ' LOCK TABLES RummyDeck WRITE;'
			. ' SELECT CardID FROM RummyDeck WHERE ISNULL(Pile) LIMIT number;'
			. ' UPDATE RummyDeck SET Pile=player WHERE ISNULL(Pile) LIMIT number; END';
//			. ' UNLOCK TABLES; END';
if($mysqli->query($query) === TRUE) $response .= sprintf("deal procedure created\n");
else $response .= sprintf("deal procedure could not be created\n");

return 1;

?>
