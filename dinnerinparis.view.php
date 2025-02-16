<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

/**
 * dinnerinparis.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in dinnerinparis_dinnerinparis.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

require_once(APP_BASE_PATH . "view/common/game.view.php");

/**
 * @property DinnerInParis $game
 * @see https://en.doc.boardgamearena.com/Game_layout:_view_and_template:_yourgamename.view.php_and_yourgamename_yourgamename.tpl
 */
class view_dinnerinparis_dinnerinparis extends game_view {
	
	function build_page($viewArgs) {
		// Get players & players number
		$players = $this->game->loadPlayersBasicInfos();
		$players_nbr = count($players);
		
		/*********** Place your code below:  ************/
		$this->trace(sprintf('Game with %d players', $players_nbr));
		
		//		$this->tpl['LOGS'] = SetupLogger::get()->getLogs();
		$this->tpl['LOGS'] = '';
		
		$this->tpl['endingGame'] = self::_('LAST TURN');
		$this->tpl['restaurantBoxTitle'] = self::_('All available restaurants in box');
		
		$table = $this->game->app->getTable();
		
		$objectiveCardRiver = $table->getObjectiveCardRiver();
		
		$this->page->begin_block('dinnerinparis_dinnerinparis', 'objectiveCardRiverSlot');
		for( $i = 0; $i < $objectiveCardRiver->countSlots(); $i++ ) {
			$this->page->insert_block('objectiveCardRiverSlot', []);
		}
	}
	
	function getGameName() {
		return "dinnerinparis";
	}
	
}
  

