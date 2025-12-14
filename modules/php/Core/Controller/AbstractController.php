<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Core\Controller;

use \Bga\Games\DinnerInParis\Entity\Token;
use \Bga\Games\DinnerInParis\Game\GameTable;
use \Bga\Games\DinnerInParis\Logger\AbstractLogger;
use \Bga\Games\DinnerInParis\Service\BoardGameApp;
use \Bga\Games\DinnerInParis\Service\EntityService;
use Bga\Games\DinnerInParis\Game;


abstract class AbstractController {
	
	/** @var BoardGameApp */
	protected $app;
	
	/** @var GameTable */
	protected $table;
	
	/** @var Game */
	protected $game;
	
	/** @var EntityService */
	protected $entityService;
	
	/** @var AbstractLogger */
	protected $logger;
	
	/**
	 * AbstractController constructor
	 *
	 * @param Game $table
	 */
	public function __construct(Game $table) {
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
