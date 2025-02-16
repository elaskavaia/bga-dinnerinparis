<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractPickResourceCardController;
use AppGame\Core\Controller\ArgumentBag;
use AppGame\Core\Exception\InvalidInputException;
use AppGame\Entity\Token;
use AppGame\Logger\BgaLogger;

class PickResourceCardController extends AbstractPickResourceCardController {
	
	public function run(int $cardId) {
		$this->game->checkAction('pickResourceCard');
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('PickResourceCardController(%d) for player %s (pid=%s)', $cardId, $player->getId(), getmypid()));
		
		// Retrieve card token
		/** @var Token $selectedCard */
		$selectedCard = $this->app->getToken($cardId);
		
		// Check card is pickable
		if( !$selectedCard ) {
			throw new InvalidInputException(sprintf('Card token "%d" not found', $cardId));
		}
		if( !in_array($selectedCard->getContainer(), [TOKEN_CONTAINER_BOARD_DECK, TOKEN_CONTAINER_BOARD_RIVER], true) ) {
			throw new InvalidInputException(sprintf('Card token "%d" is not in the board\'s deck or river (but in %d)', $cardId, $selectedCard->getContainer()));
		}
		
		// Initialize table
		$table = $this->app->getTable();
		// By order of container
		$river = $table->getResourceCardRiver();
		$drawPile = $table->getResourceCardDrawPile();
		$playerHand = $table->getResourceCardPlayerHand($player);
		
		BgaLogger::get()->log(sprintf("HAND_COUNT - Player's hand is having %d cards", $playerHand->count()));
		
		// Check selected card
		if( $selectedCard->getContainer() === $drawPile->getContainer() ) {
			// First hidden card from draw pile
			$topCard = $drawPile->getFirst();
			if( !$topCard->equals($selectedCard) ) {
				throw new InvalidInputException(sprintf('Try to select wrong card #%d against #%d from top of resource card draw pile', $selectedCard->getId(), $topCard->getId()));
			}
		} elseif( $selectedCard->getContainer() === $river->getContainer() ) {
			// One visible card from river
			if( !$river->contains($selectedCard) ) {
				throw new InvalidInputException(sprintf('Try to select wrong card #%d from resource card river, this card is not in river', $selectedCard->getId()));
			}
		} else {
			throw new InvalidInputException(sprintf('Try to select card from invalid container %d', $selectedCard->getContainer()));
		}
		
		// From river, the card is visible, from draw pile, we keep it secret
		$wasVisible = true;
		if( $selectedCard->getContainer() === $drawPile->getContainer() ) {
			// Draw pile
			$selectedCard = $drawPile->pickFirst();
			$wasVisible = false;
		} else {
			// River
			$selectedCard = $river->pick($selectedCard);
		}
		// Add card to player's hand
		$playerHand->calculateAllPositions();// Fix invalid positions
		$playerHand->add($selectedCard);
		
		$this->entityService->startBatching();
		$this->entityService->updateList($playerHand->getTokens());
		$this->entityService->applyBatching();
		
		// Notify user of card move
		$this->app->notifyTokenUpdate([$selectedCard], $wasVisible ? clienttranslate('${player_name} drew ${token_name}') : clienttranslate('${player_name} drew a resource card from draw pile'), [
			'player_name' => $player->getLabel(),
			'token_name'  => $selectedCard->getLabel(),
		], $wasVisible ? null : clienttranslate('${player_name} drew ${token_name}'));
		
		// Next state
		$this->app->useStateAction('check');
	}
	
	public function generateArguments(ArgumentBag $arguments): void {
		$player = $this->app->getActivePlayer();
		
		$arguments->setPlayerArgumentList($player, ['allowCancel' => $this->allowCancel($player)]);
	}
	
}
