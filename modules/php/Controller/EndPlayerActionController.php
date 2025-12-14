<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use Exception;

class EndPlayerActionController extends AbstractController {
	
	public function run() {
		// Active next player
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('EndPlayerActionController(%s)', $player->getEntityLabel()));
		
		// Calculate done action
		$nextAction = $player->getNextActionFlag();
		//		$this->game->trace(sprintf('EndPlayerActionController() - nextAction is %s', $nextAction));
		if( $nextAction ) {
			$player->addTurnFlag($nextAction);
		} else {
			//$this->logger->error(new Exception(sprintf('Game reached more action than the 3 allowed for player %s', $player->getEntityLabel())));
		}
		$this->entityService->update($player);
		
		// Check this is an ending game
		if( !$this->app->isGameEnding() ) {
			$endingStats = $this->app->getEndingStats();
			$ending = false;
			foreach( $endingStats as $statKey => $stats ) {
				if( $stats['completed'] ) {
					//$this->logger->log(sprintf('Calculate game end - Stat "%s" is completed, game will now end at last player turn', $statKey));
					$ending = true;
					break;
				}
			}
			if( $ending ) {
				$this->app->setGameEnding();
				$this->app->notifyGameUpdate(['state' => 'ending'], '${player_name} triggered the end of game, this is the last turn', [
					'player_name' => $player->getLabel(),
				]);
			}
		}
		
		// Next state
		if( $nextAction === 'action_2' ) {
			$this->app->useStateAction('endPlayerTurn');
		} else {
			$this->app->useStateAction('chooseAction');
		}
	}
	
}
