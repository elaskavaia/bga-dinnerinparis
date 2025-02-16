<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractImmediatePigeonCardStartController;
use AppGame\Entity\PigeonCard;
use AppGame\Entity\Player;

class PigeonDrawResourceStartController extends AbstractImmediatePigeonCardStartController {
	
	protected $action = 'pickResourceCard';
	
	public function prepareCard(Player $player, PigeonCard $pigeonCard): void {
		$player->addActionFlag(Player::FLAG_DRAW_RESOURCE_CARD);
		$player->addActionFlag(Player::FLAG_DRAW_RESOURCE_CARD);
		$player->addActionFlag(Player::FLAG_RESUME_PIGEON_CARD_DRAW_RESOURCE);
	}
	
}
