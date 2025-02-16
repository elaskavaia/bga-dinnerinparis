<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractController;
use AppGame\Entity\Player;
use AppGame\Entity\Token;

class CompleteObjectiveCheckController extends AbstractController {
	
	public function run() {
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('CompleteObjectiveCheckController() for player %s (pid=%s)', $player->getEntityLabel(), getmypid()));
		
		// Use pigeon card ?
		$doItAgain = false;
		$playerUpdate = false;
		// Request to use a pigeon card and did not already use one for this action
		if( $player->hasActionFlag(Player::FLAG_REQUEST_PIGEON_CARD_OBJECTIVE) && !$player->hasActionFlag(Player::FLAG_USE_PIGEON_CARD_OBJECTIVE) ) {
			$this->entityService->startBatching();
			$player->removeActionFlag(Player::FLAG_REQUEST_PIGEON_CARD_OBJECTIVE);
			$completableCards = $this->table->getCompletableObjectiveCards($player);
			$pigeonCard = $this->getObjectivePigeonCard($player);
			if( $completableCards && $pigeonCard ) {
				// has more objective to complete and a valid pigeon card
				$player->addActionFlag(Player::FLAG_USE_PIGEON_CARD_OBJECTIVE);
				$pigeonDiscardPile = $this->table->getPigeonCardPlayerDiscard($player);
				$pigeonDiscardPile->putOnTop($pigeonCard);
				$movedTokens = $pigeonDiscardPile->getTokens();
				$this->entityService->updateList($movedTokens);
				
				$this->app->notifyTokenUpdate($movedTokens, clienttranslate('${player_name} used an objective pigeon card to complete another objective'), [
					'player_name' => $player->getLabel(),
					'card_name'   => $pigeonCard->getLabel(),
				]);
				
				$doItAgain = true;
			}
			$playerUpdate = true;
		} elseif( $player->hasActionFlag(Player::FLAG_USE_PIGEON_CARD_OBJECTIVE) ) {
			$player->removeActionFlag(Player::FLAG_USE_PIGEON_CARD_OBJECTIVE);
			$playerUpdate = true;
		}
		if( $playerUpdate ) {
			// Save
			$this->entityService->update($player);
			$this->entityService->applyBatching();
		}
		
		// Next state
		if( $doItAgain ) {
			// Player used a pigeon card
			$this->app->useStateAction('again');
		} else {
			$this->app->useStateAction('endAction');
		}
		
	}
	
	public function getObjectivePigeonCard(Player $player): ?Token {
		return $this->table->getFirstPlayerPigeonCard($player, PIGEON_CARD_OBJECTIVE);
	}
	
}
