<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game;

use \Bga\Games\DinnerInParis\Core\Debug\Dump;
use \Bga\Games\DinnerInParis\Entity\PigeonCard;
use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Entity\ResourceCard;
use \Bga\Games\DinnerInParis\Entity\Restaurant;
use \Bga\Games\DinnerInParis\Entity\Terrace;
use \Bga\Games\DinnerInParis\Entity\Token;
use \Bga\Games\DinnerInParis\Game\MajorityResolver\AbstractMajorityResolver;
use \Bga\Games\DinnerInParis\Game\MajorityResolver\AreaTerraceMajorityResolver;
use \Bga\Games\DinnerInParis\Game\MajorityResolver\DecorTerraceMajorityResolver;
use \Bga\Games\DinnerInParis\Game\MajorityResolver\MoneyMajorityResolver;
use \Bga\Games\DinnerInParis\Game\MajorityResolver\PigeonCardMajorityResolver;
use \Bga\Games\DinnerInParis\Game\MajorityResolver\RestaurantMajorityResolver;
use \Bga\Games\DinnerInParis\Game\ObjectiveResolver\AbstractObjectiveResolver;
use \Bga\Games\DinnerInParis\Game\ObjectiveResolver\AreaMinimalObjectiveResolver;
use \Bga\Games\DinnerInParis\Game\ObjectiveResolver\QuarterMinimalObjectiveResolver;
use \Bga\Games\DinnerInParis\Game\ObjectiveResolver\TerraceAroundDecorObjectiveResolver;
use \Bga\Games\DinnerInParis\Game\ObjectiveResolver\TerraceCategoryEmptyObjectiveResolver;
use \Bga\Games\DinnerInParis\Game\ObjectiveResolver\TerraceCategoryMinimalObjectiveResolver;
use \Bga\Games\DinnerInParis\Game\ObjectiveResolver\TerraceShapePatternObjectiveResolver;
use \Bga\Games\DinnerInParis\Game\ObjectiveResolver\TerraceTotalObjectiveResolver;
//use \Bga\Games\DinnerInParis\Logger\BgaLogger;
use \Bga\Games\DinnerInParis\Service\BoardGameApp;
use \Bga\Games\DinnerInParis\Service\GeometryService;
use \Bga\Games\DinnerInParis\Service\TokenService;
use DateTime;
use RuntimeException;


class GameTable {
	
	/** @var BoardGameApp */
	private $app;
	
	/** @var array All sorted game tokens */
	private $typeTokens = null;
	
	/** @var TokenHeap */
	private $restaurantBox = null;
	
	/** @var array All game tokens */
	private $playerTables = [];
	
	/** @var CardPile */
	private $resourceCardDrawPile = null;
	
	/** @var CardPile */
	private $resourceCardDiscardPile = null;
	
	/** @var TokenRiver */
	private $resourceCardRiver = null;
	
	/** @var CardPile */
	private $objectiveCardDrawPile = null;
	
	/** @var CardPile */
	private $pigeonCardDrawPile = null;
	
	/** @var TokenRiver */
	private $objectiveCardRiver = null;
	
	/** @var TokenGrid */
	private $grid = null;
	
	/** @var GeometryService */
	private $geometryService;
	
	/** @var int */
	private $gridModel;
	
	/**
	 * GameTable constructor
	 *
	 * @param BoardGameApp $boardGameApp
	 */
	public function __construct(BoardGameApp $boardGameApp) {
		$this->app = $boardGameApp;
		$this->geometryService = new GeometryService();
	}
	
	public function getRestaurantPlacement(array $point, int $size, int $orientation): array {
		// Calculate restaurant points
		$restaurantPattern = $this->geometryService->getPatternFromSize($size);
		// Restaurant coordinate is not the origin point for SOUTH and EAST
		if( $orientation === ORIENTATION_SOUTH ) {
			$orientation = ORIENTATION_NORTH;
		} elseif( $orientation === ORIENTATION_WEST ) {
			$orientation = ORIENTATION_EAST;
		}
		
		return $this->geometryService->getOrientedPatternPoints($point, $restaurantPattern, $orientation);
	}
	
	public function canPlaceMoreRestaurant(): bool {
		$table = $this;
		// Restaurant tokens
		$restaurantBox = $table->getRestaurantBox();
		$variantRestaurants = $restaurantBox->getTokensGroupedByVariant();
		
		foreach( $this->app->getRestaurantMaterials() as $variant => $restaurantMaterial ) {
			// Get available token for this restaurant
			$restaurant = $variantRestaurants[$variant][0] ?? null;
			if( !$restaurant ) {
				// Stop if no token, restaurant is not available
				continue;
			}
			$restaurantLocations = $this->getBuildableRestaurantLocations($restaurant);
			// If restaurant can be placed, so any restaurant could be
			// If first variants can not be, so no one could be
			return !!$restaurantLocations;
		}
		
		return false;
	}
	
