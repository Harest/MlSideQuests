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

// Convert a time in milliseconds as a DateInterval
function gameTimeToDateInterval($time) {
	$seconds = floor($time / 1000);
	$dateInterval = new \DateInterval('P0D');
	$dateInterval->y = floor($seconds / (365 * 24 * 60 * 60));
	$dateInterval->m = floor(($seconds - ($dateInterval->y * 365 * 24 * 60 * 60)) / (30 * 24 * 60 * 60));
	$dateInterval->d = floor(($seconds - ($dateInterval->m * 30 * 24 * 60 * 60)) / (24 * 60 * 60));
	$dateInterval->h = floor(($seconds - ($dateInterval->d * 24 * 60 * 60)) / (60 * 60));
	$dateInterval->i = floor(($seconds - ($dateInterval->h * 60 * 60)) / (60));
	$dateInterval->s = floor($seconds % 60);
	$dateInterval->f = $time - ($seconds * 1000);
	return $dateInterval;
}