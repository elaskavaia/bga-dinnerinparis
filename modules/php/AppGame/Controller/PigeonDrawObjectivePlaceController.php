<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Action\ObjectiveCardPlace;
use AppGame\Core\Controller\AbstractController;
use AppGame\Entity\Player;
use AppGame\Entity\Token;

class PigeonDrawObjectivePlaceController extends AbstractController {
	
	use ObjectiveCardPlace;
	
	public function run(bool $keep) {
		$this->game->checkAction($keep ? 'keep' : 'reject');
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('PigeonDrawObjectivePlaceController(%s) for player %s (pid=%s)', $keep ? 'keep' : 'reject', $player->getEntityLabel(), getmypid()));
		
		$this->placeObjectiveCard($keep);
		
		// Pigeon card already consumed in PigeonDrawObjectiveStartController
		
		// Next state
		$this->app->useStateAction('continue');
	}
	
	public function getObjectivePigeonCard(Player $player): ?Token {
		return null;
	}
	
}
