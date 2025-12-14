<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game\ObjectiveResolver;

use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Game\GameTable;
//use \Bga\Games\DinnerInParis\Logger\BgaLogger;

class QuarterMinimalObjectiveResolver extends AbstractObjectiveResolver {
	
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
		$quarters = [
			QUARTER_NORTH_WEST => 0,
			QUARTER_NORTH_EAST => 0,
			QUARTER_SOUTH_WEST => 0,
			QUARTER_SOUTH_EAST => 0,
		];
		$grid = $this->table->getGrid();
		$terraces = $this->table->getPlayerTerraces($this->player);
		foreach( $terraces as $terrace ) {
			$quarter = $grid->getTerraceQuarter($terrace);
			$quarters[$quarter]++;
		}
		$minimal = min($quarters);
		$this->solution = [
			'minimal'  => $minimal,
			'quarters' => $quarters,
		];
		$this->completed = $minimal >= $this->required;
	}
	
}
