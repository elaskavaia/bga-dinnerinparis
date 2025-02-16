<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game\ObjectiveResolver;

//use AppGame\Logger\BgaLogger;

class TerraceCategoryEmptyObjectiveResolver extends AbstractObjectiveResolver {
	
	public function run(): void {
		//BgaLogger::get()->log(sprintf('%s::run', static::class));
		// Calculate all category completion total
		$categoryCompletingTotal = 0;
		$categoryLeft = [];
		$categories = $this->app->getRestaurantCategoryMaterials();
		foreach( $categories as $category => $categoryOptions ) {
			$terraceRiver = $this->table->getPlayerCategoryTerraceRiver($this->player, $category);
			$leftCount = $terraceRiver->count();
			$categoryLeft[$category] = $leftCount;
			if( !$leftCount ) {
				$categoryCompletingTotal++;
			}
		}
		// Calculate solution
		$this->solution = [
			'completing' => $categoryCompletingTotal,
			'left'       => $categoryLeft,
		];
		// All categories are completing requirements
		$this->completed = $categoryCompletingTotal > 0;
	}
	
}
