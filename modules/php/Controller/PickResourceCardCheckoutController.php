<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Core\Debug\Dump;
use \Bga\Games\DinnerInParis\Core\Exception\UserException;
use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Game\CardPile;
use \Bga\Games\DinnerInParis\Game\TokenRiver;

class PickResourceCardCheckoutController extends AbstractController {
	
	public function run() {
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('PickResourceCardCheckoutController() for player %s', $player->getId()));
		
		$table = $this->app->getTable();
		// By order of container
		$river = $table->getResourceCardRiver();
		$drawPile = $table->getResourceCardDrawPile();
		$discardPile = $table->getResourceCardDiscardPile();
		$playerHand = $table->getResourceCardPlayerHand($player);
		
		$allMovedCards = [];
		
		// Fill the river
		[$newRiverCards, $riverAllChanges] = $this->fillRiver($river, $drawPile, $discardPile);
		$allMovedCards = array_merge($allMovedCards, $riverAllChanges);
		unset($riverAllChanges);
		
		// Notify user of card move
		if( $newRiverCards ) {
			// If we processed multiple cards, we are fixing a previous error, we only notice players for the first one
			$this->app->notifyTokenUpdate($newRiverCards, clienttranslate('Completed resource card of game board with ${token_name}'), [
				'token_name' => $newRiverCards[0]->getLabel(),
			]);
		}
		
		// Check river is not having 3 identical cards, or discard all until they are good
		while( ($riverMostDuplicated = $river->getMostDuplicated()) && $riverMostDuplicated[0] > 2 ) {
			// 3 duplicated cards, we discard the entire river and deal another one
			//$this->logger->log('Found duplicated card in river, we deal a new one : ' . json_encode($riverMostDuplicated));
			// Discard previous river
			$riverCards = $river->extractAll();
			$discardPile->putListOnTop($riverCards);
			// Build new river
			//$this->logger->log('Fill the river to complete all discarded card due to duplicate');
			[, $riverAllChanges] = $this->fillRiver($river, $drawPile, $discardPile);
			$riverCards = array_merge($riverCards, $riverAllChanges);// All move to notify browsers
			$allMovedCards = array_merge($allMovedCards, $riverCards);// All move to save in db, others changes was already notified to browsers
			unset($riverAllChanges);
			// Notify about all river changes
			usleep(300);
			$this->app->notifyTokenUpdate($riverCards, clienttranslate('Three resource cards are identical, all resource cards from game board was discarded and new ones was dealt'));
		}
		
		// If discard pile is empty, we refill it
		$allMovedCards = array_merge($allMovedCards, $this->checkDrawPile($drawPile, $discardPile));
		
		$this->entityService->startBatching();
		//		//$this->logger->log(sprintf('Update list (non unique) : %s', Dump::tokenDetailsList($allMovedCards)));
		//		//$this->logger->log(sprintf('Update list (unique) : %s', Dump::tokenDetailsList(array_unique($allMovedCards))));
		$this->entityService->updateList($allMovedCards);
		$this->entityService->applyBatching();
		
		// Update majorities (new gold cards)
		$this->app->updatePlayerMajorities();
		
		//$this->logger->log(sprintf('PickResourceCardCheckoutController() - player\'s hand has cards : %s, pid=%s', Dump::tokenIdList($playerHand), getmypid()));
		
		// Next state
		if( $playerHand->count() > 7 ) {
			// The user is having more than 7 resource card in hand, he must discard one
			//$this->logger->log(sprintf('PickResourceCardCheckoutController() - request player to discard surplus'));
			$this->app->useStateAction('discardSurplus');
		} else {
			// We are good to continue
			//$this->logger->log(sprintf('PickResourceCardCheckoutController() - All is good, ending draw action'));
			$action = null;
			if( $player->hasActionFlag(Player::FLAG_DRAW_RESOURCE_CARD) ) {
				$player->removeActionFlag(Player::FLAG_DRAW_RESOURCE_CARD);
				if( $player->hasActionFlag(Player::FLAG_DRAW_RESOURCE_CARD) ) {
					$action = 'pickAnotherOne';
				}
			}
			if( !$action && $player->hasActionFlag(Player::FLAG_RESUME_PIGEON_CARD_DRAW_RESOURCE) ) {
				$action = 'endDrawResourcePigeonCard';
			}
			if( $action ) {
				$this->entityService->update($player);
			}
			$this->app->useStateAction($action ?: 'endAction');
			//			//$this->logger->$this->log(sprintf('PickResourceCardCheckoutController() - Continue testing by picking another card'));
			//			$this->table->gamestate->nextState('redoTest');
		}
	}
	
	protected function checkDrawPile(CardPile $drawPile, CardPile $discardPile): array {
		$allChanges = [];
		// If draw pile is empty, shuffle card and put it in
		if( $drawPile->isEmpty() ) {
			$newDrawCards = $discardPile->getShuffledCards();
			$drawPile->addList($newDrawCards);
			//$this->logger->log('Shuffled cards from resource draw pile, new draw pile cards', $drawPile->getIdList());
			// Notify user of card move for new draw pile
			$this->app->notifyTokenUpdate($newDrawCards, clienttranslate('Shuffle resource discard pile and put it in draw pile'));
			$allChanges = array_merge($allChanges, $newDrawCards);
		}
		
		return $allChanges;
	}
	
	/**
	 * @param TokenRiver $river
	 * @param CardPile $drawPile
	 * @param CardPile $discardPile
	 * @return array[] All changes cards [[riverChanges], [allChanges]]
	 * @throws UserException
	 */
	protected function fillRiver(TokenRiver $river, CardPile $drawPile, CardPile $discardPile): array {
		$allChanges = [];
		$riverChanges = [];
		foreach( $river->getEmptySlots() as $slot ) {
			$allChanges = array_merge($allChanges, $this->checkDrawPile($drawPile, $discardPile));
			
			$card = $drawPile->pickFirst();
			$river->set($card, $slot);
			
			$riverChanges[] = $card;
			$allChanges[] = $card;
		}
		
		return [$riverChanges, $allChanges];
	}
	
}

