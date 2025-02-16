<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Core\Controller;

use AppGame\Entity\Player;
use AppGame\Service\BoardGameApp;
use RuntimeException;

class ArgumentBag {
	
	/** @var BoardGameApp */
	private $app;
	
	/** @var array */
	private $arguments = [];
	
	/**
	 * ArgumentBag constructor
	 *
	 * @param BoardGameApp $app
	 */
	public function __construct(BoardGameApp $app) {
		$this->app = $app;
	}
	
	public function setPublicArgument(string $key, $value): ArgumentBag {
		if( $key === '_private' ) {
			throw new RuntimeException(sprintf('Invalid key "%s", reserved one', $key));
		}
		$this->arguments[$key] = $value;
		
		return $this;
	}
	
	public function setPublicArgumentList(array $arguments): ArgumentBag {
		if( isset($arguments['_private']) ) {
			throw new RuntimeException(sprintf('Invalid key "%s", reserved one', '_private'));
		}
		$this->arguments += $arguments;
		
		return $this;
	}
	
	public function setPlayerArgumentList(Player $player, array $arguments): ArgumentBag {
		if( !isset($this->arguments['_private']) ) {
			$this->arguments['_private'] = [];
		}
		$playerId = $player->getId();
		if( !isset($this->arguments['_private'][$playerId]) ) {
			$this->arguments['_private'][$playerId] = [];
		}
		$this->arguments['_private'][$playerId] += $arguments;
		
		return $this;
	}
	
	public function setPlayerArgument(Player $player, string $key, $value): ArgumentBag {
		$this->setPlayerArgumentList($player, [$key => $value]);
		
		return $this;
	}
	
	/**
	 * @return array
	 */
	public function getArguments(): array {
		return $this->arguments;
	}
	
}
