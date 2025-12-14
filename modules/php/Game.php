<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */
namespace Bga\Games\DinnerInParis;

require_once 'functions.php';
require_once 'constants.php';
require_once 'polyfill.php';

use \Bga\Games\DinnerInParis\Controller\GameSetupController;
use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Core\Controller\ArgumentBag;
use \Bga\Games\DinnerInParis\Core\Debug\Dump;
use \Bga\Games\DinnerInParis\Core\Exception\UserException;
use \Bga\Games\DinnerInParis\Entity\PigeonCard;
use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Entity\ResourceCard;
use \Bga\Games\DinnerInParis\Entity\Restaurant;
use \Bga\Games\DinnerInParis\Entity\Token;
use \Bga\Games\DinnerInParis\Game\TerraceBuildResolver;
use \Bga\Games\DinnerInParis\Logger\BgaLogger;
use \Bga\Games\DinnerInParis\Logger\SetupLogger;
use \Bga\Games\DinnerInParis\Service\BoardGameApp;
use \Bga\Games\DinnerInParis\Service\EntityService;
use \Bga\Games\DinnerInParis\Service\GeometryService;
use Bga\GameFramework\Table;
use Bga\GameFramework\VisibleSystemException;

/**
 * @method string getBgaEnvironment
 */
class Game extends Table {
	
	const GLOBAL_MAJORITY_CARD = 'majority_card';
	const GLOBAL_STATE_ENDING = 'state_ending';
	
	/** @var array */
	public $resources;
	
	/** @var array */
	public $resourceCards;
	
	/** @var array */
	public $restaurants;
	
	/** @var array */
	public $restaurantCategories;
	
	/** @var array */
	public $tiles;
	
	/** @var array */
	public $pigeonCards;
	
	/** @var array */
	public $objectiveCards;
	
	/** @var array */
	public $areas;
	
	/** @var array */
	public $majorities;
	
	/** @var array */
	public $majorityCards;
	
	/** @var AbstractController */
	public $controller;
	
	/** @var BoardGameApp */
	public $app;
	
	/** @var BgaLogger */
	public $logger;
	
	function __construct() {
		// Your global variables labels:
		//  Here, you can assign labels to global variables you are using for this game.
		//  You can use any number of global variables with IDs between 10 and 99.
		//  If your game has options (variants), you also have to associate here a label to
		//  the corresponding ID in gameoptions.inc.php.
		// Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
		parent::__construct();

		require 'material.inc.php';
		
		$this->initGameStateLabels([
			// Variable name => Variable ID
			// Generic game global variables
			self::GLOBAL_STATE_ENDING  => 10,
			// Game's global variables
			self::GLOBAL_MAJORITY_CARD => 20,
		]);
	}
	
	protected function initTable(): void {
		// During game setup, data are not yet available, so we wait to load it
		$this->trace('Initialize table');
		//		SetupLogger::instantiate($this);
		BgaLogger::instantiate($this);
		//		WebLogger::instantiate($this);
		//		ExceptionLogger::instantiate($this);
		EntityService::initialize($this);
		
		BoardGameApp::instantiate($this);
		$this->app = BoardGameApp::get();
		//$this->logger = BgaLogger::get();
	}
	
	/**
	 * @return AbstractController
	 */
	public function getController(): AbstractController {
		return $this->controller;
	}
	
	protected function instantiateController(string $class) {
		return new $class($this);
	}
	
	/**
	 * This method is called only once, when a new game is launched.
	 * In this method, you must set up the game according to the game rules, so that the game is ready to be played.
	 *
	 * @param $players
	 * @param array $options
	 */
	protected function setupNewGame($players, $options = []) {
		try {
			$this->controller = $this->instantiateController(GameSetupController::class);
			$this->controller->run($players, $options);
		} catch(\Throwable $exception ) {
			var_dump($exception);
			SetupLogger::get()->log($exception);
		}
	}
	
	// Chat Commands - Start
	
	public function thisIsTheEnd() {
		$this->app->setGameEnding();
		$this->app->notifyGameUpdate(['state' => 'ending'], 'This is the end');
	}
	
