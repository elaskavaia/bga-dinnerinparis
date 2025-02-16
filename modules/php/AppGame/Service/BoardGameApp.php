<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Service;

use AppGame\Core\Exception\UserException;
use AppGame\Entity\Player;
use AppGame\Entity\Token;
use AppGame\Game\GameTable;
//use AppGame\Logger\BgaLogger;
use DinnerInParis;
use feException;

class BoardGameApp {
	
	/** @var static */
	public static $instance = null;
	
	/** @var Player[] */
	protected $players;
	
	/** @var DinnerInParis */
	private $game;
	
	/** @var GameTable */
	private $table;
	
	/** @var EntityService */
	private $entityService;
	
	/** @var Token[] */
	private $tokens;
	
	/** @var object */
	private $config;
	
	private function __construct(DinnerInParis $game) {
		$this->game = $game;
		$this->entityService = EntityService::get();
		$this->table = new GameTable($this);
		$this->tokens = null;
		$this->config = (object) [
			'restaurantMax' => [
				1 => 15,
				2 => 11,
				3 => 13,
				4 => 15,
			],
			'majorityScore' => [
				1 => [12],
				2 => [10, 4],
				3 => [12, 6, 0],
				4 => [12, 8, 4, 0],
			],
		];
	}
	
	public function giveExtraTime(Player $player, $value = null) {
		//BgaLogger::get()->log(sprintf('Give extra time to player %s (give %s)', $player->getId(), $value ?? 'default'));
		$this->game->giveExtraTime($player->getId(), $value);
	}
	
	public function getMajorityPositionScore(int $position) {
		return $this->config->majorityScore[$this->getPlayerCount()][$position - 1];
	}
	
	/**
	 * Active player is the first to play any turn
	 *
	 * @return bool
	 */
	public function isFirstTurnPlayer(?Player $player = null): bool {
		$player = $player ?? $this->getActivePlayer();
		
		return $player->getPosition() === 1;
	}
	
	/**
	 * Active player is the last to play this turn
	 *
	 * @return bool
	 */
	public function isLastTurnPlayer(?Player $player = null): bool {
		$player = $player ?? $this->getActivePlayer();
		
		return $player->getPosition() === $this->getPlayerCount();
	}
	
	public function startGame() {
		//		SetupLogger::get()->log(sprintf('Start game with %d players', $this->getPlayerCount()));
		// Game state values
		$this->game->setGameStateInitialValue(DinnerInParis::GLOBAL_STATE_ENDING, 0);
		
		// Stats (see $stats_type in stats.inc.php)
		$statTypes = $this->game->getStatTypes();
		//		SetupLogger::get()->log(sprintf('Stat types : %s', json_encode($statTypes)));
		foreach( $statTypes['table'] as $stat => $statInfo ) {
			if( $statInfo['id'] < 10 ) {
				// Ignore not handled stats
				continue;
			}
			$this->game->initStat('table', $stat, 0);
		}
		foreach( $this->getPlayers() as $player ) {
			foreach( $statTypes['player'] as $stat => $statInfo ) {
				if( $statInfo['id'] < 10 ) {
					// Ignore not handled stats
					continue;
				}
				//				SetupLogger::get()->log(sprintf('Init player stat %s of player "%s"', $stat, $player->getEntityLabel()));
				$this->game->initStat('player', $stat, 0, $player->getId());
			}
		}
	}
	
	/**
	 * @param string $stat
	 * @param int|float|bool $value
	 * @param Player|null $player If null, this is a table stat, else a player one
	 * @return void
	 * @throws feException
	 */
	public function setStat(string $stat, $value, ?Player $player = null) {
		$this->game->setStat($value, $stat, $player ? $player->getId() : null);
	}
	
	/**
	 * @param string $stat
	 * @param Player|null $player If null, this is a table stat, else a player one
	 * @return mixed
	 * @throws feException
	 */
	public function getStat(string $stat, ?Player $player = null) {
		return $this->game->getStat($stat, $player ? $player->getId() : null);
	}
	
