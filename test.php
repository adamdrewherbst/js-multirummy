<?php

if((include_once 'dbconnect.php') < 0) fail('Could not connect to database');

global $mysqli;
$response = '';

$nextID = null;
if(($result = $mysqli->query('SELECT MIN(ID) FROM RummyPlayer WHERE ID > 1')) !== FALSE and ($row = $result->fetch_row()) !== FALSE)
	$nextID = $row[0];
else fail('Min query failed');
if(!$nextID and ($result = $mysqli->query('SELECT ID FROM RummyPlayer ORDER BY ID LIMIT 1')) !== FALSE and ($row = $result->fetch_row()) !== FALSE)
	$nextID = $row[0];
else fail('Could not get next player index');
$result->free();

$mysqli->close();
echo json_encode(array('response' => $response, 'next' => $nextID));

?>
