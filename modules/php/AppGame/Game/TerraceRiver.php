<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game;

use AppGame\Entity\Player;
use AppGame\Entity\Terrace;

class TerraceRiver extends TokenRiver {
	
	protected $material;
	
	public function __construct(string $name, int $type, int $container, ?Player $player, array $material) {
		// Init slots
		parent::__construct($name, $type, $container, $player, [], $material['playerTotal']);
		$this->material = $material;
	}
	
	public function getCompletion(): float {
		return $this->count() / $this->material['playerTotal'];
	}
	
	/**
	 * Pick up from top of pile
	 *
	 * @return Terrace|null
	 */
	public function pickFirst(): ?Terrace {
		/** @var Terrace $token */
		$token = $this->getFirst();
		$this->remove($token);
		
		return $token;
	}
	
	public function getScore(): int {
		$last = $this->getLastTerrace();
		$material = $this->getTerraceMaterial($last);
		if( !$material ) {
			// No terrace placed
			return 0;
		}
		if( !empty($material['score']) ) {
			// Return score of last terrace
			return $material['score'];
		}
		// Last terrace has no score, so the previous one should have
		$material = $this->getTerraceMaterial($last - 1);
		if( !$material ) {
			// Previous does not exist (if first gives an income)
			return 0;
		}
		
		// If last gives an income, so the previous must give a score
		return $material['score'] ?? 0;
	}
	
	public function getIncome(): int {
		$last = $this->getLastTerrace();
		$income = 0;
		for( $i = 0; $i <= $last; $i++ ) {
			$material = $this->getTerraceMaterial($i);
			$income += $material['income'] ?? 0;
		}
		
		return $income;
	}
	
	public function getTerraceMaterial(int $index): ?array {
		return $this->material['terraces'][$index] ?? null;
	}
	
	public function nextTerraceMaterial(): ?array {
		//		BgaLogger::get()->log(sprintf('Currently having %d items of %d', $this->count(), $this->material['playerTotal']));
		$terrace = $this->material['playerTotal'] - $this->count();
		
		return $this->getTerraceMaterial($terrace);
	}
	
	public function getLastTerrace(): int {
		return $this->material['playerTotal'] - $this->count() - 1;// -1 if no placed terrace
	}
	
}