	public function setGameEnding() {
		$this->game->setGameStateValue(DinnerInParis::GLOBAL_STATE_ENDING, 1);
	}
	
	public function isGameEnding(): bool {
		return boolval($this->game->getGameStateValue(DinnerInParis::GLOBAL_STATE_ENDING, 1));
	}
	
	public function getEndingStats(): array {
		$stats = [];
		// Restaurant max completion
		$stats['restaurantCount'] = $this->getRestaurantMaxProgressionStats();
		// Category completion
		$stats['category'] = $this->getCategoryProgressionStats();
		// Terrace placement completion - Removed case, this could not be possible and is a heavy computation
		//		$stats['terrace'] = ['completed' => !$this->table->canPlaceMoreTerrace()];
		// Restaurant build completion
		$stats['restaurantBuild'] = ['completed' => !$this->table->canPlaceMoreRestaurant()];
		
		return $stats;
	}
	
	public function getProgression(): int {
		$stats = $this->getProgressionStats();
		//BgaLogger::get()->log(sprintf('Progression stats are %s', json_encode($stats)));
		$progression = max($stats['restaurants']['progression'], $stats['terraces'], $stats['category']['progression']);
		
		return (int) ($progression * 100);
	}
	
	public function getProgressionStats(): array {
		// max(% de restaurant, % de terrace, max(% d'avancement des 2 catégories de terrasse les plus avancées des joueurs))
		$stats = [];
		// Percent of restaurant completion
		$stats['restaurants'] = $this->getRestaurantMaxProgressionStats();
		// Percent of terrace completion
		$grid = $this->table->getGrid();
		$stats['terraces'] = $grid->getTerraceCompletion();
		// Category completion
		$stats['category'] = $this->getCategoryProgressionStats();
		
		return $stats;
	}
	
	public function getRestaurantMaxProgressionStats(): array {
		$stats = [];
		$grid = $this->table->getGrid();
		$stats['progression'] = count($grid->getTokenList(TOKEN_TYPE_RESTAURANT)) / $this->getRestaurantMax();
		$stats['completed'] = $stats['progression'] >= 1;
		
		return $stats;
	}
	
	public function getCategoryProgressionStats(): array {
		$categoryProgression = 0;
		$players = $this->getPlayers();
		$stats = [];
		foreach( $players as $player ) {
			$stats['players'][$player->getId()] = ['category' => []];
			$playerCatProgressions = [];
			foreach( range(1, 4) as $category ) {
				$terraceRiver = $this->table->getPlayerCategoryTerraceRiver($player, $category);
				$progression = 1 - $terraceRiver->getCompletion();
				$playerCatProgressions[] = $progression;
				$stats['players'][$player->getId()]['category'][$category] = $progression;
			}
			rsort($playerCatProgressions);
			$stats['players'][$player->getId()]['categoryTotal'] = ($playerCatProgressions[0] + $playerCatProgressions[1]) / 2;
			$categoryProgression = max($categoryProgression, $stats['players'][$player->getId()]['categoryTotal']);
		}
		$stats['progression'] = $categoryProgression;
		$stats['completed'] = $stats['progression'] >= 1;
		
		return $stats;
	}
	
	public function getRestaurantMax(): int {
		return $this->config->restaurantMax[$this->getPlayerCount()];
	}
	
	public function updatePlayerMajorities() {
		$changedPlayers = $this->table->updatePlayerMajorities();
		$entityService = EntityService::get();
		$entityService->startBatching();
		// Save all players (info may change but not position)
		foreach( $this->getPlayers() as $player ) {
			//BgaLogger::get()->log(sprintf('Update player "%s" for majority changes', $player->getEntityLabel()));
			$entityService->update($player);
		}
		$entityService->applyBatching();
		// Notify only about changed users
		foreach( $changedPlayers as $player ) {
			// Notify users of player's update (majority)
			//BgaLogger::get()->log(sprintf('Notify about "%s" player majority changes', $player->getEntityLabel()));
			$this->notifyPlayerUpdate($player);
		}
	}
	
