<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

/**
 * dinnerinparis.action.php
 *
 * DinnerInParis main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/dinnerinparis/dinnerinparis/myAction.html", ...)
 *
 */

use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Entity\Restaurant;
use \Bga\Games\DinnerInParis\Game\GameTable;
use \Bga\Games\DinnerInParis\Game\TerraceBuildResolver;
use \Bga\Games\DinnerInParis\Logger\BgaLogger;
use \Bga\Games\DinnerInParis\Service\BoardGameApp;
use \Bga\Games\DinnerInParis\Service\GeometryService;


/**
 * @property DinnerInParis $game
 */
class action_dinnerinparis extends APP_GameAction {
	
	public function setResponseAsJson() {
		//		$this->setAjaxMode();
		header('Content-Type: application/json; charset=utf-8');
	}
	
	protected function requireDevEnvironment() {
		if( !$this->getApp()->isDev() ) {
			throw new RuntimeException('Forbidden Operation');
		}
	}
	
	public function json($data = null) {
		die(json_encode($data));
	}
	
	// Constructor: please do not modify
	public function __default() {
		if( self::isArg('notifwindow') ) {
			$this->view = "common_notifwindow";
			$this->viewArgs['table'] = self::getArg("table", AT_posint, true);
		} else {
			$this->view = "dinnerinparis_dinnerinparis";
			self::trace("Complete reinitialization of board game");
		}
	}
	
	// App action entry points
	// In controller, use "/dinnerinparis/dinnerinparis/{method}.html", this.app.realizeAction() do it for you
	
	public function chooseObjectiveCard() {
		self::setAjaxMode();
		
		$card = $this->getApp()->getToken(self::getArg("cardId", AT_posint, true));
		$this->game->initializationChooseObjectiveCard($card);
		
		self::ajaxResponse();
	}
	
	public function playerActionChoose() {
		self::setAjaxMode();
		
		$action = self::getArg("nextAction", AT_alpha_strict, true);
		$this->game->playerActionChoose($action);
		
		self::ajaxResponse();
	}
	
	public function finalizeSummary() {
		self::setAjaxMode();
		
		$this->game->finalizationSummary();
		
		self::ajaxResponse();
	}
	
	public function pickResourceCard() {
		self::setAjaxMode();
		
		$cardId = (int) self::getArg("cardId", AT_posint, true);
		$this->game->pickResourceCard($cardId);
		
		self::ajaxResponse();
	}
	
	public function cancelPickResourceCardAction() {
		self::setAjaxMode();
		
		$this->game->pickResourceCardCancel();
		
		self::ajaxResponse();
	}
	
	public function discardResourceCard() {
		self::setAjaxMode();
		
		$cardId = (int) self::getArg("cardId", AT_posint, true);
		$this->game->discardResourceCard($cardId);
		
		self::ajaxResponse();
	}
	
	public function cancelBuildRestaurantChoose() {
		self::setAjaxMode();
		
		$this->game->cancelBuildRestaurantChoose();
		
		self::ajaxResponse();
	}
	
	public function chooseRestaurantToBuild() {
		self::setAjaxMode();
		
		$tokenId = (int) self::getArg("tokenId", AT_posint, true);
		$userCards = self::getArg("cards", AT_json, true);// Resource card list
		$this->game->buildRestaurantChoose($tokenId, $userCards);
		
		self::ajaxResponse();
	}
	
	public function placeRestaurantToBuild() {
		self::setAjaxMode();
		
		$x = (int) self::getArg("x", AT_posint, true);
		$y = (int) self::getArg("y", AT_posint, true);
		BgaLogger::get()->log(sprintf('placeRestaurantToBuild (%d, %d) (type=%s)', $x, $y, gettype($x)));
		$orientation = (int) self::getArg("orientation", AT_posint, true);
		$this->game->buildRestaurantPlace([$x, $y], $orientation);
		
		self::ajaxResponse();
	}
	
