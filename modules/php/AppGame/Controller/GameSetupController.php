<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractController;
use AppGame\Entity\Token;
use AppGame\Service\OrmService;
use DinnerInParis;
use RuntimeException;
use Throwable;

class GameSetupController extends AbstractController {
	
	//	protected function initialize() {
	//		parent::initialize();
	//
	//		//$this->logger = SetupLogger::get();
	//	}
	
	public function run($players, $options = []) {
		// Set the colors of the players with HTML color code
		// The default below is red/green/blue/orange/brown
		// The number of colors defined here must correspond to the maximum number of players allowed for the gams
		$gameInfo = $this->game->getGameinfos();
		$defaultColors = $gameInfo['player_colors'];

		//$this->logger->eraseLogs();

		// Create players (see dbmodel.sql)
		// Most app values are initialized in dbmodel.sql as default value
		$ormService = OrmService::get();
		$values = [];
		foreach( $players as $playerId => $player ) {
			$playerColor = array_shift($defaultColors);
			$data = [$playerId, $playerColor, $player['player_canal'], $player['player_name'], $player['player_avatar']];
			$values[] = '(' . implode(', ', array_map([$ormService, 'formatSqlValue'], $data)) . ')';
		}
		$sql = sprintf('INSERT INTO `player` (`player_id`, `player_color`, `player_canal`, `player_name`, `player_avatar`) VALUES %s', implode(',', $values));
		//		//$this->logger->log(sprintf('Insert players query : "%s"', $sql));
		$this->game->DbQuery($sql);
		$this->game->reattributeColorsBasedOnPreferences($players, $gameInfo['player_colors']);
		$this->game->reloadPlayersBasicInfos();

		/************ Start the game initialization *****/

		try {
			// Force initializing app because first time, database was empty
			$this->app->initialize();
			$this->app->startGame();
			$this->createTokens();
			$this->dealTokens();
		} catch( Throwable $exception ) {
			//$this->logger->error($exception);
			var_dump($exception->getMessage());
			die();
		}

		// Activate first player (which is in general a good idea :) )
		$this->app->nextPlayer();
		/************ End of the game initialization *****/
	}
	
	protected function updateCardDeck(array $tokenList) {
		/** @var Token $token */
		$position = 0;
		foreach( $tokenList as $token ) {
			$token->setContainer(TOKEN_CONTAINER_BOARD_DECK);
			$token->setPosition($position);
			$this->entityService->update($token);
			$position++;
		}
	}
	
	private function dealCardsToPlayers(array &$deck, int $number, int $container = TOKEN_CONTAINER_PLAYER_HAND) {
		$players = $this->app->getPlayers();
		foreach( $players as $player ) {
			for( $i = 0; $i < $number; $i++ ) {
				$token = array_shift($deck);
				if( !$token ) {
					throw new RuntimeException('No token to complete resource cards river');
				}
				$token->setPlayer($player);
				$token->setContainer($container);
				$token->setPosition($i);
				$this->entityService->update($token);
			}
		}
	}
	
	private function dealTokens() {
		$tokens = $this->app->loadTokens();// Reload tokens and get type sorted list
		$this->entityService->startBatching();
		/** @var Token $token */

		// *** Resource cards ***
		// Shuffle resource cards deck
		$resourceCardDeck = $tokens[TOKEN_TYPE_RESOURCE_CARD];
		shuffle($resourceCardDeck);
		// Build river of Resource cards
		$riverSize = 4;
		for( $i = 0; $i < $riverSize; $i++ ) {
			$token = array_shift($resourceCardDeck);
			if( !$token ) {
				throw new RuntimeException('No token to complete resource cards river');
			}
			$token->setContainer(TOKEN_CONTAINER_BOARD_RIVER);
			$token->setPosition($i);
			$this->entityService->update($token);
		}
		// Deal 4 cards to each player
		$handSize = 4;
		$this->dealCardsToPlayers($resourceCardDeck, $handSize);
		// Save Resource cards deck
		$this->updateCardDeck($resourceCardDeck);

		// *** Pigeon Cards ***
		// Shuffle Pigeon cards deck
		$pigeonCardDeck = $tokens[TOKEN_TYPE_PIGEON_CARD];
		shuffle($pigeonCardDeck);
		// Save Pigeon cards deck
		$this->updateCardDeck($pigeonCardDeck);

		// *** Objective Cards ***
		// Shuffle Objective cards
		$objectiveCardDeck = $tokens[TOKEN_TYPE_OBJECTIVE_CARD];
		shuffle($objectiveCardDeck);
		$this->dealCardsToPlayers($objectiveCardDeck, 2, TOKEN_CONTAINER_PLAYER_BOARD);// Should match getPendingPlayerObjectiveCards()
		// Save Objective cards deck
		$this->updateCardDeck($objectiveCardDeck);

		// *** Players' Terraces ***
		// Deal terraces to players on their own board
		$terraceLimit = 20;
		$terraces = [];
		$categories = $this->game->restaurantCategories;
		foreach( $tokens[TOKEN_TYPE_TERRACE] as $token ) {
			if( !isset($terraces[$token->getPlayerId()]) ) {
				$terraces[$token->getPlayerId()] = (object) ['category' => RESTAURANT_CATEGORY_1, 'count' => 0, 'more' => 1];
			}
			$playerTerracing = &$terraces[$token->getPlayerId()];
			if( !$playerTerracing->more ) {
				throw new RuntimeException('We placed all terrace of player, is there too much ones ?');
			}
			// Set current
			$token->setContainer(TOKEN_CONTAINER_PLAYER_BOARD);
			$token->setPosition(($playerTerracing->category - 1) * $terraceLimit + $playerTerracing->count);
			// Calculate next
			$playerTerracing->count++;
			//			SetupLogger::get()->log(sprintf('dealTokens() - Placed count %d against %d of category %s at pos %d',
			//				$playerTerracing->count, $categories[$playerTerracing->category]['playerTotal'], $playerTerracing->category, $token->getPosition()));
			if( $playerTerracing->count >= $categories[$playerTerracing->category]['playerTotal'] ) {
				//				SetupLogger::get()->log('dealTokens() - Next category');
				$playerTerracing->category++;// Categories must be incremental
				$playerTerracing->count = 0;
				$playerTerracing->more = $playerTerracing->category <= RESTAURANT_CATEGORY_4;
			}
			$this->entityService->update($token);
		}

		$this->entityService->applyBatching();
		//		//$this->logger->log(sprintf('Dealt all tokens'));
	}
	
