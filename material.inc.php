<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

/**
 * material.inc.php
 * DOC: https://en.doc.boardgamearena.com/Game_material_description:_material.inc.php
 *
 * DinnerInParis game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 * Warning: Tokens are generated in game setup, you should create a new table after editing this file
 */

/** @var DinnerInParis $this */
/** @var DinnerInParis self */

if( !defined('RESOURCE_BREAD') ) {
	define('TOKEN_CONTAINER_BOX', 0);// Outside the game
	define('TOKEN_CONTAINER_BOARD_RIVER', 1);// Visible on table
	define('TOKEN_CONTAINER_BOARD_DECK', 2);// Hidden on table
	define('TOKEN_CONTAINER_BOARD_DISCARD', 3);// Unavailable on table
	define('TOKEN_CONTAINER_BOARD_GRID', 4);// On board grid
	define('TOKEN_CONTAINER_PLAYER_BOARD', 6);// On table
	define('TOKEN_CONTAINER_PLAYER_HAND', 7);// Virtually in hand
	define('TOKEN_CONTAINER_PLAYER_DISCARD', 8);// Unavailable on table
	
	define('TOKEN_TYPE_RESOURCE_CARD', 1);
	define('TOKEN_TYPE_RESTAURANT', 2);
	define('TOKEN_TYPE_PROPERTY', 3);
	define('TOKEN_TYPE_TERRACE', 4);
	define('TOKEN_TYPE_PIGEON_CARD', 5);
	define('TOKEN_TYPE_OBJECTIVE_CARD', 6);
	define('TOKEN_TYPE_MAJORITY_CARD', 7);
	
	// Build permissions
	define('BUILD_RESTAURANT', 'restaurant');
	define('BUILD_TERRACE', 'terrace');
	
	// Tiles
	define('TILE_OUT', 'out');
	define('TILE_EMPTY', 'empty');
	define('TILE_BUSH', 'bush');
	define('TILE_RESTAURANT', 'restaurant');
	define('TILE_BAND', 'band');
	define('TILE_FLOWER_BED', 'flower_bed');
	define('TILE_FOUNTAIN', 'fountain');
	define('TILE_LAMP', 'lamp');
	define('TILE_PIGEON', 'pigeon');
	
	// Resources
	define('RESOURCE_BREAD', 'bread');
	define('RESOURCE_CHEESE', 'cheese');
	define('RESOURCE_FLOUR', 'flour');
	define('RESOURCE_GOLD', 'gold');
	define('RESOURCE_MEAT', 'meat');
	define('RESOURCE_POTATOES', 'potatoes');
	define('RESOURCE_SEA_FOOD', 'sea_food');
	define('RESOURCE_TOMATOES', 'tomatoes');
	
	// Areas
	define('AREA_EAST_STREET', 'east_street');
	define('AREA_NORTH_STREET', 'north_street');
	define('AREA_SOUTH_STREET', 'south_street');
	define('AREA_WEST_STREET', 'west_street');
	
	// Quarters
	define('QUARTER_NORTH_WEST', 'north_west');
	define('QUARTER_NORTH_EAST', 'north_east');
	define('QUARTER_SOUTH_WEST', 'south_west');
	define('QUARTER_SOUTH_EAST', 'south_east');
	
	// Restaurant Category
	define('RESTAURANT_CATEGORY_1', 1);
	define('RESTAURANT_CATEGORY_2', 2);
	define('RESTAURANT_CATEGORY_3', 3);
	define('RESTAURANT_CATEGORY_4', 4);
	
	// Majorities
	define('MAJORITY_DECOR_LAMP', 'around_lamp');
	define('MAJORITY_DECOR_FOUNTAIN', 'around_fountain');
	define('MAJORITY_DECOR_FLOWER_BED', 'around_flower_bed');
	define('MAJORITY_DECOR_BAND', 'around_band');
	define('MAJORITY_AREA_NORTH', 'in_area_north');
	define('MAJORITY_AREA_EAST', 'in_area_east');
	define('MAJORITY_AREA_SOUTH', 'in_area_south');
	define('MAJORITY_AREA_WEST', 'in_area_west');
	define('MAJORITY_RESTAURANTS', 'more_restaurants');
	define('MAJORITY_MONEY', 'more_money');
	define('MAJORITY_PIGEON_CARDS', 'more_pigeon_cards');
	
	// Majority types
	define('MAJORITY_TYPE_DECOR_TERRACES', 'decor_terraces');
	define('MAJORITY_TYPE_AREA_TERRACES', 'area_terraces');
	define('MAJORITY_TYPE_RESTAURANTS', 'restaurants');
	define('MAJORITY_TYPE_MONEY', 'money');
	define('MAJORITY_TYPE_PIGEON_CARDS', 'pigeon_cards');
	
	// Objectives about
	define('OBJECTIVE_ABOUT_TERRACE_DECOR', 'terrace_decor');
	define('OBJECTIVE_ABOUT_TERRACE_CATEGORY', 'terrace_category');
	define('OBJECTIVE_ABOUT_QUARTER_MINIMALS', 'quarter_minimals');
	define('OBJECTIVE_ABOUT_AREA_MINIMALS', 'quarter_total');
	define('OBJECTIVE_ABOUT_TERRACE_TOTAL', 'terrace_total');
	define('OBJECTIVE_ABOUT_CATEGORY_EMPTY', 'category_empty');
	define('OBJECTIVE_ABOUT_TERRACE_SHAPE', 'terrace_shape');
	
	// Pigeon cards
	define('PIGEON_CARD_ADD_TERRACE', 'AddTerrace');// Immediate
	define('PIGEON_CARD_ADJACENT_TERRACE', 'AdjacentTerrace');
	define('PIGEON_CARD_DRAW_RESOURCE', 'DrawResource');// Immediate
	define('PIGEON_CARD_INGREDIENT', 'Ingredient');
	define('PIGEON_CARD_OBJECTIVE', 'Objective');
	define('PIGEON_CARD_TWO_GOLDS', 'TwoGolds');
}

