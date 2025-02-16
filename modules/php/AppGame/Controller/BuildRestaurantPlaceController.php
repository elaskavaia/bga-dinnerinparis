<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\ArgumentBag;
use AppGame\Core\Debug\Dump;
use AppGame\Core\Exception\InvalidInputException;
use AppGame\Entity\PigeonCard;
use AppGame\Logger\BgaLogger;

class BuildRestaurantPlaceController extends AbstractBuildRestaurantController {
	
	public function run(array $point, int $orientation) {
		$this->game->checkAction('place');
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('BuildRestaurantPlaceController(%s) for player %s (pid=%s)', json_encode($point), $player->getId(), getmypid()));
		
		// Load player turn info
		$build = (object) $player->getTurnInfo('buildRestaurant');// ['restaurant' => $tokenId, 'cards' => $cost]);
		
		// Check data are valid and load them
		$restaurant = $this->loadRestaurant($build->restaurant);
		// Load & check cards
		$cards = $this->loadRestaurantCards($restaurant, $build->cards);
		// All required cards are available
		
		$points = $this->table->getRestaurantPlacement($point, $restaurant->getSize(), $orientation);
		BgaLogger::get()->log(sprintf('Using points %s for origin %s', json_encode($points), Dump::point($point)));
		
		$grid = $this->table->getGrid();
		foreach( $points as $loopPoint ) {
			$cellToken = $grid->getTokenAt($loopPoint);
			if( $cellToken ) {
				BgaLogger::get()->log(sprintf('Restaurant can not be placed here, the cell %s already contains token "%s", we would place it on %s',
					Dump::point($loopPoint), $cellToken->getEntityLabel(), json_encode($points)));
				throw new InvalidInputException(sprintf('Restaurant can not be placed here, the cell %s already contains token "%s", we would place it on %s',
					Dump::point($loopPoint), $cellToken->getEntityLabel(), json_encode($points)));
				//				throw new InvalidInputException(sprintf('Restaurant can not be placed here, the cell %s already contains token "%s".',
				//					Dump::point($loopPoint), $cellToken->getLabel()));
			}
		}
		// All required cell to build restaurant are available
		
		// Move restaurant on grid
		$restaurant->setOrientation($orientation);
		$restaurant->setPlayer($player);
		$grid->set($restaurant, $point);
		//		$this->app->sendSystemMessage(sprintf('Build restaurant %s with player=%d', $restaurant->getEntityLabel(), $restaurant->getPlayerId()));
		
		// move cards from player's hand to discard
		$resourceCardDiscard = $this->table->getResourceCardDiscardPile();
		$pigeonCardDiscard = $this->table->getPigeonCardPlayerDiscard($player);
		$usingPigeonCard = false;
		foreach( $cards as $card ) {
			if( $card instanceof PigeonCard ) {
				// Pigeon card to player pigeon card discard
				$pigeonCardDiscard->putOnTop($card);
				$usingPigeonCard = true;
			} else {
				// Resource card to board resource card discard
				$resourceCardDiscard->putOnTop($card);
			}
		}
		
		$this->table->updatePlayerScoreAndIncome($player);
		
		// Save
		$movedTokens = $resourceCardDiscard->getTokens();
		if( $usingPigeonCard ) {
			$movedTokens = array_merge($movedTokens, $pigeonCardDiscard->getTokens());
		}
		$movedTokens[] = $restaurant;
		//		//$this->logger->log(sprintf('DiscardResourceCardController() - after putOnTop - discardPile cards (%d) are %s', $discardPile->count(), json_encode($discardPile->getIdList())));
		$this->entityService->startBatching();
		$this->entityService->updateList($movedTokens);
		$this->entityService->update($player);
		$this->entityService->applyBatching();
		
		// Notify users of all tokens move (restaurant + cards)
		$this->app->notifyTokenUpdate($movedTokens, clienttranslate('${player_name} placed restaurant "${restaurant_name}" by using ${card_count} cards'), [
			'player_name'     => $player->getLabel(),
			'restaurant_name' => $restaurant->getLabel(),
			'card_count'      => count($cards),
		]);
		
		// Notify users of player's update (score + income)
		$this->app->notifyPlayerUpdate($player);
		
		// Update majorities (new restaurants)
		$this->app->updatePlayerMajorities();
		
		// Next state
		$this->app->useStateAction('endAction');
	}
	
	public function generateArguments(ArgumentBag $arguments): void {
		$player = $this->app->getActivePlayer();
		$build = (object) $player->getTurnInfo('buildRestaurant');
		$data = [
			'build' => $build,
		];
		if( $this->app->isCurrentPlayerActive() ) {
			$restaurant = $this->loadRestaurant($build->restaurant);
			$data['availableLocations'] = $this->table->getBuildableRestaurantLocations($restaurant);
		}
		
		$arguments->setPlayerArgumentList($player, $data);
	}
	
}