	public function updatePlayer($playerId = null): void {
		$playerId = $playerId ?: null;// BGA may give an empty string
		$player = $this->getPlayer($playerId);
		$this->app->sendSystemMessage(sprintf('updatePlayer(%d)', $player->getId()));
		$this->app->getTable()->updatePlayerScoreAndIncome($player);
		$entityService = EntityService::get();
		$entityService->update($player);
		// Notify users of player's update (score + income)
		$this->app->notifyPlayerUpdate($player);
	}
	
	public function stats($playerId = null, $final = true): void {
		$playerId = $playerId ?: null;// BGA may give an empty string
		$final = !!$final;
		$player = $this->getPlayer($playerId);
		$stats = $this->app->getTable()->calculatePlayerStats($player, $final);
		
		$this->app->sendSystemMessage(sprintf(
			'Player %s - Total score is %d and income %d. Restaurant score=%d, Terrace score=%d, Objective score=%d, Majority score=%d (%s)',
			$player->getEntityLabel(), $stats['totalScore'], $stats['income'], $stats['score']['restaurant'], $stats['score']['terrace'], $stats['score']['objective'],
			$stats['score']['majority'], json_encode($stats['majorityScores'])));
	}
	
	public function updateMajorities(): void {
		$this->app->updatePlayerMajorities();
	}
	
	public function addScore($amount = null, $playerId = null): void {
		$player = $this->getPlayer($playerId);
		$amount = $amount ?: 1;
		$this->app->sendSystemMessage(sprintf('addScore(%d, %d)', $amount, $player->getId()));
		$player->setScore($player->getScore() + $amount);
		$entityService = EntityService::get();
		$entityService->update($player);
		// Notify users of player's update (score + income)
		$this->app->notifyPlayerUpdate($player);
	}
	
	public function addIncome($amount = null, $playerId = null): void {
		$player = $this->getPlayer($playerId);
		$amount = $amount ?: 10;
		$this->app->sendSystemMessage(sprintf('addIncome(%d, %d)', $amount, $player->getId()));
		$player->addIncome($amount);
		$entityService = EntityService::get();
		$entityService->update($player);
		// Notify users of player's update (score + income)
		$this->app->notifyPlayerUpdate($player);
	}
	
	public function addBalance($amount = null, $playerId = null): void {
		$player = $this->getPlayer($playerId);
		$amount = $amount ?: 50;
		$this->app->sendSystemMessage(sprintf('addBalance(%d, %d)', $amount, $player->getId()));
		$player->setBalance($player->getBalance() + $amount);
		$entityService = EntityService::get();
		$entityService->update($player);
		// Notify users of player's update (balance)
		$this->app->notifyPlayerUpdate($player);
	}
	
	public function position($player = null): void {
		$player = $this->getPlayer($player);
		// The purpose is to test these methods
		$state = 'middle';
		if( $this->app->isFirstTurnPlayer($player) ) {
			$state = 'first';
		} elseif( $this->app->isLastTurnPlayer($player) ) {
			$state = 'last';
		}
		$this->app->sendSystemMessage(sprintf('position(%s) is %d (%s)', $player->getEntityLabel(), $player->getPosition(), $state));
	}
	
	public function pigeonCard($position = 0): void {
		$pigeonCardDraw = $this->app->getTable()->getPigeonCardDrawPile();
		/** @var PigeonCard $pigeonCard */
		$pigeonCard = $pigeonCardDraw->at((int) $position);
		if( $pigeonCard ) {
			$this->app->sendSystemMessage(sprintf('At #%d, draw pile contains %s with key=%s and immediate=%s', $position, $pigeonCard->getEntityLabel(), $pigeonCard->getKey(), Dump::bool($pigeonCard->isImmediate())));
		} else {
			$this->app->sendSystemMessage(sprintf('At #%d, draw pile does not contain any card', $position));
		}
	}
	
