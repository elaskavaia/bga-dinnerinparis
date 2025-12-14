<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Service;

use \Bga\Games\DinnerInParis\Core\Orm\EntityIterator;
use \Bga\Games\DinnerInParis\Entity\AbstractEntity;
use Bga\GameFramework\Table;
use \feException;
use \mysqli_result;
use \RuntimeException;

class EntityService {
	
	const ORDER_ASC = false;
	const ORDER_DESC = true;
	
	const STATE_INSERT = 'insert';
	const STATE_UPDATE = 'update';
	const STATE_REMOVE = 'remove';
	
	const FETCH_ASSOC = 1;
	const FETCH_OBJECT = 2;
	
	public static $instance = null;
	
	/** @var OrmService */
	private $ormService;
	
	/** @var Table */
	private $databaseManager;
	
	/** @var string[] */
	private $tables = [];
	
	/** @var array */
	private $cache = [];
	
	/** @var array */
	private $states = [];
	
	/** @var bool */
	private $batching = false;
	
	// For now, we only need batching to insert, and we can not reload from multiple inserts
	
	/** @var array|null */
	private $batchingEntities;
	
	private function __construct(Table $databaseManager) {
		$this->ormService = OrmService::instantiate($databaseManager);
		$this->databaseManager = $databaseManager;
		$this->batchingEntities = [];
	}
	
	public function startBatching() {
		if( $this->batching ) {
			// Ignore already batching
			return;
		}
		$this->batching = true;
		$this->batchingEntities = [];
		//$this->databaseManager->DbStartTransaction();
	}
	
	public function setBatchingState(AbstractEntity $entity, string $state) {
		$this->batchingEntities[spl_object_id($entity)] = [$entity, $state];
	}
	
	public function cancelBatching() {
		if( !$this->batching ) {
			// Ignore not batching
			return;
		}
		//$this->databaseManager->DbRollback();
	}
	
	public function applyBatching() {
		if( !$this->batching ) {
			// Ignore not batching
			return;
		}
		// Commit
		//$this->databaseManager->DbCommit();
		// Apply db changes to entities
		$reloadIds = [];
		$currentEntities = [];
		/**
		 * @var AbstractEntity $entity
		 * @var string $state
		 */
		foreach( $this->batchingEntities as [$entity, $state] ) {
			if( $state === self::STATE_INSERT || $state === self::STATE_UPDATE ) {
				$this->entity($entity, $class, $id);
				if( !isset($reloadIds[$class]) ) {
					$reloadIds[$class] = [];
					$currentEntities[$class] = [];
				}
				$reloadIds[$class][] = $id;
				$currentEntities[$class][$id] = $entity;
			}
		}
		if( $reloadIds ) {
			foreach( $reloadIds as $class => $ids ) {
				$entitiesIterator = $this->findMultiple($class, ['id', 'IN', $ids]);
				$entitiesIterator->setFetchMode(self::FETCH_ASSOC);
				foreach( $entitiesIterator as $entityData ) {
					$entity = $this->ormService->format($currentEntities[$class][$entityData['id']], $entityData);
					$this->saveEntityState($entity);
				}
			}
		}
		// Reset
		$this->batching = false;
		$this->batchingEntities = [];
	}
	
	public function getEntityTable(string $class) {
		if( !isset($this->tables[$class]) ) {
			/** @var AbstractEntity $class */
			$table = call_user_func([$class, 'getEntityTable']);
			/** @var string $class */
			$this->tables[$class] = $table ?? strtolower(basename(str_replace('\\', '/', $class)));
		}
		
		return $this->tables[$class];
	}
	
	/**
	 * @param string $query
	 * @return bool|mysqli_result|null
	 * @throws feException
	 */
	protected function query(string $query) {
		//		GameSetupController::$setupLogger->log($query);
		return $this->databaseManager->DbQuery($query);
	}
	
	protected function getLastInsertedId() {
		return $this->databaseManager->DbGetLastId();
	}
	
	protected function fetchOne(mysqli_result $result): ?array {
		$data = $result->fetch_assoc();
		$result->close();
		
		return $data;
	}
	
	protected function fetchMultiple(string $class, mysqli_result $result): EntityIterator {
		return new EntityIterator($this, $class, $result);
	}
	
	/**
	 * @param string $class
	 * @param array $where
	 * @param array $orderBy
	 * @param int|null $limit
	 * @return bool|mysqli_result|null
	 */
	public function select(string $class, array $where = [], array $orderBy = [], ?int $limit = null) {
		$query = $this->formatSelectQuery($class, $where, $orderBy, $limit);
		
		return $this->query($query);
	}
	
	public function findMultiple(string $class, array $where = [], array $orderBy = [], ?int $limit = null): EntityIterator {
		$sqlResult = $this->select($class, $where, $orderBy, $limit);
		
		return $this->fetchMultiple($class, $sqlResult);
	}
	
