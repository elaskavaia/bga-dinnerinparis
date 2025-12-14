<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game\ObjectiveResolver;

use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Game\GameTable;
use \Bga\Games\DinnerInParis\Game\GridResolver\TerraceAroundDecorResolver;

class TerraceAroundDecorObjectiveResolver extends AbstractObjectiveResolver {
	
	/**
	 * @var string[]
	 */
	protected $types;
	
	/**
	 * @var int
	 */
	protected $required;
	
	/**
	 * @var bool True for minimal of total, false for minimal of each one
	 */
	protected $totalOnly;
	
	/**
	 * TerraceAroundDecorResolver constructor
	 *
	 * @param GameTable $table
	 * @param Player $player
	 * @param string $type
	 * @param int $required
	 */
	public function __construct(GameTable $table, Player $player, string $type, int $required) {
		parent::__construct($table, $player);
		$this->types = $type === 'all' ? TerraceAroundDecorResolver::ALL_DECORS : [$type];
		$this->totalOnly = $type === 'all';
		$this->required = $required;
	}
	
	public function run(): void {
		//		BgaLogger::get()->log(sprintf('TerraceAroundDecorObjectiveResolver::run with types %s and required %d', json_encode($this->types), $this->required));
		$solution = [];
		$completed = true;
		foreach( $this->types as $type ) {
			$resolver = new TerraceAroundDecorResolver($this->table, $this->player, $type);
			$solution[$type] = $this->totalOnly ? $resolver->countTotal() : $resolver->countMinimal();
			if( $solution[$type] < $this->required ) {
				$completed = false;
			}
		}
		//		BgaLogger::get()->log(sprintf('TerraceAroundDecorObjectiveResolver::run() - solution "%s"', json_encode($solution)));
		
		$this->completed = $completed;
		$this->solution = $solution;
	}
	
}
