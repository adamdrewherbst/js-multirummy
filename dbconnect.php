<?php

//since all server events will include this module, set up game-wide globals here
define('HAND_SIZE', 7);
define('DECK_SIZE', 52);
$suits = array('Clubs', 'Spades', 'Diamonds', 'Hearts');
$numbers = array('Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Jack', 'Queen', 'King', 'Ace');

//to be used by clients who hit an error - set unlock if there are still locked tables we should try to unlock
function fail($msg, $unlock = false) {
	global $mysqli, $response, $result, $db_indirect;
	$response .= $msg . PHP_EOL;
	//if($mysqli->errno != 0)
		addResponse('  Error: ' . $mysqli->errno . ' - ' . $mysqli->error);
	if($result !== null and get_class($result) === 'mysqli_result') $result->free();
	if($unlock) $mysqli->query('UNLOCK TABLES');
	if(!$db_indirect) {
		echo json_encode(array('failure' => true, 'response' => $response));
		$mysqli->close();
		exit();
	}else { //since indirect is set, we assume there is a calling script ready to catch our exception
		throw new Exception($msg);
	}
}
//to be used by clients to exit with data
function succeed($data, $unlock = false) {
	global $mysqli, $response, $result, $db_indirect;
	if($result !== null and get_class($result) === 'mysqli_result') $result->free();
	if($unlock) $mysqli->query('UNLOCK TABLES');
	if(!$db_indirect) {
		echo json_encode(array_merge(array('response' => $response), $data));
		$mysqli->close();
		exit();
	}else {
		throw new Exception();
	}
}
//to be used by clients to add to the response
function addResponse($line) {
	global $response;
	$response .= $line . PHP_EOL;
}
$response = '';

//retrieve a single table cell - if fail is set, the whole script will abort if the query fails
function single($select, $from, $where, $fail = true) {
	global $mysqli;
	$query = 'SELECT '.$select.' FROM '.$from.' WHERE '.$where;
	if(($result = $mysqli->query($query)) !== FALSE and ($row = $result->fetch_row()))
		return $row[0];
	elseif($fail) fail('Query failed: ' . $query, true);
}
//multiple assumes multiple rows but only one column - use multiple_list for multiple columns
function multiple($select, $from, $where, $fail = true) {
	global $mysqli;
	$query = 'SELECT '.$select.' FROM '.$from.' WHERE '.$where;
	$ret = array();
	if(($result = $mysqli->query($query)) !== FALSE) {
		while($row = $result->fetch_row()) {
			$ret[] = $row[0];
		}
		return $ret;
	}
	elseif($fail) fail('Query failed: ' . $query, true);
}
function multiple_list($select, $from, $where, $fail = true) {
	global $mysqli;
	$query = 'SELECT '.$select.' FROM '.$from.' WHERE '.$where;
	$ret = array();
	if(($result = $mysqli->query($query)) !== FALSE) {
		while($row = $result->fetch_row()) {
			$ret[] = $row;
		}
		return $ret;
	}
	elseif($fail) fail('Query failed: ' . $query, true);
}
//for updates/inserts/deletes
function update($tbl, $set, $where, $fail = true) {
	global $mysqli, $response;
	$query = 'UPDATE ' . $tbl . ' SET ' . $set;
	if($where != '') $query .= ' WHERE ' . $where;
	if($mysqli->query($query) === FALSE && $fail)
		fail('Query failed: ' . $query);
}
function insert($tbl, $cols, $vals, $fail = true) {
	global $mysqli, $response;
	$query = 'INSERT INTO ' . $tbl . ' (' . $cols . ') VALUES ' . $vals;
	if($mysqli->query($query) === FALSE && $fail)
		fail('Query failed: ' . $query);
}
function delete($tbl, $where, $fail = true) {
	global $mysqli, $response;
	$query = 'DELETE FROM ' . $tbl;
	if($where != '') $query .= ' WHERE ' . $where;
	if($mysqli->query($query) === FALSE && $fail)
		fail('Query failed: ' . $query);
}
function lock($tbl, $type, $fail = true) {
	global $mysqli, $response;
	$query = 'LOCK TABLES ';
	$tbl = explode(',', $tbl);
	for($i = 0; $i < count($tbl); $i++) {
		$query .= $tbl[$i] . ($type != '' ? ' '.$type : '');
		if($i < count($tbl)-1) $query .= ', ';
	}
	if($mysqli->query($query) === FALSE && $fail)
		fail('Query failed: ' . $query);
}
function unlock($fail = true) {
	global $mysqli, $response;
	$query = 'UNLOCK TABLES';
	if($mysqli->query($query) === FALSE && $fail)
		fail('Query failed: ' . $query);
}

//now connect to the MySQL 'games' database
$user = '';
$pass = '';
$db = '';
$server = '';

$mysqli = new mysqli($server, $user, $pass, $db);
if($mysqli->connect_errno) {
	fail('Could not connect to database');
}

?>