/*** GAME RESOURCES ***/
$this->resources = [
	RESOURCE_BREAD    => [
		'label' => clienttranslate('bread'),
	],
	RESOURCE_CHEESE   => [
		'label' => clienttranslate('cheese'),
	],
	RESOURCE_FLOUR    => [
		'label' => clienttranslate('flour'),
	],
	RESOURCE_GOLD     => [
		'label' => clienttranslate('gold'),
	],
	RESOURCE_MEAT     => [
		'label' => clienttranslate('meat'),
	],
	RESOURCE_POTATOES => [
		'label' => clienttranslate('potatoes'),
	],
	RESOURCE_SEA_FOOD => [
		'label' => clienttranslate('sea_food'),
	],
	RESOURCE_TOMATOES => [
		'label' => clienttranslate('tomatoes'),
	],
];

/*** GAME BOARD TILES ***/
$this->tiles = [
	[
		'key'    => TILE_EMPTY,
		'label'  => clienttranslate('Empty Tile'),
		'allows' => [BUILD_TERRACE],
	],
	[
		'key'       => TILE_BUSH,
		'label'     => clienttranslate('Bush Tile'),
		'allows'    => [],
		'locations' => [[[0, 0]], [[19, 0]], [[0, 19]], [[19, 199]]],
	],
	[
		'key'    => TILE_RESTAURANT,
		'label'  => clienttranslate('Restaurant Tile'),
		'allows' => [BUILD_RESTAURANT],
	],
	[
		'key'       => TILE_BAND,
		'label'     => clienttranslate('Band Tile'),
		'allows'    => [],
		'locations' => [[[4, 5], [5, 5]], [[14, 14], [15, 14]]],
	],
	[
		'key'       => TILE_FLOWER_BED,
		'label'     => clienttranslate('Flower bed Tile'),
		'allows'    => [],
		'locations' => [[[9, 4], [10, 4], [11, 4]], [[8, 15], [9, 15], [10, 15]]],
	],
	[
		'key'       => TILE_FOUNTAIN,
		'label'     => clienttranslate('Fountain Tile'),
		'allows'    => [],
		'locations' => [[[9, 8], [10, 8], [8, 9], [9, 9], [10, 9], [11, 9], [8, 10], [9, 10], [10, 10], [11, 10], [9, 11], [10, 11]]],
	],
	[
		'key'       => TILE_LAMP,
		'label'     => clienttranslate('Street light Tile'),
		'allows'    => [],
		'locations' => [[[15, 7]], [[4, 12]]],
	],
	[
		'key'       => TILE_PIGEON,
		'label'     => clienttranslate('Pigeon Tile'),
		'allows'    => [BUILD_TERRACE],
		'locations' => [
			[[3, 3]], [[7, 3]], [[12, 3]], [[16, 3]],
			[[3, 7]], [[6, 7]], [[13, 7]], [[16, 7]],
			[[8, 8]], [[11, 8]],
			[[8, 11]], [[11, 11]],
			[[3, 12]], [[6, 12]], [[13, 12]], [[16, 12]],
			[[3, 16]], [[7, 16]], [[12, 16]], [[16, 16]],
		],
	],
];

