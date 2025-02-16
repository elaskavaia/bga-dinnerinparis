<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Action\ObjectiveCardDraw;
use AppGame\Core\Controller\AbstractController;
use AppGame\Core\Controller\ArgumentBag;
use AppGame\Core\Debug\Dump;
use AppGame\Core\Exception\InvalidInputException;
use AppGame\Entity\Player;
use AppGame\Entity\Token;

class CompleteObjectiveChooseController extends AbstractController {
	
	use ObjectiveCardDraw;
	
	public function run(Token $card, ?bool $usePigeonCard = false) {
		$this->game->checkAction('choose');
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('CompleteObjectiveChooseController(%s) for player %s (pid=%s)',
		//	$card->getEntityLabel(), $player->getEntityLabel(), getmypid()));
		
		// Check card is valid
		// Check owner & location
		$fromHand = false;
		if( $card->getContainer() === TOKEN_CONTAINER_PLAYER_HAND ) {
			if( $card->getPlayerId() !== $player->getId() ) {
				throw new InvalidInputException('Invalid player');
			}
			$fromHand = true;
		} elseif( $card->getContainer() !== TOKEN_CONTAINER_BOARD_RIVER ) {
			throw new InvalidInputException('Invalid container');
		}
		// Check is resolvable
		if( !$this->table->canCompleteObjective($player, $card) ) {
			throw new InvalidInputException('Non resolvable objective card');
		}
		
		// Apply
		$playerDiscardPile = $this->table->getObjectiveCardPlayerDiscard($player);
		$playerDiscardPile->putOnTop($card);
		$movedTokens = $playerDiscardPile->getTokens();
		
		$this->table->updatePlayerScoreAndIncome($player);
		if( $usePigeonCard ) {
			// We will see it in check
			$player->addActionFlag(Player::FLAG_REQUEST_PIGEON_CARD_OBJECTIVE);
		}
		
		// Save
		$this->entityService->startBatching();
		$this->entityService->updateList($movedTokens);
		$this->entityService->update($player);
		$this->entityService->applyBatching();
		
		//		//$this->logger->log(sprintf('Moved card %s to discard pile, save %d tokens', $card->getEntityLabel(), count($movedTokens)));
		$this->app->notifyTokenUpdate($movedTokens, clienttranslate('${player_name} completed objective card "${token_name}"'), [
			'player_name' => $player->getLabel(),
			'token_name'  => $card->getLabel(),
		]);
		
		// Next state
		if( $fromHand ) {
			$this->drawObjectiveCard($player);
			$this->app->useStateAction('pick');
		} else {
			$this->app->useStateAction('check');
		}
	}
	
	public function getObjectivePigeonCard(Player $player): ?Token {
		return $this->table->getFirstPlayerPigeonCard($player, PIGEON_CARD_OBJECTIVE);
	}
	
	public function generateArguments(ArgumentBag $arguments): void {
		$player = $this->app->getActivePlayer();
		$completableCards = $this->table->getCompletableObjectiveCards($player);
		//		//$this->logger->log(sprintf('Completable cards ? %d (%s), hasActionFlag : %s, getObjectivePigeonCard : %s', count($completableCards),
		//			Dump::bool(count($completableCards) > 1), Dump::bool($player->hasActionFlag(Player::FLAG_USE_PIGEON_CARD_OBJECTIVE)), $this->getObjectivePigeonCard($player) ?? 'NONE'));
		// Could use an Objective pigeon card ? Having an Objective pigeon card, not already used one and multiple objective to complete
		// At least 2 completable objective card
		$allowUsePigeonCard = count($completableCards) >= 2 && !$player->hasActionFlag(Player::FLAG_USE_PIGEON_CARD_OBJECTIVE);
		$objectivePigeonCard = $allowUsePigeonCard ? $this->getObjectivePigeonCard($player) : null;
		$allowUsePigeonCard = $allowUsePigeonCard && $objectivePigeonCard;
		//$this->logger->log(sprintf('CompleteObjectiveChooseController::generateArguments - allowUsePigeonCard "%s" with Completable cards "%s" and pigeon card "%s"'
		//	, Dump::bool($objectivePigeonCard), json_encode($completableCards), $objectivePigeonCard ? $objectivePigeonCard->getEntityLabel() : 'NONE'));
		$arguments->setPlayerArgumentList($player, [
			'allowCancel'         => $player->hasActionFlag(Player::FLAG_USED_PIGEON_CARD),
			'allowUsePigeonCard'  => $allowUsePigeonCard,
			'objectivePigeonCard' => $objectivePigeonCard ? $objectivePigeonCard->getId() : null,// To show card only, could use another for real
			'cards'               => $completableCards,
		]);
	}
	
}
