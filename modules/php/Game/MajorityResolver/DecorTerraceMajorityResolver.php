<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game\MajorityResolver;

use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Game\GameTable;
use \Bga\Games\DinnerInParis\Game\GridResolver\TerraceAroundDecorResolver;
//use \Bga\Games\DinnerInParis\Logger\BgaLogger;

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
