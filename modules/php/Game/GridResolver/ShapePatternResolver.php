<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game\GridResolver;

use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Game\GameTable;
use \Bga\Games\DinnerInParis\Service\GeometryService;

class ShapePatternResolver extends AbstractGridResolver {
	
	/** @var array */
	protected $pattern;
	
	/** @var GeometryService */
	protected $geometryService;
	
	/**
	 * TerraceAroundDecorResolver constructor
	 *
	 * @param GameTable $table
	 * @param Player $player
	 * @param array $patternTable
	 */
	public function __construct(GameTable $table, Player $player, array $patternTable) {
		parent::__construct($table, $player);
		$this->geometryService = new GeometryService();
		$this->pattern = $this->geometryService->formatPattern($patternTable);
	}
	
	/**
	 * Minimal count around all decor of type
	 *
	 * @return int[][][]|null
	 */
	public function getOneSolution(): ?array {
		$points = $this->getPlayerTerracePoints();
//		BgaLogger::get()->log(sprintf('getOneSolution() - pattern "%s"', json_encode($this->pattern)));
		$solutions = $this->geometryService->getMatchingPatternPoints($points, $this->pattern, 1);
		
		return $solutions ? $solutions[0] : null;
	}
	
}
