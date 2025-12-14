<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game\ObjectiveResolver;

use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Game\GameTable;
use \Bga\Games\DinnerInParis\Service\BoardGameApp;
use RuntimeException;

abstract class AbstractObjectiveResolver {
	
	/** @var BoardGameApp */
	protected $app;
	
	/** @var GameTable */
	protected $table;
	
	/** @var Player */
	protected $player;
	
	/** @var bool */
	protected $completed = null;
	
	/** @var array */
	protected $solution = null;
	
	/**
	 * AbstractGridResolver constructor
	 *
	 * @param GameTable $table
	 * @param Player $player
	 */
	public function __construct(GameTable $table, Player $player) {
		$this->app = $table->getApp();
		$this->table = $table;
		$this->player = $player;
	}
	
	public function resolve() {
		if( $this->isResolved() ) {
			// Already resolved
			return;
		}
		$this->run();
		if( !$this->isResolved() ) {
			throw new RuntimeException(sprintf('Resolver "%s" must set the "completed" property', get_called_class()));
		}
	}
	
	protected abstract function run(): void;
	
	/**
	 * @return bool
	 */
	public function isResolved(): bool {
		return $this->completed !== null;
	}
	
	/**
	 * @return bool
	 */
	public function isCompleted(): bool {
		return $this->isResolved() && $this->completed;
	}
	
	/**
	 * @return array
	 */
	public function getSolution(): ?array {
		return $this->solution;
	}
	
}
