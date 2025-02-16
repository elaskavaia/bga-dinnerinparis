<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game\MajorityResolver;

use AppGame\Entity\Player;
//use AppGame\Logger\BgaLogger;

class RestaurantMajorityResolver extends AbstractMajorityResolver {
	
	protected function resolvePlayer(Player $player): array {
		//BgaLogger::get()->log(sprintf('%s::resolvePlayer(%s)', static::class, $player->getEntityLabel()));
		$result = [];
		
		// Calculate restaurants
		$result['restaurants'] = count($this->table->getGrid()->getTokenList(TOKEN_TYPE_RESTAURANT, $player));
		
		// Calculate total
		$result['score'] = array_sum($result);
		
		return $result;
	}
	
}