	public function setPigeonCard($variant): void {
		// Format input
		if( is_numeric($variant) ) {
			$variant = (int) $variant;
		} else {
			$key = $variant;
			$variant = null;
			foreach( $this->app->getPigeonCardMaterials() as $materialVariant => $material ) {
				if( $material['key'] === $key ) {
					$variant = $materialVariant;
					break;
				}
			}
			if( $variant === null ) {
				$this->app->sendSystemMessage(sprintf('Key "%s" not found', $key));
				
				return;
			}
		}
		// Search card for variant
		$pigeonCardDraw = $this->app->getTable()->getPigeonCardDrawPile();
		$matchingPigeonCard = null;
		foreach( $pigeonCardDraw->getTokenList() as $pigeonCard ) {
			if( $pigeonCard->getVariant() === $variant ) {
				$matchingPigeonCard = $pigeonCard;
				break;
			}
		}
		if( !$matchingPigeonCard ) {
			$this->app->sendSystemMessage(sprintf('No pigeon card found for variant "%d"', $variant));
			
			return;
		}
		// Move variant to first
		$pigeonCardDraw->putOnTop($matchingPigeonCard);
		$entityService = EntityService::get();
		$entityService->startBatching();
		$entityService->updateList($pigeonCardDraw->getTokens());
		$entityService->applyBatching();
		$this->app->sendSystemMessage(sprintf('Moved %s on top of pigeon card draw pile (position=%d)', $matchingPigeonCard->getEntityLabel(), $matchingPigeonCard->getPosition()));
	}
	
	public function terracing($playerId = null): void {
		$playerId = $playerId ?: null;// BGA may give an empty string
		$player = $this->getPlayer($playerId);
		
		$terraceCount = $player->getTurnInfo('placingTerraceCount') ?? 0;
		$adjTerraceRestaurants = $player->getTurnInfo(Player::FLAG_PIGEON_CARD_ADJACENT_TERRACE_RESTAURANTS) ?? [];
		$newAdjTerrPC = $player->hasActionFlag(Player::FLAG_PENDING_PIGEON_CARD_ADJACENT_TERRACE);
		$lastRestaurantId = $player->getTurnInfo('placingTerraceLastRestaurant');
		$lastRestaurant = $lastRestaurantId ? $this->app->getToken($lastRestaurantId) : null;
		$freeTerrace = $player->getTurnInfo('placingTerraceFree');
		$onlyRestaurantId = $player->getTurnInfo('placingTerraceForceRestaurant');
		/** @var Restaurant|null $onlyRestaurant */
		$onlyRestaurant = $onlyRestaurantId ? $this->app->getToken($onlyRestaurantId) : null;
		$canBuyMoreWithCard = false;
		
		$resolver = new TerraceBuildResolver($player, $this->app->getTable());
		$resolver->setAdjacentTerraceRestaurants($adjTerraceRestaurants);
		$resolver->setAllowAnyCover($newAdjTerrPC);
		$resolver->setOnlyRestaurant($onlyRestaurant);
		$resolver->setFreeTerrace($freeTerrace);
		$resolver->resolveAvailableLocations();
		$availableLocations = $resolver->getAvailableLocations();
		
		$availableRestaurants = [];
		foreach( $availableLocations as $restaurantId => $weDontCare ) {
			$restaurant = $this->app->getToken($restaurantId);
			$availableRestaurants[] = $restaurant->getEntityLabel();
		}
		
		$this->app->sendSystemMessage(sprintf('terracing(Player=%d) : Placed = %d; Active Restaurant = %s; Unlocked by gold card ? %s; Available restaurants = %s; ' .
			'AdjTerrace PC pending ? %s; AdjTerrace Restaurants = %s; Next is free ? %s; Restricted to restaurant = %s;',
			$player->getEntityLabel(), $terraceCount, $lastRestaurant ? $lastRestaurant->getEntityLabel() : 'NONE', Dump::bool($canBuyMoreWithCard),
			json_encode($availableRestaurants), Dump::bool($newAdjTerrPC), json_encode($adjTerraceRestaurants), Dump::bool($freeTerrace), $onlyRestaurant ? $onlyRestaurant->getEntityLabel() : 'NONE'));
	}
	
	
	/**
	 * @param string|int $variantOrKey Variant or key
	 * @param int $quantity
	 * @param int|null $playerId
	 * @return void
	 */
	public function giveResourceCard($variantOrKey, int $quantity = 3, ?int $playerId = null): void {
		$table = $this->app->getTable();
		$drawPile = $table->getResourceCardDrawPile();
		$player = $this->getPlayer($playerId);
		$this->app->sendSystemMessage(sprintf('giveResourceCard(%s, %d, %s)', $variantOrKey, $quantity, $player->getEntityLabel()));
		$key = $variant = null;
		if( ctype_digit($variantOrKey) ) {
			$variant = intval($variantOrKey);
		} else {
			$key = $variantOrKey;
		}
		$cards = [];
		foreach( $drawPile->getTokens() as $card ) {
			/** @var ResourceCard $card */
			// Card provide resource and is not already used
			if( $variant && $card->getVariant() === $variant ) {
				$cards[] = $card;
			}
			if( $key && $card->getKey() === $key ) {
				$cards[] = $card;
			}
			if( count($cards) >= $quantity ) {
				// Stop when reaching maximum
				break;
			}
		}
		
		$this->giveCardsFromResourceDrawPile($cards, $player);
	}
	
