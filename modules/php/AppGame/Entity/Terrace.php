<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Entity;

class Terrace extends Token {
	
	public function setRestaurant(Restaurant $restaurant) {
		$this->setParentToken($restaurant);
	}
	
	public function getRestaurantId(): ?int {
		return $this->getParentTokenId();
	}
	
	public function getCategory(): int {
		return intval($this->getPosition() / 20) + 1;
	}
	
	public function getCategoryPosition(): int {
		return $this->getPosition() % 20;
	}
	
	public function getLabel(): string {
		return sprintf('Terrace at #%d in category %d', $this->getCategoryPosition(), $this->getCategory());
	}
	
}