	public function getMajorityMaterial(string $majority): array {
		return $this->game->majorities[$majority];
	}
	
	public function getMajorityCardId(): int {
		return $this->game->getGameStateValue(DinnerInParis::GLOBAL_MAJORITY_CARD);
	}
	
	public function getGameValue(string $key, $default = null) {
		return $this->game->getGameStateValue($key, $default);
	}
	
	/**
	 * Set all players active, use GAME_STATE_TYPE_MULTIPLE_PLAYERS
	 *
	 * @see setPlayerInactive() Use it to deactive players one by one
	 */
	public function setAllPlayersActive() {
		$this->game->gamestate->setAllPlayersMultiactive();
	}
	
	/**
	 * Set one player inactive, waiting for others
	 *
	 * @see setAllPlayersActive() Use it to allow playing for all players
	 */
	public function setPlayerInactive(Player $player, string $nextState) {
		$this->game->gamestate->setPlayerNonMultiactive($player->getId(), $nextState);
	}
	
	public function getBgaEnvironment(): string {
		return $this->game->getBgaEnvironment();
	}
	
	public function allowDebug(): bool {
		return $this->isDev();
	}
	
	public function isDev(): bool {
		return $this->getBgaEnvironment() === 'studio';
	}
	
	public function isProd(): bool {
		return !$this->isDev();
	}
	
	public function getTileMaterials(): array {
		return $this->game->tiles;
	}
	
	public function getRestaurantMaterials(): array {
		return $this->game->restaurants;
	}
	
	public function getRestaurantCategoryMaterials(): array {
		return $this->game->restaurantCategories;
	}
	
	public function getObjectiveCardMaterials(): array {
		return $this->game->objectiveCards;
	}
	
	public function getPigeonCardMaterials(): array {
		return $this->game->pigeonCards;
	}
	
	public function getGame(): DinnerInParis {
		return $this->game;
	}
	
	public function sendSystemMessage($message, array $parameters = []): void {
		$this->game->notifyAllPlayers('systemMessage', $message, $parameters);
	}
	
	protected function filterPrivateParameters(string $message, array $parameters): array {
		$outputParameters = [];
		foreach( $parameters as $key => $value ) {
			if( strpos($message, sprintf('${%s}', $key)) !== false ) {
				$outputParameters[$key] = $value;
			}
		}
		
		return $outputParameters;
	}
	
	public function sendSystemMessageToActivePlayer(string $activePlayerMessage, string $commonMessage = '', array $parameters = []): void {
		$commonMessage = $commonMessage ?: $activePlayerMessage;
		$commonParameters = $this->filterPrivateParameters($commonMessage, $parameters);// Remove private parameters
		foreach( $this->players as $player ) {
			$isActive = $this->isPlayerActive($player);
			$message = $isActive ? $activePlayerMessage : $commonMessage;
			$messageParameters = $isActive ? $parameters : $commonParameters;
			$this->game->notifyPlayer($player->getId(), 'systemMessage', $message, $messageParameters);
		}
	}
	