	public function getAreaTerraces(Player $player): array {
		$areas = [
			AREA_NORTH_STREET => 0,
			AREA_EAST_STREET  => 0,
			AREA_SOUTH_STREET => 0,
			AREA_WEST_STREET  => 0,
		];
		$grid = $this->getGrid();
		$terraces = $this->getPlayerTerraces($player);
		foreach( $terraces as $terrace ) {
			$areas[$grid->isNorth($terrace) ? AREA_NORTH_STREET : AREA_SOUTH_STREET]++;
			$areas[$grid->isWest($terrace) ? AREA_WEST_STREET : AREA_EAST_STREET]++;
		}
		
		return $areas;
	}
	
	public function calculatePlayerStats(Player $player, bool $finalScore = false): array {
		$table = $this;
		$app = $this->app;
		$grid = $this->getGrid();
		$stats = [
			'income'         => 1,
			'score'          => [
				'restaurant' => 0,
				'terrace'    => 0,
				'objective'  => 0,
				'majority'   => 0,
			],
			'majorityScores' => [],
		];
		
		// Restaurants - Loop on player restaurants on grid
		foreach( $grid->getTokenList(TOKEN_TYPE_RESTAURANT, $player) as $token ) {
			// Restaurant of this player
			$material = $token->getMaterial();
			$stats['score']['restaurant'] += $material['score'] ?? 0;
			$stats['income'] += $material['income'] ?? 0;
		}
		
		// Terraces - Loop on player terrace category rivers
		/** @var TerraceRiver $terraceRiver */
		$categories = $app->getRestaurantCategoryMaterials();
		foreach( $categories as $category => $categoryOptions ) {
			$terraceRiver = $table->getPlayerCategoryTerraceRiver($player, $category);
			//			BgaLogger::get()->log(sprintf('Terrace river "%s" gives %d score and %d income',
			//				$terraceRiver->getName(), $terraceRiver->getScore(), $terraceRiver->getIncome()));
			$stats['score']['terrace'] += $terraceRiver->getScore();
			$stats['income'] += $terraceRiver->getIncome();
		}
		
		// Objective Cards - Loop player discard pile
		// Add card in discard pile (completed)
		$playerObjectiveDiscardPile = $table->getObjectiveCardPlayerDiscard($player);
		foreach( $playerObjectiveDiscardPile->getTokenList() as $card ) {
			$stats['score']['objective'] += $card->getMaterial()['score'] ?? 0;
		}
		if( $finalScore ) {
			// Remove card left in hand (non-completed)
			$playerObjectiveDiscardPile = $table->getObjectiveCardPlayerHand($player);
			foreach( $playerObjectiveDiscardPile->getTokenList() as $card ) {
				$stats['score']['objective'] -= $card->getMaterial()['score'] ?? 0;
			}
		}
		
		// Majorities - Loop player discard pile
		if( $finalScore ) {
			$playerMajorities = $player->getMajority();
			foreach( $playerMajorities as $majority => $majorityResult ) {
				if( !$majorityResult['valid'] ) {
					continue;
				}
				$score = $app->getMajorityPositionScore($majorityResult['position']);
				$stats['majorityScores'][] = [$majority, $majorityResult['position'], $score];
				$stats['score']['majority'] += $score;
			}
		}
		
		$stats['totalScore'] = $stats['score']['restaurant'] + $stats['score']['terrace'] + $stats['score']['objective'] + $stats['score']['majority'];
		
		return $stats;
	}
	
	/**
	 * @param Player $player
	 * @param bool $finalScore
	 */
	public function updatePlayerScoreAndIncome(Player $player, bool $finalScore = false): void {
		$app = $this->app;
		//BgaLogger::get()->log(sprintf('updatePlayerScoreAndIncome(%s, %s)', $player, Dump::bool($finalScore)));
		
		$stats = $this->calculatePlayerStats($player, $finalScore);
		
		//BgaLogger::get()->log(sprintf(
		//	'Player %s - Calculated new score %d and income %d. Restaurant score=%d, Terrace score=%d, Objective score=%d, Majority score=%d (%s)',
		//	$player->getEntityLabel(), $stats['totalScore'], $stats['income'], $stats['score']['restaurant'], $stats['score']['terrace'], $stats['score']['objective'],
		//	$stats['score']['majority'], json_encode($stats['majorityScores'])));
		$app->setStat('score_restaurant', $stats['score']['restaurant'], $player);
		$app->setStat('score_terrace', $stats['score']['terrace'], $player);
		$app->setStat('score_objective', $stats['score']['objective'], $player);
		$app->setStat('score_majority', $stats['score']['majority'], $player);
		
		foreach( $stats['majorityScores'] as $index => [$majority, $position, $score] ) {
			$app->setStat('score_majority_' . ($index + 1), $score, $player);
		}
		
		$player->setScore($stats['totalScore']);
		// Income is calculated including all incomes, even those usable the next turn, so we exclude it
		$player->setIncome($stats['income'] - $player->getPendingIncome());
	}
	