/*** GAME BOARD AREAS ***/
$this->areas = [
	[
		'key'   => AREA_EAST_STREET,
		'label' => 'RUE EST',
	],
	[
		'key'   => AREA_NORTH_STREET,
		'label' => 'RUE NORD',
	],
	[
		'key'   => AREA_SOUTH_STREET,
		'label' => 'RUE SUD',
	],
	[
		'key'   => AREA_WEST_STREET,
		'label' => 'RUE OUEST',
	],
];

/*** GAME RESOURCE CARDS ***/
$this->resourceCards = [
	[
		'key'   => 'C1Bread',
		'label' => clienttranslate('Bread Resource Card'),
		'gives' => [RESOURCE_BREAD],
		'total' => 4,
	],
	[
		'key'   => 'C1Cheese',
		'label' => clienttranslate('Cheese Resource Card'),
		'gives' => [RESOURCE_CHEESE],
		'total' => 5,
	],
	[
		'key'   => 'C1Flour',
		'label' => clienttranslate('Flour Resource Card'),
		'gives' => [RESOURCE_FLOUR],
		'total' => 5,
	],
	[
		'key'   => 'C1Gold',
		'label' => clienttranslate('Gold Card'),
		'gives' => [RESOURCE_GOLD],
		'total' => 7,
	],
	[
		'key'   => 'C1Meat',
		'label' => clienttranslate('Meat Resource Card'),
		'gives' => [RESOURCE_MEAT],
		'total' => 4,
	],
	[
		'key'   => 'C1Potatoes',
		'label' => clienttranslate('Potatoes Resource Card'),
		'gives' => [RESOURCE_POTATOES],
		'total' => 8,
	],
	[
		'key'   => 'C1SeaFood',
		'label' => clienttranslate('Sea food Resource Card'),
		'gives' => [RESOURCE_SEA_FOOD],
		'total' => 3,
	],
	[
		'key'   => 'C1Tomatoes',
		'label' => clienttranslate('Tomatoes Resource Card'),
		'gives' => [RESOURCE_TOMATOES],
		'total' => 5,
	],
	[
		'key'   => 'C2BT',
		'label' => clienttranslate('Bread/Tomatoes Resource Card'),
		'gives' => [RESOURCE_BREAD, RESOURCE_TOMATOES],
		'total' => 1,
	],
	[
		'key'   => 'C2MF',
		'label' => clienttranslate('Meat/Flour Resource Card'),
		'gives' => [RESOURCE_MEAT, RESOURCE_FLOUR],
		'total' => 1,
	],
	[
		'key'   => 'C2PC',
		'label' => clienttranslate('Potatoes/Cheese Resource Card'),
		'gives' => [RESOURCE_POTATOES, RESOURCE_CHEESE],
		'total' => 1,
	],
	[
		'key'   => 'C2PT',
		'label' => clienttranslate('Potatoes/Tomatoes Resource Card'),
		'gives' => [RESOURCE_POTATOES, RESOURCE_TOMATOES],
		'total' => 1,
	],
	[
		'key'   => 'C3CFT',
		'label' => clienttranslate('Cheese/Flour/Tomatoes Resource Card'),
		'gives' => [RESOURCE_CHEESE, RESOURCE_FLOUR, RESOURCE_TOMATOES],
		'total' => 1,
	],
	[
		'key'   => 'C3SBP',
		'label' => clienttranslate('Sea food/Bread/Potatoes Resource Card'),
		'gives' => [RESOURCE_SEA_FOOD, RESOURCE_BREAD, RESOURCE_POTATOES],
		'total' => 1,
	],
	[
		'key'   => 'C3SMP',
		'label' => clienttranslate('Sea food/Meat/Potatoes Resource Card'),
		'gives' => [RESOURCE_SEA_FOOD, RESOURCE_MEAT, RESOURCE_POTATOES],
		'total' => 1,
	],
];

