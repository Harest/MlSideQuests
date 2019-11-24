<?php
// Manialink main script - Generate the ManiaLink Script for ManiaPlanet
require(__DIR__."/includes/functions.php");
require(__DIR__."/class/class.cacheManager.php");
$cacheManager = new cacheManager();

if(isClientName('maniaplanet'))
{
// Get vars
$QuestId = intval(filter_input(INPUT_GET, 'QuestId')); // Quest Id needed for every request
$State = strtolower(filter_input(INPUT_GET, 'State')); // Action to do, required for every request
$Token = filter_input(INPUT_GET, 'Token'); // Used for the RemoveToken state
$SimpleBoard = boolval(filter_input(INPUT_GET, 'SimpleBoard')); // Used to display a board without start quest button (e.g. quest started elsewhere w/o the board)
$SimpleBoard = ($SimpleBoard == true) ? "True" : "False";
$ShootManiaCall = boolval(filter_input(INPUT_GET, 'SM')); // Used to know if it's a ShootMania call, else it's considered a TM² one
$HideGuiGet =  strtolower(filter_input(INPUT_GET, 'HideGui'));
$DisplayPosition = boolval(filter_input(INPUT_GET, 'DisplayPosition')); // Used during map edition to know your exact position (call it w/ MT clip)

// Set constants used in the script
$BgColor = "000B"; // Background color of the board window
$HideGui = "no";
if ($HideGuiGet == "yes") {
// Hide the background and some UI elements when you click on the "Start Quest" button of the board
// Useful if you don't want to display another window to the player when (s)he starts the quest
	$HideGui = "yes";
	$BgColor = "000F";
}
// Name of the RaceTime constant of GUIPlayer in TM²/SM used for a check
$startTimeVar = "RaceStartTime";
// Name of the RequireContext condition for the script to run in-game
$RequiredContext = "CTmMlScriptIngame";
if ($ShootManiaCall == true) {
	$startTimeVar = "StartTime";
	$RequiredContext = "CSmMlScriptIngame";
}
// URL used in the few requests done in the script (to get the board, the tokens and register a completed quest)
$urlToRequest = ($_SERVER['HTTPS'] == "on") ? "https://" : "http://";
$urlToRequest .= $_SERVER['HTTP_HOST'].str_replace("Main.php", "", $_SERVER['SCRIPT_NAME']);

// Get the quest info
$QuestInfo = array("title" => "", "map_uid" => "", "author_login" => "");
if ($State == "board" or $State == "start") {
	// Quest info are cached for 1h
	$cacheId = "tm2ml_board_q".$QuestId."_info";
	$QuestInfo = $cacheManager->get($cacheId);
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
}

// If we only want to log the Player positions for mapping purpose, we just return this script
// Display the player position constantly
// Press Enter of Backspace to stop the loop
if ($DisplayPosition) {
	echo '
	<manialink version="2">
	<frame pos="-27.0 53.2" size="53.8 8.8">
		<label id="Position" hidden="0" text="" pos="-27.0 53.2" size="53.8 8.8" textprefix="$o$s" textcolor="FFFF" valign="top" halign="center"/>
	</frame>
	<script><!--
	#RequireContext '.$RequiredContext.'
	#Include "TextLib" as TextLib
	main() {
		declare CMlLabel LblPosition = (Page.GetFirstChild("Position") as CMlLabel);
		declare Boolean Continue = True;
		declare Integer DisplayFrame = 10;
		declare Integer CurrentFrame = 0;
		while(Continue) {
			if (DisplayFrame < CurrentFrame) {
				LblPosition.SetText(TextLib::ToText(GUIPlayer.Position));
				CurrentFrame = 0;
			}
			CurrentFrame += 1;
			
			foreach (Event in PendingEvents)
			{
				if (Event.KeyCode == 20 || Event.KeyCode == 109)
				{
					Continue = False;
					LblPosition.SetText("");
					log("DisplayPositionScript stopped.");
				}
			}
			yield;
		}
	}
	--></script>
	</manialink>';
	return;
}

// Echo whole manialink
echo '
	<manialink version="2">
		<timeout>15</timeout>
		<frame id="Window" hidden="0">
			<quad id="Background" hidden="0" sizen="160 120" posn="0 80 1"  valign="top"  bgcolor="'.$BgColor.'" halign="center"/>
			<quad id="TitleBackground" hidden="0" sizen="160 10" posn="0 80 2"  valign="top"  bgcolor="000F" halign="center"/>
			<label id="Close" style="TextValueBig" hidden="0" sizen="30 10" posn="73 78.5 3" text="x" textcolor="F30F" scriptevents="1" valign="top" halign="center"/>
			<label id="Title" hidden="0" text="" sizen="160 90" posn="0 77.5 3" textprefix="$o$s" textcolor="FFFF" valign="top" halign="center"/>
			<label id="Message" hidden="0" text="" sizen="130 90" posn="-60 60 3" textcolor="FF0F" maxline="25" autonewline="1" valign="top" halign="left"/>
			<label id="StartQuest" style="CardButtonMedium" hidden="1" sizen="100 10" posn="0 -30 3" text="Start Quest" textcolor="FF0F" scriptevents="1" valign="top" halign="center"/>
			<label id="Left" style="TextValueBig" hidden="0" sizen="30 10" posn="-30 -20 3" text="<" scriptevents="1" valign="top" halign="center"/>
			<label id="Right" style="TextValueBig" hidden="0" sizen="30 10" posn="30 -20 3" text=">" scriptevents="1" valign="top" halign="center"/>

		</frame>
		<script><!--
			#RequireContext '.$RequiredContext.'
			#Include "TextLib" as TextLib
			#Include "MathLib" as MathLib
			
			// Defining struct for the requests responses
			#Struct SPlayer {
				Text login;
				Text nickname;
				Text firstTime;
				Text bestTime;
			}
			#Struct SBoardJsonResponse {
				SPlayer[] Players;
				Text QuestShortDesc;
				Text QuestFullDesc;
				Text QuestTitleList;
				Text QuestTitleEmptyList;
			}
			#Struct SToken {
				Integer id;
			}
			#Struct STokensJsonResponse {
				SToken[] Tokens;
			}
			
			// Global declarations //
			// QuestId concerned
			declare Integer G_QuestId;
			
			// Quest window UI vars
			declare CMlFrame Window;
			declare CMlLabel Title;
			declare CMlLabel Message;
			declare CMlLabel Left;
			declare CMlLabel Right;
			declare Text HideGui;
			
			// Variables used to display the players list properly in the board
			declare Integer MaxNumPerPage;
			declare Integer NumOfPages;
			declare Integer CurPage;

			// Check if all the tokens are collected to trigger server processing if yes
			Boolean TokensAreCollected()
			{
				declare persistent Boolean[][Integer] Per_LocalTokensCollected for Map;
				
				if (Per_LocalTokensCollected[G_QuestId].count == 0) return False;

				for(i,0,Per_LocalTokensCollected[G_QuestId].count-1)
				{
					if (!Per_LocalTokensCollected[G_QuestId][i]) return False;
				}
				return True;
			}
			
			// Clear persistent data
			// Called when the quest is started, supposedly completed or RaceStartTime mismatch 
			Void clearPersistentData() {
				declare persistent Boolean[][Integer] Per_LocalTokensCollected for Map;
				declare persistent Text[][Integer] Pe_LocalTokensIds for Map;
				declare persistent Text[][Integer] Pe_PlayerPos for Map;
				declare persistent Integer[Integer] P_PlayerRaceStartTime for Map;
				declare persistent Text[Integer] P_QuestMapUid for Map;
				
				Per_LocalTokensCollected[G_QuestId] = [];
				Pe_LocalTokensIds[G_QuestId] = [];
				Pe_PlayerPos[G_QuestId] = [];
				P_PlayerRaceStartTime[G_QuestId] = 0;
				P_QuestMapUid[G_QuestId] = "";
			}
			
			// Display players list in the board (players who completed the quest)
			/*	Param SPlayer[] playersList = List of players logins and nicknames
				Param Text QuestShortDesc = Short description of the quest displayed alongside players list
				Param Text QuestTitleList = Sentence displayed above non empty players list
				Param Text QuestTitleEmptyList = Sentence displayed above an empty players list
			*/
			Void displayPlayers(SPlayer[] playersList, Text QuestShortDesc, Text QuestTitleList, Text QuestTitleEmptyList) {
				declare Integer StartPos = (CurPage * MaxNumPerPage) - MaxNumPerPage;
				declare Integer MaxLimit = (CurPage * MaxNumPerPage) - 1;
				declare Integer nbPlayers = playersList.count;
				declare Text PluralPlayers = "player";
				
				Message.SetText(QuestShortDesc ^ "\n\n");
				
				if (nbPlayers > 1) PluralPlayers = "players";
				if (nbPlayers >= 1) {
					declare Text QuestTitleToDisplay = TextLib::Replace(QuestTitleList, "[count]", nbPlayers ^ " " ^ PluralPlayers);
					Message.SetText(Message.Value ^ QuestTitleToDisplay ^ "\n");
					if (CurPage == 1) Message.SetText(Message.Value ^ "$n$fffNote: This list is cached 10 minutes, ordered by first completion date.$z\n");
				} else {
					Message.SetText(Message.Value ^ QuestTitleEmptyList ^ "\n$n$fffNote: This list is cached 10 minutes.$z");
				}
				for(i, StartPos, MaxLimit)
				{
					if (i > (nbPlayers - 1))
					{
						Right.Visible = False;
						break;
					}
					if (playersList.existskey(i))
					{
						declare Text RankDisplay = TextLib::ToText(i+1);
						if (i < 9) RankDisplay = "0" ^ TextLib::ToText(i+1);
						Message.SetText(Message.Value ^ "$fff#" ^ RankDisplay ^ ":$g\t" ^ playersList[i].nickname ^ "$z $fff(" ^ playersList[i].login ^ ") in " ^ playersList[i].firstTime ^ " (PB: " ^ playersList[i].bestTime ^ ")\n");
					}
				}
			}
			
			// Log tokens when the quest has started and at each step/token collected
			// Also returns the player positions list at each token as csv
			Text LogMissingTokens() 
			{
				declare persistent Boolean[][Integer] Per_LocalTokensCollected for Map;
				declare persistent Text[][Integer] Pe_LocalTokensIds for Map;
				declare persistent Text[][Integer] Pe_PlayerPos for Map;
				declare persistent Boolean DebugMode for Map;
				
				declare Text LogMsg = "Quest " ^ TextLib::ToText(G_QuestId) ^ " - Tokens missing: ";
				declare Text Pe_PlayerPosString = "";
				declare Integer NbMissingTokens = 0;
				declare Integer NbTotalTokens = Per_LocalTokensCollected[G_QuestId].count;
				for(i,0,NbTotalTokens-1)
				{
					Pe_PlayerPosString = Pe_PlayerPosString ^ Pe_LocalTokensIds[G_QuestId][i] ^ "::" ^ Pe_PlayerPos[G_QuestId][i] ^ "||";
					if (!Per_LocalTokensCollected[G_QuestId][i]) {
						if (DebugMode) LogMsg = LogMsg ^ Pe_LocalTokensIds[G_QuestId][i] ^ ", ";
						NbMissingTokens += 1;
					}
				}
				if (DebugMode) 
				{
					LogMsg = LogMsg ^ " | " ^ NbMissingTokens ^ "/" ^ NbTotalTokens;
					log(LogMsg);
				}
				return Pe_PlayerPosString;
			}

			// Main script
			main()
			{
				// Local storage
				declare persistent Boolean[][Integer] Per_LocalTokensCollected for Map;
				declare persistent Text[][Integer] Pe_LocalTokensIds for Map;
				declare persistent Text[][Integer] Pe_PlayerPos for Map;
				declare persistent Integer[Integer] P_PlayerRaceStartTime for Map;
				declare persistent Text[Integer] P_QuestMapUid for Map;
				declare persistent Boolean DebugMode for Map;
				G_QuestId = '.htmlentities($QuestId, ENT_XML1).';
				
				// Debug mode for the author of the map only
				if (!DebugMode && LocalUser.Login == "'.htmlentities($QuestInfo["author_login"], ENT_XML1).'") {
					DebugMode = True;
				}
				
				HideGui = "'.htmlentities($HideGui, ENT_XML1).'";
				if (DebugMode) log("---- Hide gui = [" ^ HideGui ^ "] ---- Debug Mode = [" ^ TextLib::ToText(DebugMode) ^ "]");
				
				// Entering the main script if the user exists and is not spectating
				if (GUIPlayer != Null && !IsSpectatorClient)
				{
					Window = (Page.GetFirstChild("Window") as CMlFrame);
					Title = (Page.GetFirstChild("Title") as CMlLabel);
					Message = (Page.GetFirstChild("Message") as CMlLabel);
					Window.Visible = False;
					declare Text State = "'.htmlentities($State, ENT_XML1).'";
					switch (State)
					{
						case "board":
						{
							// Board Request Processing - Display the quest window
							
							// Get window labels and start to show & config it
							Window.Visible = True;
							Left = (Page.GetFirstChild("Left") as CMlLabel);
							Right = (Page.GetFirstChild("Right") as CMlLabel);
							declare CMlLabel StartQuest = (Page.GetFirstChild("StartQuest") as CMlLabel);
							declare Boolean HideStartButton = '.htmlentities($SimpleBoard, ENT_XML1).';
							Title.SetText("'.htmlentities($QuestInfo["title"], ENT_XML1).'");
							
							// Requesting quest info and players who completed it
							if (DebugMode) log("Retrieving quest " ^ TextLib::ToText(G_QuestId) ^ " info...");
							declare CHttpRequest request;
							request = Http.CreateGet("'.$urlToRequest.'GetBoard.php?QuestId=" ^ TextLib::ToText(G_QuestId) ^ "&" ^ Now);
							wait(request.IsCompleted);
							
							// Assigning results
							declare SBoardJsonResponse Response;
							Response.fromjson(request.Result);
							declare SPlayer[] Parts = Response.Players;
							declare Text QuestShortDesc = Response.QuestShortDesc;
							declare Text QuestFullDesc = Response.QuestFullDesc;
							declare Text QuestTitleList = Response.QuestTitleList;
							declare Text QuestTitleEmptyList = Response.QuestTitleEmptyList;
							
							// Display parameters
							MaxNumPerPage = 10;
							NumOfPages = 0;
							CurPage = 1;
							NumOfPages = MathLib::CeilingInteger(Parts.count / (MaxNumPerPage + 0.0));
							if (!HideStartButton) {
								StartQuest.Visible = True;
								StartQuest.SetText("Start Quest");
							}
							Left.Visible = False;
							
							displayPlayers(Parts, QuestShortDesc, QuestTitleList, QuestTitleEmptyList); // Display players list who completed the quest
							
							// Start a loop waiting for the quest to either be started of the quest window to be closed
							declare Boolean Loopme = True;
							while(Loopme)
							{
								foreach (Event in PendingEvents)
								{
									if (Event.Type == CMlScriptEvent::Type::MouseClick)
									{
										if(Event.ControlId=="StartQuest")
										{
											if(StartQuest.Value == "Start Quest")
											{
												// Starting the quest
												
												
												StartQuest.SetText("OK");
												Left.Hide();
												Right.Hide();
												clearPersistentData(); // Reset any persistent data that would remain
												
												if (P_QuestMapUid[G_QuestId] == "") P_QuestMapUid[G_QuestId] = "'.htmlentities($QuestInfo["map_uid"], ENT_XML1).'";
												if (DebugMode) log("MapUid for quest " ^ TextLib::ToText(G_QuestId) ^ " set to: " ^ P_QuestMapUid[G_QuestId]);
												P_PlayerRaceStartTime[G_QuestId] = GUIPlayer.'.$startTimeVar.';
												
												// Check if the MapUid matches
												if (P_QuestMapUid[G_QuestId] != Map.MapInfo.MapUid) {
													Message.SetText("$b00Sorry, an error occured preventing you from starting the quest.\nThis is due to a map version mismatch.\nYou may want to update the map to do this!");
													if (DebugMode) log("MapUid mismatch. Current MapUid = " ^ Map.MapInfo.MapUid);
												} else {
													// MapUid matches, requesting quest tokens list
													if (DebugMode) log("Retrieving quest " ^ TextLib::ToText(G_QuestId) ^ " tokens...");
													request = Http.CreateGet("'.$urlToRequest.'GetTokens.php?QuestId=" ^ TextLib::ToText(G_QuestId) ^ "&" ^ Now);
													wait(request.IsCompleted);
													declare STokensJsonResponse Response;
													Response.fromjson(request.Result);
													declare SToken[] Parts = Response.Tokens;
													
													for(n,0,Parts.count-1)
													{
														Per_LocalTokensCollected[G_QuestId].add(False);
														Pe_LocalTokensIds[G_QuestId].add(TextLib::ToText(Parts[n].id));
														Pe_PlayerPos[G_QuestId].add("");
													}

													// Add quest info only if DisplayGUI = yes
													if(HideGui == "yes")
													{
														declare CMlQuad QuadGui = (Page.GetFirstChild("Background") as CMlQuad);
														QuadGui.Hide();
														QuadGui = (Page.GetFirstChild("TitleBackground") as CMlQuad);
														QuadGui.Hide();
														declare CMlLabel LabelGui = (Page.GetFirstChild("Close") as CMlLabel);
														LabelGui.Hide();
														LabelGui = (Page.GetFirstChild("Title") as CMlLabel);
														LabelGui.Hide();
														Message.SetText("");
													}
													else
													{
														Message.SetText(QuestFullDesc ^ "\n\n$fffHave fun!\n\n$nNote: You need to complete the quest in the same race from where you started it. If you restart (e.g. by pressing del), you\'ll need to start the quest again. Several quests can be done in the same race.$z");
													}
													
													// Log all tokens for quest if debug activated
													if (DebugMode)
													{
														log("Quest " ^ TextLib::ToText(G_QuestId) ^ " started.");
														LogMissingTokens();
													}
												
												}

												break;
											}
											else
											{
												declare CMlFrame Window = (Page.GetFirstChild("Window") as CMlFrame);
												Window.Visible = False;
												Loopme  = False;
												break;
											}
										}
										else if(Event.ControlId=="Left")
										{
											Left.Visible = True;
											Right.Visible = True;
											CurPage -= 1;
											if (CurPage == 1) Left.Visible = False;
											displayPlayers(Parts, QuestShortDesc, QuestTitleList, QuestTitleEmptyList);
										}
										else if(Event.ControlId=="Right")
										{
											Left.Visible = True;
											Right.Visible = True;
											CurPage += 1;
											if (CurPage == NumOfPages) Right.Visible = False;
											displayPlayers(Parts, QuestShortDesc, QuestTitleList, QuestTitleEmptyList);
										}
										else if(Event.ControlId=="Close")
										{
											declare CMlFrame Window = (Page.GetFirstChild("Window") as CMlFrame);
											Window.Visible = False;
											Loopme = False;
										}
									}
								}
								yield;
							}
						}
						case "start":
						{
							// Start Request Processing - Start a quest without any board display in the first place

							clearPersistentData(); // Reset any persistent data that would remain for the quest
							
							if (P_QuestMapUid[G_QuestId] == "") P_QuestMapUid[G_QuestId] = "'.htmlentities($QuestInfo["map_uid"], ENT_XML1).'";
							if (DebugMode) log("MapUid for quest " ^ TextLib::ToText(G_QuestId) ^ " set to: " ^ P_QuestMapUid[G_QuestId]);
							P_PlayerRaceStartTime[G_QuestId] = GUIPlayer.'.$startTimeVar.';
							
							// Check if the MapUid matches
							if (P_QuestMapUid[G_QuestId] != Map.MapInfo.MapUid) {
								Window.Visible = True;
								Message.SetText("$b00Sorry, an error occured preventing you from starting the quest.\nThis is due to a map version mismatch.\nYou may want to update the map to do this!\nPress restart to continue.");
								if (DebugMode) log("MapUid mismatch. Current MapUid = " ^ Map.MapInfo.MapUid);
							} else {
								// MapUid matches, requesting quest tokens list
								if (DebugMode) log("Retrieving quest " ^ TextLib::ToText(G_QuestId) ^ " tokens...");
								declare CHttpRequest request;
								request = Http.CreateGet("'.$urlToRequest.'GetTokens.php?QuestId=" ^ TextLib::ToText(G_QuestId) ^ "&" ^ Now);
								wait(request.IsCompleted);
								declare STokensJsonResponse Response;
								Response.fromjson(request.Result);
								declare SToken[] Parts = Response.Tokens;
								
								for(n,0,Parts.count-1)
								{
									Per_LocalTokensCollected[G_QuestId].add(False);
									Pe_LocalTokensIds[G_QuestId].add(TextLib::ToText(Parts[n].id));
									Pe_PlayerPos[G_QuestId].add("");
								}
								
								// Log all tokens for quest if debug activated
								if (DebugMode)
								{
									log("Quest " ^ TextLib::ToText(G_QuestId) ^ " started.");
									LogMissingTokens();
								}
							}
						}
						case "removetoken":
						{
							// Remove token Request Processing - Token triggered, we set it as collected
							
							// If a call is made without the quest started, we stop there
							if (!Per_LocalTokensCollected.existskey(G_QuestId) || Pe_LocalTokensIds[G_QuestId].count == 0) return;
							
							// Check we\'re in the same race than when the quest started
							if (P_PlayerRaceStartTime[G_QuestId] != GUIPlayer.'.$startTimeVar.') {
								if (DebugMode) log("RaceStartTime mismatch: " ^ TextLib::ToText(P_PlayerRaceStartTime[G_QuestId]) ^ " / " ^ GUIPlayer.'.$startTimeVar.');
								clearPersistentData();
								return;
							}
							
							declare Text RemoveToken = "'.htmlentities($Token, ENT_XML1).'";
							// We set the right token state as collected (True)
							for(i,0,Pe_LocalTokensIds[G_QuestId].count-1)
							{
								if (TextLib::ToLowerCase(RemoveToken) == TextLib::ToLowerCase(Pe_LocalTokensIds[G_QuestId][i]))
								{
									if (DebugMode) log("Quest " ^ TextLib::ToText(G_QuestId) ^ " - Token " ^ Pe_LocalTokensIds[G_QuestId][i] ^ " collected");
									Per_LocalTokensCollected[G_QuestId][i] = True;
									Pe_PlayerPos[G_QuestId][i] = TextLib::ToText(GUIPlayer.Position);
									break;
								}

							}

							// If all the tokens are collected we submit the completion to the server for processing
							if (TokensAreCollected())
							{
								// Get the player positions list at each token as csv
								// Log all tokens for quest if debug activated
								declare Text Pe_PlayerPosString = LogMissingTokens();
								
								// Get quest completion time
								declare Integer QuestCompletionTime = GameTime - P_PlayerRaceStartTime[G_QuestId];
								
								declare CHttpRequest request;
								if (DebugMode) log("Quest " ^ TextLib::ToText(G_QuestId) ^ " completed. Processing...");
								request = Http.CreateGet("'.$urlToRequest.'UpdateQuest.php?QuestId=" ^ TextLib::ToText(G_QuestId) ^ "&Login=" ^ LocalUser.Login ^ "&UserName=" ^ TextLib::URLEncode(LocalUser.Name) ^ "&Positions=" ^ TextLib::URLEncode(Pe_PlayerPosString) ^ "&MapUid=" ^ P_QuestMapUid[G_QuestId] ^ "&Time=" ^ TextLib::ToText(QuestCompletionTime) ^ "&" ^ Now);
								clearPersistentData();
								wait(request.IsCompleted);
								if (DebugMode) {
									log("All tokens collected.");
								}
								// Only log visible to anyone that returns what happened, can be useful
								log(request.Result);
							} else if (DebugMode) {
								LogMissingTokens();
							}
						}
					}
				}
				else
				{
					declare CMlFrame Window = (Page.GetFirstChild("Window") as CMlFrame);
					Window.Visible = False;
					if (DebugMode) log("IsSpectatorClient: " ^ TextLib::ToText(IsSpectatorClient));
				}
			}
		--></script>
	</manialink>';

} else {
	$client = strtolower($_SERVER['HTTP_USER_AGENT']);
	echo '
	<manialink version="2">
	<script><!--
	main()
	{
		log("Client id: " ^ "'.htmlentities($client, ENT_XML1).'" ^ " does not match");
	}
	--></script>
	</manialink>';

}