	/**
	 * @return Player[] Updated players
	 */
	public function updatePlayerMajorities(): array {
		$majorities = $this->resolveMajorities();
		$players = $this->app->getPlayers();
		$changedPlayers = [];// Players that changed position
		foreach( $players as $player ) {
			$previousResults = $player->getMajority();
			$positionChanges = 0;
			$playerResults = [];
			foreach( $majorities as $majority => $results ) {
				$playerResults[$majority] = $results[$player->getId()];
				//				BgaLogger::get()->log(sprintf('Majority "%s" - new "%s"', $majority, json_encode($playerResults[$majority])));
				$oldPosition = ($previousResults[$majority]['valid'] ?? false) ? ($previousResults[$majority]['position'] ?? null) : null;
				$newPosition = $playerResults[$majority]['valid'] ? $playerResults[$majority]['position'] : null;
				//				BgaLogger::get()->log(sprintf('Majority "%s" - Compare old position "%s" to new one "%s"', $majority, $oldPosition ?? 'NONE', $newPosition ?? 'NONE'));
				if( $oldPosition !== $newPosition ) {
					//					BgaLogger::get()->log(sprintf('Majority "%s" - POSITION CHANGED !', $majority));
					$positionChanges++;
				}
			}
			$player->setMajority($playerResults);
			$player->setMajorityUpdateDate(new DateTime());
			if( $positionChanges ) {
				$changedPlayers[] = $player;
			}
		}
		
		return $changedPlayers;
	}
	
	public function getBuildableRestaurantLocations(?Restaurant $restaurant, $withKeys = false): array {
		// 4p : 1 -> 18  //  3p : 2 -> 17  //  2p : 3 -> 16
		$table = $this;
		$availableLocations = [];
		// List all location cells
		$gridModel = $table->getGridModel();
		$start = 5 - $gridModel;
		$end = 14 + $gridModel;
		$first = $start - 1;
		$last = $end + 1;
		for( $i = $start; $i <= $end; $i++ ) {
			$availableLocations[$i . '-' . $first] = [$i, $first, 0];// Top - 0 horizontal
			$availableLocations[$i . '-' . $last] = [$i, $last, 0];// Bottom - 0 horizontal
			$availableLocations[$first . '-' . $i] = [$first, $i, 1];// Left - 1 vertical
			$availableLocations[$last . '-' . $i] = [$last, $i, 1];// Right - 1 vertical
		}
		// Exclude all current restaurant cells
		$grid = $table->getGrid();
		//		$restaurants = $table->extractTokens(TOKEN_TYPE_RESTAURANT, TOKEN_CONTAINER_BOARD_GRID);
		foreach( $availableLocations as $key => $loopPoint ) {
			$cellToken = $grid->getTokenAt($loopPoint);
			if( $cellToken ) {
				unset($availableLocations[$key]);
			}
		}
		
		if( $restaurant ) {
			$availableLocations = $this->geometryService->getPointsMatchingPattern($availableLocations,
				$this->geometryService->getPatternFromSize($restaurant->getSize()), true, true);
		}
		
		ksort($availableLocations);
		
		return $withKeys ? $availableLocations : array_values($availableLocations);
	}
	
	public function getGoldCards(Player $player): array {
		$playerHand = $this->getResourceCardPlayerHand($player);
		$handCards = $playerHand->getTokenList();
		$goldCards = [];
		/** @var ResourceCard $card */
		foreach( $handCards as $card ) {
			if( in_array(RESOURCE_GOLD, $card->getGives()) ) {
				$goldCards[] = $card->getId();
			}
		}
		
		return $goldCards;
	}
	
	public function getConsumableGold(Player $player): int {
		$amount = 0;
		// Gold resource cards
		$goldCards = $this->getGoldCards($player);
		$amount += count($goldCards);// Gold card can only bring 1 coin
		$goldPigeonCards = $this->getPlayerPigeonCards($player, PIGEON_CARD_TWO_GOLDS);
		$amount += count($goldPigeonCards) * 2;// TwoGolds Pigeon cards can only bring 2 coins
		
		return $amount;
	}
	
	/**
	 * @param Player $player
	 * @return array List of completable cards, not a list of cards
	 */
	public function getCompletableObjectiveCards(Player $player): array {
		$completableCards = [];
		// Check hand objectives
		$cards = $this->getObjectiveCardPlayerHand($player)->getTokenList();
		foreach( $cards as $card ) {
			if( $this->canCompleteObjective($player, $card, $solution) ) {
				$completableCards[] = [$card->getId(), 'player_hand', $solution];
			}
		}
		// Check river objectives
		$cards = $this->getObjectiveCardRiver()->getTokenList();
		foreach( $cards as $card ) {
			if( $this->canCompleteObjective($player, $card, $solution) ) {
				$completableCards[] = [$card->getId(), 'board_river', $solution];
			}
		}
		
		// Check river objectives
		return $completableCards;
	}
	