	public function placeTerrace() {
		self::setAjaxMode();
		
		$x = (int) self::getArg("x", AT_posint, true);
		$y = (int) self::getArg("y", AT_posint, true);
		$restaurant = $this->getApp()->getToken(self::getArg("restaurant", AT_posint, true));
		$this->game->placeTerrace($restaurant, [$x, $y]);
		
		self::ajaxResponse();
	}
	
	public function useGoldCard() {
		self::setAjaxMode();
		
		$card = $this->getApp()->getToken(self::getArg("card", AT_posint, true));
		$this->game->placeTerraceUseGoldCard($card);
		
		self::ajaxResponse();
	}
	
	public function useAdjacentTerracePigeonCard() {
		self::setAjaxMode();
		
		$this->game->callController('placeTerrace', 'useAdjacentTerracePigeonCard');
		
		self::ajaxResponse();
	}
	
	public function cancelPlaceTerrace() {
		self::setAjaxMode();
		
		$this->game->placeTerraceCancel();
		
		self::ajaxResponse();
	}
	
	public function confirmPlaceTerrace() {
		self::setAjaxMode();
		
		$this->game->placeTerraceConfirm();
		
		self::ajaxResponse();
	}
	
	public function confirmShowPigeonCard() {
		self::setAjaxMode();
		
		// Manual call (new standard ?)
		$this->game->runController('PlaceTerraceShowPigeonCard');
		
		self::ajaxResponse();
	}
	
	public function chooseObjectiveToComplete() {
		self::setAjaxMode();
		
		$card = $this->getApp()->getToken(self::getArg('card', AT_posint, true));
		$usePigeonCard = (bool) self::getArg('usePigeonCard', AT_bool, true);
		$this->game->completeObjectiveChoose($card, $usePigeonCard);
		
		self::ajaxResponse();
	}
	
	public function cancelCompleteObjective() {
		self::setAjaxMode();
		
		$this->game->completeObjectiveCancel();
		
		self::ajaxResponse();
	}
	
	public function placeObjectiveToComplete() {
		self::setAjaxMode();
		
		$keep = (bool) self::getArg('keep', AT_bool, true);
		$usePigeonCard = (bool) self::getArg('usePigeonCard', AT_bool, true);
		$this->game->completeObjectiveDraw($keep, $usePigeonCard);
		
		self::ajaxResponse();
	}
	
	public function continuePigeonDrawResource() {
		self::setAjaxMode();
		
		$this->game->pigeonDrawResourceStart();
		
		self::ajaxResponse();
	}
	
	public function continuePigeonAddTerrace() {
		self::setAjaxMode();
		
		$this->game->pigeonAddTerraceStart();
		
		self::ajaxResponse();
	}
	
	public function placePigeonDrawObjective() {
		self::setAjaxMode();
		
		$keep = (bool) self::getArg('keep', AT_bool, true);
		$this->game->runController('PigeonDrawObjectivePlace', [$keep]);
		
		self::ajaxResponse();
	}
	
	// Test Only
	public function listMyHand() {
		$this->requireDevEnvironment();
		self::setResponseAsJson();
		
		$player = $this->getApp()->getCurrentPlayer();
		$resourceCardHand = $this->getTable()->getResourceCardPlayerHand($player);
		
		$this->json([
			'resourceCards' => $resourceCardHand->getTokens(),
		]);
	}
	
	// Test Only
	public function listBuildableRestaurants() {
		$this->requireDevEnvironment();
		self::setResponseAsJson();
		
		$data = $this->getTable()->getPlayerBuildableRestaurants($this->getApp()->getActivePlayer());
		
		$this->json($data);
	}
	
	// Test Only
	public function listBuildableRestaurantLocations() {
		$this->requireDevEnvironment();
		self::setResponseAsJson();
		
		$activePlayer = $this->getApp()->getActivePlayer();
		$build = (object) $activePlayer->getTurnInfo('buildRestaurant');
		/** @var Restaurant $restaurant */
		$restaurant = $build && is_array($build) ? $this->getApp()->getToken($build->restaurant) : null;
		
		$data = $this->getTable()->getBuildableRestaurantLocations($restaurant);
		
		$this->json($data);
	}
	
