<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Entity;

use \Bga\Games\DinnerInParis\Game\IngredientPaymentUsable;

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
