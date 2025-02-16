<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractController;
use RuntimeException;

class InitializationApplyController extends AbstractController {
	
	public function run() {
		//$this->logger->log(sprintf('InitializationApplyController()'));
		
		$cards = [];
		$players = $this->app->getPlayers();
		$this->entityService->startBatching();
		$objectiveCardRiver = $this->table->getObjectiveCardRiver();
		foreach( $players as $player ) {
			$selectedObjectiveCard = $player->getTurnInfo('selectedObjectiveCard');
			if( $selectedObjectiveCard ) {
				$selectedCard = $rejectedCard = null;
				$pendingCards = $this->table->getPendingPlayerObjectiveCards($player);
				foreach( $pendingCards as $card ) {
					if( $card->getId() === $selectedObjectiveCard ) {
						$selectedCard = $card;
					} else {
						$rejectedCard = $card;
					}
				}
				////$this->logger->log(sprintf('Apply objective cards for player %s, selected is %s, rejected is %s', $player->getEntityLabel(),
				//	$selectedCard ? '#' . $selectedCard->getId() : 'Unknown', $rejectedCard ? '#' . $rejectedCard->getId() : 'Unknown'));
				// Handle errors, but it was checked multiple times previously, it should be impossible
				if( !$selectedCard ) {
					throw new RuntimeException(sprintf('Issue with selected objective card #%d by player %s, card not found', $selectedObjectiveCard, $player->getEntityLabel()));
				}
				if( !$selectedCard->containedBy(TOKEN_CONTAINER_PLAYER_BOARD) ) {
					throw new RuntimeException(sprintf('Issue with selected objective card #%d by player %s, not in player board (but %d)', $selectedObjectiveCard, $player->getEntityLabel(), $selectedCard->getContainer()));
				}
				if( !$selectedCard->isOwnedBy($player) ) {
					throw new RuntimeException(sprintf('Issue with selected objective card #%d by player %s, card not owned by player (but %d)', $selectedObjectiveCard, $player->getEntityLabel(), $selectedCard->getPlayerId()));
				}
				$playerObjectiveCardHand = $this->table->getObjectiveCardPlayerHand($player);
				$playerObjectiveCardHand->add($selectedCard);
				$objectiveCardRiver->add($rejectedCard);
				$player->setTurnData(null);
				$this->entityService->update($player);
				$this->entityService->update($selectedCard);
				$cards[] = $selectedCard;
				$this->entityService->update($rejectedCard);
				$cards[] = $rejectedCard;
			}
		}
		$this->entityService->applyBatching();
		
		$this->app->notifyTokenUpdate($cards);
		
		$this->app->useStateAction('start');
	}
	
}
