<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game\MajorityResolver;

use \Bga\Games\DinnerInParis\Entity\Player;
//use \Bga\Games\DinnerInParis\Logger\BgaLogger;

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
