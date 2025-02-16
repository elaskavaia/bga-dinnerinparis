<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game\MajorityResolver;

use AppGame\Entity\Player;
use AppGame\Game\GameTable;
use AppGame\Game\GridResolver\TerraceAroundDecorResolver;
//use AppGame\Logger\BgaLogger;

class DecorTerraceMajorityResolver extends AbstractMajorityResolver {
	
	/**
	 * @var string
	 */
	protected $decor;
	
	/**
	 * AreaMinimalObjectiveResolver constructor
	 *
	 * @param GameTable $table
	 * @param Player $player
	 * @param string $decor
	 */
	public function __construct(GameTable $table, string $decor) {
		parent::__construct($table);
		$this->decor = $decor;
	}
	
	protected function resolvePlayer(Player $player): array {
		//BgaLogger::get()->log(sprintf('%s::resolvePlayer(%s)', static::class, $player->getEntityLabel()));
		$result = [];
		
		// Calculate total terrace around decor
		$resolver = new TerraceAroundDecorResolver($this->table, $player, $this->decor);
		$result['decor'] = $resolver->countTotal();
		
		// Calculate total
		$result['score'] = array_sum($result);
		
		return $result;
	}
	
}
