<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Entity;

use AppGame\Service\OrmService;
use DateTime;
use JsonSerializable;

abstract class AbstractEntity implements JsonSerializable {
	
	/** @var array|null */
	private static $reverseMapping = [];
	
	/** @var bool */
	private $savedState = false;
	
	/** @var int|null */
	private $id;
	
	public function equals($other): bool {
		return $other && is_object($other) && get_class($this) === get_class($other) && !$this->isNew() && !$other->isNew() && $this->getId() === $other->getId();
	}
	
	/**
	 * Is this instance new ? Or is it saved to database ?
	 * It tests the id to know.
	 *
	 * @return bool
	 */
	public function isNew(): bool {
		return empty($this->id);
	}
	
	abstract public function getLabel(): string;
	
	public function __toString() {
		// Used for comparison (only for debug or technical purposes, never uses it to display entity's label)
		return $this->getEntityLabel();
	}
	
	public function getEntityLabel(): string {
		return sprintf('"%s" (#%d)', $this->getLabel(), $this->getId());
	}
	
	/**
	 * @return int
	 */
	public function getId(): ?int {
		return $this->id;
	}
	
	/**
	 * @param mixed $id
	 */
	public function setId($id): void {
		$this->id = intval($id);
	}
	
	public function jsonSerialize(): array {
		return [
			//			'object_class' => get_class($this),
			'id' => $this->getId(),
		];
	}
	
	/**
	 * @param string|DateTime|null $value
	 * @return DateTime|null
	 * @see OrmService
	 */
	public function parseDateTime($value): ?DateTime {
		if( is_string($value) ) {
			$value = DateTime::createFromFormat('Y-m-d H:i:s', $value) ?: null;
		}
		
		return $value;
	}
	
	/**
	 * @param string|array|null $value
	 * @return array
	 * @see OrmService
	 */
	public function parseArray($value): ?array {
		if( is_string($value) ) {
			$value = json_decode($value, true);
		}
		
		return $value ?? [];
	}
	
	public function getSaveState(): AbstractEntity {
		$state = clone $this;
		$state->setSavedState();
		
		return $state;
	}
	
	/**
	 * @return bool
	 */
	public function isSavedState(): bool {
		return $this->savedState;
	}
	
	private function setSavedState(): void {
		$this->savedState = true;
	}
	
	/**
	 * Require all fields [SQL Field => Object Property]
	 *
	 * @return array
	 */
	public abstract static function getMapping(): array;
	
	public static function getReverseMapping(): array {
		$class = get_called_class();
		if( !isset(self::$reverseMapping[$class]) ) {
			self::$reverseMapping[$class] = array_flip(static::getMapping());
		}
		
		return self::$reverseMapping[$class];
	}
	
	/**
	 * @return string|null
	 */
	public static function getEntityTable(): ?string {
		return null;
	}
	
}