	/**
	 * @param array|Token $card
	 * @param Player $player
	 * @return AbstractObjectiveResolver
	 */
	public function getObjectiveCardResolver($card, Player $player): AbstractObjectiveResolver {
		$material = is_array($card) ? $card : $card->getMaterial();
		//		BgaLogger::get()->log(sprintf('getObjectiveCardResolver(%s)', $material ? sprintf('%s (%s)', $material['label'], $material['key']) : 'Unknown material'));
		//		$material = $card->getMaterial();
		//		BgaLogger::get()->log(sprintf('getObjectiveCardResolver() - material about "%s"', $material['about']));
		switch( $material['about'] ) {
			case OBJECTIVE_ABOUT_TERRACE_DECOR:
				return new TerraceAroundDecorObjectiveResolver($this, $player, $material['around_each'], $material['terraces']);
			case OBJECTIVE_ABOUT_TERRACE_SHAPE:
				return new TerraceShapePatternObjectiveResolver($this, $player, $material['as']);
			case OBJECTIVE_ABOUT_TERRACE_TOTAL:
				return new TerraceTotalObjectiveResolver($this, $player, $material['terraces']);
			case OBJECTIVE_ABOUT_QUARTER_MINIMALS:
				return new QuarterMinimalObjectiveResolver($this, $player, $material['terraces']);
			case OBJECTIVE_ABOUT_TERRACE_CATEGORY:
				return new TerraceCategoryMinimalObjectiveResolver($this, $player, $material['terraces']);
			case OBJECTIVE_ABOUT_CATEGORY_EMPTY:
				return new TerraceCategoryEmptyObjectiveResolver($this, $player);
			case OBJECTIVE_ABOUT_AREA_MINIMALS:
				return new AreaMinimalObjectiveResolver($this, $player, $material['terraces'], $material['in']);
			default:
				throw new RuntimeException(sprintf('Unknown objective "%s" of card %s', $material['about'], $card->getEntityLabel()));
		}
	}
	
	public function canCompleteObjective(Player $player, Token $card, &$solution = null): bool {
		//BgaLogger::get()->log(sprintf('canCompleteObjective(%s, %s)', $player->getEntityLabel(), $card->getEntityLabel()));
		$resolver = $this->getObjectiveCardResolver($card, $player);
		//BgaLogger::get()->log(sprintf('canCompleteObjective() - resolver "%s"', get_class($resolver)));
		$resolver->resolve();
		$solution = $resolver->getSolution();
		//BgaLogger::get()->log(sprintf('canCompleteObjective() - solution "%s" is completed ? %s', json_encode($solution), Dump::bool($resolver->isCompleted())));
		
		return $resolver->isCompleted();
	}
	
	public function getMajorityCard(): Token {
		return $this->app->getToken($this->app->getMajorityCardId());
	}
	
	public function resolveMajorities(): array {
		$card = $this->getMajorityCard();
		//BgaLogger::get()->log(sprintf('resolveMajorities(%s)', $card->getEntityLabel()));
		$material = $card->getMaterial();
		$results = [];
		foreach( $material['achievements'] as $majority ) {
			$resolver = $this->getMajorityResolver($majority);
			$resolver->resolve();
			$results[$majority] = $resolver->getResult();
		}
		
		return $results;
	}
	
	protected function getMajorityResolver(string $majority): AbstractMajorityResolver {
		//BgaLogger::get()->log(sprintf('getMajorityResolver(%s)', $majority));
		$material = $this->app->getMajorityMaterial($majority);
		
		switch( $material['about'] ) {
			case MAJORITY_TYPE_DECOR_TERRACES:
				return new DecorTerraceMajorityResolver($this, $material['around']);
			case MAJORITY_TYPE_AREA_TERRACES:
				return new AreaTerraceMajorityResolver($this, $material['in']);
			case MAJORITY_TYPE_RESTAURANTS:
				return new RestaurantMajorityResolver($this);
			case MAJORITY_TYPE_MONEY:
				return new MoneyMajorityResolver($this);
			case MAJORITY_TYPE_PIGEON_CARDS:
				return new PigeonCardMajorityResolver($this);
			default:
				throw new RuntimeException(sprintf('Unknown majority achievement "%s" for majority %s', $material['about'], $majority));
		}
	}
	
	/**
	 * @param Player $player
	 * @param array $adjTerraceRestaurants
	 * @param bool $allowAnyAdjacent Allow any adjacent terrace (but self, AdjacentTerrace PC T540)
	 * @param bool $canBuyMoreWithCard Output parameter to know if a gold card could be used
	 * @param Restaurant|null $onlyRestaurant The only allowed restaurant
	 * @param bool $freeTerrace
	 * @return array
	 * @deprecated Use TerraceBuildResolver
	 * @see Test with https://studio.boardgamearena.com/1/dinnerinparis/dinnerinparis/listBuildableTerraceLocations.html?table=TABLE_ID#bottom
	 */
	public function getBuildableTerraceLocations(Player $player, array $adjTerraceRestaurants, bool $allowAnyAdjacent, bool &$canBuyMoreWithCard, ?Restaurant $onlyRestaurant = null, bool $freeTerrace = false): array {
		$resolver = new TerraceBuildResolver($player, $this);
		$resolver->resolveAvailableLocations();
		
		return $resolver->getAvailableLocations();
	}
	
	/**
	 * @param Player $player
	 * @return Terrace[]
	 */
	public function getPlayerTerraces(Player $player, ?Restaurant $restaurant = null): array {
		return $this->getGrid()->getTokenList(TOKEN_TYPE_TERRACE, $player, $restaurant);
	}
	
	/**
	 * @param array $tokens
	 * @return array[]
	 */
	public function getTokenPoints(array $tokens): array {
		$grid = $this->getGrid();
		
		return array_map(function (Token $token) use ($grid) {
			return $grid->getTokenPoint($token);
		}, $tokens);
	}
	
