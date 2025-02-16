<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractController;
use AppGame\Core\Controller\ArgumentBag;
use AppGame\Core\Debug\Dump;
use AppGame\Core\Exception\InvalidInputException;
use AppGame\Core\Exception\UserException;
use AppGame\Entity\PigeonCard;
use AppGame\Entity\Player;
use AppGame\Entity\Restaurant;
use AppGame\Entity\Terrace;
use AppGame\Entity\Token;
use AppGame\Game\TerraceBuildResolver;
use AppGame\Service\TokenService;

class PlaceTerraceController extends AbstractController {
	
	/** @var array|null */
	private $locationSolver;
	
	public function run(Restaurant $restaurant, array $point) {
		$this->game->checkAction('place');
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('PlaceTerraceController(%s, %s) for player %s (pid=%s)',
		//	$restaurant->getEntityLabel(), json_encode($point), $player->getEntityLabel(), getmypid()));
		
		// Restaurant must be owned by player
		if( $player->getId() !== $restaurant->getPlayerId() ) {
			throw new InvalidInputException('Invalid player');
		}
		
		// Restaurant must be placed on board grid
		if( $restaurant->getContainer() !== TOKEN_CONTAINER_BOARD_GRID ) {
			throw new InvalidInputException('Restaurant not on board grid');
		}
		$restaurantId = $restaurant->getId();
		
		// Adjacent Terrace Pigeon Card
		$adjTerraceRestaurants = $player->getTurnInfo(Player::FLAG_PIGEON_CARD_ADJACENT_TERRACE_RESTAURANTS) ?? [];
		if( $player->hasActionFlag(Player::FLAG_PENDING_PIGEON_CARD_ADJACENT_TERRACE) ) {
			// New pending adjacent terrace pigeon card
			if( !isset($adjTerraceRestaurants[$restaurantId]) ) {
				$adjTerraceRestaurants[$restaurantId] = 0;
			}
			$adjTerraceRestaurants[$restaurantId] += 2;// Allowed cover up left
			$player->removeActionFlag(Player::FLAG_PENDING_PIGEON_CARD_ADJACENT_TERRACE);
		}
		
		$table = $this->table;
		$grid = $table->getGrid();
		$allowAnyAdjacent = $player->hasActionFlag(Player::FLAG_PENDING_PIGEON_CARD_ADJACENT_TERRACE);
		
		// Check grid
		$resolver = new TerraceBuildResolver($player, $table);
		$buildRequirement = $resolver->getTerraceBuildRequirement($restaurant, $point);
		$permission = $adjTerraceRestaurants[$restaurantId] ?? ($allowAnyAdjacent ? 0 : null);
		if( !$resolver->hasBuildPermission($permission, $buildRequirement) ) {
			throw new UserException(sprintf('Terrace can not be placed here, the cell %s requires "%s"', json_encode($point), $buildRequirement));
		}
		
		[$targetDecor, $targetToken] = $grid->get($point);
		
		$coverToken = null;
		$coverPlayer = null;
		$covering = false;
		if( $buildRequirement === TerraceBuildResolver::TERRACE_COVER && $targetToken ) {
			// Cover means there is a token
			/** @var Terrace $coverToken */
			$covering = true;
			$coverToken = $targetToken;
			$coverPlayer = $this->app->getPlayer($coverToken->getPlayerId());
			// Remove from grid
			$grid->remove($coverToken);
			// Un-assign terrace
			$coverToken->setPlayer(null);
			$coverToken->setParentToken(null);
			// Put terrace into the game box
			$coverToken->setContainer(TOKEN_CONTAINER_BOX);
			$coverToken->setPosition(0);
			// No more target token
			$targetToken = null;
			// Decrease allowed cover
			$adjTerraceRestaurants[$restaurantId]--;
		}
		
		// Place terrace
		$terracePile = $table->getPlayerCategoryTerraceRiver($player, $restaurant->getCategory());
		$terraceMaterial = $terracePile->nextTerraceMaterial();
		$terrace = $terracePile->pickFirst();
		$terrace->setRestaurant($restaurant);
		$grid->set($terrace, $point);
		
		// Pay terrace
		$cost = $terraceMaterial['cost'];
		$freeTerrace = $player->getTurnInfo('placingTerraceFree') ?? false;
		if( !$freeTerrace ) {
			$player->pay($cost);
		}
		$player->setTurnInfo('placingTerraceCount', ($player->getTurnInfo('placingTerraceCount') ?? 0) + 1);
		$player->setTurnInfo('placingTerraceLastRestaurant', $restaurantId);
		
		// Give bonus to player
		$player->setPendingIncome($player->getPendingIncome() + ($terraceMaterial['income'] ?? 0));
		$table->updatePlayerScoreAndIncome($player);
		
		$action = null;
		
		$this->entityService->startBatching();
		$movedTokens = [$terrace];
		
		if( $coverToken ) {
			$movedTokens[] = $coverToken;
		}
		
		// Pick a pigeon card on a pigeon tile
		if( $targetDecor && $targetDecor->getType() === TILE_PIGEON && !$covering ) {
			//			//$this->logger->log(sprintf('PIGEON_TILE : There IS a pigeon tile on %s', json_encode($point)));
			$pigeonCardDrawPile = $table->getPigeonCardDrawPile();
			$playerPigeonCardHand = $table->getPigeonCardPlayerHand($player);
			/** @var PigeonCard $pigeonCard */
			$pigeonCard = $pigeonCardDrawPile->pickFirst();
			$playerPigeonCardHand->add($pigeonCard);
			$this->entityService->updateList($pigeonCardDrawPile->getTokens());
			$this->entityService->updateList($playerPigeonCardHand->getTokens());
			$movedTokens[] = $pigeonCard;
			$this->app->sendSystemMessageToActivePlayer(
				clienttranslate('${player_name} drew the pigeon card "${token_name}"'),
				clienttranslate('${player_name} drew a new pigeon card'),
				[
					'player_name' => $player->getLabel(),
					'token_name'  => $pigeonCard->getLabel(),
				]
			);
			if( !$pigeonCard->isImmediate() ) {
				$player->excludePigeonCardThisTurn($pigeonCard);
			}
			$player->setTurnInfo('showPigeonCard', $pigeonCard->getId());
			$player->setTurnInfo('applyImmediate', true);
			$action = 'showPigeonCard';
		}
		
		// Save
		$player->setTurnInfo(Player::FLAG_PIGEON_CARD_ADJACENT_TERRACE_RESTAURANTS, $adjTerraceRestaurants);
		$this->entityService->updateList($movedTokens);
		$this->entityService->update($player);
		$this->entityService->applyBatching();
		
		// Notify users of all tokens move (terrace)
		$this->notifyTokenChanges($movedTokens, [
			'player_name'     => $player->getLabel(),
			'restaurant_name' => $restaurant->getLabel(),
			'price'           => $cost,
		], $coverPlayer);
		
		// Notify users of player update (balance)
		$this->app->notifyPlayerUpdate($player);
		
		// Update majorities (new terrace)
		$this->app->updatePlayerMajorities();
		
		// Next state
		// Check Add terrace pigeon card
		if( $player->hasActionFlag(Player::FLAG_RESUME_PIGEON_CARD_ADD_TERRACE) ) {
			// Override any showPigeonCard action
			// Playing Pigeon Card AddTerrace - Back to end of pigeon card
			$action = 'endAddTerracePigeonCard';
			// If player drew another pigeon card, this will lead to the pigeon card show up but first, we have to end the current one
		}
		//$this->logger->log(sprintf('PlaceTerrace action is "%s"', $action));
		$this->app->useStateAction($action ?: 'place');
	}
	
	protected function notifyTokenChanges(array $movedTokens, array $params, ?Player $coverPlayer) {
		if( $coverPlayer ) {
			$params['cover_player_name'] = $coverPlayer->getLabel();
			$message = clienttranslate('${player_name} placed terrace onto restaurant "${restaurant_name}" for ${price} coins and covered a terrace from ${cover_player_name}');
		} else {
			$message = clienttranslate('${player_name} placed terrace onto restaurant "${restaurant_name}" for ${price} coins');
		}
		$this->app->notifyTokenUpdate($movedTokens, $message, $params);
	}
	
	public function useAdjacentTerracePigeonCard() {
		$this->game->checkAction('useAdjacentTerracePigeonCard');
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('PlaceTerraceController.useAdjacentTerracePigeonCard() for player %s (pid=%s)', $player->getEntityLabel(), getmypid()));
		$pigeonCard = $this->getAdjacentTerracePigeonCard($player);
		if( !$pigeonCard ) {
			throw new UserException('No pigeon card');
		}
		// Consume pigeon card
		//$this->logger->log(sprintf('PlaceTerraceController.useAdjacentTerracePigeonCard() Discard pigeon card %s', $pigeonCard->getEntityLabel()));
		$pigeonDiscardPile = $this->table->getPigeonCardPlayerDiscard($player);
		$pigeonDiscardPile->putOnTop($pigeonCard);
		$movedTokens = $pigeonDiscardPile->getTokens();
		
		// Set pending flag (wait player to play next terrace to assign restaurant to pigeon card)
		$player->addActionFlag(Player::FLAG_PENDING_PIGEON_CARD_ADJACENT_TERRACE);
		
		// Save
		$this->entityService->startBatching();
		$this->entityService->updateList($movedTokens);
		$this->entityService->update($player);
		$this->entityService->applyBatching();
		
		// Notify
		$this->app->notifyTokenUpdate($movedTokens, clienttranslate('${player_name} used a pigeon card to place adjacent or covering terraces'), [
			'player_name' => $player->getLabel(),
		]);
		
		// Next state
		$this->app->useStateAction('place');
	}
	