	/**
	 * @param int|null $quantity
	 * @return void
	 */
	public function discardResourceDrawPile($quantity = null): void {
		$table = $this->app->getTable();
		$drawPile = $table->getResourceCardDrawPile();
		$discardPile = $table->getResourceCardDiscardPile();
		$quantity = $quantity ? min($quantity, $drawPile->count()) : $drawPile->count() - 1;
		$this->app->sendSystemMessage(sprintf('discardResourceDrawPile(%d)', $quantity));
		$cards = [];
		for( $i = 0; $i < $quantity; $i++ ) {
			$cards[] = $drawPile->pickFirst();
		}
		$discardPile->addList($cards);
		$discardPile->calculateAllPositions();
		$drawPile->calculateAllPositions();
		$allChanges = array_merge($discardPile->getTokenList(), $drawPile->getTokenList());
		
		$entityService = EntityService::get();
		$entityService->startBatching();
		$entityService->updateList($allChanges);
		$entityService->applyBatching();
		// Notify users of cards' update
		$this->app->notifyTokenUpdate($allChanges, sprintf('Discarded %d cards from draw pile', count($cards)));
	}
	
	/**
	 * @param int $max Maximum number of cards the player will receive
	 * @param int $comboMin 2 or 3
	 * @param int|null $playerId
	 * @return void
	 */
	public function giveComboResourceCards(int $max, int $comboMin = 3, ?int $playerId = null): void {
		$table = $this->app->getTable();
		$drawPile = $table->getResourceCardDrawPile();
		$player = $this->getPlayer($playerId);
		$this->app->sendSystemMessage(sprintf('getComboResourceCards(%d, %d, %s)', $max, $comboMin, $player->getEntityLabel()));
		$cards = [];
		foreach( $drawPile->getTokens() as $card ) {
			/** @var ResourceCard $card */
			// Card provide resource and is not already used
			if( count($card->getGives()) >= $comboMin ) {
				$cards[] = $card;
			}
			if( count($cards) >= $max ) {
				// Stop when reaching maximum
				break;
			}
		}
		
		$this->giveCardsFromResourceDrawPile($cards, $player);
	}
	
	protected function getRestaurantMaterial(string $restaurant) {
		$materials = $this->app->getRestaurantMaterials();
		$restaurantMaterial = null;
		foreach( $materials as $variant => $material ) {
			if( $material['key'] === $restaurant ) {
				$restaurantMaterial = $material;
				$restaurantMaterial['variant'] = $variant;
				break;
			}
		}
		if( !$restaurantMaterial ) {
			throw new UserException(sprintf('Restaurant %s not found', $restaurant));
		}
		
		return $restaurantMaterial;
	}
	