	/**
	 * Send generic notification to everybody, even spectators.
	 * Players with changes they should see, receive a second notification with private info
	 * Current active player is the only receiving the $activePlayerMessage
	 *
	 * @param array $tokens
	 * @param string $commonMessage Empty string to no log
	 * @param array $parameters
	 * @param string|null $activePlayerMessage
	 * @return void
	 * @throws feException
	 */
	public function notifyTokenUpdate(array $tokens, string $commonMessage = '', array $parameters = [], ?string $activePlayerMessage = null) {
		$activePlayer = $this->getCurrentActivePlayer();
		$sendActivePlayerMessage = $activePlayer && $activePlayerMessage;
		// Common notification (all tokens as non-owner)
		$notificationParameters = $parameters;
		if( $sendActivePlayerMessage ) {
			// Request to client to ignore generic notification for active player
			// https://en.doc.boardgamearena.com/Main_game_logic:_yourgamename.game.php#Excluding_some_players
			// https://en.doc.boardgamearena.com/Game_interface_logic:_yourgamename.js#Ignoring_notifications
			$notificationParameters['exclude_player_id'] = $activePlayer->getId();
		}
		$notificationParameters['items'] = $this->formatTokensFor($tokens);
		$this->game->notifyAllPlayers('tokensUpdate', $commonMessage, $notificationParameters);
		// Specific notification (only tokens for owner)
		// No message, no other parameter
		foreach( $this->players as $player ) {
			$message = $sendActivePlayerMessage && $player->equals($activePlayer) ? $activePlayerMessage : '';
			$items = $this->formatTokensFor($tokens, $player, true);
			if( $items ) {
				// Send only if there is private info
				$notificationParameters = $parameters;
				$notificationParameters['items'] = $items;
				$this->game->notifyPlayer($player->getId(), 'tokensUpdate', $message, $notificationParameters);
			}
			// We ignore the case the current active player has a message but no private data, it should never happen
		}
	}
	
	/**
	 * @param Player $player
	 * @param string $message
	 * @param array $parameters
	 * @return void
	 */
	public function notifyPlayerUpdate(Player $player, string $message = '', array $parameters = []) {
		//		BgaLogger::get()->log(sprintf('notifyPlayerUpdate(%s)', $player->getEntityLabel()));
		$parameters['item'] = $player->jsonSerialize();
		$this->game->notifyAllPlayers('playerUpdate', $message, $parameters);
	}
	
	public function notifyGameUpdate(array $data, string $message = '', array $parameters = []) {
		$parameters['item'] = $data;
		$this->game->notifyAllPlayers('gameUpdate', $message, $parameters);
	}
	
	public function isPlayerActive(Player $player): bool {
		return $this->getActivePlayer()->equals($player);
	}
	
	public function getConfigValue(string $key, $default = null) {
		return $this->game->getGameStateValue($key, $default);
	}
	
	public function useStateAction(string $state, ?Player $player = null): void {
		//		BgaLogger::get()->log(sprintf('useStateAction(%s)', $state));
		if( $player ) {
			// https://en.doc.boardgamearena.com/Your_game_state_machine:_states.inc.php#Implementation_Notes
			$this->game->gamestate->nextPrivateState($player->getId(), $state);
		} else {
			$this->game->gamestate->nextState($state);
		}
	}
	
	public function nextPlayer(): Player {
		$this->game->activeNextPlayer();
		
		return $this->getActivePlayer();
	}
	
	public function getTurnNumber(): int {
		return $this->getConfigValue('playerturn_nbr');
	}
	
	public function getNextMove(): int {
		return $this->getConfigValue('next_move_id');
	}
	
	public function getTokenMaterial(Token $token): array {
		switch( $token->getType() ) {
			case TOKEN_TYPE_RESOURCE_CARD:
				return $this->game->resourceCards[$token->getVariant()];
			case TOKEN_TYPE_RESTAURANT:
				return $this->game->restaurants[$token->getVariant()];
			case TOKEN_TYPE_OBJECTIVE_CARD:
				return $this->game->objectiveCards[$token->getVariant()];
			case TOKEN_TYPE_PIGEON_CARD:
				return $this->game->pigeonCards[$token->getVariant()];
			case TOKEN_TYPE_MAJORITY_CARD:
				return $this->game->majorityCards[$token->getVariant()];
		}
		throw new UserException(sprintf('Error getting material, unknown "%s" token type', $token->getType()));
	}
	
	public function getCurrentActivePlayer(): ?Player {
		$currentPlayer = $this->getCurrentPlayer();
		if( !$currentPlayer ) {
			return null;
		}
		foreach( $this->getActivePlayers() as $activePlayer ) {
			if( $activePlayer->equals($currentPlayer) ) {
				return $activePlayer;
			}
		}
		
		return null;
	}
	
