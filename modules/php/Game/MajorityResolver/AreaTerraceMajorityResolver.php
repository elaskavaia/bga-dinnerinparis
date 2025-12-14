<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game\MajorityResolver;

use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Game\GameTable;
//use \Bga\Games\DinnerInParis\Logger\BgaLogger;

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
