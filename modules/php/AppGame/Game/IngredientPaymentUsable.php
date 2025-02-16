<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game;

interface IngredientPaymentUsable {
	
	function getGives(): array;
	
	function getGiveAmount(string $resource): int;
	
}
