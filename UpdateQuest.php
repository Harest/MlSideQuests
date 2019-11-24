<?php
// Manialink called script - Player completed the quest
require(__DIR__."/includes/functions.php");
require(__DIR__."/class/class.cacheManager.php");
$cacheManager = new cacheManager();

// Check if client is maniaplanet
if(isClientName('maniaplanet')) {
	// Include database info
	require(__DIR__."/includes/dbConfig.php");
	$dbh = databaseConnect($dbHost, $dbUser, $dbPwd, $dbName); // Database Handler

	// Get vars
	$Login = filter_input(INPUT_GET, 'Login');
	$QuestId = intval(filter_input(INPUT_GET, 'QuestId'));
	$UserName = urldecode(filter_input(INPUT_GET, 'UserName'));
	$Positions = urldecode(filter_input(INPUT_GET, 'Positions'));
	$MapUid = filter_input(INPUT_GET, 'MapUid');
	$Time = intval(filter_input(INPUT_GET, 'Time'));
	
	// Check the player already finished or not
	$sql = "SELECT COUNT(*) FROM players WHERE quest_id = :questid AND login = :login";
	$params = array(":questid" => $QuestId,
					":login" => $Login);
	$qh = $dbh->prepare($sql);
	$qexec = $qh->execute($params);
	$alreadyFinished = $qh->fetchColumn();
	
	$processingRequired = false; // See if we need to either insert completion or update best time
	
	// Get completion info if the player already finished, see if the completime time is better
	if ($alreadyFinished == 1) {
		$sql = "SELECT completion_time_best FROM players WHERE quest_id = :questid AND login = :login";
		$params = array(":questid" => $QuestId,
						":login" => $Login);
		$qh = $dbh->prepare($sql);
		$qexec = $qh->execute($params);
		$bestTime = $qh->fetchColumn();
		if ($bestTime > $Time) $processingRequired = true;
	} else {
		$processingRequired = true;
	}

	// Player never finished yet (first time) or best time beaten, starting the checks
	if($processingRequired == true) {
		// MapUid check //
		// Quest info are cached for 1h
		$cacheId = "tm2ml_board_q".$QuestId."_info";
		$QuestInfo = $cacheManager->get($cacheId);
		if ($cacheManager->getCacheHit() == false) {
			$sql = "SELECT * FROM quests WHERE id = :questid"; // Single row res
			$params = array(":questid" => $QuestId);
			$qh = $dbh->prepare($sql);
			$qexec = $qh->execute($params);
			$QuestInfo = $qh->fetch(PDO::FETCH_ASSOC);
			$qh = null;
			
			$cacheManager->set($cacheId, $QuestInfo, 3600);
		}
		
		if ($QuestInfo["map_uid"] != $MapUid) {
			echo "The quest is set to be on a different map...";
			return;
		}
		
		// Positions check //
		$posErrorMarginX = $QuestInfo["tokens_errormargin_x"]; // Error margin values are used for min and max, so doubled
		$posErrorMarginY = $QuestInfo["tokens_errormargin_y"];
		$posErrorMarginZ = $QuestInfo["tokens_errormargin_z"];
		$checkPassed = false;
		$tokensPassed = 0;
		$positions = explode("||", $Positions);
		
		// Select positions of the quest tokens from cache if available and not expired, or db
		// Cache of 1h
		$cacheId = "tm2ml_tokens_q".$QuestId."_full_list";
		$tokens = $cacheManager->get($cacheId);
		if ($cacheManager->getCacheHit() == false) {
			$sql = "SELECT * FROM tokens WHERE quest_id = :questid ORDER BY id ASC";
			$params = array(":questid" => $QuestId);
			$qh = $dbh->prepare($sql);
			$qexec = $qh->execute($params);
			$tokens = $qh->fetchAll(PDO::FETCH_ASSOC);
			$qh = null;
			
			$cacheManager->set($cacheId, $tokens, 3600);
		}
		
		// Compare positions
		$tokensFailed = [];
		$posTokensFailed = [];
		foreach ($tokens as $key => $token) {
			list($idToken, $posToken) = explode("::", $positions[$key]);
			if($idToken != "NULL" and $idToken == $token["id"]) {
				// Check if the positions in the database are != null, else we consider the token obtained
				if ($token["min_pos_x"] === null) {
					$tokensPassed++;
					continue;
				}
				// Get the 3 positions to compare ; Positions are returned like that : <1954., 278.541, 2014.>
				$posToken = substr(substr($posToken, 1), 0, -1); // get rid of the first and last char (< and >)
				list($posX, $posY, $posZ) = explode(",", $posToken);
				$posX = intval($posX);
				$posY = intval($posY);
				$posZ = intval($posZ);
				
				if ($posX >= ($token["min_pos_x"]-$posErrorMarginX) and $posX <= ($token["max_pos_x"]+$posErrorMarginX)
					and $posY >= ($token["min_pos_y"]-$posErrorMarginY) and $posY <= ($token["max_pos_y"]+$posErrorMarginY)
					and $posZ >= ($token["min_pos_z"]-$posErrorMarginZ) and $posZ <= ($token["max_pos_z"]+$posErrorMarginZ))
				{
					$tokensPassed++;
				} else {
					$tokensFailed[] = $key;
					$posTokensFailed[] = "X: ".($token["min_pos_x"]-$posErrorMarginX)."<=".$posX."<=".($token["max_pos_x"]+$posErrorMarginX)." ; Y: ".($token["min_pos_y"]-$posErrorMarginY)."<=".$posY."<=".($token["max_pos_y"]+$posErrorMarginY)." ; Z: ".($token["min_pos_z"]-$posErrorMarginZ)."<=".$posZ."<=".($token["max_pos_z"]+$posErrorMarginZ);
				}
			}
		}
		
		if ($tokensPassed == count($tokens)) $checkPassed = true;
		
		// Check OK, insert completion in db for a first time, update otherwise
		if ($checkPassed) {
			if ($alreadyFinished == 0) {
				$sql = "INSERT INTO players (quest_id, login, nickname, completion_time_first, completion_time_best) VALUES (:questid, :login, :nickname, :time, :time)";
			} else {
				$sql = "UPDATE players SET nickname = :nickname, completion_date_best = NOW(), completion_time_best = :time WHERE quest_id = :questid AND login = :login";
			}
			$params = array(":login" => $Login,
							":questid" => $QuestId,
							":nickname" => $UserName,
							":time" => $Time);
			$qh = $dbh->prepare($sql);
			$qexec = $qh->execute($params);
			$qh = null;
			if ($qexec) {
				if ($alreadyFinished == 0) {
					echo "Congrats, you completed this quest!";
				} else {
					echo "Congrats, you beat your best time for this quest!";
				}
			} else {
				echo "An error occured while processing your completion, sorry :/. Error: ".htmlentities($qh->errorInfo()[2], ENT_XML1)." (s".intval($qh->errorInfo()[0])."d".$qh->errorInfo()[1].")";
			}
		} else {
			echo "Sorry, seems like you didn't collect all the tokens or an error occured.";
		}
	} else {
		echo "You already completed this quest and didn't beat your best time, hope you still enjoyed!";
	}

} else {
	echo "Sorry, something went wrong.";
}
