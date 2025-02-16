<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

/**
 * stats.inc.php
 *
 * Doc: https://en.doc.boardgamearena.com/Game_statistics:_stats.inc.php
 *
 * DinnerInParis game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each player (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contain alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

$stats_type = [
	// Statistics global to table
	'table'  => [
		'turns_number' => [
			'id'   => 10,
			'name' => totranslate('Number of turns'),
			'type' => 'int',
		],
	],
	
	// Statistics existing for each player
	'player' => [
		'turns_number'     => [
			'id'   => 10,
			'name' => totranslate('Number of turns'),
			'type' => 'int',
		],
		'score_restaurant' => [
			'id'   => 30,
			'name' => totranslate('Score of restaurants'),
			'type' => 'int',
		],
		'score_terrace'    => [
			'id'   => 31,
			'name' => totranslate('Score of terraces'),
			'type' => 'int',
		],
		'score_objective'  => [
			'id'   => 32,
			'name' => totranslate('Score of objective cards'),
			'type' => 'int',
		],
		'score_majority'   => [
			'id'   => 33,
			'name' => totranslate('Score of majority card'),
			'type' => 'int',
		],
		'score_majority_1' => [
			'id'   => 34,
			'name' => totranslate('Score of first majority'),
			'type' => 'int',
		],
		'score_majority_2' => [
			'id'   => 35,
			'name' => totranslate('Score of second majority'),
			'type' => 'int',
		],
		'score_majority_3' => [
			'id'   => 36,
			'name' => totranslate('Score of third majority'),
			'type' => 'int',
		],
		'income'           => [
			'id'   => 40,
			'name' => totranslate('Income'),
			'type' => 'int',
		],
		'restaurants'      => [
			'id'   => 41,
			'name' => totranslate('Owned restaurants'),
			'type' => 'int',
		],
		'terraces'         => [
			'id'   => 42,
			'name' => totranslate('Placed terraces'),
			'type' => 'int',
		],
		'objectives'       => [
			'id'   => 43,
			'name' => totranslate('Completed objectives'),
			'type' => 'int',
		],
	],

];
