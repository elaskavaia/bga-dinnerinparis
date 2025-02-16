<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game;

use AppGame\Entity\Player;
use AppGame\Entity\Restaurant;
use AppGame\Entity\Terrace;
use AppGame\Entity\Token;
//use AppGame\Logger\BgaLogger;
use AppGame\Service\BoardGameApp;
use AppGame\Service\GeometryService;

/**
 * Class to resolve the list of buildable cells
 *
 * @see Test using https://studio.boardgamearena.com/1/dinnerinparis/dinnerinparis/listBuildableTerraceLocations.html?table=TABLE_ID#bottom
 */
class TerraceBuildResolver {
	
	const TERRACE_NORMAL = 'n';
	const TERRACE_ADJACENT = 'a';
	const TERRACE_COVER = 'c';
	
	const ERROR_BALANCE = 'balance';// Insufficient balance
	const ERROR_TERRACE = 'terrace';// Insufficient terrace in category
	const ERROR_PIGEON_CARD = 'pigeon_card';// Restricted by pigeon card
	const ERROR_CELL = 'cell';// No available cell
	
	/** @var GeometryService */
	protected $geometryService;
	
	/** @var Player */
	protected $player;
	
	/** @var GameTable */
	protected $table;
	
	/** @var TokenGrid */
	protected $grid;
	
	/** @var BoardGameApp */
	protected $app;
	
	/** @var bool */
	protected $allowAnyCover = false;
	
	/** @var bool */
	protected $freeTerrace = false;
	
	/** @var Restaurant */
	protected $onlyRestaurant = null;
	
	/** @var array [RestaurantId => CoverLeft] */
	protected $adjTerraceRestaurants = [];
	
	/** @var array|null */
	protected $availableLocations = null;
	
	/** @var bool */
	protected $moreWithGold = false;
	
	/** @var array */
	protected $unavailableRestaurants = [];
	
	protected $restaurantTerracePoints = [];
	
	protected $restaurantsTerracesPoints = [];
	
	protected $restaurantsPoints = [];
	
	/**
	 * TerraceBuildResolver constructor
	 *
	 * @param Player $player
	 * @param GameTable $table
	 */
	public function __construct(Player $player, GameTable $table) {
		$this->geometryService = GeometryService::get();
		$this->player = $player;
		$this->table = $table;
		$this->grid = $table->getGrid();
		$this->app = $table->getApp();
	}
	
