<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractImmediatePigeonCardEndController;
use AppGame\Entity\PigeonCard;
use AppGame\Entity\Player;

class PigeonDrawResourceEndController extends AbstractImmediatePigeonCardEndController {
	
	protected function finalizeCard(Player $player, PigeonCard $pigeonCard): void {
		$player->removeActionFlag(Player::FLAG_RESUME_PIGEON_CARD_DRAW_RESOURCE);
	}
	
	protected function getNotificationMessage(): string {
		return clienttranslate('${player_name} drew 2 resource cards');
	}
	
}
