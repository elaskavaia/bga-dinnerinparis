<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

/**
 * states.inc.php
 *
 * Doc: https://en.doc.boardgamearena.com/Your_game_state_machine:_states.inc.php
 *
 * DinnerInParis game states description
 */

/*
   Game state machine is a tool used to facilitate game development by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javascript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

if( !defined('GAME_STATE_TYPE_ACTIVE_PLAYER') ) {
	
	define('GAME_STATE_TYPE_ACTIVE_PLAYER', 'activeplayer');
	define('GAME_STATE_TYPE_MULTIPLE_PLAYERS', 'multipleactiveplayer');
	define('GAME_STATE_TYPE_GAME', 'game');
	define('GAME_STATE_TYPE_MANAGER', 'manager');
	
	define('GAME_STATE_GAME_START', 1);
	define('GAME_STATE_GAME_END', 99);
	define('GAME_STATE_TURN_START', 10);
	define('GAME_STATE_TURN_END', 11);
	define('GAME_STATE_NEXT_PLAYER', 12);
	define('GAME_STATE_ACTION_CHOOSE', 20);
	define('GAME_STATE_ACTION_END', 21);
	define('GAME_STATE_GAME_END_FINALIZE', 90);
	define('GAME_STATE_GAME_END_SUMMARY', 91);
	define('GAME_STATE_PICK_RESOURCE_CARD', 100);
	define('GAME_STATE_PLACE_TERRACE', 121);
	
}// Else already included

$machinestates = [
	
	
	/*** Game states ***/
	
	2 => [
		"name"              => "initialization.chooseObjectiveCard",
		"description"       => clienttranslate('Some other players must choose an objective card'),
		"descriptionmyturn" => clienttranslate('${you} must choose an objective card'),
		"type"              => GAME_STATE_TYPE_MULTIPLE_PLAYERS,
		"action"            => 'setAllActive',
		"args"              => 'argInitializationChooseObjectiveCard',
		"possibleactions"   => ["chooseObjectiveCard"],
		"transitions"       => ["apply" => 3],
	],
	
	3 => [
		"name"        => "initialization.apply",
		"description" => clienttranslate('Applying chosen objective cards'),
		"type"        => GAME_STATE_TYPE_GAME,
		"action"      => 'initializationApply',
		"transitions" => ['start' => GAME_STATE_TURN_START],
	],// newPlayerTurn
	
	GAME_STATE_NEXT_PLAYER => [
		"name"                  => "nextPlayer",
		"description"           => '',
		"type"                  => GAME_STATE_TYPE_GAME,
		"action"                => "nextPlayer",
		"transitions"           => ["endGame" => 99, "nextPlayer" => GAME_STATE_TURN_START],
		"updateGameProgression" => true,
	],// nextPlayer
	
	GAME_STATE_TURN_START => [
		"name"        => "newPlayerTurn",
		"description" => clienttranslate('Starting turn'),
		"type"        => GAME_STATE_TYPE_GAME,
		"action"      => 'startPlayerTurn',
		"transitions" => ['pickResourceCard' => GAME_STATE_PICK_RESOURCE_CARD],
	],// newPlayerTurn
	
	GAME_STATE_TURN_END => [
		"name"        => "endPlayerTurn",
		"description" => clienttranslate('Ending turn'),
		"type"        => GAME_STATE_TYPE_GAME,
		"action"      => 'endPlayerTurn',
		"transitions" => ["nextPlayer" => GAME_STATE_NEXT_PLAYER, 'endGame' => GAME_STATE_GAME_END_FINALIZE],
	],// endPlayerTurn
	
	GAME_STATE_ACTION_CHOOSE => [
		'name'                  => 'playerActionChoose',
		'description'           => clienttranslate('${actplayer} must choose an action (${actionNumber}/2)'),
		'descriptionmyturn'     => clienttranslate('${you} must choose an action (${actionNumber}/2)'),
		'type'                  => GAME_STATE_TYPE_ACTIVE_PLAYER,
		'args'                  => 'argPlayerActionChoose',
		'possibleactions'       => [// See PlayerActionChooseController for action buttons
			'actionPickResourceCard', 'actionBuildRestaurant', 'actionPlaceTerraces', 'actionCompleteObjective',
			'pigeonDrawObjective', 'actionEndGame',
		],
		'transitions'           => ['actionPickResourceCard'  => GAME_STATE_PICK_RESOURCE_CARD, 'actionBuildRestaurant' => 110, 'actionPlaceTerraces' => 120,
									'actionCompleteObjective' => 130, 'pigeonDrawObjective' => 220, 'endGame' => GAME_STATE_GAME_END_FINALIZE,
									'zombiePass'              => GAME_STATE_TURN_END],
		'updateGameProgression' => true,
	],// playerActionChoose
	
	GAME_STATE_ACTION_END         => [
		"name"        => "endPlayerAction",
		"description" => clienttranslate('Ending action of ${actplayer}'),
		"type"        => GAME_STATE_TYPE_GAME,
		"action"      => 'endPlayerAction',
		"transitions" => ['chooseAction' => GAME_STATE_ACTION_CHOOSE, 'endPlayerTurn' => GAME_STATE_TURN_END],
	],// endPlayerAction
	
	// End game
	GAME_STATE_GAME_END_FINALIZE  => [
		"name"        => "finalization.process",
		"description" => clienttranslate('Finalizing game'),
		"type"        => GAME_STATE_TYPE_GAME,
		"action"      => 'finalizationProcess',
		"transitions" => ['summary' => GAME_STATE_GAME_END_SUMMARY],
	],
	GAME_STATE_GAME_END_SUMMARY   => [
		"name"              => "finalization.summary",
		"description"       => clienttranslate('Show summary'),
		"descriptionmyturn" => null,
		"type"              => GAME_STATE_TYPE_MULTIPLE_PLAYERS,
		//		"action"      => 'finalizationSummary',
		"action"            => 'setAllActive',
		"args"              => 'argFinalizationSummary',
		"possibleactions"   => ["finalizeSummary"],
		"transitions"       => ['endGame' => GAME_STATE_GAME_END],
	],
	
	// ACTION: Draw new resource card
	GAME_STATE_PICK_RESOURCE_CARD => [
		"name"              => "actionPickResourceCard.pickNew",
		"description"       => clienttranslate('${actplayer} must draw a resource card'),
		"descriptionmyturn" => clienttranslate('${you} must draw a resource card'),
		"type"              => GAME_STATE_TYPE_ACTIVE_PLAYER,
		"args"              => 'argActionPickResourceCard',
		"possibleactions"   => ["pickResourceCard", "cancel"],
		"transitions"       => ["check" => 101, "cancel" => GAME_STATE_ACTION_CHOOSE, 'zombiePass' => GAME_STATE_TURN_END],
	],
	101                           => [
		"name"        => "actionPickResourceCard.check",
		"description" => clienttranslate('Picking card for active player'),
		"type"        => GAME_STATE_TYPE_GAME,
		"action"      => 'pickResourceCardCheckout',
		"transitions" => ['pickAnotherOne'            => GAME_STATE_PICK_RESOURCE_CARD, 'discardSurplus' => 102, 'endAction' => GAME_STATE_ACTION_END,
						  'endDrawResourcePigeonCard' => 201],
	],
	102                           => [
		"name"              => "actionPickResourceCard.discardSurplus",
		"description"       => clienttranslate('${actplayer} must discard a resource card from hand'),
		"descriptionmyturn" => clienttranslate('${you} must choose a card to discard from your hand'),
		"type"              => GAME_STATE_TYPE_ACTIVE_PLAYER,
		"possibleactions"   => ["discardResourceCard"],
		"transitions"       => ["check" => 101, 'zombiePass' => GAME_STATE_TURN_END],
	],
	
	// ACTION: Build a restaurant
	110                      => [
		"name"              => "actionBuildRestaurant.choose",
		"description"       => clienttranslate('${actplayer} is selecting a restaurant to build'),
		"descriptionmyturn" => clienttranslate('${you} must select a restaurant to build'),
		"type"              => GAME_STATE_TYPE_ACTIVE_PLAYER,
		"args"              => 'argActionBuildRestaurantChoose',
		"possibleactions"   => ["choose", "cancel"],
		"transitions"       => ["place" => 111, "cancel" => GAME_STATE_ACTION_CHOOSE, 'zombiePass' => GAME_STATE_TURN_END],
	],
	111                      => [
		"name"              => "actionBuildRestaurant.place",
		"description"       => clienttranslate('${actplayer} is choosing a location for the restaurant'),
		"descriptionmyturn" => clienttranslate('${you} must choose a location for the restaurant'),
		"type"              => GAME_STATE_TYPE_ACTIVE_PLAYER,
		"args"              => 'argActionBuildRestaurantPlace',
		"possibleactions"   => ["place", "cancel"],
		"transitions"       => ["endAction" => GAME_STATE_ACTION_END, "cancel" => 110, 'zombiePass' => GAME_STATE_TURN_END],
	],
	
	// ACTION: Place Terraces T497
	120                      => [
		'name'        => 'actionPlaceTerraces.start',
		'description' => clienttranslate('Starting placing terraces...'),
		'type'        => GAME_STATE_TYPE_GAME,
		'action'      => 'placeTerraceStart',
		'transitions' => ['place' => GAME_STATE_PLACE_TERRACE],
	],// actionPlaceTerraces.start
	GAME_STATE_PLACE_TERRACE => [
		'name'              => 'actionPlaceTerraces.place',
		'description'       => clienttranslate('${actplayer} is placing some terraces on restaurant\'s place'),
		'descriptionmyturn' => clienttranslate('${you} must select a restaurant on game board to place a terrace'),
		'type'              => GAME_STATE_TYPE_ACTIVE_PLAYER,
		'args'              => 'argActionPlaceTerrace',
		'possibleactions'   => ['place', 'useGoldCard', 'useAdjacentTerracePigeonCard', 'confirm', 'cancel'],
		'transitions'       => ['place'     => GAME_STATE_PLACE_TERRACE, 'endAddTerracePigeonCard' => 211, 'showPigeonCard' => 122,
								'endAction' => GAME_STATE_ACTION_END, 'cancel' => GAME_STATE_ACTION_CHOOSE, 'zombiePass' => GAME_STATE_TURN_END],
	],// actionPlaceTerraces.place
	122                      => [
		'name'              => 'actionPlaceTerraces.showPigeonCard',
		'description'       => clienttranslate('${actplayer} drew a pigeon card'),
		'descriptionmyturn' => clienttranslate('${you} drew a pigeon card'),
		'type'              => GAME_STATE_TYPE_ACTIVE_PLAYER,
		'args'              => 'argActionPlaceTerraceShowPigeonCard',
		'possibleactions'   => ['continue'],
		'transitions'       => ['pigeonDrawResource' => 200, 'pigeonAddTerrace' => 210, 'continue' => GAME_STATE_PLACE_TERRACE, 'zombiePass' => GAME_STATE_TURN_END],
	],// actionPlaceTerraces.showPigeonCard
	
	// ACTION: Complete Objective T536
	130                      => [// Initialize state
		'name'        => 'actionCompleteObjective.start',
		'description' => clienttranslate('Starting choosing an objective to complete...'),
		'type'        => GAME_STATE_TYPE_GAME,
		'action'      => 'completeObjectiveStart',
		'transitions' => ['choose' => 131],
	],
	131                      => [
		// The player chooses an objective to complete from hand or river
		'name'              => 'actionCompleteObjective.choose',
		'description'       => clienttranslate('${actplayer} is choosing an objective to complete'),
		'descriptionmyturn' => clienttranslate('${you} must select an objective to complete'),
		'type'              => GAME_STATE_TYPE_ACTIVE_PLAYER,
		'args'              => 'argCompleteObjectiveChoose',
		'possibleactions'   => ['choose', 'cancel'],
		'transitions'       => ['pick' => 132, 'check' => 133, 'cancel' => GAME_STATE_ACTION_CHOOSE, 'zombiePass' => GAME_STATE_TURN_END],
	],
	132                      => [
		// If completing an objective from hand, the player picks a new objective card, choose to keep or reject, else nothing.
		'name'              => 'actionCompleteObjective.draw',
		'description'       => clienttranslate('${actplayer} just drew a new objective card'),
		'descriptionmyturn' => clienttranslate('${you} must choose to keep the objective card or not'),
		'type'              => GAME_STATE_TYPE_ACTIVE_PLAYER,
		'args'              => 'argCompleteObjectiveDraw',
		'possibleactions'   => ['keep', 'reject'],
		'transitions'       => ['check' => 133, 'zombiePass' => GAME_STATE_TURN_END],
	],
	133                      => [
		'name'        => 'actionCompleteObjective.check',
		'description' => clienttranslate('Choosing an objective to complete...'),
		'type'        => GAME_STATE_TYPE_GAME,
		'action'      => 'completeObjectiveCheck',
		'transitions' => ['endAction' => GAME_STATE_ACTION_END, 'again' => 131],
	], // Objective Complete - Check state
	
	// Pigeon card: Draw 2 resource cards T538
	200                      => [
		"name"        => "pigeonDrawResource.start",
		"description" => clienttranslate('Active player picked a pigeon card that is allowing to draw 2 resource cards'),
		"type"        => GAME_STATE_TYPE_GAME,
		"action"      => 'pigeonDrawResourceStart',
		"transitions" => ['pickResourceCard' => GAME_STATE_PICK_RESOURCE_CARD],
	],
	201                      => [
		"name"        => "pigeonDrawResource.end",
		"description" => clienttranslate('Active player drew 2 resource cards'),
		"type"        => GAME_STATE_TYPE_GAME,
		"action"      => 'pigeonDrawResourceEnd',
		"transitions" => ['continue' => GAME_STATE_PLACE_TERRACE],
	],
	
	// Pigeon card: Add free terrace T539
	210                      => [
		"name"        => "pigeonAddTerrace.start",
		"description" => clienttranslate('Active player drew a pigeon card that is allowing to add an additional terrace'),
		"type"        => GAME_STATE_TYPE_GAME,
		"action"      => 'pigeonAddTerraceStart',
		"transitions" => ['placeTerrace' => GAME_STATE_PLACE_TERRACE, 'cancelPigeonCard' => 211],// Cancel leads to end
	],
	211                      => [
		"name"        => "pigeonAddTerrace.end",
		"description" => clienttranslate('Active player placed a free terrace'),
		"type"        => GAME_STATE_TYPE_GAME,
		"action"      => 'pigeonAddTerraceEnd',
		"transitions" => ['continue' => GAME_STATE_PLACE_TERRACE, 'showPigeonCard' => 122],
	],
	
	// Pigeon card: Draw Objective Card T543
	220                      => [
		"name"        => "pigeonDrawObjective.start",
		"description" => clienttranslate('Active player drew an objective card 🕊'),
		"type"        => GAME_STATE_TYPE_GAME,
		"action"      => 'pigeonDrawObjectiveStart',
		"transitions" => ['place' => 221],
	],
	221                      => [
		"name"              => "pigeonDrawObjective.place",
		"description"       => clienttranslate('${actplayer} drew an objective card 🕊'),
		"descriptionmyturn" => clienttranslate('${you} drew an objective card 🕊'),
		"type"              => GAME_STATE_TYPE_ACTIVE_PLAYER,
		"args"              => 'argPigeonDrawObjectivePlace',
		"possibleactions"   => ['keep', 'reject'],
		"transitions"       => ['continue' => GAME_STATE_ACTION_CHOOSE, 'zombiePass' => GAME_STATE_TURN_END],
	],
	
	// Pigeon card: Adjacent Terraces T540
	230                      => [
		"name"        => "pigeonAdjacentTerrace.start",
		"description" => clienttranslate('Active player played an Adjacent Terrace pigeon card 🕊'),
		"type"        => GAME_STATE_TYPE_GAME,
		"action"      => 'pigeonAdjacentTerraceStart',
		"transitions" => ['continue' => GAME_STATE_PLACE_TERRACE, 'zombiePass' => GAME_STATE_TURN_END],
	],

];



