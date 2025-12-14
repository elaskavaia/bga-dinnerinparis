<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractImmediatePigeonCardStartController;
use \Bga\Games\DinnerInParis\Entity\PigeonCard;
use \Bga\Games\DinnerInParis\Entity\Player;
//use \Bga\Games\DinnerInParis\Logger\BgaLogger;

class PigeonAddTerraceStartController extends AbstractImmediatePigeonCardStartController {
	
	protected $action = 'placeTerrace';
	
	public function prepareCard(Player $player, PigeonCard $pigeonCard): void {
		$restaurant = $this->app->getToken($player->getTurnInfo('placingTerraceLastRestaurant'));
		$terracePile = $this->table->getPlayerCategoryTerraceRiver($player, $restaurant->getCategory());
		if( $terracePile->isEmpty() ) {
			//BgaLogger::get()->log(sprintf('Can not use this pigeon card, Terrace #%d pile is empty, container=%d', $restaurant->getCategory(), $terracePile->getContainer()));
			$this->app->sendSystemMessage('${player_name} has no more terrace for this category, this pigeon card has no effect', [
				'player_name' => $player->getLabel(),
			]);
			$this->action = 'cancelPigeonCard';
			
			return;
		}
		
		$this->action = 'placeTerrace';
		// Only apply restaurant category is having an available terrace
		$player->setTurnInfo('placingTerraceForceRestaurant', $restaurant->getId());
		$player->setTurnInfo('placingTerraceFree', true);
		$player->addActionFlag(Player::FLAG_RESUME_PIGEON_CARD_ADD_TERRACE);
	}
	
}
