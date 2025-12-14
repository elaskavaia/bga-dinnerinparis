<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Core\Exception\InvalidInputException;
use \Bga\Games\DinnerInParis\Entity\PigeonCard;
use \Bga\Games\DinnerInParis\Entity\ResourceCard;
use \Bga\Games\DinnerInParis\Entity\Restaurant;
use \Bga\Games\DinnerInParis\Entity\Token;

class AbstractBuildRestaurantController extends AbstractController {
	
	protected function loadRestaurant(int $tokenId): Restaurant {
		/** @var Restaurant $restaurantToken */
		$restaurantToken = $this->app->getToken($tokenId);
		
		// Check restaurant is buildable and available
		if( !$restaurantToken ) {
			throw new InvalidInputException(sprintf('Restaurant token "%d" not found', $tokenId));
		}
		if( $restaurantToken->getContainer() !== TOKEN_CONTAINER_BOX ) {
			throw new InvalidInputException(sprintf('Restaurant token "%d" is not in the game box (but in %d), so this token is not available to place',
				$tokenId, $restaurantToken->getContainer()));
		}
		
		return $restaurantToken;
	}
	
	/**
	 * @param Restaurant $restaurant
	 * @param array $cardIds
	 * @return Token[]
	 */
	protected function loadRestaurantCards(Restaurant $restaurant, array $cardIds): array {
		/** @var ResourceCard $card */
		// Check chosen resource card matches the player's hand
		$table = $this->app->getTable();
		// Are selected cards in player's hands ?
		$player = $this->app->getActivePlayer();
		//		$handCards = $table->getResourceCardPlayerHand($player)->getTokens();
		//		$handCards = $table->getPigeonCardPlayerHand($player)->getTokens();
		$cards = [];
		foreach( $cardIds as $cardId ) {
			$cardId = (int) $cardId;
			$card = null;
			
			$card = $this->app->getToken($cardId);
			if( !$card ) {
				throw new InvalidInputException(sprintf('Card %d was not found', $cardId));
			}
			if( !$card->isOwnedBy($player) ) {
				//			if( true ) {
				throw new InvalidInputException(sprintf('Card "%s" not owned by player', $card->getLabel()));
			}
			if( !($card instanceof ResourceCard) && !($card instanceof PigeonCard) ) {
				throw new InvalidInputException(sprintf('Card "%s" is not available for build', $card->getLabel()));
			}
			$cards[] = $card;
		}
		
		// Are selected cards validating the restaurant build ?
		$restaurantMaterial = $restaurant->getMaterial();
		$costIndex = 0;
		foreach( $restaurantMaterial['cost'] as $requiredResource => $requiredQuantity ) {
			for( $resIndex = 0; $resIndex < $requiredQuantity; $resIndex++ ) {
				$card = $cards[$costIndex];
				if( !in_array($requiredResource, $card->getGives(), true) ) {
					throw new InvalidInputException(sprintf('Card %d is unable to pay cost for resource %s giving %s', $cardId, $requiredResource, json_encode($card->getGives())));
				}
				$costIndex++;
			}
		}
		
		return $cards;
	}
	
}