	private function createTokens() {
		$this->entityService->startBatching();
		$players = $this->app->getPlayers();
		//		//$this->logger->log('players', $players);

		// Resource Cards
		$resourceCardCount = 0;
		foreach( $this->game->resourceCards as $variantId => $card ) {
			for( $i = 0; $i < $card['total']; $i++ ) {
				$token = new Token();
				$token->setContainer(TOKEN_CONTAINER_BOX);
				$token->setType(TOKEN_TYPE_RESOURCE_CARD);
				$token->setVariant($variantId);
				$this->entityService->insert($token);
				$resourceCardCount++;
			}
		}
		//		//$this->logger->log(sprintf('Create %d resource cards', $resourceCardCount));

		// Restaurants
		$restaurantCount = 0;
		foreach( $this->game->restaurants as $variantId => $restaurant ) {
			for( $i = 0; $i < $restaurant['total']; $i++ ) {
				$token = new Token();
				$token->setContainer(TOKEN_CONTAINER_BOX);
				$token->setType(TOKEN_TYPE_RESTAURANT);
				$token->setVariant($variantId);
				$this->entityService->insert($token);
				$restaurantCount++;
			}
		}
		//		//$this->logger->log(sprintf('Create %d restaurants', $restaurantCount));

		// Properties (by player)
		$propertyCount = 0;
		foreach( $this->game->restaurants as $variantId => $restaurant ) {
			foreach( $players as $player ) {
				for( $i = 0; $i < $restaurant['total']; $i++ ) {
					$token = new Token();
					$token->setContainer(TOKEN_CONTAINER_PLAYER_BOARD);
					$token->setType(TOKEN_TYPE_PROPERTY);
					$token->setVariant($variantId);
					$token->setPlayer($player);
					$this->entityService->insert($token);
					$propertyCount++;
				}
			}
		}
		//		//$this->logger->log(sprintf('Create %d properties', $propertyCount));

		// Terraces (by player)
		// 16 + 16 + 12 + 8 = 52
		$terraceCount = 0;
		foreach( $players as $player ) {
			for( $i = 0; $i < 52; $i++ ) {
				$token = new Token();
				$token->setContainer(TOKEN_CONTAINER_PLAYER_BOARD);
				$token->setType(TOKEN_TYPE_TERRACE);
				$token->setPlayer($player);
				$this->entityService->insert($token);
				$terraceCount++;
			}
		}
		//		//$this->logger->log(sprintf('Create %d terraces', $terraceCount));

		// Pigeon cards
		$pigeonCardCount = 0;
		foreach( $this->game->pigeonCards as $variantId => $card ) {
			for( $i = 0; $i < $card['total']; $i++ ) {
				$token = new Token();
				$token->setContainer(TOKEN_CONTAINER_BOX);
				$token->setType(TOKEN_TYPE_PIGEON_CARD);
				$token->setVariant($variantId);
				$this->entityService->insert($token);
				$pigeonCardCount++;
			}
		}
		//		//$this->logger->log(sprintf('Create %d pigeon cards', $pigeonCardCount));

		// Objective cards
		$objectiveCardCount = 0;
		foreach( $this->game->objectiveCards as $variantId => $card ) {
			$token = new Token();
			$token->setContainer(TOKEN_CONTAINER_BOX);
			$token->setType(TOKEN_TYPE_OBJECTIVE_CARD);
			$token->setVariant($variantId);
			$this->entityService->insert($token);
			$objectiveCardCount++;
		}
		//		//$this->logger->log(sprintf('Create %d objective cards', $objectiveCardCount));

		// Majority Card
		// We need only one
		{
			// Get random variant
			$variantId = mt_rand(0, count($this->game->majorityCards) - 1);
			// Create token
			$token = new Token();
			$token->setContainer(TOKEN_CONTAINER_BOARD_RIVER);
			$token->setType(TOKEN_TYPE_MAJORITY_CARD);
			$token->setVariant($variantId);
			$this->entityService->insert($token);
			// Store token ID as selected majority card
			$this->game->setGameStateInitialValue(DinnerInParis::GLOBAL_MAJORITY_CARD, $token->getId());
			//			//$this->logger->log(sprintf('Create 1 majority card (#%d)', $token->getId()));
		}

		$this->entityService->applyBatching();
		//		//$this->logger->log(sprintf('Created %d tokens',
		//			$resourceCardCount + $restaurantCount + $propertyCount + $terraceCount + $pigeonCardCount + $objectiveCardCount + 1));

		// No initialize majorities
		$this->app->loadTokens();
		$this->app->updatePlayerMajorities();
	}
	
}
