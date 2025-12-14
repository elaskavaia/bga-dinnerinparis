<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractImmediatePigeonCardStartController;
use \Bga\Games\DinnerInParis\Entity\PigeonCard;
use \Bga\Games\DinnerInParis\Entity\Player;

class PigeonDrawResourceStartController extends AbstractImmediatePigeonCardStartController {
	
	protected $action = 'pickResourceCard';
	
	public function prepareCard(Player $player, PigeonCard $pigeonCard): void {
		$player->addActionFlag(Player::FLAG_DRAW_RESOURCE_CARD);
		$player->addActionFlag(Player::FLAG_DRAW_RESOURCE_CARD);
		$player->addActionFlag(Player::FLAG_RESUME_PIGEON_CARD_DRAW_RESOURCE);
	}
	
}
