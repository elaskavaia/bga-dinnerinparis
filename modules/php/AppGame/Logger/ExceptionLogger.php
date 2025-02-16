<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Logger;

use AppGame\Service\BoardGameApp;
use RuntimeException;
use Table;

class ExceptionLogger extends AbstractLogger {
	
	/** @var self */
	private static $logger;
	
	private $buffer;
	
	public function initialize() {
		$this->stackExcludedFiles[] = __FILE__;
	}
	
	public static function instantiate(Table $table) {
		if( static::$logger ) {
			return;
		}
		static::$logger = new ExceptionLogger($table);
	}
	
	public function prepare() {
		if( !isset($this->buffer) ) {
			//			$this->buffer = [];
			$this->buffer = '';
		}
	}
	
	public function throw() {
		//		throw new RuntimeException(json_encode($this->buffer));
		throw new RuntimeException($this->buffer);
	}
	
	protected function logRaw(string $log) {
		//		$this->buffer[] = sprintf('Previous[%d] - ', count($this->buffer)).$log;
		$this->buffer .= $log . "\n";
	}
	
	/**
	 * @return self
	 */
	public static function get(): self {
		if( !static::$logger ) {
			self::instantiate(BoardGameApp::get()->getGame());
		}
		
		return static::$logger;
	}
	
}
