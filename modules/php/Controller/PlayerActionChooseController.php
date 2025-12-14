<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Core\Controller\ArgumentBag;
use \Bga\Games\DinnerInParis\Core\Exception\UserException;
use \Bga\Games\DinnerInParis\Entity\Player;

class PlayerActionChooseController extends AbstractController {
	
	private $allowEndGame = false;
	
	public function run(string $action) {
		$this->game->checkAction($action);
		$player = $this->app->getActivePlayer();
		$this->game->trace(sprintf('PlayerActionChooseController(%s) for player %s', $action, $player->getId()));
		
		// See GAME_STATE_ACTION_CHOOSE state, possibleactions property
		$allowedActions = [
			'actionPickResourceCard'  => null,
			'actionBuildRestaurant'   => 'allowBuildRestaurantAction',
			'actionPlaceTerraces'     => 'allowPlaceTerraceAction',
			'actionCompleteObjective' => 'allowCompleteObjectiveAction',
			'pigeonDrawObjective'     => 'allowDrawObjectivePigeon',
			'endGame'                 => 'allowEndGame',
		];
		if( !array_key_exists($action, $allowedActions) ) {
			throw new UserException(sprintf('Invalid action %s', $action));
		}
		$testAvailability = $allowedActions[$action];
		if( $testAvailability && !$this->$testAvailability($player) ) {
			throw new UserException(sprintf('Forbidden action %s', $action));
		}
		
		// Next state
		$this->app->useStateAction($action);
	}
	
	public function generateArguments(ArgumentBag $arguments): void {
		$player = $this->app->getActivePlayer();
		$arguments->setPublicArgumentList([
			'actionNumber' => $player->hasTurnFlag('action_1') ? 2 : 1,
		]);
		$arguments->setPlayerArgumentList($player, [
			'allowBuildRestaurantAction'     => $this->allowBuildRestaurantAction($player),
			'allowPlaceTerraceAction'        => $this->allowPlaceTerraceAction($player),
			'allowCompleteObjectiveAction'   => $this->allowCompleteObjectiveAction($player),
			'allowDrawObjectivePigeonAction' => $this->allowDrawObjectivePigeon($player),
			'allowEndGame'                   => $this->allowEndGame($player),
		]);
	}
	
	public function allowDrawObjectivePigeon(Player $player): bool {
		$pigeonCard = $this->table->getFirstPlayerPigeonCard($player, PIGEON_CARD_OBJECTIVE);
		//$this->logger->log(sprintf('allowDrawObjectivePigeon - got card ? %s', $pigeonCard ? $pigeonCard->getEntityLabel() : 'NOPE'));
		
		return !!$pigeonCard;
	}
	
	public function allowBuildRestaurantAction(Player $player): bool {
		return (bool) $this->table->getPlayerBuildableRestaurants($player);
	}
	
	public function allowCompleteObjectiveAction(Player $player): bool {
		return count($this->table->getCompletableObjectiveCards($player));
	}
	
	public function allowPlaceTerraceAction(Player $player): bool {
		// Once per turn
		if( $player->hasTurnFlag(Player::FLAG_TERRACE_PLACED) ) {
			return false;
		}
		$table = $this->app->getTable();
		$grid = $table->getGrid();
		$playerRestaurants = $grid->getTokenList(TOKEN_TYPE_RESTAURANT, $player);
		
		// Player has restaurants (he can always buy at least one terrace for a restaurant)
		return !!$playerRestaurants;
	}
	
	public function allowEndGame(Player $player): bool {
		return $this->allowEndGame && $this->app->isDev();
	}
	
}
