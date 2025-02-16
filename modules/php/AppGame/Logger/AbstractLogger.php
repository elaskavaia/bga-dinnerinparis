<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Logger;

use DinnerInParis;
use Throwable;

abstract class AbstractLogger {
	
	/** @var DinnerInParis */
	protected $table;
	
	/** @var array */
	protected $stackExcludedFiles = [__FILE__];
	
	/**
	 * AbstractLogger constructor
	 *
	 * @param DinnerInParis $table
	 */
	protected function __construct($table) {
		$this->table = $table;
		$this->initialize();
	}
	
	public function __invoke(...$arguments) {
		$this->log(...$arguments);
	}
	
	
	public function error(Throwable $error) {
		$this->log(['class' => get_class($error), 'message' => $error->getMessage(), 'code' => $error->getCode(), 'file' => $error->getFile(), 'line' => $error->getLine()]);
	}
	
	public function initialize() {
	}
	
	public function prepare() {
	}
	
	protected abstract function logRaw(string $log);
	
	public function log(...$messages) {
		$this->prepare();
		foreach( $messages as $message ) {
			// $this->logRaw($this->formatLog($message));
		}
	}
	
	public function formatLog($message): string {
		return sprintf("[%s] [%d] [%s] %s [%s]\n", date('c'), $this->table->table_id, $this->getType($message), json_encode($message), $this->getCallInfo());
	}
	
	protected function getType($value): string {
		$type = gettype($value);
		if( $type === 'object' ) {
			return get_class($value);
		}
		if( $type === 'array' ) {
			return sprintf('%s(%d)', $type, count($value));
		}
		
		return $type;
	}
	
	protected function getCallInfo(): string {
		$trace = null;
		$nextTrace = null;
		foreach( debug_backtrace() as $loopTrace ) {
			if( $trace ) {
				$nextTrace = $loopTrace;
				break;
			}
			if( !in_array($loopTrace['file'], $this->stackExcludedFiles) ) {
				// First out of this file
				$trace = $loopTrace;
			}
		}
		
		return sprintf('%s:%d:%s', preg_replace('#.*/modules/php#', '', $trace['file']), $trace['line'], $nextTrace['function'] ?? 'NONE');
	}
	
}
