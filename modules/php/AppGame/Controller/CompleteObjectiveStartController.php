<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractController;

class CompleteObjectiveStartController extends AbstractController {
	
	public function run() {
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('CompleteObjectiveStartController() for player %s (pid=%s)', $player->getEntityLabel(), getmypid()));
		
		// Next state
		$this->app->useStateAction('choose');
	}
	
}
