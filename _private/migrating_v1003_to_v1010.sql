ALTER TABLE `players` CHANGE `completion_date` `completion_date_first` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP; 
ALTER TABLE `players` ADD `completion_date_best` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `completion_date_first`, ADD `completion_time_first` INT NOT NULL AFTER `completion_date_best`, ADD `completion_time_best` INT NOT NULL AFTER `completion_time_first`; 

-- You'll need to update the completion times yourself if values already exist in the table