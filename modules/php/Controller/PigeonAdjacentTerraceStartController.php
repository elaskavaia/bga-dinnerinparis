<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Entity\Player;

class PigeonAdjacentTerraceStartController extends AbstractController {
	
	protected $action = 'placeTerrace';
	
	public function run() {
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('PigeonAdjacentTerraceStartController() for player %s (pid=%s)', $player->getEntityLabel(), getmypid()));
		
		// Apply
		// Wait for next terrace to be played
		$player->addActionFlag(Player::FLAG_PENDING_PIGEON_CARD_ADJACENT_TERRACE);
		
		// Save
		$this->entityService->update($player);
		
		// Next state
		$this->app->useStateAction('continue');
	}
	
}
