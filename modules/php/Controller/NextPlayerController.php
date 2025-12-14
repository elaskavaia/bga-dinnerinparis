<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;

class NextPlayerController extends AbstractController {
	
	public function run() {
		// Active next player
		$player = $this->app->getActivePlayer();
		// $this->game->trace(sprintf('NextPlayerController(%s)', $player->getEntityLabel()));

		$this->app->nextPlayer();
		$this->app->useStateAction('nextPlayer');
		//		$this->boardGameApp->useStateAction('endGame');
	}
	
}