	public function bringRestaurant(string $restaurantKey, ?int $playerId = null): void {
		$restaurantMaterial = $this->getRestaurantMaterial($restaurantKey);
		$player = $this->getPlayer($playerId);
		$this->app->sendSystemMessage(sprintf('bringRestaurant(%s, %d), require resources %s', $restaurantKey, $player->getId(),
			json_encode($restaurantMaterial['cost'])));
		$table = $this->app->getTable();
		
		// Find cards in draw pile
		$drawPile = $table->getResourceCardDrawPile();
		$cards = [];
		// For each cost
		foreach( $restaurantMaterial['cost'] as $requiredResource => $requiredQuantity ) {
			for( $resIndex = 0; $resIndex < $requiredQuantity; $resIndex++ ) {
				// Find the matching card
				$usedCard = null;
				foreach( $drawPile->getTokens() as $card ) {
					/** @var ResourceCard $card */
					// Card provide resource and is not already used
					if( in_array($requiredResource, $card->getGives()) && !isset($cards[$card->getId()]) ) {
						$usedCard = $card;
					}
				}
				if( !$usedCard ) {
					throw new UserException(sprintf('Not finding card in draw pile for resource "%s" (#%d)', $requiredResource, $resIndex));
				}
				$cards[$usedCard->getId()] = $usedCard;
			}
		}
		$this->giveCardsFromResourceDrawPile($cards, $player);
	}
	
	public function sampleGame($playerId = null): void {
		// Fix BGA bugs with first param (always a string)
		$playerId = $playerId ? (int) $playerId : null;
		$players = $playerId ? array_pad([], 4, $this->getPlayer($playerId)) : $this->app->getPlayers();
		$restaurants = ['gastronomique', 'friterie', 'pizzeria', 'grill'];
		shuffle($restaurants);
		shuffle($players);
		$playerCount = count($players);
		$only4Restaurants = $playerCount < 2;
		$maxRestaurant = $only4Restaurants ? 4 : 8;
		$lastRestaurantIndex = $maxRestaurant - 1;
		$this->app->sendSystemMessage("playerCount=$playerCount lastRestaurantIndex=$lastRestaurantIndex only4Restaurants=" . Dump::bool($only4Restaurants));
		$i = 0;
		$tries = 0;
		while( $i < $lastRestaurantIndex && $tries < 20 ) {// Stop
			foreach( $restaurants as $restaurant ) {
				$tries++;
				$playerIndex = $i % $playerCount;
				try {
					$this->buildRestaurant($restaurant, $players[$playerIndex], $only4Restaurants);
					//$this->logger->log(sprintf('Placed restaurant %s for player %d', $playerIndex, $playerIndex));
					$i++;
					if( $i >= $lastRestaurantIndex ) {
						// Stop
						break;
					}
				} catch( UserException $exception ) {
					// No more token or location
				}
			}
		}
		if( $i >= $lastRestaurantIndex ) {
			//$this->logger->log(sprintf('End build because of no more restaurant index (%d)', $i));
		} else {
			//$this->logger->log(sprintf('End build because of max tries exceeded (%d)', $tries));
		}
	}
	
