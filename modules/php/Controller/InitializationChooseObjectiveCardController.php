<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Core\Controller\ArgumentBag;
use \Bga\Games\DinnerInParis\Entity\Token;
use \Bga\Games\DinnerInParis\Service\TokenService;

class InitializationChooseObjectiveCardController extends AbstractController {
	
	public function run(Token $card) {
		$this->game->checkAction('chooseObjectiveCard');
		$player = $this->app->getCurrentPlayer();
		//$this->logger->log(sprintf('InitializationChooseObjectiveCard(%s) - chooseObjectiveCard for player %s (pid=%s)', $card->getEntityLabel(), $player->getId(), getmypid()));
		
		// Player saving
		$player->setTurnInfo('selectedObjectiveCard', $card->getId());
		$this->entityService->update($player);
		
		// Deactivate player; if none left, transition to 'start' state
		$this->app->setPlayerInactive($player, 'apply');
	}
	
	public function generateArguments(ArgumentBag $arguments): void {
		foreach( $this->app->getActivePlayers() as $player ) {
			$objectiveCards = $this->table->getPendingPlayerObjectiveCards($player);
			$tokenService = new TokenService();
			$selected = $player->getTurnInfo('selectedObjectiveCard');
			
			$arguments->setPlayerArgumentList($player, [
				'allowChoose' => !$selected,// Else already chosen
				'cards'       => $tokenService->listId($objectiveCards),
				'selected'    => $selected,
			]);
		}
	}
	
}
