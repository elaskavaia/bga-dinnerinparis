<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Entity\Player;

class PlaceTerraceConfirmController extends AbstractController {
	
	public function run() {
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('PlaceTerraceConfirmController() for player %s (pid=%s)', $player->getEntityLabel(), getmypid()));
		
		//		$this->table->updatePlayerScoreAndIncome($player);
		$player->addTurnFlag(Player::FLAG_TERRACE_PLACED);
		$this->entityService->update($player);
		
		// Notify users of player's update (balance)
		$this->app->notifyPlayerUpdate($player);
		
		// Next state
		$this->app->useStateAction('endAction');
	}
	
}
