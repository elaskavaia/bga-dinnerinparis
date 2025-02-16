<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Entity;

class Restaurant extends Token {
	
	public function getSize(): int {
		return $this->getMaterial()['size'];
	}
	
	public function getCategory(): int {
		return $this->getMaterial()['category'];
	}
	
}
