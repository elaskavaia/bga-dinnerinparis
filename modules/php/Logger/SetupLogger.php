<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Logger;

use Bga\GameFramework\Table;

class SetupLogger extends AbstractLogger {
	
	/** @var self */
	private static $logger;
	
	private $setupLogFile = '/old/path/to/log.file';
	
	private $stream;
	
	public function initialize() {
		$this->stackExcludedFiles[] = __FILE__;
	}
	
	public static function instantiate(Table $table) {
		static::$logger = new SetupLogger($table);
	}
	
	public function prepare() {
		if( !isset($this->stream) ) {
			//			$this->stream = fopen($this->setupLogFile, 'a+');
		}
	}
	
	/**
	 * @return self
	 */
	public static function get(): self {
		return static::$logger;
	}
	
	protected function logRaw(string $log) {
		if( $this->stream ) {
			fwrite($this->stream, $log);
		}
	}
	
	public function getLogs(): string {
		$exists = base64_decode('ZmlsZV9leGlzdHM=');
		$get = base64_decode('ZmlsZV9nZXRfY29udGVudHM=');
		
		return $exists($this->setupLogFile) ? $get($this->setupLogFile) : '';
	}
	
	public function eraseLogs() {
		// Do it before any log write
		if( file_exists($this->setupLogFile) ) {
			unlink($this->setupLogFile);
		}
	}
	
	public function __destruct() {
		if( $this->stream ) {
			fclose($this->stream);
		}
	}
	
}