	/**
	 * @param string $restaurantKey
	 * @param int|null|Player $player
	 * @param bool $only4 True for 4-restaurants placement, false for 8-restaurants placement
	 * @return void
	 */
	public function buildRestaurant(string $restaurantKey, $player = null, bool $only4 = true): void {
		try {
			$restaurantMaterial = $this->getRestaurantMaterial($restaurantKey);
			$player = $this->getPlayer($player);
			//			$this->app->sendSystemMessage(sprintf('buildRestaurant(%s, %d), require resources %s', $restaurantKey, $player->getId(),
			//				json_encode($restaurantMaterial['cost'])));
			$table = $this->app->getTable();
			
			// Find token
			//$this->logger->log(sprintf('Find token'));
			$restaurantBox = $table->getRestaurantBox();
			$variantRestaurants = $restaurantBox->getTokensGroupedByVariant();
			/** @var Restaurant $restaurant */
			$restaurant = $variantRestaurants[$restaurantMaterial['variant']][0] ?? null;
			if( !$restaurant ) {
				//$this->logger->log(sprintf('variantRestaurants = %s', json_encode($variantRestaurants)));
				throw new UserException(sprintf('No more token for restaurant "%s"', $restaurantKey));
			}
			//$this->logger->log(sprintf('Found token %s for material %s', $restaurant->getEntityLabel(), $restaurantMaterial['key']));
			
			// Find free location
			//$this->logger->log(sprintf('Find location, $only4 ? %s', Dump::bool($only4)));
			$geometryService = GeometryService::get();
			$availableLocations = $table->getBuildableRestaurantLocations($restaurant, true);
			$grid = $table->getGrid();
			$first = $grid->getFirstIndex();
			$last = $grid->getLastIndex();
			$locationPoint = null;
			$placements = $only4 ? [[8, $first, ORIENTATION_SOUTH], [$last, 8, ORIENTATION_WEST], [8, $last, ORIENTATION_NORTH], [$first, 8, ORIENTATION_EAST]] :
				[
					[5, $first, ORIENTATION_SOUTH], [$last, 5, ORIENTATION_WEST], [5, $last, ORIENTATION_NORTH], [$first, 5, ORIENTATION_EAST],
					[10, $first, ORIENTATION_SOUTH], [$last, 10, ORIENTATION_WEST], [10, $last, ORIENTATION_NORTH], [$first, 10, ORIENTATION_EAST],
				];
			foreach( $placements as $point ) {
				$pointIndex = $geometryService->getPointIndex($point);
				//$this->logger->log(sprintf('$pointIndex=%s', $pointIndex));
				if( isset($availableLocations[$pointIndex]) ) {
					$locationPoint = $point;
					break;
				}
			}
			if( !$locationPoint ) {
				//$this->logger->log(sprintf('Unable to find an available location for "%s", looked at %s', $restaurantKey, json_encode($placements)));
				//$this->logger->log(sprintf('Available locations are %s', json_encode($availableLocations)));
				throw new UserException(sprintf('No available location to build restaurant "%s"', $restaurantKey));
			}
			//$this->logger->log(sprintf('Found location %s', json_encode($locationPoint)));
			
			// Move restaurant on grid
			$restaurant->setOrientation($locationPoint[2]);
			$restaurant->setPlayer($player);
			$grid->set($restaurant, $locationPoint);
			$this->app->sendSystemMessage(sprintf('Build restaurant %s with player=%d', $restaurant->getEntityLabel(), $restaurant->getPlayerId()));
			//$this->logger->log(sprintf('Moved restaurant token'));
			
			$table->updatePlayerScoreAndIncome($player);
			//$this->logger->log(sprintf('Updated player %s', $player->getEntityLabel()));
			
			// Save
			$entityService = EntityService::get();
			$entityService->startBatching();
			$entityService->update($restaurant);
			$entityService->update($player);
			$entityService->applyBatching();
			
			// Notify users of all tokens move (restaurant + cards)
			$this->app->notifyTokenUpdate([$restaurant], clienttranslate('${player_name} built restaurant "${restaurant_name}"'), [
				'player_name'     => $player->getLabel(),
				'restaurant_name' => $restaurant->getLabel(),
			]);
			
			// Notify users of player's update (score + income)
			$this->app->notifyPlayerUpdate($player);
			//			//$this->logger->log(sprintf('buildRestaurant() - ENDED'));
			
			// Update and notify all user about majority changes
			$this->app->updatePlayerMajorities();
			
		} catch(\Exception $exception ) {
			BgaLogger::get()->log(sprintf('%s occurred with message "%s"', Dump::shortName($exception), $exception->getMessage()));
			throw $exception;
		}
	}
	
	// Chat Commands - End
	
	/**
	 * @param int|string|null $player
	 * @return Player
	 */
	public function getPlayer($player, string $default = 'me'): Player {
		if( $player instanceof Player ) {
			return $player;
		}
		if( !$player ) {
			// Null or empty
			$player = $default;
		}
		if( $player === 'active' ) {
			return $this->app->getActivePlayer();
		}
		if( $player === 'me' ) {
			return $this->app->getCurrentPlayer();
		}
		
		// Null value must throw an exception
		return $this->app->getPlayer($player);
	}
	
