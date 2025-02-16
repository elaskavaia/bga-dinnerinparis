<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game\MajorityResolver;

use AppGame\Entity\Player;
use AppGame\Game\GameTable;
use AppGame\Service\BoardGameApp;
use RuntimeException;

abstract class AbstractMajorityResolver {
	
	/** @var BoardGameApp */
	protected $app;
	
	/** @var GameTable */
	protected $table;
	
	/** @var Player[] */
	protected $players;
	
	/** @var array|null */
	protected $result = null;
	
	/**
	 * AbstractGridResolver constructor
	 *
	 * @param GameTable $table
	 */
	public function __construct(GameTable $table) {
		$this->app = $table->getApp();
		$this->table = $table;
		$this->players = $this->app->getPlayers();
	}
	
	public function resolve() {
		if( $this->isResolved() ) {
			// Already resolved
			return;
		}
		$this->run();
		if( !$this->isResolved() ) {
			throw new RuntimeException(sprintf('Resolver "%s" must set the "result" property', get_called_class()));
		}
	}
	
	protected function run() {
		// Calculate players' score
		$scores = [];
		foreach( $this->players as $player ) {
			$playerResult = $this->resolvePlayer($player);
			if( !isset($playerResult['score']) ) {
				throw new RuntimeException(sprintf('No "score" key provided from %s::resolvePlayer', static::class));
			}
			if( !isset($scores[$playerResult['score']]) ) {
				$scores[$playerResult['score']] = [];
			}
			// If invalid, player is not participating to majority ranking
			$playerResult['valid'] = $this->isPlayerResultValid($playerResult);
			$scores[$playerResult['score']][] = [$player, $playerResult];
		}
		// Order descending (higher to lower score)
		krsort($scores);
		// Format result
		// [PLAYER_ID=>['position'=> POSITION, 'score'=>SCORE,... details]]
		$result = [];
		$position = 1;
		foreach( $scores as $playerScores ) {
			foreach( $playerScores as [$player, $playerResult] ) {
				$playerResult['position'] = $position;
				$playerResult['exaequo'] = count($playerScores) > 1;
				$result[$player->getId()] = $playerResult;
			}
			$position += count($playerScores);// 1, 2ex, 2ex, 4
		}
		$this->result = $result;
	}
	
	protected function isPlayerResultValid(array $playerResult): bool {
		return !!$playerResult['score'];
	}
	
	/**
	 * @param Player $player
	 * @return array An array with details and a mandatory score key
	 */
	protected abstract function resolvePlayer(Player $player): array;
	
	/**
	 * @return bool
	 */
	public function isResolved(): bool {
		return $this->result !== null;
	}
	
	/**
	 * @return array|null
	 */
	public function getResult(): ?array {
		return $this->result;
	}
	
}
