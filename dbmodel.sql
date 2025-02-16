--
-- @author Florent HAZARD <f.hazard@sowapps.com>
-- @copyright 2022 Funnyfox
--


-- dbmodel.sql
-- DOC : https://en.doc.boardgamearena.com/Game_database_model:_dbmodel.sql
-- For comments: Use only "--" and with its own line, nothing else, no code.

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Token Entity for all game tokens
-- Includes: ressource cards, restaurants, terrace tiles, pigeon cards, objective cards and the majority card.
CREATE TABLE IF NOT EXISTS `token` (
	`id`              INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
	`type`            TINYINT(9) UNSIGNED NOT NULL,
	`variant`         TINYINT(2) UNSIGNED  DEFAULT NULL,
	`orientation`     TINYINT(1) UNSIGNED  DEFAULT NULL,
	`container`       TINYINT(1) UNSIGNED NOT NULL,
	-- Position in hand/river
	-- Coordinates : from left to right, smaller to greater values
	`position`        SMALLINT(3) UNSIGNED DEFAULT 0,
	-- In which player's hand ?
	`player_id` INT (10) UNSIGNED DEFAULT NULL,
	-- Property of which restaurant ?
	`parent_token_id` INT (10) UNSIGNED DEFAULT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`player_id`) REFERENCES `player` (`player_id`)
) ENGINE = InnoDB DEFAULT CHARSET = `utf8` AUTO_INCREMENT = 1;

ALTER TABLE `player`
	-- Must be initialized
	ADD `income` TINYINT(2)   DEFAULT 1,
	ADD `balance`              TINYINT(2)   DEFAULT NULL,
	ADD `pending_income`       TINYINT(2)   DEFAULT NULL,
	ADD `turn_flags`           TEXT			DEFAULT NULL,
	ADD `turn_data`            TEXT         DEFAULT NULL,
	ADD `action_flags`         TEXT  		DEFAULT NULL,
	ADD `majority`             TEXT 		DEFAULT NULL,
	ADD `majority_update_date` DATETIME     DEFAULT NULL;
