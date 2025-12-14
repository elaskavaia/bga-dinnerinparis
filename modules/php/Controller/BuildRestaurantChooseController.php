<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\ArgumentBag;

class BuildRestaurantChooseController extends AbstractBuildRestaurantController {
	
	/**
	 * @param int $tokenId
	 * @param int[] $cardIds
	 */
	public function run(int $tokenId, array $cardIds) {
		$this->game->checkAction('choose');
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('BuildRestaurantChooseController(%d, %s) for player %s (pid=%s)', $tokenId, json_encode($cardIds), $player->getId(), getmypid()));
		
		// Check data are valid and load them
		$restaurant = $this->loadRestaurant($tokenId);
		$this->loadRestaurantCards($restaurant, $cardIds);// Check cards are valid
		
		$player->setTurnInfo('buildRestaurant', ['restaurant' => $tokenId, 'cards' => $cardIds]);
		$this->entityService->update($player);
		
		// Next state
		$this->app->useStateAction('place');
	}
	
	public function generateArguments(ArgumentBag $arguments): void {
		$player = $this->game->app->getActivePlayer();
		$arguments->setPlayerArgument($player, 'availableRestaurants', $this->table->getPlayerBuildableRestaurants($player));
	}
	
}