	public function generateArguments(ArgumentBag $arguments): void {
		//$this->logger->log(sprintf('%s::generateArguments', Dump::shortName(self::class)));
		$player = $this->app->getActivePlayer();
		$terraceCount = $player->getTurnInfo('placingTerraceCount') ?? 0;
		
		// Resolve available terrace locations
		$adjTerraceRestaurants = $player->getTurnInfo(Player::FLAG_PIGEON_CARD_ADJACENT_TERRACE_RESTAURANTS) ?? [];
		$newAdjTerrPC = $player->hasActionFlag(Player::FLAG_PENDING_PIGEON_CARD_ADJACENT_TERRACE);
		$freeTerrace = $player->getTurnInfo('placingTerraceFree') ?? false;
		$onlyRestaurant = $player->getTurnInfo('placingTerraceForceRestaurant');
		if( $onlyRestaurant ) {
			$onlyRestaurant = $this->app->getToken($onlyRestaurant);
		}// Or null, not restricted to one restaurant
		
		$resolver = new TerraceBuildResolver($player, $this->table);
		$resolver->setAdjacentTerraceRestaurants($adjTerraceRestaurants);
		$resolver->setAllowAnyCover($newAdjTerrPC);
		$resolver->setOnlyRestaurant($onlyRestaurant);
		$resolver->setFreeTerrace($freeTerrace);
		$resolver->resolveAvailableLocations();
		$availableLocations = $resolver->getAvailableLocations();
		
		$tokenService = new TokenService();
		// Exclude card from same turn
		$goldPigeonCard = $this->table->getPlayerPigeonCards($player, PIGEON_CARD_TWO_GOLDS);
		// No pending AdjacentTerracePigeonCard and having a pigeon card
		$adjTerracePigeonCard = !$newAdjTerrPC && $this->getAdjacentTerracePigeonCard($player);
		
		$args = [
			'allowPlace'                 => $availableLocations || $resolver->hasMoreWithGold(),// Could have no more terrace or balance + available coins
			// Finish if placed anything or stuck with no more terrace to place, do not finish automatically when using PC AddTerrace
			// Placement availability is checked in PigeonAddTerraceStartController
			'allowConfirm'               => !$availableLocations || ($terraceCount && !$onlyRestaurant),
			// Cancel if placed nothing, can not cancel when using PC AdjacentTerrace
			'allowCancel'                => !$terraceCount && !$newAdjTerrPC,
			'allowAdjacentTerracePigeon' => !!$adjTerracePigeonCard,
			'placingTerraceCount'        => $terraceCount,
			'previousRestaurant'         => $player->getTurnInfo('placingTerraceLastRestaurant'),
			'availableLocations'         => $availableLocations,
			'unavailableRestaurants'     => $resolver->getUnavailableRestaurants(),
			'resourceCards'              => $onlyRestaurant ? [] : $this->table->getGoldCards($player),
			'goldPigeonCards'            => $onlyRestaurant ? [] : $tokenService->listId($goldPigeonCard),
		];
		if( $onlyRestaurant ) {
			$args['stateTitle'] = clienttranslate('${you} must place your free terrace on an available cell');
		}
		$arguments->setPlayerArgumentList($player, $args);
	}
	
	protected function getAdjacentTerracePigeonCard(Player $player): ?Token {
		return $this->table->getFirstPlayerPigeonCard($player, PIGEON_CARD_ADJACENT_TERRACE);
	}
	
}
