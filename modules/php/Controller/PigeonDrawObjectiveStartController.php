<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Action\ObjectiveCardDraw;
use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Core\Exception\UserException;

class PigeonDrawObjectiveStartController extends AbstractController {
	
	use ObjectiveCardDraw;
	
	public function run() {
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('PigeonDrawObjectiveStartController() for player %s (pid=%s)', $player->getEntityLabel(), getmypid()));
		
		$pigeonCard = $this->table->getFirstPlayerPigeonCard($player, PIGEON_CARD_OBJECTIVE);
		if( !$pigeonCard ) {
			throw new UserException('No pigeon card to use to draw a new objective card');
		}
		$discardPile = $this->table->getPigeonCardPlayerDiscard($player);
		
		// Move card to discard pile
		$discardPile->putOnTop($pigeonCard);
		
		$movedTokens = $discardPile->getTokens();
		
		// Save
		$this->entityService->startBatching();
		$this->drawObjectiveCard($player);
		$this->entityService->updateList($movedTokens);
		$this->entityService->applyBatching();
		
		// Notify users of all tokens move (terrace)
		$this->app->notifyTokenUpdate($movedTokens, clienttranslate('${player_name} used an objective pigeon card to draw another objective card'), [
			'player_name' => $player->getLabel(),
		]);
		
		// Next state
		$this->app->useStateAction('place');
	}
	
}
