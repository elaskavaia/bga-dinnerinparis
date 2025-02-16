<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Logger;

use AppGame\Service\BoardGameApp;
use Table;

class BgaLogger extends AbstractLogger {
	
	/** @var self */
	private static $logger;
	
	public function initialize() {
		$this->stackExcludedFiles[] = __FILE__;
	}
	
	public static function instantiate(Table $table) {
		static::$logger = new BgaLogger($table);
	}
	
	/**
	 * @return self
	 */
	public static function get(): self {
		return static::$logger;
	}
	
	protected function logRaw($log) {
		$this->table->trace($log);
	}
	
	public function formatLog($message): string {
		$app = BoardGameApp::get();
		$turn = $nextMove = null;
		if( $app ) {
			$turn = $app->getTurnNumber();
			$nextMove = $app->getNextMove();
		}
		
		return sprintf("[%s][%s] %s [%s] ", $this->getType($message), ($turn ? 'M' . $turn : 'NT') . ':' . ($nextMove ?: 0), json_encode($message), $this->getCallInfo());
	}
	
	public function eraseLogs() {
		// Nope, just do nothing
	}
	
}
