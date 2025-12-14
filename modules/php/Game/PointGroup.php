<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game;

use JsonSerializable;

class PointGroup implements JsonSerializable {
	
	/** @var int */
	protected static $increment = 1;
	
	/** @var GroupedPoint[]|null */
	protected $points = [];
	
	/** @var PointGroup|null */
	protected $parent = null;
	
	/** @var int */
	protected $id = 0;
	
	public function __construct() {
		$this->id = static::$increment++;
	}
	
	public function add(GroupedPoint $point): ?PointGroup {
		if( $this->parent ) {
			// Proxy operation to parent
			return $this->parent->add($point);
		}
		// Ignore when already in group
		$currentGroup = $point->getGroup();
		if( $currentGroup && $currentGroup !== $this ) {
			// If different group, merge other into this one
			$this->merge($currentGroup);
		} elseif( !$currentGroup ) {
			$this->points[] = $point;
			$point->setGroup($this);
		}
		
		// Return previous group to remove it from lists
		return $currentGroup;
	}
	
	/**
	 * Merge other into this one
	 *
	 * @param PointGroup $otherGroup
	 * @return void
	 */
	public function merge(PointGroup $otherGroup) {
		if( $otherGroup === $this ) {
			// Ignore merge with itself
			return;
		}
		foreach( $otherGroup->points as $point ) {
			// Detach first
			$point->setGroup(null);
			// Attach to new
			$this->add($point);
		}
		// Other should not be used anymore but to handle the case, it's still used, we created proxy
		$otherGroup->points = null;
		$otherGroup->parent = $this;
	}
	
	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}
	
	/**
	 * @return PointGroup|null
	 */
	public function getParent(): ?PointGroup {
		return $this->parent;
	}
	
	/**
	 * @return bool
	 */
	public function isRoot(): bool {
		return !$this->parent;
	}
	
	/**
	 * @return GroupedPoint[]|null
	 */
	public function getPoints(): array {
		return $this->points ?? [];
	}
	
	public function jsonSerialize(): array {
		return [
			'id'     => $this->id,
			'points' => $this->points,
			'parent' => $this->parent ? $this->parent->getId() : null,
		];
	}
	
}