/*** GAME RESTAURANTS ***/
$this->restaurants = [
	// Total is the number of restaurant of this type AND the number of property for each player
	// Label & key of restaurant should never be translated, we would to use the French writing for all
	[
		'key'      => 'friterie',
		'label'    => 'Friterie',
		'cost'     => [RESOURCE_POTATOES => 2],
		'category' => RESTAURANT_CATEGORY_1,
		'total'    => 5,
		'size'     => 2,
		'income'   => 0,
		'score'    => 2,
	],
	[
		'key'      => 'fruits-de-mer',
		'label'    => 'Fruits de mer',
		'cost'     => [RESOURCE_SEA_FOOD => 2, RESOURCE_BREAD => 1],
		'category' => RESTAURANT_CATEGORY_2,
		'total'    => 2,
		'size'     => 3,
		'income'   => 1,
		'score'    => 3,
	],
	[
		'key'      => 'creperie',
		'label'    => 'Crêperie',
		'cost'     => [RESOURCE_FLOUR => 2, RESOURCE_CHEESE => 1],
		'category' => RESTAURANT_CATEGORY_2,
		'total'    => 2,
		'size'     => 3,
		'income'   => 1,
		'score'    => 3,
	],
	[
		'key'      => 'pizzeria',
		'label'    => 'Pizzeria',
		'cost'     => [RESOURCE_TOMATOES => 2, RESOURCE_FLOUR => 1],
		'category' => RESTAURANT_CATEGORY_2,
		'total'    => 2,
		'size'     => 3,
		'income'   => 1,
		'score'    => 3,
	],
	[
		'key'      => 'grill',
		'label'    => 'Grill',
		'cost'     => [RESOURCE_POTATOES => 1, RESOURCE_TOMATOES => 1, RESOURCE_CHEESE => 1, RESOURCE_MEAT => 1],
		'category' => RESTAURANT_CATEGORY_3,
		'total'    => 2,
		'size'     => 4,
		'income'   => 2,
		'score'    => 5,
	],
	[
		'key'      => 'bar-a-vin',
		'label'    => 'Bar à vin',
		'cost'     => [RESOURCE_CHEESE => 1, RESOURCE_MEAT => 1, RESOURCE_FLOUR => 1, RESOURCE_BREAD => 1],
		'category' => RESTAURANT_CATEGORY_3,
		'total'    => 2,
		'size'     => 4,
		'income'   => 2,
		'score'    => 5,
	],
	[
		'key'      => 'brasserie',
		'label'    => 'Brasserie',
		'cost'     => [RESOURCE_POTATOES => 1, RESOURCE_TOMATOES => 1, RESOURCE_CHEESE => 1, RESOURCE_MEAT => 1, RESOURCE_BREAD => 1],
		'category' => RESTAURANT_CATEGORY_4,
		'total'    => 2,
		'size'     => 5,
		'income'   => 3,
		'score'    => 8,
	],
	[
		'key'      => 'gastronomique',
		'label'    => 'Restaurant gastronomique',
		'cost'     => [RESOURCE_POTATOES => 1, RESOURCE_TOMATOES => 1, RESOURCE_CHEESE => 1, RESOURCE_MEAT => 1, RESOURCE_BREAD => 1, RESOURCE_FLOUR => 1, RESOURCE_SEA_FOOD => 1],
		'category' => RESTAURANT_CATEGORY_4,
		'total'    => 1,
		'size'     => 5,
		'income'   => 4,
		'score'    => 12,
	],
];

