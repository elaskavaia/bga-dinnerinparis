<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game\MajorityResolver;

use AppGame\Entity\Player;
//use AppGame\Logger\BgaLogger;

class PigeonCardMajorityResolver extends AbstractMajorityResolver {
	
	protected function resolvePlayer(Player $player): array {
		//BgaLogger::get()->log(sprintf('%s::resolvePlayer(%s)', static::class, $player->getEntityLabel()));
		$result = [];
		
		// Calculate pigeon cards - T592
		// Hand
		$result['hand'] = $this->table->getPigeonCardPlayerHand($player)->count();
		// Discard Pile
		$result['discard'] = $this->table->getPigeonCardPlayerDiscard($player)->count();
		
		// Calculate total
		$result['score'] = array_sum($result);
		
		return $result;
	}
	
}
