<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game\MajorityResolver;

use AppGame\Entity\Player;
use AppGame\Game\GameTable;
//use AppGame\Logger\BgaLogger;

class AreaTerraceMajorityResolver extends AbstractMajorityResolver {
	
	/**
	 * @var string
	 */
	protected $area;
	
	/**
	 * AreaMinimalObjectiveResolver constructor
	 *
	 * @param GameTable $table
	 * @param Player $player
	 * @param string $area
	 */
	public function __construct(GameTable $table, string $area) {
		parent::__construct($table);
		$this->area = $area;
	}
	
	protected function resolvePlayer(Player $player): array {
		//BgaLogger::get()->log(sprintf('%s::resolvePlayer(%s)', static::class, $player->getEntityLabel()));
		$result = [];
		
		// Calculate areas - T535
		$result['areas'] = $this->table->getAreaTerraces($player);
		
		// Calculate total
		$result['score'] = $result['areas'][$this->area];
		
		return $result;
	}
	
}