	public function findOne(string $class, array $where = [], array $orderBy = []): ?AbstractEntity {
		$sqlResult = $this->select($class, $where, $orderBy, 1);
		$data = $this->fetchOne($sqlResult);
		
		return $this->buildEntity($class, $data);
	}
	
	/**
	 * Synchronize entity with cache from db row
	 *
	 * @param string $class
	 * @param array $data
	 * @return AbstractEntity|null
	 */
	public function summon(string $class, array $data): ?AbstractEntity {
		$idField = $this->getSqlIdField($class);
		if( !isset($data[$idField]) ) {
			throw new RuntimeException(sprintf('Mismatching id field with data from database for class "%s"', $class));
		}
		$entity = $this->getCachedEntity($class, $data[$idField]);
		if( $entity ) {
			$this->ormService->format($entity, $data);
			
			return $entity;
		}
		
		return $this->buildEntity($class, $data);
	}
	
	/**
	 * @param string $class
	 * @param array|null $data SQL data
	 * @return AbstractEntity|null
	 */
	public function buildEntity(string $class, ?array $data): ?AbstractEntity {
		// Format entity
		$entity = $data ? $this->ormService->create($class, $data) : false;
		// Store in cache
		$this->setCachedEntity($entity);
		// Save first state to compare while updating
		if( $entity ) {
			$this->saveEntityState($entity);
		}
		
		return $entity;
	}
	
	protected function formatSelectQuery(string $class, array $where = [], array $orderBy = [], ?int $limit = null): string {
		$table = $this->getEntityTable($class);
		$whereClause = $this->formatCondition($where);
		$orderByClause = $this->prefix(' ORDER BY ', $this->formatOrderList($orderBy));
		$limitClause = $this->prefix(' LIMIT ', $limit);
		
		return sprintf('SELECT * FROM `%s` WHERE %s%s%s;', $table, $whereClause, $orderByClause, $limitClause);
	}
	
	public function formatCondition($condition, $andLevel = true): string {
		if( !is_array($condition) ) {
			throw new RuntimeException('Invalid condition, array required');
		}
		if( !$condition ) {
			return '1';
		}
		if( is_array($condition[0]) ) {
			// Array of array => Multiple conditions, we use the OR operator
			$conditionStr = '';
			// Array of string or array of object
			foreach( $condition as $conditionRow ) {
				$conditionStr .= ($conditionStr ? ($andLevel ? ' AND ' : ' OR ') : '') .
					$this->formatCondition($conditionRow, !$andLevel);
				// Fall into the One condition
			}
			
			return '(' . $conditionStr . ')';
		}
		[$field, $operator, $value] = array_pad($condition, 3, null);
		if( $value === null ) {
			$value = $operator ?? true;
			$operator = null;// Auto
		}
		if( $operator === null ) {
			$operator = is_array($value) ? 'IN' : '=';
		}
		// Format value
		if( is_array($value) ) {
			$value = '(' . $this->formatValueList($value) . ')';
		}
		
		return sprintf('`%s` %s %s', $field, $operator, $value);
	}
	
	protected function prefix(string $prefix, ?string $value): string {
		return $value ? $prefix . $value : '';
	}
	
	public function load(string $class, int $id): ?AbstractEntity {
		// Check cache
		$entity = $this->getCachedEntity($class, $id);
		if( $entity !== null ) {
			// May be falsy if not existing
			return $entity ?: null;
		}
		
		return $this->findOne($class, [$this->getSqlIdField($class), '=', $id]);
	}
	
	/**
	 * @param AbstractEntity $entity
	 * @return AbstractEntity|null null if batching
	 * @throws feException
	 */
	public function insert(AbstractEntity $entity): ?AbstractEntity {
		if( $entity->getId() ) {
			throw new RuntimeException('Entity is already stored in database, to update it, use update()');
		}
		
		$class = get_class($entity);
		$table = $this->getEntityTable($class);
		// Parse entity
		$data = $this->ormService->parse($entity);
		// Generate insert query
		$assignmentList = $this->formatAssignmentList($data);
		$query = sprintf('INSERT INTO `%s` SET %s ;', $table, $assignmentList);
		
		// Apply
		$sqlResult = $this->query($query);
		if( !$sqlResult ) {
			throw new RuntimeException('Insert query issue');
		}
		$entity->setId($this->getLastInsertedId());
		if( $this->batching ) {
			$this->setBatchingState($entity, self::STATE_INSERT);
			$this->setCachedEntity($entity);
			
			// Can not reload now
			return null;
		}
		
		return $this->load($class, $entity->getId());
	}
	
	public function updateList(array $entityList): int {
		$entityList = array_unique($entityList);// Update each one once only
		$count = 0;
		foreach( $entityList as $entity ) {
			$count += intval($this->update($entity));
		}
		
		return $count;
	}
	
