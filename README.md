# TM2 / SM Manialink Side Quests info  
[Official forum link](https://forum.maniaplanet.com/viewtopic.php?f=266&t=46335)  
## Introduction  
This is a tool usable in Trackmania² and ShootMania which are both on ManiaPlanet. It allows you to add quests inside your maps, or more specifically, to keep track of the players who completed these quests as the rest is managed by the MediaTracker.  
It's made of ManiaScript alongside PHP/SQL to read/write data used in the quests. This ManiaScript is loaded in your maps from a Manialink call inside a MediaTracker clip.  

## How it works  
Each quest has a set of `tokens`. The tokens are conceptually the things the player needs to collect, while realistically it's your MediaTracker triggers, so it's where the player need to be at some point.
Currently, there's no display output by this script of the quest progression. Everything will have to be done with your MediaTracker.  
This means you can currently do two things properly:  

* Either the supposedly last token will always be the last token gathered by players in a specific place and you don't need to restrict the order with MediaTracker clips condition. You'll be able to set a MediaTracker trigger at that place to display something to the player for the quest completion.  
* Either you can't be sure of the order in which tokens will be collected so you establish your own order (either by giving hints to the player or not). Here you'll restrict the order with MediaTracker clips condition as explained below.  

So, if you followed, this currently exclude a case: handling a quest where tokens don't have any specific order and can be collected in a common place. While players will be able to complete it, the issue is that you'll not be able to know it and do something with that. The Manialink calls from the MediaTracker work in one-way. It'd require the script and not the MediaTracker to handle it which is not yet a possibility.  

## How to install  
Unless authorized to use the manialink MLSideQuests, you'll need to create one (thankfully the first is free) [over there](https://www.maniaplanet.com/account/manialinks).  
It needs to point out to wherever you hosted the scripts, and on the Main.php script.  
Yes, you need to host the scripts and create the database (see `_private` folder, file `tm2ml_sidequests.sql` for the tables). I'll let you find a web host yourself for that, there's a lot of free ones that should do the job fine.  
The database connection info need to be set in `includes/dbConfig.php`.  
Note: While Memcached is used to cache some data, mostly database query results, it'll still work fine without having Memcached installed. If you know how to edit the scripts, feel free to strip all the Memcached layer for your own Manialink.  

Once it's done, you'll need to populate the database tables `quests` and `tokens`.  
Quests table is kinda self-explanatory, what the tokens are is explained above.  
Some values may stay `NULL`, it's as you want. Not everything is used as of yet, like the tokens names or the map MX id. They're there for an eventual improvement of all this with a web interface for instance to admin it.  
To help you set the positions of the tokens (if you want to, positions are not needed if you don't care), you can find a MapEditor Plugin in `_private`: `CursorCoordsMod.Script.txt`. You need to place it in `Documents/ManiaPlanet/Scripts/EditorPlugins`. It's quite primitive but it mostly does the job.  

## MediaTracker information and how to use  
There's several things to know here:  

* You can't place several triggers on the same place without having some conflicts. Avoid that. Even if they've opposite conditions, it won't work properly.
* You can only place MediaTracker triggers inside the building area. It sounds obvious but just in case...
* There's two very useful conditions you'll want to use : `not yet triggered X`, `already triggered X`. You may have never understand what that X is. Well, it's the index of the MT clip. 	An index is the position of the clip in the list minus 1 because in programming languages most of the data structures start at 0. To help you a bit : there's 9 clips / page.  
So, what you can do is for instance with a quest start in the clip index 5, specify that any token that doesn't have any order to be collected need a condition of `already triggered 5`.  
What you can also do if there's a real tokens order is to set the first one at `already triggered index of quest start` and the others to `already triggered index of token-1`.  

Now, here's the format of the Manialinks. You want to replace `MLSideQuests` by the name of your own manialink.  
You'll need to specify the QuestId value and the Token id value if needed depending on the State.  
To use it in ShootMania you only need to add the parameter `SM=1` in each Manialink call (don't forget the & before).  

### Start a quest  
There's two ways for that:  

* By displaying the board with a start quest button: `MLSideQuests?State=board&QuestId=1`  
* By starting the quest without board: `MLSideQuests?State=start&QuestId=1`  
Note: In that case, if you want to display the board somewhere, you can by calling: `MLSideQuests?State=board&QuestId=1&SimpleBoard=1`  

### Set a token as collected (remove token)  
`MLSideQuests?State=removetoken&QuestId=1&Token=1`  

### See your position while mapping  
This one is available for everyone. It returns a specific script that'll display your position while racing. Useful to set the tokens of your quests at their correct position in the database.  
You can stop the display by either pressing `Enter` or `Backspace`.  
`MLSideQuests?DisplayPosition=1`  

That's it, enjoy creating quests for your maps!  

## Thanks  

* Maxi031 for the initial script which i took as a great base. You can still find most of what he did in there.  
* Anyone who answered my questions regarding all that stuff which was new to me (ManiaScript, manialinks, ...) : Dommy, Reaby, Miss, Cgbd, Smoke, MrLag, ... Sorry if i forgot someone.