/*** GAME RESTAURANT CATEGORIES ***/
$this->restaurantCategories = [
	RESTAURANT_CATEGORY_1 => [
		'label'       => clienttranslate('1st category'),
		'playerTotal' => 16,
		'terraces'    => [
			['cost' => 1, 'score' => 1],
			['cost' => 1, 'score' => 2],
			['cost' => 1, 'score' => 3],
			['cost' => 1, 'income' => 1],
			['cost' => 1, 'score' => 4],
			['cost' => 1, 'score' => 5],
			['cost' => 1, 'score' => 6],
			['cost' => 1, 'score' => 7],
			['cost' => 2, 'income' => 1],
			['cost' => 2, 'score' => 8],
			['cost' => 2, 'score' => 9],
			['cost' => 2, 'score' => 10],
			['cost' => 2, 'score' => 11],
			['cost' => 3, 'score' => 12],
			['cost' => 3, 'score' => 13],
			['cost' => 3, 'score' => 14],
		],
	],
	RESTAURANT_CATEGORY_2 => [
		'label'       => clienttranslate('2nd category'),
		'playerTotal' => 16,
		'terraces'    => [
			['cost' => 2, 'score' => 2],
			['cost' => 2, 'score' => 4],
			['cost' => 2, 'income' => 1],
			['cost' => 2, 'score' => 6],
			['cost' => 2, 'score' => 8],
			['cost' => 2, 'score' => 10],
			['cost' => 3, 'income' => 1],
			['cost' => 3, 'score' => 12],
			['cost' => 3, 'score' => 14],
			['cost' => 3, 'score' => 16],
			['cost' => 3, 'score' => 18],
			['cost' => 4, 'income' => 1],
			['cost' => 4, 'score' => 20],
			['cost' => 4, 'score' => 22],
			['cost' => 4, 'score' => 24],
			['cost' => 5, 'score' => 26],
		],
	],
	RESTAURANT_CATEGORY_3 => [
		'label'       => clienttranslate('3th category'),
		'playerTotal' => 12,
		'terraces'    => [
			['cost' => 3, 'score' => 3],
			['cost' => 3, 'income' => 1],
			['cost' => 3, 'score' => 6],
			['cost' => 3, 'score' => 9],
			['cost' => 4, 'income' => 1],
			['cost' => 4, 'score' => 12],
			['cost' => 4, 'score' => 15],
			['cost' => 4, 'score' => 18],
			['cost' => 5, 'income' => 1],
			['cost' => 5, 'score' => 21],
			['cost' => 5, 'score' => 24],
			['cost' => 7, 'score' => 28],
		],
	],
	RESTAURANT_CATEGORY_4 => [
		'label'       => clienttranslate('4th category'),
		'playerTotal' => 8,
		'terraces'    => [
			['cost' => 4, 'income' => 1],
			['cost' => 4, 'score' => 4],
			['cost' => 4, 'score' => 8],
			['cost' => 6, 'score' => 14],
			['cost' => 6, 'income' => 1],
			['cost' => 8, 'score' => 20],
			['cost' => 8, 'score' => 26],
			['cost' => 12, 'score' => 34],
		],
	],
];

/*** GAME PIGEON CARDS ***/
$this->pigeonCards = [
	[
		'key'         => PIGEON_CARD_ADD_TERRACE,
		'label'       => clienttranslate('Free terrace'),
		'description' => clienttranslate('Place an additional terrace on the same restaurant for free'),
		'immediate'   => true,
		'total'       => 4,
	], // 0
	[
		'key'         => PIGEON_CARD_ADJACENT_TERRACE,
		'label'       => clienttranslate('Adjacent terraces'),
		'description' => clienttranslate('Place terraces adjacent to other players\' terraces and cover up to 2 terraces'),
		'immediate'   => false,
		'total'       => 4,
	], // 1
	[
		'key'         => PIGEON_CARD_DRAW_RESOURCE,
		'label'       => clienttranslate('Draw 2 resource cards'),
		'description' => clienttranslate('Draw 2 resource cards'),
		'immediate'   => true,
		'total'       => 4,
	], // 2
	[
		'key'         => PIGEON_CARD_INGREDIENT,
		'label'       => clienttranslate('Free ingredient'),
		'description' => clienttranslate('You can open a restaurant using one less ingredient'),
		'immediate'   => false,
		'total'       => 4,
	], // 3
	[
		'key'         => PIGEON_CARD_OBJECTIVE,
		'label'       => clienttranslate('Objective bonus'),
		'description' => clienttranslate('You can draw an objective or complete a 2nd objective during your turn'),
		'immediate'   => false,
		'total'       => 4,
	], // 4
	[
		'key'         => PIGEON_CARD_TWO_GOLDS,
		'label'       => clienttranslate('2 golds'),
		'description' => clienttranslate('Get 2 more golds once'),
		'immediate'   => false,
		'total'       => 4,
	], // 5
];

