<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Core\Orm;

use AppGame\Entity\AbstractEntity;
use AppGame\Service\EntityService;
use Iterator;
use mysqli_result;

class EntityIterator implements Iterator {
	
	/** @var EntityService */
	private $entityService;
	
	/** @var string */
	private $class;
	
	/** @var mysqli_result */
	private $mysqliResult;
	
	/** @var AbstractEntity|null */
	private $row = null;
	
	/** @var int */
	private $position = 0;
	
	/** @var int */
	private $fetchMode = EntityService::FETCH_OBJECT;
	
	public static $log = false;
	
	public function __construct(EntityService $entityService, string $class, mysqli_result $mysqliResult) {
		$this->entityService = $entityService;
		$this->class = $class;
		$this->mysqliResult = $mysqliResult;
	}
	
	public function __destruct() {
		$this->mysqliResult->free();
	}
	
	public function current() {
		return $this->row;
	}
	
	public function next() {
		$data = $this->mysqliResult->fetch_assoc();
		//		if(static::$log) {
		//			BgaLogger::get()->log('EntityIterator::next', $data);
		//		}
		$this->row = $data !== null ? $this->format($data) : null;
		$this->position++;
		//		if(static::$log) {
		//			BgaLogger::get()->log('EntityIterator::next - row', gettype($this->row));
		//		}
	}
	
	/**
	 * @param array $data
	 * @return AbstractEntity|array|null
	 */
	protected function format(array $data) {
		return $this->fetchMode === EntityService::FETCH_OBJECT ? $this->entityService->summon($this->class, $data) : $data;
	}
	
	public function key() {
		return $this->position;
	}
	
	public function valid(): bool {
		return !!$this->row;
	}
	
	public function rewind() {
		$this->position = 0;
		$this->mysqliResult->data_seek(0);
		$this->next();
	}
	
	/**
	 * @return int
	 */
	public function getFetchMode(): int {
		return $this->fetchMode;
	}
	
	/**
	 * @param int $fetchMode
	 */
	public function setFetchMode(int $fetchMode): void {
		$this->fetchMode = $fetchMode;
	}
	
}
