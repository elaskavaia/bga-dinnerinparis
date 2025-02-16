<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Entity;

use AppGame\Game\IngredientPaymentUsable;

class ResourceCard extends Token implements IngredientPaymentUsable {
	
	public function getGives(): array {
		return $this->getMaterial()['gives'];
	}
	
	public function getGiveAmount(string $resource): int {
		return count(array_filter($this->getGives(), function ($loopResource) use ($resource) {
			return $loopResource === $resource;
		}));
	}
	
}
