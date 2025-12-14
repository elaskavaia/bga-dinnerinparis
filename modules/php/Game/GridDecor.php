<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game;

use JsonSerializable;

class GridDecor implements GridLocatable, JsonSerializable {
	
	/** @var array */
	private $material;
	
	/**
	 * @param array $material
	 */
	public function __construct(array $material) {
		$this->material = $material;
	}
	
	public function getType(): string {
		return $this->material['key'];
	}
	
	public function allowBuildTerrace(): bool {
		return in_array(BUILD_TERRACE, $this->material['allows']);
	}
	
	public function jsonSerialize(): array {
		return $this->material;
	}
	
}
