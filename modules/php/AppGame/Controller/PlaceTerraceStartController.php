<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractController;

class PlaceTerraceStartController extends AbstractController {
	
	public function run() {
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('PlaceTerraceStartController() for player %s (pid=%s)', $player->getEntityLabel(), getmypid()));
		
		$player->setBalance($player->getIncome());
		$this->entityService->update($player);
		
		// Notify users of player's update (balance)
		$this->app->notifyPlayerUpdate($player);
		
		// Next state
		$this->app->useStateAction('place');
	}
	
}
