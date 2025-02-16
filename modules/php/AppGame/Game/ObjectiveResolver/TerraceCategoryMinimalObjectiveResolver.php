<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game\ObjectiveResolver;

use AppGame\Entity\Player;
use AppGame\Game\GameTable;
//use AppGame\Logger\BgaLogger;

class TerraceCategoryMinimalObjectiveResolver extends AbstractObjectiveResolver {
	
	/**
	 * @var int
	 */
	protected $required;
	
	/**
	 * TerraceCategoryMinimalObjectiveResolver constructor
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
		// Calculate all category completion total
		$categoryCompletingTotal = 0;
		$categoryConsumption = [];
		$categories = $this->app->getRestaurantCategoryMaterials();
		foreach( $categories as $category => $categoryOptions ) {
			$terraceRiver = $this->table->getPlayerCategoryTerraceRiver($this->player, $category);
			$usedCount = count($terraceRiver->getEmptySlots());
			$categoryConsumption[$category] = $usedCount;
			if( $usedCount >= $this->required ) {
				$categoryCompletingTotal++;
			}
		}
		// Calculate solution
		$this->solution = [
			'completing' => $categoryCompletingTotal,
			'used'       => $categoryConsumption,
		];
		// All categories are completing requirements
		$this->completed = $categoryCompletingTotal == count($categoryConsumption);
	}
	
}