/*** GAME BOARD AREAS ***/
$this->objectiveCards = [
	[
		'key'         => 'M1TAll',
		'label'       => clienttranslate('1 terrace around each type of decor'),
		'score'       => 5,
		'about'       => OBJECTIVE_ABOUT_TERRACE_DECOR,
		'terraces'    => 1,
		'around_each' => 'all',
	],// 0
	[
		'key'         => 'M1TLamps',
		'label'       => clienttranslate('1 terrace around each street light'),
		'score'       => 3,
		'about'       => OBJECTIVE_ABOUT_TERRACE_DECOR,
		'terraces'    => 1,
		'around_each' => TILE_LAMP,
	],// 1
	[
		'key'         => 'M2TFlowers',
		'label'       => clienttranslate('2 terraces around each flower bed'),
		'score'       => 5,
		'about'       => OBJECTIVE_ABOUT_TERRACE_DECOR,
		'terraces'    => 2,
		'around_each' => TILE_FLOWER_BED,
	],// 2
	[
		'key'         => 'M2TMusicians',
		'label'       => clienttranslate('2 terraces around each band'),
		'score'       => 5,
		'about'       => OBJECTIVE_ABOUT_TERRACE_DECOR,
		'terraces'    => 2,
		'around_each' => TILE_BAND,
	],// 3
	[
		'key'         => 'M5TFountain',
		'label'       => clienttranslate('5 terraces around the fountain'),
		'score'       => 4,
		'about'       => OBJECTIVE_ABOUT_TERRACE_DECOR,
		'terraces'    => 5,
		'around_each' => TILE_FOUNTAIN,
	],// 4
	[
		'key'      => 'M3CategoryT',
		'label'    => clienttranslate('Place 3 terraces of each restaurant category'),
		'score'    => 6,
		'about'    => OBJECTIVE_ABOUT_TERRACE_CATEGORY,
		'terraces' => 3,
	],// 5
	[
		'key'      => 'M3TQuarter',
		'label'    => clienttranslate('3 terraces in each quarter'),
		'score'    => 4,
		'about'    => OBJECTIVE_ABOUT_QUARTER_MINIMALS,
		'terraces' => 3,
	],// 6
	[
		'key'      => 'M9TEast',
		'label'    => clienttranslate('9 terraces in "RUE EST" area'),
		'score'    => 3,
		'about'    => OBJECTIVE_ABOUT_AREA_MINIMALS,
		'terraces' => 9,
		'in'       => AREA_EAST_STREET,
	],// 7
	[
		'key'      => 'M9TNorth',
		'label'    => clienttranslate('9 terraces in "RUE NORD" area'),
		'score'    => 3,
		'about'    => OBJECTIVE_ABOUT_AREA_MINIMALS,
		'terraces' => 9,
		'in'       => AREA_NORTH_STREET,
	],// 8
	[
		'key'      => 'M9TSouth',
		'label'    => clienttranslate('9 terraces in "RUE SUD" area'),
		'score'    => 3,
		'about'    => OBJECTIVE_ABOUT_AREA_MINIMALS,
		'terraces' => 9,
		'in'       => AREA_SOUTH_STREET,
	],// 9
	[
		'key'      => 'M9TWest',
		'label'    => clienttranslate('9 terraces in "RUE OUEST" area'),
		'score'    => 3,
		'about'    => OBJECTIVE_ABOUT_AREA_MINIMALS,
		'terraces' => 9,
		'in'       => AREA_WEST_STREET,
	],// 10
	[
		'key'      => 'M20Terraces',
		'label'    => clienttranslate('Place at least 20 terraces'),
		'score'    => 3,
		'about'    => OBJECTIVE_ABOUT_TERRACE_TOTAL,
		'terraces' => 20,
	],// 11
	[
		'key'   => 'MAllCatT',
		'label' => clienttranslate('Place all terraces from a restaurant category'),
		'score' => 3,
		'about' => OBJECTIVE_ABOUT_CATEGORY_EMPTY,
	],// 12
	[
		'key'   => 'P01',
		'label' => clienttranslate('Shape 1'),
		'score' => 4,
		'about' => OBJECTIVE_ABOUT_TERRACE_SHAPE,
		'as'    => [[0, 1, 0], [1, 1, 1], [0, 1, 0], [0, 1, 0]],
	],// 13
	[
		'key'   => 'P02',
		'label' => clienttranslate('Shape 2'),
		'score' => 4,
		'about' => OBJECTIVE_ABOUT_TERRACE_SHAPE,
		'as'    => [[1, 1], [1, 1], [1, 0], [1, 0]],
	],// 14
	[
		'key'   => 'P03',
		'label' => clienttranslate('Shape 3'),
		'score' => 4,
		'about' => OBJECTIVE_ABOUT_TERRACE_SHAPE,
		'as'    => [[1, 1], [0, 1], [1, 1], [1, 0]],
	],// 15
	[
		'key'   => 'P04',
		'label' => clienttranslate('Shape 4'),
		'score' => 4,
		'about' => OBJECTIVE_ABOUT_TERRACE_SHAPE,
		'as'    => [[1, 0], [1, 0], [1, 1], [1, 0], [1, 0]],
	],// 16
	[
		'key'   => 'P05',
		'label' => clienttranslate('Shape 5'),
		'score' => 4,
		'about' => OBJECTIVE_ABOUT_TERRACE_SHAPE,
		'as'    => [[1, 0], [1, 1], [1, 1], [1, 0]],
	],// 17
	[
		'key'   => 'P06',
		'label' => clienttranslate('Shape 6'),
		'score' => 5,
		'about' => OBJECTIVE_ABOUT_TERRACE_SHAPE,
		'as'    => [[1, 0, 1], [1, 1, 1], [0, 1, 0]],
	],// 18
	[
		'key'   => 'P07',
		'label' => clienttranslate('Shape 7'),
		'score' => 5,
		'about' => OBJECTIVE_ABOUT_TERRACE_SHAPE,
		'as'    => [[1, 1, 1], [1, 1, 0], [1, 0, 0]],
	],
	[
		'key'   => 'P08',
		'label' => clienttranslate('Shape 8'),
		'score' => 5,
		'about' => OBJECTIVE_ABOUT_TERRACE_SHAPE,
		'as'    => [[1, 1, 1], [0, 1, 0], [0, 1, 1]],
	],
	[
		'key'   => 'P09',
		'label' => clienttranslate('Shape 9'),
		'score' => 5,
		'about' => OBJECTIVE_ABOUT_TERRACE_SHAPE,
		'as'    => [[0, 1, 1], [1, 1, 1], [0, 1, 0]],
	],
	[
		'key'   => 'P10',
		'label' => clienttranslate('Shape 10'),
		'score' => 5,
		'about' => OBJECTIVE_ABOUT_TERRACE_SHAPE,
		'as'    => [[0, 1, 0], [1, 1, 1], [0, 0, 1], [0, 0, 1]],
	],
	[
		'key'   => 'P11',
		'label' => clienttranslate('Shape 11'),
		'score' => 5,
		'about' => OBJECTIVE_ABOUT_TERRACE_SHAPE,
		'as'    => [[1, 0, 0], [1, 0, 1], [1, 1, 1]],
	],
];

