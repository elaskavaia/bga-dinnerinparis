<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Entity;

use AppGame\Game\IngredientPaymentUsable;

class PigeonCard extends Token implements IngredientPaymentUsable {
	
	public function getGives(): array {
		$key = $this->getKey();
		if( $key === PIGEON_CARD_INGREDIENT ) {
			// Any restaurant resources (all but gold)
			return [RESOURCE_BREAD, RESOURCE_CHEESE, RESOURCE_FLOUR, RESOURCE_MEAT, RESOURCE_POTATOES, RESOURCE_SEA_FOOD, RESOURCE_TOMATOES];
		}
		if( $key === PIGEON_CARD_TWO_GOLDS ) {
			return [RESOURCE_GOLD, RESOURCE_GOLD];
		}
		
		return [];
	}
	
	public function getGiveAmount(string $resource): int {
		return count(array_filter($this->getGives(), function ($loopResource) use ($resource) {
			return $loopResource === $resource;
		}));
	}
	
	public function isImmediate(): bool {
		return $this->getMaterial()['immediate'];
	}
	
	public function getDescription(): string {
		return $this->getMaterial()['description'];
	}
	
}
