<?php
// Functions used by the scripts called

// Connect to the database using PDO
function databaseConnect($dbHost, $dbUser, $dbPwd, $dbName, $dbPort = 3306) {
	$pdo = new PDO('mysql:host='.$dbHost.';port='.$dbPort.';dbname='.$dbName, $dbUser, $dbPwd);
	$pdo->exec("set names utf8");
	return $pdo;
}

// Check if the user agent is the one passed as argument
function isClientName($clientName) {
	if(startsWith(strtolower($_SERVER['HTTP_USER_AGENT']),$clientName))
		return true;
	else
		return false;
}

// Check if a string starts with $needle
function startsWith($haystack, $needle) {
    return ($needle === "" || strpos($haystack, $needle) === 0);
}