	public function getPlayerBuildableRestaurants(Player $player): array {
		$availableRestaurants = [];
		$table = $this;
		/** @var ResourceCard[] $playerCards */
		$playerCards = $table->getResourceCardPlayerHand($player)->getTokens();
		// Add one FreeIngredient Pigeon Card if owned (exclude cards from same turn)
		$pigeonCards = $this->getPlayerPigeonCards($player, PIGEON_CARD_INGREDIENT);
		if( $pigeonCards ) {
			//			BgaLogger::get()->log(sprintf('getPlayerBuildableRestaurants() -  PIGEON CARDS ! %s', json_encode($pigeonCards)));
			$playerCards = array_merge($playerCards, $pigeonCards);
		}
		// Sort card by priority [Simple, Combo, Joker]
		usort($playerCards, function (IngredientPaymentUsable $card1, IngredientPaymentUsable $card2) {
			return count($card1->getGives()) - count($card2->getGives());
		});
		//		BgaLogger::get()->log(sprintf('getPlayerBuildableRestaurants() -  $playerCards = %s', json_encode($playerCards)));
		// Restaurant tokens
		$restaurantBox = $table->getRestaurantBox();
		$variantRestaurants = $restaurantBox->getTokensGroupedByVariant();
		// For each restaurant variant (friterie, pizzeria, gastronomique...)
		foreach( $this->app->getRestaurantMaterials() as $variant => $restaurantMaterial ) {
			// Get available token for this restaurant
			$token = $variantRestaurants[$variant][0] ?? null;
			if( !$token ) {
				// Stop if no token, restaurant is not available
				continue;
			}
			// Look for required resources to build this restaurant
			if( !$this->canBuyRestaurant($restaurantMaterial, $playerCards) ) {
				// Stop if costs are not matched (restaurant not buildable)
				continue;
			}
			// Restaurant is buildable, now look for all alternative resource cards
			$availableCards = $playerCards;
			//			BgaLogger::get()->log(sprintf('For Restaurant "%s", $availableCards %s', $restaurantMaterial['key'], json_encode($availableCards)));
			$costCards = [];
			foreach( $restaurantMaterial['cost'] as $requiredResource => $requiredQuantity ) {
				$resourceCards = [];// All cards providing this resource
				foreach( $availableCards as $card ) {
					//					BgaLogger::get()->log(sprintf('For Resource "%s", card %s gives %s', $requiredResource, $card->getEntityLabel(), json_encode($card->getGives())));
					if( in_array($requiredResource, $card->getGives()) ) {
						// Consume card for this resource
						$resourceCards[] = $card->getId();
					}
				}
				//				BgaLogger::get()->log(sprintf('For Resource "%s", available cards = %s', $requiredResource, json_encode($resourceCards)));
				// Repeat for given quantity
				for( $resIndex = 0; $resIndex < $requiredQuantity; $resIndex++ ) {
					// The n-th resource is defaulting the n-th card
					$choiceCards = $resourceCards;
					// Move given card as first one, the first is the default one
					array_unshift($choiceCards, $choiceCards[$resIndex]);
					$choiceCards = array_values(array_unique($choiceCards));
					//					BgaLogger::get()->log(sprintf('For Resource "%s" (qty=%d), choice cards = %s', $requiredResource, $requiredQuantity, json_encode($choiceCards)));
					$costCards[] = ['resource' => $requiredResource, 'cards' => $choiceCards];
				}
			}
			//			BgaLogger::get()->log(sprintf('For All Resources, cost cards = %s', json_encode($costCards)));
			$availableRestaurants[] = [
				'restaurant' => $token,
				'costs'      => $costCards,
			];
		}
		
		//		dump($availableRestaurants);
		//		die();
		
		return $availableRestaurants;
	}
	
	protected function canBuyRestaurant(array $material, array $cards): bool {
		// Calculate all resources
		$requiredResources = $material['cost'];
		// Filter to keep only one FreeIngredient pigeon card maximum
		$loopCards = $cards;
		$usePigeonCard = false;
		$cards = [];
		foreach( $loopCards as $card ) {
			$isPigeonCard = $card instanceof PigeonCard;
			if( !$isPigeonCard || !$usePigeonCard ) {
				$cards[] = $card;
				if( $isPigeonCard ) {
					$usePigeonCard = true;
				}
			}
		}
		// Use cards with only one resource
		foreach( $cards as $cardIndex => $card ) {
			$gives = $card->getGives();
			if( count($gives) > 1 ) {
				continue;
			}
			if( isset($requiredResources[$gives[0]]) ) {
				// Card matches a required resource, we consume it
				//				dump('Consume card', $cards[$cardIndex]);
				unset($cards[$cardIndex]);
				$requiredResources[$gives[0]]--;
				if( !$requiredResources[$gives[0]] ) {
					// If no more need in this resource, we remove it
					unset($requiredResources[$gives[0]]);
				}
			}
		}
		if( !$requiredResources ) {
			return true;
		}
		// Some required resource still left, so we calculate alternative tree for combo cards
		$tokenService = new TokenService();
		$requiredResources = $tokenService->flattenQuantity($requiredResources);// Repeat resource to flatten array [potatoes => 2] => [potatoes, potatoes]
		$cardsByResource = $tokenService->groupByResource($cards);// [potatoes => [Card#27, Card#41]]
		$solution = $tokenService->getOneConsumptionSolution($cardsByResource, $requiredResources);
		
		return !!$solution;
	}
	
