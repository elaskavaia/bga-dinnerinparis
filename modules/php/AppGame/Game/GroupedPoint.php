<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game;

use JsonSerializable;

class GroupedPoint implements JsonSerializable {
	
	/** @var array */
	protected $point;
	
	/** @var PointGroup|null */
	protected $group = null;
	
	/** @var string */
	protected $key;
	
	public function __construct(string $key, array $point) {
		$this->key = $key;
		$this->point = $point;
	}
	
	public function getGroup(): ?PointGroup {
		return $this->group;
	}
	
	public function setGroup(?PointGroup $group) {
		$this->group = $group;
	}
	
	/**
	 * @return array
	 */
	public function getPoint(): array {
		return $this->point;
	}
	
	/**
	 * @return string
	 */
	public function getKey(): string {
		return $this->key;
	}
	
	public function jsonSerialize(): array {
		return [
			'point' => $this->point,
			'group' => $this->group ? $this->group->getId() : null,
		];
	}
	
}
