<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Service;

use APP_DbObject;
use AppGame\Entity\AbstractEntity;
use DateTimeInterface;
use RuntimeException;

class OrmService {
	
	public static $instance = null;
	
	/** @var APP_DbObject */
	private $databaseManager;
	
	private function __construct(APP_DbObject $databaseManager) {
		$this->databaseManager = $databaseManager;
	}
	
	public function getAlternativeClass(string $class, array $data): string {
		if( method_exists($class, 'getAlternativeClass') ) {
			// Alternative class must inherits he base $class
			// You should also specify getEntityTable()
			$class = $class::getAlternativeClass($data);
		}
		
		return $class;
	}
	
	public function create(string $class, array $data): AbstractEntity {
		/** @var AbstractEntity $entity */
		$class = $this->getAlternativeClass($class, $data);
		$entity = new $class();
		$this->format($entity, $data);
		
		return $entity;
	}
	
	/**
	 * Format SQL data into AbstractEntity
	 *
	 * @param AbstractEntity $entity
	 * @param array $data
	 * @return AbstractEntity
	 */
	public function format(AbstractEntity $entity, array $data): AbstractEntity {
		$mapping = $entity->getMapping();
		foreach( $data as $key => $value ) {
			if( !isset($mapping[$key]) ) {
				// Ignore unknown field
				continue;
			}
			call_user_func([$entity, 'set' . $mapping[$key]], $value);
		}
		
		return $entity;
	}
	
	/**
	 * Parse AbstractEntity into SQL data
	 *
	 * @param AbstractEntity $entity
	 * @return array
	 */
	public function parse(AbstractEntity $entity): array {
		$data = [];
		$mapping = $entity->getMapping();
		foreach( $mapping as $sqlField => $property ) {
			$data[$sqlField] = $this->parsePropertyValue($entity, $property);
		}
		
		return $data;
	}
	
	public function parsePropertyValue($entity, $property): string {
		if( method_exists($entity, 'format' . $property) ) {
			$value = call_user_func([$entity, 'format' . $property]);
		} elseif( method_exists($entity, 'is' . $property) ) {
			$value = call_user_func([$entity, 'is' . $property]);
		} else {
			$value = call_user_func([$entity, 'get' . $property]);
		}
		
		return $this->formatSqlValue($value);
	}
	
	/**
	 * Return well formatted SQL values with quotes for a string, value is escaped
	 * Reverse methods are in AbstractEntity
	 *
	 * @param $value
	 * @return string
	 */
	public function formatSqlValue($value): string {
		if( $value === null ) {
			return 'NULL';
		}
		if( is_bool($value) ) {
			return sprintf('"%d"', intval($value));
		}
		if( is_array($value) ) {
			$value = json_encode($value);
		}
		if( $value instanceof DateTimeInterface ) {
			$value = $value->format('Y-m-d H:i:s');
		}
		if( is_scalar($value) ) {
			return sprintf('"%s"', $this->databaseManager->escapeStringForDB($value));
		}
		throw new RuntimeException(sprintf('Unknown value [%s] "%s"', gettype($value), $value));
	}
	
	/**
	 * @param APP_DbObject $databaseManager
	 * @return static
	 */
	public static function instantiate(APP_DbObject $databaseManager) {
		if( !static::$instance ) {
			static::$instance = new static($databaseManager);
		}
		
		return static::$instance;
	}
	
	/**
	 * @return static
	 */
	public static function get() {
		return static::$instance;
	}
	
}