	public function update(AbstractEntity $entity): bool {
		if( !$entity->getId() ) {
			throw new RuntimeException('Entity is not stored in database, to insert it, use insert()');
		}
		
		$this->entity($entity, $class, $id);
		$table = $this->getEntityTable($class);
		$data = $this->ormService->parse($entity);
		// Compare changes
		$previousState = $this->getEntityState($entity);
		$previousStateData = $previousState ? $this->ormService->parse($previousState) : [];
		//		BgaLogger::get()->log(sprintf('Update(%s) from %s', $entity->getEntityLabel(), json_encode($previousStateData)));
		//		BgaLogger::get()->log(sprintf('Update(%s) to %s', $entity->getEntityLabel(), json_encode($data)));
		$deltaData = array_diff_assoc($data, $previousStateData);
		if( !$deltaData ) {
			// There is no changes
			return false;
		}
		
		// Generate update query
		$assignmentList = $this->formatAssignmentList($data);
		$query = sprintf('UPDATE `%s` SET %s WHERE `%s` = %d LIMIT 1;', $table, $assignmentList, $this->getSqlIdField($class), $id);
		
		// Apply
		//		BgaLogger::get()->log('Apply query : ' . $query);
		$sqlResult = $this->query($query);
		if( !$sqlResult ) {
			throw new RuntimeException('Update query issue');
		}
		
		return true;
	}
	
	public function formatOrderList(array $orders): string {
		$list = '';
		foreach( $orders as $order ) {
			[$field, $direction] = array_pad(is_array($order) ? $order : [$order], 2, null);
			// Value is already well-formatted
			$list .= ($list ? ', ' : '') . sprintf('`%s` %s', $field, $direction ? 'DESC' : 'ASC');
		}
		
		return $list;
	}
	
	public function formatAssignmentList(array $data): string {
		$list = '';
		foreach( $data as $key => $value ) {
			// Value is already well-formatted
			$list .= ($list ? ', ' : '') . sprintf('`%s` = %s', $key, $value);
		}
		
		return $list;
	}
	
	public function formatValueList(array $data): string {
		$list = '';
		foreach( $data as $value ) {
			// Value is already well-formatted
			$list .= ($list ? ', ' : '') . $value;
		}
		
		return $list;
	}
	
	protected function entity(AbstractEntity $entity, string &$class = null, int &$id = null) {
		$class = get_class($entity);
		$id = $entity->getId();
	}
	
	protected function saveEntityState(AbstractEntity $entity) {
		$this->entity($entity, $class, $id);
		//		$class = get_class($entity);
		//		$id = $entity->getId();
		if( !isset($this->states[$class]) ) {
			$this->states[$class] = [];
		}
		if( !isset($this->states[$class][$id]) ) {
			$this->states[$class][$id] = [];
		}
		$this->states[$class][$id][] = $entity->getSaveState();
	}
	
	/**
	 * If you serialize entity into session (for example), there will have no cached and state-saved entity.
	 * You expose your app to an invalid state where entity mismatch the database
	 *
	 * @param AbstractEntity $entity
	 * @param int|null $index Null is last state (the previous one)
	 * @return AbstractEntity|null
	 */
	protected function getEntityState(AbstractEntity $entity, ?int $index = null): ?AbstractEntity {
		$this->entity($entity, $class, $id);
		if( $index === null ) {
			$end = array_slice($this->states[$class][$id], -1, 1, false);
			
			return $end[0] ?? null;
		}
		
		return $this->states[$class][$id][$index] ?? null;
	}
	
	/**
	 * @param AbstractEntity $entity
	 */
	protected function setCachedEntity(AbstractEntity $entity) {
		$this->entity($entity, $class, $id);
		if( !isset($this->cache[$class]) ) {
			$this->cache[$class] = [];
		}
		$this->cache[$class][$id] = $entity;
	}
	
	/**
	 * @param string $class
	 * @param int $id
	 * @return AbstractEntity|false|null False if not existing
	 */
	protected function getCachedEntity(string $class, int $id): ?AbstractEntity {
		return $this->cache[$class][$id] ?? null;
	}
	
	public function getMappingOf(string $class) {
		return call_user_func([$class, 'getMapping']);
	}
	
	public function getReverseMappingOf(string $class) {
		return call_user_func([$class, 'getReverseMapping']);
	}
	
	public function getSqlIdField(string $class) {
		$reverseMapping = $this->getReverseMappingOf($class);
		if( !isset($reverseMapping['id']) ) {
			throw new RuntimeException('Missing reverse mapping for field "id"');
		}
		
		return $reverseMapping['id'];
	}
	
	public static function initialize(Table $databaseManager) {
		static::$instance = new static($databaseManager);
	}
	
	public static function get(): self {
		return static::$instance;
	}
	
}
