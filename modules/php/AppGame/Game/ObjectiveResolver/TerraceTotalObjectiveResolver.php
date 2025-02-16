<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game\ObjectiveResolver;

use AppGame\Entity\Player;
use AppGame\Game\GameTable;
//use AppGame\Logger\BgaLogger;

class TerraceTotalObjectiveResolver extends AbstractObjectiveResolver {
	
	/**
	 * @var int
	 */
	protected $required;
	
	/**
	 * TerraceAroundDecorResolver constructor
	 *
	 * @param GameTable $table
	 * @param Player $player
	 * @param int $required
	 */
	public function __construct(GameTable $table, Player $player, int $required) {
		parent::__construct($table, $player);
		$this->required = $required;
	}
	
	public function run(): void {
		//BgaLogger::get()->log(sprintf('%s::run', static::class));
		$total = count($this->table->getPlayerTerraces($this->player));
		$this->solution = ['total' => $total];
		$this->completed = $total >= $this->required;
	}
	
}
