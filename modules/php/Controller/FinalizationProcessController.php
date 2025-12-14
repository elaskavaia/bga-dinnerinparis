<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;

class FinalizationProcessController extends AbstractController {
	
	public function run() {
		//$this->logger->log(sprintf('FinalizationProcessController()'));
		
		// Count last score
		$this->entityService->startBatching();
		// Refresh majorities
		$this->table->updatePlayerMajorities();
		// Re-calculate all players' scores
		foreach( $this->app->getPlayers() as $player ) {
			$this->table->updatePlayerScoreAndIncome($player, true);
			$this->app->notifyPlayerUpdate($player);
			$this->entityService->update($player);
		}
		$this->entityService->applyBatching();
		
		$grid = $this->table->getGrid();
		// Calculate players' stats
		foreach( $this->app->getPlayers() as $player ) {
			$objectiveDiscardPile = $this->table->getObjectiveCardPlayerDiscard($player);
			$this->app->setStat('income', $player->getIncome(), $player);
			$this->app->setStat('restaurants', count($grid->getTokenList(TOKEN_TYPE_RESTAURANT, $player)), $player);
			$this->app->setStat('terraces', count($grid->getTokenList(TOKEN_TYPE_TERRACE, $player)), $player);
			$this->app->setStat('objectives', $objectiveDiscardPile->count(), $player);
		}
		
		$this->app->useStateAction('summary');
	}
	
}
