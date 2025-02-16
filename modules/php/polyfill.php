<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

// Polyfill PHP 7.3
if( !function_exists("array_key_last") ) {
	/**
	 * @param array $array
	 * @return int|string|null
	 * @see https://www.php.net/manual/fr/function.array-key-last.php#123950
	 */
	function array_key_last(array $array) {
		return key(array_slice($array, -1));
	}
}