	// Test Only
	public function listBuildableTerraceLocations() {
		$this->requireDevEnvironment();
		self::setResponseAsJson();
		
		$player = $this->getApp()->getActivePlayer();
		$adjTerraceRestaurants = $player->getTurnInfo(Player::FLAG_PIGEON_CARD_ADJACENT_TERRACE_RESTAURANTS) ?? [];
		$newAdjTerrPC = $player->hasActionFlag(Player::FLAG_PENDING_PIGEON_CARD_ADJACENT_TERRACE);
		
		$resolver = new TerraceBuildResolver($player, $this->getTable());
		$resolver->setAllowAnyCover($newAdjTerrPC);
		$resolver->setAdjacentTerraceRestaurants($adjTerraceRestaurants);
		$resolver->resolveAvailableLocations();
		
		$this->json([
			'locations'              => $resolver->getAvailableLocations(),
			'canBuyMoreWithCard'     => $resolver->hasMoreWithGold(),
			'unavailableRestaurants' => $resolver->getUnavailableRestaurants(),
		]);
	}
	
	// Test Only
	public function listCompletableObjectiveCards() {
		$this->requireDevEnvironment();
		self::setResponseAsJson();
		
		$activePlayer = $this->getApp()->getActivePlayer();
		
		$cards = $this->getTable()->getCompletableObjectiveCards($activePlayer);
		
		$this->json([
			'cards' => $cards,
		]);
	}
	
	// Test Only
	
	/**
	 * Test shape rotation
	 *
	 * @return void
	 */
	public function testShapeRotate() {
		$this->requireDevEnvironment();
		self::setResponseAsJson();
		
		$variant = self::getArg("variant", AT_posint, true);
		$materials = $this->getApp()->getObjectiveCardMaterials();
		if( $variant < 13 || !isset($materials[$variant]) ) {
			throw new RuntimeException(sprintf('Invalid variant %d', $variant));
		}
		$material = $materials[$variant];
		$geometryService = GeometryService::get();
		$pattern = $geometryService->formatPattern($material['as']);
		
		$activePlayer = $this->getApp()->getActivePlayer();
		$resolver = $this->getTable()->getObjectiveCardResolver($material, $activePlayer);
		$resolver->resolve();
		$solution = null;
		if( $resolver->isCompleted() ) {
			$solution = $resolver->getSolution();
		}
		
		$this->json([
			'variant'  => $variant,
			'material' => $material,
			'pattern'  => $pattern,
			'solution' => $solution,
		]);
	}
	
	// Test Only
	public function listMajorities() {
		$this->requireDevEnvironment();
		self::setResponseAsJson();
		
		$majorities = $this->getTable()->resolveMajorities();
		
		$this->json([
			'majorities' => $majorities,
		]);
	}
	
	// Test Only
	public function getProgressionStats() {
		$this->requireDevEnvironment();
		self::setResponseAsJson();
		
		$start = new DateTime();
		$stats = $this->getApp()->getProgressionStats();
		$end = new DateTime();
		
		$this->json([
			'stats'       => $stats,
			'computation' => $start->diff($end)->format('%H:%I:%S.%F'),
		]);
	}
	
	// Test Only
	public function getEndingStats() {
		$this->requireDevEnvironment();
		self::setResponseAsJson();
		
		$start = new DateTime();
		$stats = $this->getApp()->getEndingStats();
		$end = new DateTime();
		
		$this->json([
			'stats'       => $stats,
			'computation' => $start->diff($end)->format('%H:%I:%S.%F'),
		]);
	}
	
	public function getApp(): BoardGameApp {
		return $this->game->app;
	}
	
	public function getTable(): GameTable {
		return $this->game->app->getTable();
	}
	
}
  