	/**
	 * @param array $typeTokens
	 * @return GameTable
	 */
	public function initializeTypeTokens(array $typeTokens): GameTable {
		if( !$this->typeTokens && $typeTokens ) {
			// Do not override existing list
			$this->typeTokens = $typeTokens;
		}
		
		return $this;
	}
	
	public function getRestaurantBox(): TokenHeap {
		if( !$this->restaurantBox ) {
			$this->restaurantBox = new TokenHeap('Box Restaurant Heap', TOKEN_TYPE_RESTAURANT, TOKEN_CONTAINER_BOX, null,
				$this->extractTokens(TOKEN_TYPE_RESTAURANT, TOKEN_CONTAINER_BOX));
		}
		
		return $this->restaurantBox;
	}
	
	public function getGrid(): TokenGrid {
		if( !$this->grid ) {
			$decor = [];
			$restaurantTileMaterial = null;
			foreach( $this->app->getTileMaterials() as $tileMaterial ) {
				if( !$restaurantTileMaterial && $tileMaterial['key'] === TILE_RESTAURANT ) {
					$restaurantTileMaterial = $tileMaterial;
				}
				if( empty($tileMaterial['locations']) ) {
					continue;
				}
				$decorMaterial = $tileMaterial;
				unset($decorMaterial['locations']);
				foreach( $tileMaterial['locations'] as $decorCoords ) {
					$decor[] = [new GridDecor($decorMaterial), $decorCoords];
				}
			}
			$this->grid = new TokenGrid('Board Grid', [TOKEN_TYPE_RESTAURANT, TOKEN_TYPE_TERRACE], TOKEN_CONTAINER_BOARD_GRID, $decor,
				array_merge($this->extractTokens(TOKEN_TYPE_RESTAURANT, TOKEN_CONTAINER_BOARD_GRID, false), $this->extractTokens(TOKEN_TYPE_TERRACE, TOKEN_CONTAINER_BOARD_GRID, false))
				, 20, $this->gridModel);
			// Generate restaurant decor element
			$first = $this->grid->getFirstIndex();
			$last = $this->grid->getLastIndex();
			$restaurantDecor = new GridDecor($restaurantTileMaterial);
			for( $i = $first; $i <= $last; $i++ ) {
				$this->grid->setDecor($restaurantDecor, [$first, $i], true);
				$this->grid->setDecor($restaurantDecor, [$i, $first], true);
				$this->grid->setDecor($restaurantDecor, [$last, $i], true);
				$this->grid->setDecor($restaurantDecor, [$i, $last], true);
			}
		}
		
		return $this->grid;
	}
	
	public function getResourceCardRiver(): TokenRiver {
		if( !$this->resourceCardRiver ) {
			$this->resourceCardRiver = new TokenRiver('Board Resource Card River', TOKEN_TYPE_RESOURCE_CARD, TOKEN_CONTAINER_BOARD_RIVER, null,
				$this->extractTokens(TOKEN_TYPE_RESOURCE_CARD, TOKEN_CONTAINER_BOARD_RIVER), 4);
		}
		
		return $this->resourceCardRiver;
	}
	
	public function getResourceCardDrawPile(): CardPile {
		if( !$this->resourceCardDrawPile ) {
			$this->resourceCardDrawPile = new CardPile('Board Resource Card Draw Pile', TOKEN_TYPE_RESOURCE_CARD, TOKEN_CONTAINER_BOARD_DECK, null);
			$this->resourceCardDrawPile->addList($this->extractTokens(TOKEN_TYPE_RESOURCE_CARD, TOKEN_CONTAINER_BOARD_DECK));
		}
		
		return $this->resourceCardDrawPile;
	}
	
	public function getResourceCardDiscardPile(): CardPile {
		if( !$this->resourceCardDiscardPile ) {
			$this->resourceCardDiscardPile = new CardPile('Board Resource Card Discard Pile', TOKEN_TYPE_RESOURCE_CARD, TOKEN_CONTAINER_BOARD_DISCARD, null);
			$this->resourceCardDiscardPile->addList($this->extractTokens(TOKEN_TYPE_RESOURCE_CARD, TOKEN_CONTAINER_BOARD_DISCARD));
		}
		
		return $this->resourceCardDiscardPile;
	}
	
	public function getResourceCardPlayerHand(Player $player): CardPile {
		$playerTable = $this->getPlayerTable($player);
		if( !$playerTable->resourceCardHand ) {
			//BgaLogger::get()->log(sprintf('Initialize player %s resource cards\' hand, pid=%s', $player->getEntityLabel(), getmypid()));
			$playerTable->resourceCardHand = new CardPile(sprintf('Player #%d Resource Card Hand', $player->getId()), TOKEN_TYPE_RESOURCE_CARD, TOKEN_CONTAINER_PLAYER_HAND, $player);
			$playerTable->resourceCardHand->addList($this->extractTokens(TOKEN_TYPE_RESOURCE_CARD, TOKEN_CONTAINER_PLAYER_HAND, $player));
		}
		
		return $playerTable->resourceCardHand;
	}
	
