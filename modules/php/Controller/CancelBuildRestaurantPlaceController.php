<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;

class CancelBuildRestaurantPlaceController extends AbstractController {
	
	public function run() {
		$this->game->checkAction('cancel');
		$player = $this->app->getActivePlayer();
		// $this->game->trace(sprintf('CancelBuildRestaurantPlaceController() for player %s', $player->getId()));
		
		// Next state
		$this->app->useStateAction('cancel');
	}
	
}
