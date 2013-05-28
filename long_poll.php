<?php

include 'dbconnect.php';
include 'validate_client.php';
global $mysqli, $response, $player, $playerID, $gameID, $isOwner;

$start_time = microtime(true);
$time_limit = 5; //seconds

//the client posts the last game state it knows of
$state = $_POST;

//each query corresponds to one part of the game state - it stores the SELECT, FROM, and WHERE clauses and whether multiple values may be returned
//if the query result differs from the posted value, we need to send the update
$queries = array(
	'game' => array('GameID', 'RummyPlayer', 'ID='.$playerID, false),
	'request' => array('GameRequest', 'RummyPlayer', 'ID='.$playerID, false),
	'players' => array('NickName', 'RummyPlayer', 'ID <> '.$playerID, true),
	'games' => array('Name,InProgress', 'RummyGame', 'NOT ISNULL(Name)', true),
	'requests' => array('NickName', 'RummyPlayer', 'GameRequest='.$gameID, true),
	//these are only checked if we are actually in a game
	'hand' => array('CardID', 'RummyDeck', 'Pile="'.$player.'"', true),
	'owner' => array('PlayerID', 'RummyRole', 'GameID='.$gameID.' AND Role="OWNER"', false),
	'turn' => array('PlayerID', 'RummyRole', 'GameID='.$gameID.' AND Role="TURN"', false),
	'winner' => array('PlayerID', 'RummyRole', 'GameID='.$gameID.' AND Role="WINNER"', false),
);
$data = array(); //will store all the changes in the game state

do{
	foreach($queries as $attr => $fields) {

		if(($gameID == NULL or $gameID === '0') and
			($attr === 'hand' or $attr === 'owner' or $attr === 'turn' or $attr === 'winner')) continue;
		if(!$isOwner and $attr === 'requests') continue;
	
		if(!$fields[3]) { //query returns a single value
			$val = single($fields[0], $fields[1], $fields[2]);
			if($attr === 'game' and $val != null) $val = single('Name', 'RummyGame', 'ID='.$val);
			if($val != $state[$attr]) {
				$data[$attr] = $val;
				if($attr == 'request') addResponse($attr.': '.$state[$attr].' ['.strlen($state[$attr]).'] => '.$val.' ['.strlen($val).']');
			}
		}else { //query returns multiple values - send all new/updated list members and all those that are no longer in the list
			if(!array_key_exists($attr, $state) or $state[$attr] === NULL) $state[$attr] = array();
			$vals = multiple_list($fields[0], $fields[1], $fields[2]); //may have multiple columns - eg. for games, we fetch GameID and InProgress
			$vals_first = array();
			for($i = 0; $i < count($vals); $i++) $vals_first[] = $vals[$i][0];
			$oldVals = array(); //stores members gotten rid of since last time
			$newVals = array(); //newly added or updated
			//for each obselete game/request, just append the name of the game/request that has been removed
			foreach($state[$attr] as $key => $val) {
				if(array_search($key, $vals_first, FALSE) === FALSE) {
					$oldVals[] = array($key);
					addResponse($attr . ' ' . $key . ' was removed');
				}
			}
			//for each new/updated one, include its attributes as well
			foreach($vals as $val) {
				$curVal = $state[$attr][$val[0]];
				if(!array_key_exists($val[0], $state[$attr])) { //new member
					$newVals[] = $val;
					addResponse($attr . ' ' . $val[0] . ' was added');
				}
				elseif(count($val) > 1) { //existing member - if this query includes attributes, see if they are updated
					$updated = false;
					for($i = 1; $i < count($val) && !$updated; $i++)
						if($val[$i] != $curVal[$i-1]) {
							$updated = true;
							addResponse($attr . ' ' . $val[0] . ' attr ' . $i . ' was updated from ' . $curVal[$i-1] . ' to ' . $val[$i]);
						}
					if($updated) $newVals[] = $val;
				}
			}
			//send an array that is all the old values followed by all the new ones, where the first element is the number that are old
			if(count($oldVals) + count($newVals) > 0) {
				$data[$attr] = array_merge(array(count($oldVals)), $oldVals, $newVals);
				$curStr = ''; $newStr = '';
				foreach($state[$attr] as $k=>$v) $curStr .= $k . ',';
				foreach($data[$attr] as $arr) $newStr .= $arr[0]. ',';
				addResponse($attr . ': ' . $curStr . ' => ' . $newStr);
			}
		}
	}

	if(count($data) > 0) {
		//foreach($data as $key => $val) if($key == 'request') addResponse('sending: ' . json_encode(array_merge(array('response' => $response), $data)));
		succeed($data); //there were changes to the game state, so send them immediately
	}

	usleep(250000); //hang for 250ms

}while(microtime(true) - $start_time < $time_limit);

$mysqli->close();
addResponse('long_poll: no change');
succeed(array());

?>