	public function resolveAvailableLocations() {
		$player = $this->player;
		$table = $this->table;
		$grid = $this->grid;
		$adjTerraceRestaurants = $this->adjTerraceRestaurants;
		$this->availableLocations = [];
		$this->moreWithGold = false;
		// consumable gold amount is used to decrease costs
		$consumableGold = $table->getConsumableGold($player);
		// List all restaurants of player
		$restaurantAvailableLocations = [];
		$restaurants = [];// Player's restaurants
		$restaurantTerraces = [];
		/** @var Restaurant $restaurant */
		//BgaLogger::get()->log(sprintf('getTokenList() restaurants'));
		foreach( $grid->getTokenList(TOKEN_TYPE_RESTAURANT, $player) as $restaurant ) {
			//BgaLogger::get()->log(sprintf('Loop restaurant %s', $restaurant->getEntityLabel()));
			$restaurantId = $restaurant->getId();
			// Player's restaurant
			$terracePile = $table->getPlayerCategoryTerraceRiver($player, $restaurant->getCategory());
			if( $terracePile->isEmpty() ) {
				//BgaLogger::get()->log(sprintf('Terrace #%d pile is empty, container=%d', $restaurant->getCategory(), $terracePile->getContainer()));
				$this->unavailableRestaurants[$restaurantId] = self::ERROR_TERRACE;
				continue;
			}
			if( $this->onlyRestaurant && !$this->onlyRestaurant->equals($restaurant) ) {
				// Pigeon card "AddTerrace" effect - Force one Restaurant
				//BgaLogger::get()->log(sprintf('Terrace restaurant restricted by pigeon card'));
				$this->unavailableRestaurants[$restaurantId] = self::ERROR_PIGEON_CARD;
				continue;
			}
			$terraceMaterial = $terracePile->nextTerraceMaterial();
			$cost = $terraceMaterial['cost'];
			if( $this->freeTerrace ) {
				// Pigeon card "AddTerrace" effect - Free Terrace
				$cost = 0;
			}
			//			dump('Can pay ?', $player->canPay($cost));
			$restaurants[$restaurantId] = $restaurant;
			$restaurantTerraces[$restaurantId] = [];
			if( $player->canPay($cost) ) {
				// Can pay one more terrace
				$restaurantAvailableLocations[$restaurantId] = [
					'cost'     => $cost,
					'score'    => $terraceMaterial['score'] ?? 0,
					'income'   => $terraceMaterial['income'] ?? 0,
					'cells'    => [],
					'terraces' => 0,
				];
			} else {
				//BgaLogger::get()->log(sprintf('resolveAvailableLocations() - Can no buy a terrace for %s', $restaurant->getEntityLabel()));
				$this->unavailableRestaurants[$restaurantId] = self::ERROR_BALANCE;
				// Check if player could pay by using a card
				if( $player->canPay($cost - $consumableGold) ) {
					$this->moreWithGold = true;
				}
			}
		}
		//		dump('$restaurantAvailableLocations', $restaurantAvailableLocations, count($restaurantAvailableLocations));
		// List all terraces of restaurant to filter restaurant having terraces
		if( $restaurantAvailableLocations ) {
			foreach( $grid->getTokenList(TOKEN_TYPE_TERRACE, $player) as $terrace ) {
				/** @var Terrace $terrace */
				$restaurantId = $terrace->getRestaurantId();
				if( $restaurantId && isset($restaurantAvailableLocations[$restaurantId]) ) {
					$restaurantAvailableLocations[$restaurantId]['terraces']++;
					$restaurantTerraces[$restaurantId][] = $grid->getTokenPoint($terrace);
				}
			}
			// ForEach terrace, get all buildable cells
			foreach( $restaurantTerraces as $restaurantId => $terraces ) {
				if( !isset($restaurantAvailableLocations[$restaurantId]) ) {
					// Player can not buy terrace for this restaurant
					continue;
				}
				$restaurant = $restaurants[$restaurantId];
				if( !$terraces ) {
					//					BgaLogger::get()->log(sprintf('No terrace for restaurant "%s"', $restaurant->getEntityLabel()));
					// Restaurant has no terrace, we fake it by faking its own cells as terraces
					$terraces = $this->getRestaurantPoints($restaurant);
					//					BgaLogger::get()->log(sprintf('Generated pattern points %s', json_encode($terraces)));
				}
				//				BgaLogger::get()->log(sprintf('Restaurant %s has terraces %s', $restaurant->getEntityLabel(), json_encode($terraces)));
				$permission = $adjTerraceRestaurants[$restaurantId] ?? ($this->allowAnyCover ? 2 : null);
				foreach( $terraces as $terracePoint ) {
					//				$terracePoint = is_array($terrace) ? $terrace : $grid->getTokenPoint($terrace);
					foreach( $this->geometryService->getAdjacentPoints($terracePoint) as $adjacentPoint ) {
						//					BgaLogger::get()->log(sprintf('Terrace point has adjacent point %s', json_encode($adjacentPoint)));
						$buildRequirement = $this->getTerraceBuildRequirement($restaurant, $adjacentPoint);
						//						BgaLogger::get()->log(sprintf('Terrace point has adjacent point %s with requirement %s against perm = %s', json_encode($adjacentPoint), $buildRequirement ?: 'NONE', $permission ?? 'NONE'));
						if( $this->hasBuildPermission($permission, $buildRequirement) ) {
							$restaurantAvailableLocations[$restaurant->getId()]['cells'][] = [$adjacentPoint, $buildRequirement];
						}
					}
				}
			}
		}
		
		// Look for restaurants with no available cell
		foreach( $restaurantAvailableLocations as $restaurantId => $restaurantInfo ) {
			if( !$restaurantInfo['cells'] ) {
				unset($restaurantAvailableLocations[$restaurantId]);
				$this->unavailableRestaurants[$restaurantId] = self::ERROR_CELL;
				//BgaLogger::get()->log(sprintf('Restaurant %d has no available cell', $restaurantId));
			}
		}
		
		$this->availableLocations = $restaurantAvailableLocations;
	}
	
