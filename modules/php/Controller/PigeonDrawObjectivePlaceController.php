<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Action\ObjectiveCardPlace;
use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Entity\Token;

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