	public function getObjectiveCardDrawPile(): CardPile {
		if( !$this->objectiveCardDrawPile ) {
			$this->objectiveCardDrawPile = new CardPile('Board Objective Card Draw Pile', TOKEN_TYPE_OBJECTIVE_CARD, TOKEN_CONTAINER_BOARD_DECK, null);
			$this->objectiveCardDrawPile->addList($this->extractTokens(TOKEN_TYPE_OBJECTIVE_CARD, TOKEN_CONTAINER_BOARD_DECK));
		}
		
		return $this->objectiveCardDrawPile;
	}
	
	public function getObjectiveCardRiver(): TokenRiver {
		if( !$this->objectiveCardRiver ) {
			$this->objectiveCardRiver = new TokenRiver('Board Objective Card River', TOKEN_TYPE_OBJECTIVE_CARD, TOKEN_CONTAINER_BOARD_RIVER, null,
				$this->extractTokens(TOKEN_TYPE_OBJECTIVE_CARD, TOKEN_CONTAINER_BOARD_RIVER), 8);
		}
		
		return $this->objectiveCardRiver;
	}
	
	public function getObjectiveCardPlayerHand(Player $player): CardPile {
		$playerTable = $this->getPlayerTable($player);
		if( !$playerTable->objectiveCardHand ) {
			//			BgaLogger::get()->log(sprintf('Initialize player %s objective cards\' hand, pid=%d', $player->getEntityLabel(), getmypid()));
			$playerTable->objectiveCardHand = new CardPile(sprintf('Player #%d Objective Card Hand', $player->getId()), TOKEN_TYPE_OBJECTIVE_CARD, TOKEN_CONTAINER_PLAYER_HAND, $player);
			$playerTable->objectiveCardHand->addList($this->extractTokens(TOKEN_TYPE_OBJECTIVE_CARD, TOKEN_CONTAINER_PLAYER_HAND, $player));
		}
		
		return $playerTable->objectiveCardHand;
	}
	
	public function getObjectiveCardPlayerPending(Player $player): CardPile {
		$playerTable = $this->getPlayerTable($player);
		if( !$playerTable->objectiveCardPending ) {
			//			BgaLogger::get()->log('Initialize player %s pending objective cards, pid=%s', $player->getId(), getmypid());
			$playerTable->objectiveCardPending = new CardPile(sprintf('Player #%d Objective Card Pending Pile', $player->getId()), TOKEN_TYPE_OBJECTIVE_CARD, TOKEN_CONTAINER_PLAYER_BOARD, $player);
			$playerTable->objectiveCardPending->addList($this->extractTokens(TOKEN_TYPE_OBJECTIVE_CARD, TOKEN_CONTAINER_PLAYER_BOARD, $player));
		}
		
		return $playerTable->objectiveCardPending;
	}
	
	public function getObjectiveCardPlayerDiscard(Player $player): CardPile {
		$playerTable = $this->getPlayerTable($player);
		if( !$playerTable->objectiveCardDiscard ) {
			//			BgaLogger::get()->log('Initialize player %s objective cards\' discard pile, pid=%s', $player->getId(), getmypid());
			$playerTable->objectiveCardDiscard = new CardPile(sprintf('Player #%d Objective Card Discard Pile', $player->getId()), TOKEN_TYPE_OBJECTIVE_CARD, TOKEN_CONTAINER_PLAYER_DISCARD, $player);
			$playerTable->objectiveCardDiscard->addList($this->extractTokens(TOKEN_TYPE_OBJECTIVE_CARD, TOKEN_CONTAINER_PLAYER_DISCARD, $player));
		}
		
		return $playerTable->objectiveCardDiscard;
	}
	
	public function getPigeonCardDrawPile(): CardPile {
		if( !$this->pigeonCardDrawPile ) {
			$this->pigeonCardDrawPile = new CardPile('Board Pigeon Card Draw Pile', TOKEN_TYPE_PIGEON_CARD, TOKEN_CONTAINER_BOARD_DECK, null);
			$this->pigeonCardDrawPile->addList($this->extractTokens(TOKEN_TYPE_PIGEON_CARD, TOKEN_CONTAINER_BOARD_DECK));
		}
		
		return $this->pigeonCardDrawPile;
	}
	
	public function getFirstPlayerPigeonCard(Player $player, string $key): ?PigeonCard {
		$hand = $this->getPigeonCardPlayerHand($player);
		$cards = $hand->getTokensByKey($key);
		foreach( $cards as $card ) {
			if( !$player->isPigeonCardExcludedThisTurn($card) ) {
				return $card;
			}
		}
		
		return null;
	}
	
	public function getPlayerPigeonCards(Player $player, string $key): array {
		$hand = $this->getPigeonCardPlayerHand($player);
		$cards = $hand->getTokensByKey($key);
		$all = [];
		foreach( $cards as $card ) {
			if( !$player->isPigeonCardExcludedThisTurn($card) ) {
				$all[] = $card;
			}
		}
		
		return $all;
	}
	
