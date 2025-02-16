<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Core\Controller;

use AppGame\Entity\Token;
use AppGame\Game\GameTable;
use AppGame\Logger\AbstractLogger;
use AppGame\Logger\BgaLogger;
use AppGame\Service\BoardGameApp;
use AppGame\Service\EntityService;
use DinnerInParis;


abstract class AbstractController {
	
	/** @var BoardGameApp */
	protected $app;
	
	/** @var GameTable */
	protected $table;
	
	/** @var DinnerInParis */
	protected $game;
	
	/** @var EntityService */
	protected $entityService;
	
	/** @var AbstractLogger */
	protected $logger;
	
	/**
	 * AbstractController constructor
	 *
	 * @param DinnerInParis $table
	 */
	public function __construct(DinnerInParis $table) {
		$this->game = $table;// BGA Table
		$this->entityService = EntityService::get();
		$this->app = BoardGameApp::get();
		$this->table = $this->app->getTable();
		//$this->logger = BgaLogger::get();
		$this->initialize();
	}
	
	/**
	 * This function tries to fix location of token on client side in case of inconsistent data incoming
	 *
	 * @param int|Token $card
	 * @return void
	 */
	protected function fixToken($card): void {
		if( is_int($card) ) {
			$card = $this->app->getToken($card);
		}
		$this->app->notifyTokenUpdate([$card]);
	}
	
	protected function initialize() {
	}
	
	/**
	 * This function applies independently of the player, but not the active player
	 *
	 * @param ArgumentBag $arguments
	 * @return array
	 * @see https://en.doc.boardgamearena.com/Your_game_state_machine:_states.inc.php#Private_info_in_args
	 */
	public function generateArguments(ArgumentBag $arguments): void {
	}
	
}
