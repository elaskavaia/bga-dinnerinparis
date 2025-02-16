<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Action\ObjectiveCardPlace;
use AppGame\Core\Controller\AbstractController;
use AppGame\Entity\Player;

class CompleteObjectiveDrawController extends AbstractController {
	
	use ObjectiveCardPlace;
	
	public function run(bool $keep, bool $usePigeonCard = false) {
		$this->game->checkAction($keep ? 'keep' : 'reject');
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('CompleteObjectiveDrawController(%s) for player %s (pid=%s)', $keep ? 'keep' : 'reject', $player->getEntityLabel(), getmypid()));
		
		if( $usePigeonCard ) {
			// We will see it in check
			$player->addActionFlag(Player::FLAG_REQUEST_PIGEON_CARD_OBJECTIVE);
		}
		
		$this->placeObjectiveCard($keep);
		
		// Next state
		$this->app->useStateAction('check');
	}
	
}
