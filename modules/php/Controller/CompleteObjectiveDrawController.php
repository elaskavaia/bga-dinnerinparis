<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Action\ObjectiveCardPlace;
use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Entity\Player;

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
