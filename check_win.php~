<?php

include 'dbconnect.php';
include 'validate_client.php';

global $mysqli, $response, $player, $playerID, $suits, $numbers;

//make sure the list of cards they sent is the same list we have on file for them
$hand = explode(',', $_GET['hand']);
$curHand = multiple('CardID', 'RummyDeck', 'Pile="'.$player.'"');
$handSize = count($curHand);
$response .= 'Sent: ' . $_GET['hand'] . PHP_EOL . 'Have: ' . implode(',', $curHand) . PHP_EOL;
if(count($hand) !== HAND_SIZE or count(array_intersect($hand, $curHand)) !== HAND_SIZE)
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
	addResponse('Checking ' . $numbers[$number] . ' of ' . $suits[$suit]);
	addResponse("\ttype = " . $setType . ", count = " . $setCount);
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
addResponse('Win: ' . ($win ? 'yes' : 'no'));

if($win) {
	//alert everyone that this player wins via the RummyRoles table - this will also make sure no one else has won in the meantime
	lock('RummyRole', 'WRITE');
	$winner = single('PlayerID', 'RummyRole', 'Role="WINNER"');
	if($winner === NULL) update('RummyRole', 'PlayerID="'.$player.'"', 'Role="WINNER"');
	else addResponse($winner . ' has already won');
	unlock();
	//this game is no longer in progress, so people can request to join the room
	update('RummyGame', 'InProgress=FALSE', 'ID='.$gameID);
}

succeed(array('win' => $win));

?>