/*** GAME MAJORITIES ***/
$this->majorities = [
	MAJORITY_DECOR_LAMP       => [
		'label'  => clienttranslate('Most terraces around street lights'),
		'about'  => MAJORITY_TYPE_DECOR_TERRACES,
		'around' => TILE_LAMP,
	],
	MAJORITY_DECOR_FOUNTAIN   => [
		'label'  => clienttranslate('Most terraces around the fountain'),
		'about'  => MAJORITY_TYPE_DECOR_TERRACES,
		'around' => TILE_FOUNTAIN,
	],
	MAJORITY_DECOR_FLOWER_BED => [
		'label'  => clienttranslate('Most terraces around flower beds'),
		'about'  => MAJORITY_TYPE_DECOR_TERRACES,
		'around' => TILE_FLOWER_BED,
	],
	MAJORITY_DECOR_BAND       => [
		'label'  => clienttranslate('Most terraces around bands'),
		'about'  => MAJORITY_TYPE_DECOR_TERRACES,
		'around' => TILE_BAND,
	],
	MAJORITY_AREA_NORTH       => [
		'label' => clienttranslate('Most terraces in "RUE NORD" area'),
		'about' => MAJORITY_TYPE_AREA_TERRACES,
		'in'    => AREA_NORTH_STREET,
	],
	MAJORITY_AREA_EAST        => [
		'label' => clienttranslate('Most terraces in "RUE EST" area'),
		'about' => MAJORITY_TYPE_AREA_TERRACES,
		'in'    => AREA_EAST_STREET,
	],
	MAJORITY_AREA_SOUTH       => [
		'label' => clienttranslate('Most terraces in "RUE SUD" area'),
		'about' => MAJORITY_TYPE_AREA_TERRACES,
		'in'    => AREA_SOUTH_STREET,
	],
	MAJORITY_AREA_WEST        => [
		'label' => clienttranslate('Most terraces in "RUE OUEST" area'),
		'about' => MAJORITY_TYPE_AREA_TERRACES,
		'in'    => AREA_WEST_STREET,
	],
	MAJORITY_RESTAURANTS      => [
		'label' => clienttranslate('Most restaurants'),
		'about' => MAJORITY_TYPE_RESTAURANTS,
	],
	MAJORITY_MONEY            => [
		'label' => clienttranslate('Most money'),
		'about' => MAJORITY_TYPE_MONEY,
	],
	MAJORITY_PIGEON_CARDS     => [
		'label' => clienttranslate('Most pigeons cards'),
		'about' => MAJORITY_TYPE_PIGEON_CARDS,
	],
];