	public function giveCardsFromResourceDrawPile(array $cards, Player $player): void {
		$table = $this->app->getTable();
		$drawPile = $table->getResourceCardDrawPile();
		
		$playerHand = $table->getResourceCardPlayerHand($player);
		$playerHand->addList($cards);
		
		$entityService = EntityService::get();
		$entityService->startBatching();
		$entityService->updateList($drawPile->getTokens());
		$entityService->updateList($playerHand->getTokens());
		$entityService->applyBatching();
		// Notify users of player's update (score + income)
		$this->app->notifyTokenUpdate($cards, sprintf('Player received %d cards', count($cards)));
	}
	
	public function setSameResourceCardsOnDrawPile(): void {
		$table = $this->app->getTable();
		$tokens = $table->getResourceCardDrawPile()->getTokens();
		$tokens[0]->setVariant(5);
		$tokens[1]->setVariant(5);
		$tokens[2]->setVariant(5);
		$entityService = EntityService::get();
		$entityService->startBatching();
		$entityService->updateList($tokens);
		$entityService->applyBatching();
		$this->notifyAllPlayers('systemMessage', 'Convert three first cards to potatoes', []);
	}
	
	public function setAllActive(): void {
		$this->app->setAllPlayersActive();
	}
	
	protected function loadController(string $key): void {
		// Call normal controller - we need app data
		$class = sprintf('Bga\Games\DinnerInParis\Controller\%sController', ucfirst($key));
		$this->controller = $this->instantiateController($class);
	}
	
	public function getCurrentPlayerIdOrNull(): ?int {
		return $this->getCurrentPlayerId(true);
	}
	
	public function __call(string $controller, array $arguments = []) {
		// TODO Test : Some actions seem initialized and controller is never called
		// https://boardgamearena.com/bug?id=73395
		//$this->logger->log(sprintf('Magic call of action %s', $controller));
		$this->runController($controller, $arguments);
	}
	
	protected function argueController(string $controller): array {
		$argumentBag = new ArgumentBag($this->app);
		$this->callController($controller, 'generateArguments', [$argumentBag]);
		
		return $argumentBag->getArguments();
	}
	
	public function runController(string $controller, array $arguments = []) {
		$this->callController($controller, 'run', $arguments);
	}
	
	public function callController(string $controller, string $method, array $arguments = []) {
		if ($controller=='activeNextPlayer') $this->activeNextPlayer();
		else		try {
			$this->loadController($controller);
			//$this->logger->log(sprintf('Calling "%s" on controller "%s" with args %s and pid %s', $method, $controller, json_encode($arguments), getmypid()));
			call_user_func_array([$this->controller, $method], $arguments);
		} catch(\Exception $exception ) {
			//$this->logger->error($exception);
			throw $exception;
		}
	}
	
	/*
		getAllDatas:
		
		Gather all information about current game situation (visible by the current player).
		
		The method is called each time the game interface is displayed to a player, ie:
		_ when the game starts
		_ when a player refreshes the game page (F5)
	*/
	protected function getAllDatas(): array {
		$result = [];
		$currentPlayer = $this->app->getCurrentPlayer();
		$table = $this->app->getTable();
		
		// *** Players ***
		// BGA Framework completes this list with his required data
		$result['players'] = [];
		foreach( $this->app->getPlayers() as $player ) {
			$result['players'][$player->getId()] = $player->jsonSerialize();
		}
		$result['gridSize'] = 20;
		$result['gridModel'] = $table->getGridModel();
		
		// *** Materials ***
		$result['materials'] = [];
		foreach( ['resources', 'restaurants', 'restaurantCategories', 'majorityCards', 'resourceCards', 'pigeonCards', 'objectiveCards'] as $name ) {
			$result['materials'][$name] = $this->$name;
		}
		foreach( ['resourceCards', 'pigeonCards', 'objectiveCards'] as $name ) {
			$result['materials'][$name]['hidden'] = [
				'key'   => 'hidden',
				'label' => clienttranslate('Hidden Card'),
			];
		}
		
		// *** Tokens ***
		$tokens = $this->app->getTokens();
		$outputTokens = [];
		/** @var Token $token */
		foreach( $tokens as $token ) {
			$outputTokens[$token->getId()] = $this->app->formatTokenFor($token, $currentPlayer);
		}
		$result['tokens'] = $outputTokens;
		
		// *** Majorities ***
		
		$majorityCard = $table->getMajorityCard();
		$majorityCardMaterial = $majorityCard->getMaterial();
		$majorities = [];
		foreach( $majorityCardMaterial['achievements'] as $majority ) {
			$material = $this->app->getMajorityMaterial($majority);
			$majorities[$majority] = [
				'label' => $material['label'],
			];
		}
		$result['majorities'] = $majorities;
		
		// Game state
		$result['game'] = ['state' => $this->app->isGameEnding() ? 'ending' : 'running'];
		
		return $result;
	}
	
