<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractController;
use AppGame\Core\Exception\InvalidInputException;
use AppGame\Entity\Token;

class DiscardResourceCardController extends AbstractController {
	
	public function run(int $cardId) {
		$this->game->checkAction('discardResourceCard');
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('DiscardResourceCardController(%d) for player %s, pid=%s', $cardId, $player->getId(), getmypid()));
		
		// Initialize table
		$table = $this->app->getTable();
		// By order of container
		$discardPile = $table->getResourceCardDiscardPile();
		$playerHand = $table->getResourceCardPlayerHand($player);
		
		// Retrieve card token
		/** @var Token $selectedCard */
		$selectedCard = $playerHand->getTokenById($cardId);
		//		$selectedCard = $this->entityService->load(Token::class, $cardId);// Reload non associated card from database while all tokens are loaded
		
		// Check card is pickable
		if( !$selectedCard ) {
			// Try to fix all clients
			$this->fixToken($cardId);
			// Return error
			throw new InvalidInputException(sprintf('Card token "%d" not found in player\'s hand', $cardId));
		}
		//$this->logger->log(sprintf('Got selected card %s to discard', $selectedCard->getEntityLabel()));
		//		if( $selectedCard->getPlayerId() !== $player->getId() ) {
		//			throw new InvalidInputException(sprintf('Card token "%d" is not assigned to the #%s player\'s hand (but to %s)', $cardId, $player->getId(), $selectedCard->getPlayerId()));
		//		}
		//		if( $selectedCard->getContainer() !== TOKEN_CONTAINER_PLAYER_HAND ) {
		//			throw new InvalidInputException(sprintf('Card token "%d" is not in the player\'s hand (but in %d)', $cardId, $selectedCard->getContainer()));
		//		}
		
		// We put it on top, so all have changed of position
		//		//$this->logger->log(sprintf('DiscardResourceCardController() - before putOnTop - discardPile cards (%d) are %s', $discardPile->count(), json_encode($discardPile->getIdList())));
		$discardPile->putOnTop($selectedCard);
		//		//$this->logger->log(sprintf('DiscardResourceCardController() - after putOnTop - discardPile cards (%d) are %s', $discardPile->count(), json_encode($discardPile->getIdList())));
		$this->entityService->startBatching();
		$this->entityService->updateList($discardPile->getTokens());
		$this->entityService->applyBatching();
		
		// Notify user of card move
		$this->app->notifyTokenUpdate($discardPile->getTokens(), clienttranslate('${player_name} discarded a resource card'), [
			'player_name' => $player->getLabel(),
			'token_name'  => $selectedCard->getLabel(),
		], clienttranslate('${player_name} discarded ${token_name}'));
		
		// Next state
		$this->app->useStateAction('check');
	}
	
}
