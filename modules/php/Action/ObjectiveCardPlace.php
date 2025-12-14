<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Action;

use \Bga\Games\DinnerInParis\Core\Controller\ArgumentBag;
use \Bga\Games\DinnerInParis\Core\Debug\Dump;
use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Entity\Token;

trait ObjectiveCardPlace {
	
	use ControllerTrait;
	
	public function placeObjectiveCard(bool $keep) {
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('ObjectiveCardPlace(%s) for player %s (pid=%s)',
		//	$keep ? 'keep' : 'reject', $player->getEntityLabel(), getmypid()));
		
		$card = $this->getCard($player);
		//$this->logger->log(sprintf('ObjectiveCardPlace() - %s card %s', $keep ? 'keep' : 'reject', $card->getEntityLabel()));
		
		if( $keep ) {
			$targetPile = $this->table->getObjectiveCardPlayerHand($player);
		} else {
			$targetPile = $this->table->getObjectiveCardRiver();
		}
		
		$targetPile->add($card);
		$changedTokens = $targetPile->getTokenList();
		$this->entityService->startBatching();
		$this->entityService->updateList($changedTokens);
		$this->entityService->applyBatching();
		
		if( $keep ) {
			$this->app->notifyTokenUpdate($changedTokens, clienttranslate('${player_name} is keeping new objective card in hand'), [
				'player_name' => $player->getLabel(),
			]);
		} else {
			$this->app->notifyTokenUpdate($changedTokens, clienttranslate('${player_name} is sharing new ${token_name}'), [
				'player_name' => $player->getLabel(),
				'token_name'  => $card->getLabel(),
			]);
		}
	}
	
	protected function getCard(Player $player): ?Token {
		$playerPendingPile = $this->table->getObjectiveCardPlayerPending($player);
		$objectiveCards = $playerPendingPile->getTokenList();
		
		return $objectiveCards[0] ?? null;
	}
	
	public function generateArguments(ArgumentBag $arguments): void {
		$player = $this->app->getActivePlayer();
		$card = $this->getCard($player);
		// Could use an Objective pigeon card ? Having an Objective pigeon card, not already used one and multiple objective to complete
		$completableCards = $this->table->getCompletableObjectiveCards($player);
		// At least 1 completable objective card
		$allowUsePigeonCard = count($completableCards) >= 1 && !$player->hasActionFlag(Player::FLAG_USE_PIGEON_CARD_OBJECTIVE);
		$objectivePigeonCard = $allowUsePigeonCard ? $this->getObjectivePigeonCard($player) : null;
		$allowUsePigeonCard = $allowUsePigeonCard && $objectivePigeonCard;
		//$this->logger->log(sprintf('ObjectiveCardPlace::generateArguments - allowUsePigeonCard "%s" with Completable cards "%s" and pigeon card "%s"'
		//	, Dump::bool($objectivePigeonCard), json_encode($completableCards), $objectivePigeonCard ? $objectivePigeonCard->getEntityLabel() : 'NONE'));
		$arguments->setPlayerArgumentList($player, [
			'card'                => $card->getId(),
			'allowUsePigeonCard'  => $allowUsePigeonCard,
			'objectivePigeonCard' => $objectivePigeonCard ? $objectivePigeonCard->getId() : null,// To show card only, could use another for real
		]);
	}
	
	public function getObjectivePigeonCard(Player $player): ?Token {
		return $this->table->getFirstPlayerPigeonCard($player, PIGEON_CARD_OBJECTIVE);
	}
	
	
}
