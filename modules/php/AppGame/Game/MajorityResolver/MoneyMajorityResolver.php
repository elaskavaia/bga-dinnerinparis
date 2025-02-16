<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game\MajorityResolver;

use AppGame\Entity\Player;
//use AppGame\Logger\BgaLogger;

class MoneyMajorityResolver extends AbstractMajorityResolver {
	
	protected function resolvePlayer(Player $player): array {
		//BgaLogger::get()->log(sprintf('%s::resolvePlayer(%s)', static::class, $player->getEntityLabel()));
		$result = [];
		
		// Calculate income
		$result['income'] = $player->getIncome();
		// Calculate resource cards
		$result['resource'] = count($this->table->getGoldCards($player));
		// Calculate pigeons cards T542
		$result['pigeon'] = count($this->table->getPlayerPigeonCards($player, PIGEON_CARD_TWO_GOLDS)) * 2;
		
		// Calculate total
		$result['score'] = array_sum($result);
		
		return $result;
	}
	
}
