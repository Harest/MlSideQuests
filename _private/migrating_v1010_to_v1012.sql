ALTER TABLE `tokens` CHANGE `min_pos_y` `min_pos_y` SMALLINT(5) NULL DEFAULT NULL, CHANGE `max_pos_y` `max_pos_y` SMALLINT(5) NULL DEFAULT NULL; 
ALTER TABLE `tokens` CHANGE `quest_id` `quest_id` SMALLINT(5) UNSIGNED NOT NULL; 

-- Underground mapping leads to negative Y positions, need to remove that unsigned attribute on min/max y pos