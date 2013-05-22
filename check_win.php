<?php

include 'dbconnect.php';
include 'validate_client.php';

global $mysqli, $response, $player, $playerID, $suits, $numbers;

//make sure the list of cards they sent is the same list we have on file for them
$hand = explode(',', $_GET['hand']);
$query = 'SELECT CardID FROM RummyDeck WHERE Pile="' . $player . '"';
if(($result = $mysqli->query($query)) === FALSE) fail('Could not get existing hand for ' . $player);
$curHand = array();
while($row = $result->fetch_row()) $curHand[] = $row[0];
$handSize = count($curHand);
$response .= 'Sent: ' . $_GET['hand'] . PHP_EOL . 'Have: ' . implode(',', $curHand) . PHP_EOL;
if(count($hand) !== $handSize and count(array_intersect($hand, $curHand)) !== $handSize)
	fail('The hand you sent is incorrect');

//now check the order of the hand they sent
$win = true;
$lastSuit = -1;
$setCount = 0;
$setType = -1;
foreach($hand as $card) {
	$suit = (int)($card / count($numbers));
	$number = $card % count($numbers);
	$match = -1;
	$response .= 'Checking ' . $numbers[$number] . ' of ' . $suits[$suit] . PHP_EOL;
	$response .= "\ttype = " . $setType . ", count = " . $setCount . PHP_EOL;
	if($lastSuit >= 0) {
		if($suit === $lastSuit and ($number === $lastNumber+1 or ($number === 0 and $lastNumber === 12))) $match = 1; //run
		elseif($number === $lastNumber) $match = 2; //group
		if($match < 0) {
			if($setCount < 3) { $win = false; break; }
			else { $setCount = 1; $setType = -1; }
		}elseif($setType > 0) {
			if($match === $setType) $setCount++;
			elseif($setCount < 3) { $win = false; break; }
			elseif($setCount === 3) { $setCount = 1; $setType = -1; }
			else { $setCount = 2; $setType = $match; }
		}else {
			$setType = $match;
			$setCount = 2;
		}
	}
	else $setCount = 1;
	$lastSuit = $suit;
	$lastNumber = $number;
}
if($win) $win = $setCount > 2; //make sure last set is also valid
$response .= 'Win: ' . ($win ? 'yes' : 'no') . PHP_EOL;

if($win) { //alert everyone that this player wins via the RummyRoles table - this will also make sure no one else has won in the meantime
	if($mysqli->query('LOCK TABLES RummyRoles') === FALSE) fail('Could not lock role table');
	$winner = '';
	if(($result = $mysqli->query('SELECT PlayerID FROM RummyRoles WHERE Role="WINNER"')) === FALSE) 
		$response .= 'Could not look up current winner' . PHP_EOL;
	elseif($row = $result->fetch_row()) $winner = $row[0];
	if($winner === NULL and $mysqli->query('UPDATE RummyRoles SET PlayerID="' . $player . '" WHERE Role="WINNER"') === FALSE)
		$response .= 'Could not update winner field to ' . $player . PHP_EOL;
	elseif(strlen($winner) > 0) $response .= $winner . ' has already won' . PHP_EOL;
	if($mysqli->query('UNLOCK TABLES') === FALSE) fail('Could not unlock role table');
}

$mysqli->close();
echo json_encode(array('response' => $response, 'win' => $win));

?>