	/*
		getGameProgression:
		
		Compute and return the current game progression.
		The number returned must be an integer between 0 (=the game just started) and
		100 (= the game is finished or almost finished).
	
		This method is called each time we are in a game state with the "updateGameProgression" property set to true
		(see states.inc.php)
	*/
	function getGameProgression(): int {
		try {
			return $this->app->getProgression();
		} catch (\Throwable $e) {
			// some weird error happen during initialization
			return 0;
		}
	}
	
	public function argInitializationChooseObjectiveCard(): array {
		return $this->argueController('initializationChooseObjectiveCard');
	}
	
	public function argFinalizationSummary(): array {
		return $this->argueController('finalizationSummary');
	}
	
	public function argActionPickResourceCard(): array {
		return $this->argueController('pickResourceCard');
	}
	
	public function argActionBuildRestaurantChoose(): array {
		return $this->argueController('buildRestaurantChoose');
	}
	
	public function argActionBuildRestaurantPlace(): array {
		return $this->argueController('buildRestaurantPlace');
	}
	
	public function argActionPlaceTerrace(): array {
		return $this->argueController('placeTerrace');
	}
	
	public function argActionPlaceTerraceShowPigeonCard(): array {
		return $this->argueController('placeTerraceShowPigeonCard');
	}
	
	public function argPlayerActionChoose(): array {
		return $this->argueController('playerActionChoose');
	}
	
	public function argCompleteObjectiveChoose(): array {
		return $this->argueController('completeObjectiveChoose');
	}
	
	public function argCompleteObjectiveDraw(): array {
		return $this->argueController('completeObjectiveDraw');
	}
	
	public function argPigeonDrawResourceStart(): array {
		return $this->argueController('pigeonDrawResourceStart');
	}
	
	public function argPigeonAddTerraceStart(): array {
		return $this->argueController('pigeonAddTerraceStart');
	}
	
	public function argPigeonDrawObjectivePlace(): array {
		return $this->argueController('pigeonDrawObjectivePlace');
	}
	
	/**
	 * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
	 * You can do whatever you want in order to make sure the turn of this player ends appropriately
	 * (ex: pass).
	 *
	 * Important: your zombie code will be called when the player leaves the game. This action is triggered
	 * from the main site and propagated to the gameserver from a server, not from a browser.
	 * As a consequence, there is no current player associated to this action. In your zombieTurn function,
	 * you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message.
	 *
	 * @param $state
	 * @param $active_player
	 * @return void
	 */
	function zombieTurn($state, $active_player) {
		$statename = $state['name'];
		
		if( $state['type'] === "activeplayer" ) {
			switch( $statename ) {
				default:
					$this->gamestate->nextState("zombiePass");
					break;
			}
			
			return;
		}
		
		if( $state['type'] === "multipleactiveplayer" ) {
			// Make sure player is in a non blocking status for role turn
			$this->gamestate->setPlayerNonMultiactive($active_player, '');
			
			return;
		}
		
		throw new VisibleSystemException("Zombie mode not supported at this game state: " . $statename);
	}
	
	function upgradeTableDb($from_version) {
		/*
			upgradeTableDb:

			You don't have to care about this until your game has been published on BGA.
			Once your game is on BGA, this method is called everytime the system detects a game running with your old
			Database scheme.
			In this case, if you change your Database scheme, you just have to apply the needed changes in order to
			update the game database and allow the game to continue to run with your new version.

		*/
	}
	
}
