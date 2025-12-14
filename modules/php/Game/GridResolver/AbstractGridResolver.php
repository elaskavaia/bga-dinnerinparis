<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game\GridResolver;

use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Game\GameTable;

class AbstractGridResolver {
	
	/** @var GameTable */
	protected $table;
	
	/** @var Player */
	protected $player;
	
	/**
	 * AbstractGridResolver constructor
	 *
	 * @param GameTable $table
	 * @param Player $player
	 */
	public function __construct(GameTable $table, Player $player) {
		$this->table = $table;
		$this->player = $player;
	}
	
	protected function getPlayerTerracePoints(): array {
		return $this->table->getTokenPoints($this->table->getPlayerTerraces($this->player));
	}
	
	
}
