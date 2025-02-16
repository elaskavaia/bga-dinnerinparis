<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractImmediatePigeonCardEndController;
use AppGame\Entity\PigeonCard;
use AppGame\Entity\Player;

class PigeonAddTerraceEndController extends AbstractImmediatePigeonCardEndController {
	
	private $cancelled = false;
	
	protected function finalizeCard(Player $player, PigeonCard $pigeonCard): void {
		$this->cancelled = !$player->hasActionFlag(Player::FLAG_RESUME_PIGEON_CARD_ADD_TERRACE);
		$player->removeTurnInfo('placingTerraceForceRestaurant');
		$player->removeTurnInfo('placingTerraceFree');
		$player->removeActionFlag(Player::FLAG_RESUME_PIGEON_CARD_ADD_TERRACE);// Normally already done
	}
	
	protected function getNotificationMessage(): string {
		return $this->cancelled ? clienttranslate('${player_name} can not place a free terrace') : clienttranslate('${player_name} placed a free terrace');
	}
	
}
