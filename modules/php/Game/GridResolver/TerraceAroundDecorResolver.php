<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game\GridResolver;

use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Game\GameTable;
use \Bga\Games\DinnerInParis\Game\PointGroup;
use \Bga\Games\DinnerInParis\Service\GeometryService;

class TerraceAroundDecorResolver extends AbstractGridResolver {
	
	const ALL_DECORS = [TILE_FLOWER_BED, TILE_BAND, TILE_LAMP, TILE_FOUNTAIN];
	
	/** @var string */
	protected $type;
	
	/** @var GeometryService */
	protected $geometryService;
	
	/**
	 * TerraceAroundDecorResolver constructor
	 *
	 * @param GameTable $table
	 * @param Player $player
	 * @param string $type
	 */
	public function __construct(GameTable $table, Player $player, string $type) {
		parent::__construct($table, $player);
		$this->type = $type;
		$this->geometryService = new GeometryService();
	}
	
	public function countDecorGroupPlayerTerrace(PointGroup $decorGroup, array $points): int {
		return count($this->geometryService->getGroupAroundPoints($decorGroup, $points, true));
	}
	
	/**
	 * Minimal count around all decor of type
	 *
	 * @return int
	 */
	public function countMinimal(): int {
		$decorGroups = $this->table->getGrid()->getDecorPointGroups($this->type);
		//		BgaLogger::get()->log(sprintf('countMinimalFromAll(type=%s) - $decorGroups "%s"', $this->type, json_encode($decorGroups)));
		$terracePoints = $this->getPlayerTerracePoints();
		$min = INF;
		// For each type decor group
		foreach( $decorGroups as $group ) {
			// Compare minimal value
			$count = $this->countDecorGroupPlayerTerrace($group, $terracePoints);
			//			BgaLogger::get()->log(sprintf('countMinimalFromAll() = %d', $count));
			$min = min($min, $count);
		}
		
		return $min === INF ? 0 : $min;
	}
	
	/**
	 * Count total around all decor of type
	 *
	 * @return int
	 */
	public function countTotal(): int {
		$decorGroups = $this->table->getGrid()->getDecorPointGroups($this->type);
		$terracePoints = $this->getPlayerTerracePoints();
		$total = 0;
		// For each type decor group
		foreach( $decorGroups as $group ) {
			// Add to total
			$total += $this->countDecorGroupPlayerTerrace($group, $terracePoints);
		}
		
		return $total;
	}
	
}
