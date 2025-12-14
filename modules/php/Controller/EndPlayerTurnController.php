<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;

class EndPlayerTurnController extends AbstractController {
	
	public function run() {
		// Active next player
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('EndPlayerTurnController(%s)', $player->getEntityLabel()));
		
		// Apply changes
		$player->applyPendingIncome();// Should be obsolete by updatePlayerScoreAndIncome
		$this->table->updatePlayerScoreAndIncome($player);
		$player->setTurnFlags(null);
		$player->setTurnData(null);
		$this->entityService->update($player);
		
		// Give player additional time for next turns
		$this->app->giveExtraTime($player);
		
		// Notify users of player's update
		$this->app->notifyPlayerUpdate($player);
		
		// Check if game is ending and this is last player turn
		if( $this->app->isGameEnding() && $this->app->isLastTurnPlayer($player) ) {
			$this->app->useStateAction('endGame');
		} else {
			$this->app->useStateAction('nextPlayer');
		}
	}
	
}
