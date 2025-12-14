<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;

class PlaceTerraceCancelController extends AbstractController {
	
	public function run() {
		$this->game->checkAction('cancel');
		$player = $this->app->getActivePlayer();
		// $this->game->trace(sprintf('PlaceTerraceCancelController() for player %s', $player->getEntityLabel()));
		
		$player->setBalance(null);
		$this->entityService->update($player);
		
		// Notify users of player's update (balance)
		$this->app->notifyPlayerUpdate($player);
		
		// Next state
		$this->app->useStateAction('cancel');
	}
	
}
