<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game\ObjectiveResolver;

use AppGame\Entity\Player;
use AppGame\Game\GameTable;
//use AppGame\Logger\BgaLogger;

class AreaMinimalObjectiveResolver extends AbstractObjectiveResolver {
	
	/**
	 * @var int
	 */
	protected $required;
	
	/**
	 * @var string
	 */
	protected $area;
	
	/**
	 * AreaMinimalObjectiveResolver constructor
	 *
	 * @param GameTable $table
	 * @param Player $player
	 * @param int $required
	 * @param string $area
	 */
	public function __construct(GameTable $table, Player $player, int $required, string $area) {
		parent::__construct($table, $player);
		$this->required = $required;
		$this->area = $area;
	}
	
	public function run(): void {
		//BgaLogger::get()->log(sprintf('%s::run', static::class));
		// Calculate all areas' minimal
		$areas = $this->table->getAreaTerraces($this->player);
		// Calculate solution
		$minimal = $areas[$this->area];
		$this->solution = [
			'minimal' => $minimal,
		];
		$this->completed = $minimal >= $this->required;
	}
	
}