	protected function getRestaurantTerracePoints(Restaurant $restaurant): array {
		if( !isset($this->restaurantTerracePoints[$restaurant->getId()]) ) {
			$points = [];
			$terraces = $this->table->getPlayerTerraces($this->player, $restaurant);
			foreach( $terraces as $terrace ) {
				$point = $this->grid->getTokenPoint($terrace);
				$points[$this->geometryService->getPointIndex($point)] = $point;
			}
			
			$this->restaurantTerracePoints[$restaurant->getId()] = $points;
		}
		
		return $this->restaurantTerracePoints[$restaurant->getId()];
	}
	
	protected function isRemovableTerrace(Token $terrace): bool {
		// Not a terrace or owned by player (can not remove my own terraces)
		if( !($terrace instanceof Terrace) || $terrace->isOwnedBy($this->player) ) {
			return false;
		}
		$app = $this->app;
		$grid = $this->grid;
		/** @var Restaurant $restaurant */
		$restaurant = $app->getToken($terrace->getRestaurantId());
		$restaurantTerracesPoints = $this->getRestaurantTerracesPoints($restaurant);
		$terracePointIndex = $this->geometryService->getPointIndex($grid->getTokenPoint($terrace));
		//BgaLogger::get()->log(sprintf('$restaurantTerracesPoints : %s for %s, looking for %s', json_encode($restaurantTerracesPoints), $restaurant->getEntityLabel(), $terracePointIndex));
		if( !isset($restaurantTerracesPoints[$terracePointIndex]) ) {
			// Terrace is not one of its self restaurant (WTF ?)
			//BgaLogger::get()->log(sprintf('ERROR-UNEXPECTED_BEHAVIOR : A terrace has a parent that is not having it as child, this should be impossible'));
			
			return false;
		}
		unset($restaurantTerracesPoints[$terracePointIndex]);
		$restaurantPoints = $this->getRestaurantPoints($restaurant);
		[, $nonLinkedPoints] = $this->geometryService->getPointsLinking($restaurantPoints, $restaurantTerracesPoints);
		
		return !$nonLinkedPoints;
	}
	
	public function getRestaurantTerracesPoints(Restaurant $restaurant): array {
		if( !isset($this->restaurantsTerracesPoints[$restaurant->getId()]) ) {
			$player = $this->app->getPlayer($restaurant->getPlayerId());
			$table = $this->table;
			$grid = $this->grid;
			$restaurantTerraces = $table->getPlayerTerraces($player, $restaurant);
			$restaurantTerracesPoints = [];
			foreach( $restaurantTerraces as $restaurantTerrace ) {
				$point = $grid->getTokenPoint($restaurantTerrace);
				$restaurantTerracesPoints[$this->geometryService->getPointIndex($point)] = $point;
			}
			$this->restaurantsTerracesPoints[$restaurant->getId()] = $restaurantTerracesPoints;
		}
		
		return $this->restaurantsTerracesPoints[$restaurant->getId()];
	}
	
