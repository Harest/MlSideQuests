<?php
// Manialink called script - Get the players list to display on board
require(__DIR__."/includes/functions.php");
require(__DIR__."/class/class.cacheManager.php");
$cacheManager = new cacheManager();

// Check if client is maniaplanet
if(isClientName('maniaplanet')) {
	// Get vars
	$QuestId = intval(filter_input(INPUT_GET, 'QuestId'));
	
	// Select players & quest info from cache if available and not expired, or db //
	
	// Quest info is cached for 1h
	$cacheId = "tm2ml_board_q".$QuestId."_info";
	$QuestInfo = $cacheManager->get($cacheId);
	$cacheHit = $cacheManager->getCacheHit();
	if ($cacheManager->getCacheHit() == false) {
		// Include database info
		require(__DIR__."/includes/dbConfig.php");
		$dbh = databaseConnect($dbHost, $dbUser, $dbPwd, $dbName); // Database Handler
		
		$sql = "SELECT * FROM quests WHERE id = :questid"; // Single row res
		$params = array(":questid" => $QuestId);
		$qh = $dbh->prepare($sql);
		$qexec = $qh->execute($params);
		$QuestInfo = $qh->fetch(PDO::FETCH_ASSOC);
		$qh = null;
		
		$cacheManager->set($cacheId, $QuestInfo, 3600);
	}
	
	// Players list is cached 10 min.
	$cacheId = "tm2ml_board_q".$QuestId."_players_list";
	$playersList = $cacheManager->get($cacheId);
	if ($cacheManager->getCacheHit() == false) {
		if (!isset($dbh)) {
			// Include database info
			require(__DIR__."/includes/dbConfig.php");
			$dbh = databaseConnect($dbHost, $dbUser, $dbPwd, $dbName); // Database Handler
		}
		$sql = "SELECT login, nickname FROM players WHERE quest_id = :questid AND status = 1 ORDER BY completion_date ASC";
		$params = array(":questid" => $QuestId);
		$qh = $dbh->prepare($sql);
		$qexec = $qh->execute($params);
		$players = $qh->fetchAll(PDO::FETCH_ASSOC);
		$qh = null;
		// Return the results
		$playersList = array("Players" => $players,
							 "QuestShortDesc" => ($QuestInfo["description_short"] != null) ? htmlentities($QuestInfo["description_short"], ENT_XML1) : "",
							 "QuestFullDesc" => ($QuestInfo["description_full"] != null) ? htmlentities($QuestInfo["description_full"], ENT_XML1) : "",
							 "QuestTitleList" => ($QuestInfo["board_head_sentence"] != null) ? htmlentities($QuestInfo["board_head_sentence"], ENT_XML1) : "",
							 "QuestTitleEmptyList" => ($QuestInfo["board_head_sentence_empty"] != null) ? htmlentities($QuestInfo["board_head_sentence_empty"], ENT_XML1) : ""
							 );
		$cacheManager->set($cacheId, $playersList, 600);
	}
	echo json_encode($playersList);
	
} else {
	echo "Sorry, something went wrong.";
}