<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractController;
use AppGame\Entity\Player;

class CompleteObjectiveCancelController extends AbstractController {
	
	public function run() {
		$this->game->checkAction('cancel');
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('CompleteObjectiveCancelController() for player %s (pid=%s)', $player->getEntityLabel(), getmypid()));
		
		$finishAction = false;
		// Cancel when using a pigeon card
		if( $player->hasActionFlag(Player::FLAG_USE_PIGEON_CARD_OBJECTIVE) ) {
			$finishAction = true;
		}
		
		// Reset action flags
		$player->setActionFlags(null);
		
		// Save
		$this->entityService->update($player);
		
		// Next state
		$this->app->useStateAction($finishAction ? 'check' : 'cancel');
	}
	
}
