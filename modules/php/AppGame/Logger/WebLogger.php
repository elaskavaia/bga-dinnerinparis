<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Logger;

use Table;

class WebLogger extends AbstractLogger {
	
	/** @var self */
	private static $logger;
	
	public static function instantiate(Table $table) {
		static::$logger = new WebLogger($table);
	}
	
	/**
	 * @return self
	 */
	public static function get(): self {
		return static::$logger;
	}
	
	protected function logRaw($log) {
		var_dump($log);
	}
	
}
