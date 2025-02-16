<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Action;

use AppGame\Entity\Player;

trait ObjectiveCardDraw {
	
	use ControllerTrait;
	
	public function drawObjectiveCard(Player $player) {
		$drawPile = $this->table->getObjectiveCardDrawPile();
		$playerPendingPile = $this->table->getObjectiveCardPlayerPending($player);
		$newObjectiveCard = $drawPile->pickFirst();
		// Pending state - Should match CompleteObjectivePlaceController::getArguments()
		$playerPendingPile->add($newObjectiveCard);
		//			//$this->logger->log(sprintf('Player picked objective card %s, will he keep it in hand ?', $newObjectiveCard->getEntityLabel()));
		// Save Objective card
		$this->entityService->update($newObjectiveCard);
		$this->app->notifyTokenUpdate([$newObjectiveCard]);
	}
	
}
