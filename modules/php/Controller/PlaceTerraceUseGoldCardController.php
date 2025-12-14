<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Core\Exception\UserException;
use \Bga\Games\DinnerInParis\Entity\PigeonCard;
use \Bga\Games\DinnerInParis\Entity\ResourceCard;
use \Bga\Games\DinnerInParis\Game\IngredientPaymentUsable;

class PlaceTerraceUseGoldCardController extends AbstractController {
	
	/**
	 * @param IngredientPaymentUsable $card
	 * @return void
	 */
	public function run(IngredientPaymentUsable $card) {
		$this->game->checkAction('useGoldCard');
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('PlaceTerraceUseGoldCardController(%s) for player %s (pid=%s)',
		//	$card->getEntityLabel(), $player->getEntityLabel(), getmypid()));
		
		// Card must be owned by player
		if( $player->getId() !== $card->getPlayerId() ) {
			throw new UserException('Invalid player');
		}
		
		if( $card instanceof ResourceCard ) {
			$cardHand = $this->table->getResourceCardPlayerHand($player);
			$discardPile = $this->table->getResourceCardDiscardPile();
			
		} elseif( $card instanceof PigeonCard ) {
			$cardHand = $this->table->getPigeonCardPlayerHand($player);
			$discardPile = $this->table->getPigeonCardPlayerDiscard($player);
			
		} else {
			throw new UserException('Card must be a pigeon card or a resource card');
		}
		
		// Card must be placed in player resource card hand
		if( !$cardHand->contains($card) ) {
			throw new UserException('Card not in player\'s resource card hand');
		}
		
		$goldEarned = $card->getGiveAmount(RESOURCE_GOLD);
		
		// Card must be a gold card
		if( !$goldEarned ) {
			throw new UserException('Card not giving any gold resource');
		}
		
		// Move card to discard pile
		$discardPile->putOnTop($card);
		
		// Increase balance
		$player->setBalance($player->getBalance() + $goldEarned);
		
		// Save
		$movedCards = $discardPile->getTokenList();// Position is updated for the entire pile
		//$this->logger->log(sprintf('Use gold card %s to add %d coin to balance', $card->getEntityLabel(), $goldEarned));
		$this->entityService->startBatching();
		$this->entityService->update($player);
		$this->entityService->updateList($movedCards);
		$this->entityService->applyBatching();
		
		// Notify users of all tokens move (terrace)
		$this->app->notifyTokenUpdate($movedCards,
			$card instanceof PigeonCard
				? clienttranslate('${player_name} used a pigeon card to earn 2 golds')
				: clienttranslate('${player_name} used a gold card to earn 1 gold'),
			[
				'player_name' => $player->getLabel(),
			]);
		
		// Notify users of player update (balance)
		$this->app->notifyPlayerUpdate($player);
		
		// Next state
		$this->app->useStateAction('place');
	}
	
}