/*** GAME MAJORITY CARDS ***/
$this->majorityCards = [
	// Keep order of achievements
	[
		'key'          => 'FlowersLamps',
		'label'        => clienttranslate('Flower beds / Street lights / RUE OUEST'),
		'achievements' => [MAJORITY_DECOR_FLOWER_BED, MAJORITY_DECOR_LAMP, MAJORITY_AREA_WEST],
	],
	[
		'key'          => 'FlowersRestaurants',
		'label'        => clienttranslate('Flower beds / RUE OUEST / Restaurants'),
		'achievements' => [MAJORITY_DECOR_FLOWER_BED, MAJORITY_AREA_WEST, MAJORITY_RESTAURANTS],
	],
	[
		'key'          => 'FountainFlowers',
		'label'        => clienttranslate('Fountain / Flower beds / RUE EST'),
		'achievements' => [MAJORITY_DECOR_FOUNTAIN, MAJORITY_DECOR_FLOWER_BED, MAJORITY_AREA_EAST],
	],
	[
		'key'          => 'FountainGold',
		'label'        => clienttranslate('Fountain / Money / RUE SUD'),
		'achievements' => [MAJORITY_DECOR_FOUNTAIN, MAJORITY_MONEY, MAJORITY_AREA_SOUTH],
	],
	[
		'key'          => 'FountainPigeons',
		'label'        => clienttranslate('Fountain / RUE NORD / Pigeon cards'),
		'achievements' => [MAJORITY_DECOR_FOUNTAIN, MAJORITY_AREA_NORTH, MAJORITY_PIGEON_CARDS],
	],
	[
		'key'          => 'LampRestaurants',
		'label'        => clienttranslate('Street lights / RUE EST / Restaurants'),
		'achievements' => [MAJORITY_DECOR_LAMP, MAJORITY_AREA_EAST, MAJORITY_RESTAURANTS],
	],
	[
		'key'          => 'MusiciansGold',
		'label'        => clienttranslate('Bands / RUE NORD / Money'),
		'achievements' => [MAJORITY_DECOR_BAND, MAJORITY_AREA_NORTH, MAJORITY_MONEY],
	],
	[
		'key'          => 'MusiciansPigeons',
		'label'        => clienttranslate('Bands / RUE SUD / Pigeon cards'),
		'achievements' => [MAJORITY_DECOR_BAND, MAJORITY_AREA_SOUTH, MAJORITY_PIGEON_CARDS],
	],
];