	public function getTerraceBuildRequirement(Restaurant $restaurant, array $point): ?string {
		// Get requirements for this point
		// Cover > Adjacent > Normal > Null
		$restaurantId = $restaurant->getId();
		$player = $this->player;
		$grid = $this->grid;
		/** @var GridDecor $pointDecor */
		/** @var Token $pointToken */
		[$pointDecor, $pointToken] = $grid->get($point);
		if( $pointDecor && !$pointDecor->allowBuildTerrace() ) {
			// Ignore adjacent decor not allowing terrace building
			return null;
		}
		if( $pointToken ) {
			// Ignore adjacent terrace (except if it could cover up another player's terrace)
			// AdjacentTerrace PigeonCard T540
			//			BgaLogger::get()->log(sprintf('Adjacent point %s token at %s', $pointToken->getEntityLabel(), json_encode($point)));
			return $this->isRemovableTerrace($pointToken) ? self::TERRACE_COVER : null;
		}
		$hasRejectedAdjacent = false;
		foreach( $this->geometryService->getAdjacentPoints($point) as $adjacentPoint ) {
			$adjacentToken = $grid->getTokenAt($adjacentPoint);
			if( $adjacentToken instanceof Terrace ) {
				if( $adjacentToken->isOwnedBy($player) ) {
					if( $adjacentToken->getRestaurantId() !== $restaurantId ) {
						// Immediately reject terrace from another restaurant of same player
						return null;
					}
					// Else do not reject terrace of same restaurant
				} else {
					// Allow same restaurant but no terrace from same player and other restaurant
					$hasRejectedAdjacent = true;
				}
			}
		}
		if( $hasRejectedAdjacent ) {
			// Ignore adjacent terrace with adjacent terrace from another restaurant (except if allowed by pigeon card T540)
			return self::TERRACE_ADJACENT;
		}
		
		return self::TERRACE_NORMAL;
	}
	
	public function hasBuildPermission(?int $permission, ?string $required): bool {
		if( !$required ) {
			// Null is rejecting build whatever, build is impossible
			return false;
		}
		if( $required === self::TERRACE_NORMAL ) {
			// Normal is never rejecting
			return true;
		}
		$allowAdjacent = $permission !== null;
		$allowCoverUp = !!$permission;
		if( $allowCoverUp ) {
			// Cover permission allows everything, except impossible
			return true;
		}
		if( $allowAdjacent && $required === self::TERRACE_ADJACENT ) {
			// Adjacent is only allowed with adjacent permission
			// Cover permission and normal requirement are already handled
			return true;
		}
		
		return false;
	}
	
	public function getRestaurantPoints(Restaurant $restaurant): array {
		if( !isset($this->restaurantsPoints[$restaurant->getId()]) ) {
			$grid = $this->grid;
			$this->restaurantsPoints[$restaurant->getId()] = $this->table->getRestaurantPlacement($grid->getTokenPoint($restaurant), $restaurant->getSize(), $restaurant->getOrientation());
		}
		
		return $this->restaurantsPoints[$restaurant->getId()];
	}
	
	/**
	 * @return array|null
	 */
	public function getAvailableLocations(): ?array {
		return $this->availableLocations;
	}
	
	/**
	 * @return array
	 */
	public function getUnavailableRestaurants(): array {
		return $this->unavailableRestaurants;
	}
	
	/**
	 * @return bool
	 */
	public function hasMoreWithGold(): bool {
		return $this->moreWithGold;
	}
	
	/**
	 * @param bool $allow
	 */
	public function setAllowAnyCover(bool $allow): void {
		$this->allowAnyCover = $allow;
	}
	
	/**
	 * @param bool $freeTerrace
	 */
	public function setFreeTerrace(bool $freeTerrace): void {
		$this->freeTerrace = $freeTerrace;
	}
	
	/**
	 * @param Restaurant|null $onlyRestaurant
	 */
	public function setOnlyRestaurant(?Restaurant $onlyRestaurant): void {
		$this->onlyRestaurant = $onlyRestaurant;
	}
	
	/**
	 * Set the cover left by restaurant (id)
	 *
	 * @param array $adjTerraceRestaurants [RestaurantId => CoverLeft]
	 */
	public function setAdjacentTerraceRestaurants(array $adjTerraceRestaurants): void {
		$this->adjTerraceRestaurants = $adjTerraceRestaurants;
	}
	
}
