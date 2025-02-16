<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractPickResourceCardController;
use AppGame\Core\Exception\InvalidInputException;

class PickResourceCardCancelController extends AbstractPickResourceCardController {
	
	public function run() {
		$this->game->checkAction('cancel');
		$player = $this->app->getActivePlayer();
		// $this->game->trace(sprintf('CancelPickResourceCardActionController() for player %s', $player->getId()));
		
		// Validate here the cancel of PickResourceCardAction
		if( !$this->allowCancel($player) ) {
			throw new InvalidInputException(sprintf('Can not cancel action 0 and pigeon card resource drawing, player #%s', $player->getId()));
		}
		
		// Next state
		$this->app->useStateAction('cancel');
	}
	
}
