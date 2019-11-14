<?php
// Manialink called script - Get the tokens list from a quest
require(__DIR__."/includes/functions.php");
require(__DIR__."/class/class.cacheManager.php");
$cacheManager = new cacheManager();

// Check if client is maniaplanet
if(isClientName('maniaplanet')) {
	// Get vars
	$QuestId = intval(filter_input(INPUT_GET, 'QuestId'));
	
	// Select tokens from cache if available and not expired, or db
	// Cache of 1h
	$cacheId = "tm2ml_tokens_q".$QuestId."_id_list";
	$tokensList = $cacheManager->get($cacheId);
	if ($cacheManager->getCacheHit() == false) {
		// Include database info
		require(__DIR__."/includes/dbConfig.php");
		$dbh = databaseConnect($dbHost, $dbUser, $dbPwd, $dbName); // Database Handler
		
		$sql = "SELECT id FROM tokens WHERE quest_id = :questid ORDER BY id ASC";
		$params = array(":questid" => $QuestId);
		$qh = $dbh->prepare($sql);
		$qexec = $qh->execute($params);
		$tokensList = $qh->fetchAll(PDO::FETCH_ASSOC);
		$qh = null;
		
		$cacheManager->set($cacheId, $tokensList, 3600);
	}
	echo json_encode(array("Tokens" => $tokensList));
	
} else {
	echo "Sorry, something went wrong.";
}