	public function isCurrentPlayerActive(): bool {
		return $this->getActivePlayer()->getId() === $this->game->getCurrentPlayerIdOrNull();
	}
	
	public function getPlayer(int $id): ?Player {
		return $this->players[$id] ?? null;
	}
	
	public function getCurrentPlayer(): ?Player {
		$playerId = $this->game->getCurrentPlayerIdOrNull();
		
		return $playerId ? $this->getPlayer($playerId) : null;
	}
	
	/**
	 * @return Player
	 */
	public function getActivePlayer(): Player {
		$playerId = (int) $this->game->getActivePlayerId();
		$player = $this->getPlayer($playerId);
		if( !$player ) {
			throw new UserException(sprintf('Unknown active player "%d"', $playerId));
		}
		
		return $player;
	}
	
	/**
	 * @return Player[]
	 */
	public function getActivePlayers(): array {
		$players = [];
		foreach( $this->getPlayers() as $player ) {
			if( $player->isMultiActive() ) {
				$players[] = $player;
			}
		}
		
		return $players;
	}
	
	public function getToken(int $id): ?Token {
		return $this->tokens[$id] ?? null;
	}
	
	public function loadTokens(): array {
		$iterator = $this->entityService->findMultiple(Token::class, [], [['type'], ['container'], ['position']]);
		$this->tokens = [];
		$typeTokens = [];
		foreach( $iterator as $token ) {
			// Overall reference of all tokens
			$this->tokens[$token->getId()] = $token;
			// Type tokens for table
			if( !isset($typeTokens[$token->getType()]) ) {
				$typeTokens[$token->getType()] = [];
			}
			$typeTokens[$token->getType()][] = $token;
		}
		$this->table->initializeTypeTokens($typeTokens);
		
		return $typeTokens;
	}
	
	public function load() {
		/** @var Player[] $players */
		$players = iterator_to_array($this->entityService->findMultiple(Player::class, [], ['player_no']));
		$this->players = [];
		foreach( $players as $player ) {
			$this->players[$player->getId()] = $player;
		}
		$this->loadTokens();
	}
	
	public function getPlayerCount(): int {
		return count($this->players);
	}
	
	public function initialize() {
		if( !$this->players ) {
			$this->load();
			$playerCount = $this->getPlayerCount();
			$this->table->setGridModel($playerCount > 1 ? $playerCount : 4);// 2, 3, 4
		}
	}
	
	/**
	 * @return Token[]
	 */
	public function getTokens(): array {
		return $this->tokens;
	}
	
	/**
	 * @return Player[]
	 */
	public function getPlayers(): array {
		return $this->players;
	}
	
	/**
	 * @return GameTable
	 */
	public function getTable(): ?GameTable {
		return $this->table;
	}
	
	public static function instantiate(DinnerInParis $table) {
		static::$instance = new static($table);
		static::$instance->initialize();
	}
	
	public static function get(): self {
		return static::$instance;
	}
	
	public function formatTokensFor(array $tokens, ?Player $player = null, bool $visibleOnly = false): array {
		$formattedTokens = [];
		foreach( $tokens as $token ) {
			$formattedToken = $this->formatTokenFor($token, $player, $visibleOnly);
			if( $formattedToken ) {
				$formattedTokens[] = $formattedToken;
			}
		}
		
		return $formattedTokens;
	}
	
	public function formatTokenFor(Token $token, ?Player $player = null, bool $visibleOnly = false): ?array {
		$playerVisible = $token->isPlayerVisible($player);
		if( $visibleOnly && !$playerVisible ) {
			return null;
		}
		$tokenData = $token->jsonSerialize();
		if( !$playerVisible ) {
			unset($tokenData['variant']);
		}
		$tokenData['visible'] = $playerVisible;
		
		return $tokenData;
	}
	
}
