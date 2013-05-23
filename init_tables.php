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

//ensure game table is initialized
$cols = array(
	array('name' => 'ID', 'type' => 'SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT'),
	array('name' => 'Name', 'type' => 'VARCHAR(30)'),
	array('name' => 'InProgress', 'type' => 'BOOL'),
);
initTable('RummyGame', $cols, 'ID', 'UNIQUE(Name)');

//ensure deck table is initialized
$cols = array( //for each card we want its ID and whose hand/which pile it is currently in
	array('name' => 'ID', 'type' => 'TINYINT UNSIGNED NOT NULL AUTO_INCREMENT'), //preserve the deck order for queries
	array('name' => 'GameID', 'type' => 'SMALLINT UNSIGNED'),
	array('name' => 'CardID', 'type' => 'TINYINT UNSIGNED'),
	array('name' => 'Pile', 'type' => 'VARCHAR(30)'),
);
initTable('RummyDeck', $cols, 'ID');

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
	array('name' => 'GameID', 'type' => 'SMALLINT UNSIGNED'),
	array('name' => 'Role', 'type' => 'VARCHAR(30)'),
	array('name' => 'PlayerID', 'type' => 'VARCHAR(30)'),
);
initTable('RummyRole', $cols, 'GameID,Role');

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
