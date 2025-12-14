<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Service;

use \Bga\Games\DinnerInParis\Entity\ResourceCard;
use \Bga\Games\DinnerInParis\Entity\Token;

class TokenService {
	
	public function listId(array $tokens): array {
		return array_map(function (?Token $token) {
			return $token->getId();
		}, array_filter($tokens));
	}
	
	/**
	 * @param ResourceCard[][] $cardsByResource
	 * @param array $resources
	 * @param array $reservedCards Reserved card ids
	 * @return array|null List of cards to use as one possible solution or null if no solution
	 */
	public function getOneConsumptionSolution(array $cardsByResource, array $resources, array $reservedCards = []): ?array {
		if( !$resources ) {
			return [];
		}
		// Ressource one by one
		$resource = array_shift($resources);
		//		dump('Ressource : '.$resource, '$reservedCards', $reservedCards);
		$resourceCards = $cardsByResource[$resource] ?? null;
		if( empty($resourceCards) ) {
			// Required resource is not available
			return null;
		}
		foreach( $resourceCards as $card ) {
			if( !empty($reservedCards[$card->getId()]) ) {
				// Ignore already used card
				continue;
			}
			// Try all available cards matching this resource
			$subCardsByResource = $cardsByResource;
			// Consume card by reserving it
			$consumed = $reservedCards;
			$consumed[$card->getId()] = true;
			//			unset($subCardsByResource[$resource][$cardIndex]);// Consume card
			$solution = $this->getOneConsumptionSolution($subCardsByResource, $resources, $consumed);
			if( $solution !== null ) {
				// There is a sub solution, so this solution is good enough
				$solution[] = $card;
				
				return $solution;
			}
		}
		
		return null;
	}
	
	/**
	 * @param ResourceCard[] $cards
	 * @return array
	 */
	public function groupByResource(array $cards): array {
		$grouped = [];
		foreach( $cards as $card ) {
			foreach( $card->getGives() as $resource ) {
				if( !isset($grouped[$resource]) ) {
					$grouped[$resource] = [];
				}
				$grouped[$resource][$card->getId()] = $card;
			}
		}
		
		return $grouped;
	}
	
	/**
	 * @param array $required
	 * @return array
	 */
	public function flattenQuantity(array $required): array {
		$flatten = [];
		foreach( $required as $item => $quantity ) {
			for( $i = 0; $i < $quantity; $i++ ) {
				$flatten[] = $item;
			}
		}
		
		return $flatten;
	}
	
	/**
	 * @param ResourceCard[] $cards
	 * @return array
	 */
	public function getCardsGives(array $cards): array {
		$gives = [];
		foreach( $cards as $card ) {
			$gives[] = $card->getGives();
		}
		
		return $gives;
	}
	
	public function generateListKey($list): string {
		sort($list);
		
		return implode('-', $list);
	}
	
	public function getAlternativeKeys(array $items, int $length): array {
		$costKeys = [];
		$tree = $this->getAlternativeTree($items, $length);
		foreach( $tree as $alternative ) {
			// Several Set could have the same cards if costing several times the same resource
			// Any card set in a different order, we don't care, we only keep the last one
			$costKeys[$this->generateListKey($alternative)] = $alternative;
		}
		
		return $costKeys;
	}
	
	public function getAlternativeTree($items, int $length): array {
		if( !$items ) {
			return [];
		}
		$itemList = array_shift($items);
		// Get array of array
		$subTree = $this->getAlternativeTree($items, $length - 1);
		$tree = [];
		foreach( $itemList as $itemAlt ) {
			if( $subTree ) {
				foreach( $subTree as $subAlternative ) {
					$tree[] = array_merge([$itemAlt], $subAlternative);
				}
			} else {
				// Last item
				$tree[] = [$itemAlt];
			}
		}
		
		return $tree;
	}
	
}