	public function getPigeonCardPlayerHand(Player $player): CardPile {
		$playerTable = $this->getPlayerTable($player);
		if( !$playerTable->pigeonCardHand ) {
			//BgaLogger::get()->log(sprintf('Initialize player %s pigeon cards\' hand, pid=%s', $player->getEntityLabel(), getmypid()));
			$playerTable->pigeonCardHand = new CardPile(sprintf('Player #%d Pigeon Card Hand', $player->getId()), TOKEN_TYPE_PIGEON_CARD, TOKEN_CONTAINER_PLAYER_HAND, $player);
			$playerTable->pigeonCardHand->addList($this->extractTokens(TOKEN_TYPE_PIGEON_CARD, TOKEN_CONTAINER_PLAYER_HAND, $player));
		}
		
		return $playerTable->pigeonCardHand;
	}
	
	public function getPigeonCardPlayerDiscard(Player $player): CardPile {
		$playerTable = $this->getPlayerTable($player);
		if( !$playerTable->pigeonCardDiscard ) {
			//BgaLogger::get()->log(sprintf('Initialize player %s pigeon cards\' discard pile, pid=%s', $player->getEntityLabel(), getmypid()));
			$playerTable->pigeonCardDiscard = new CardPile(sprintf('Player #%d Pigeon Card Discard Pile', $player->getId()), TOKEN_TYPE_PIGEON_CARD, TOKEN_CONTAINER_PLAYER_DISCARD, $player);
			$playerTable->pigeonCardDiscard->addList($this->extractTokens(TOKEN_TYPE_PIGEON_CARD, TOKEN_CONTAINER_PLAYER_DISCARD, $player));
		}
		
		return $playerTable->pigeonCardDiscard;
	}
	
	public function getPlayerCategoryTerraceRiver(Player $player, int $category): TerraceRiver {
		$playerTable = $this->getPlayerTable($player);
		if( !isset($playerTable->terraceCategoryList[$category]) ) {
			$materials = $this->app->getRestaurantCategoryMaterials();
			// Initialize all categories
			foreach( $materials as $loopCategory => $material ) {
				$playerTable->terraceCategoryList[$loopCategory] = new TerraceRiver(
					sprintf('Player #%d Terrace List of Category #%d', $player->getId(), $loopCategory),
					TOKEN_TYPE_TERRACE, TOKEN_CONTAINER_PLAYER_BOARD, $player, $material);
			}
			$terraces = $this->extractTokens(TOKEN_TYPE_TERRACE, TOKEN_CONTAINER_PLAYER_BOARD, $player);
			/** @var Terrace $terrace */
			foreach( $terraces as $terrace ) {
				$playerTable->terraceCategoryList[$terrace->getCategory()]->set($terrace, $terrace->getCategoryPosition());
			}
			//			BgaLogger::get()->log('Initialize player %s \'s terrace list of category %d, pid=%s', $player->getId(), $category, getmypid());
		}
		
		return $playerTable->terraceCategoryList[$category];
	}
	
	/**
	 * @internal Don't use it, components should be loaded by GameTable
	 * @param Player $player
	 * @return object
	 */
	protected function getPlayerTable(Player $player): object {
		if( !array_key_exists($player->getId(), $this->playerTables) ) {
			//BgaLogger::get()->log(sprintf('Initialize player table of %s, pid=%s', $player->getEntityLabel(), getmypid()));
			$this->playerTables[$player->getId()] = (object) [
				'resourceCardHand'     => null,
				'objectiveCardHand'    => null,
				'objectiveCardDiscard' => null,
				'objectiveCardPending' => null,
				'pigeonCardHand'       => null,
				'pigeonCardDiscard'    => null,
				'terraceCategoryList'  => [],
			];
		}
		
		return $this->playerTables[$player->getId()];
	}
	
	/**
	 * @param Player $player
	 * @return Token[]
	 */
	public function getPendingPlayerObjectiveCards(Player $player): array {
		return $this->extractTokens(TOKEN_TYPE_OBJECTIVE_CARD, TOKEN_CONTAINER_PLAYER_BOARD, $player, false);
	}
	
	/**
	 * @param int $type
	 * @param int $container
	 * @param Player|null|false $player False to disable verification
	 * @return Token[]
	 */
	protected function extractTokens(int $type, int $container, $player = null, bool $remove = true): array {
		$playerId = $player ? $player->getId() : $player;
		$tokens = [];
		/** @var Token $token */
		foreach( $this->typeTokens[$type] as $index => $token ) {
			if( $token->getContainer() === $container && ($playerId === false || $token->getPlayerId() === $playerId) ) {
				if( $container === TOKEN_CONTAINER_BOX ) {
					$tokens[] = $token;
				} else {
					// Set index close as possible as the position
					// Fix multiple element having the same position by finding the next available position
					$position = $token->getPosition();
					while( isset($tokens[$position]) ) {
						$position++;
					}
					$tokens[$position] = $token;
				}
				if( $remove ) {
					unset($this->typeTokens[$type][$index]);
				}
			}
		}
		
		return $tokens;
	}
	
	/**
	 * @return int
	 */
	public function getGridModel(): int {
		return $this->gridModel;
	}
	
	/**
	 * @param int $gridModel
	 * @return GameTable
	 */
	public function setGridModel(int $gridModel): GameTable {
		$this->gridModel = $gridModel;
		
		return $this;
	}
	
	/**
	 * @return BoardGameApp
	 */
	public function getApp(): BoardGameApp {
		return $this->app;
	}
	
}
