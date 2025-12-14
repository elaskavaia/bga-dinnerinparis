<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game\ObjectiveResolver;

use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Game\GameTable;
use \Bga\Games\DinnerInParis\Game\GridResolver\ShapePatternResolver;
//use \Bga\Games\DinnerInParis\Logger\BgaLogger;

class TerraceShapePatternObjectiveResolver extends AbstractObjectiveResolver {
	
	/** @var ShapePatternResolver */
	protected $resolver;
	
	/**
	 * TerraceShapePatternObjectiveResolver constructor
	 *
	 * @param GameTable $table
	 * @param Player $player
	 * @param array $pattern
	 */
	public function __construct(GameTable $table, Player $player, array $pattern) {
		parent::__construct($table, $player);
		//BgaLogger::get()->log(sprintf('Terrace shape with pattern "%s"', json_encode($pattern)));
		$this->resolver = new ShapePatternResolver($table, $player, $pattern);
	}
	
	public function run(): void {
		//		BgaLogger::get()->log(sprintf('%s::run', static::class));
		$this->solution = $this->resolver->getOneSolution();
//		BgaLogger::get()->log(sprintf('run - solution "%s"', json_encode($this->solution)));
		$this->completed = !!$this->solution;
	}
	
}
