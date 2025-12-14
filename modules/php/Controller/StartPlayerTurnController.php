<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;

class StartPlayerTurnController extends AbstractController {
	
	public function run() {
		// Active next player
		$player = $this->app->getActivePlayer();
		// $this->game->trace(sprintf('StartPlayerTurnController(%s)', $player->getEntityLabel()));
		
		// Increase turns number if new turn
		if( $this->app->isFirstTurnPlayer() ) {
			$this->app->setStat('turns_number', $this->app->getStat('turns_number') + 1);
		}
		// Increase player turn number
		$this->app->setStat('turns_number', $this->app->getStat('turns_number'), $player);
		
		$player->setPendingIncome(0);
		$player->setTurnFlags([]);
		$this->entityService->update($player);
		
		$this->app->useStateAction('pickResourceCard');
	}
	
}
