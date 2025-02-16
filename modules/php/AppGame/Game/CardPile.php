<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game;

use AppGame\Core\Exception\UserException;
use AppGame\Entity\Token;

class CardPile extends AbstractTokenList {
	
	/**
	 * Pick up from top of pile
	 *
	 * @return Token|null
	 */
	public function pickFirst(): ?Token {
		$token = $this->getFirst();
		$this->remove($token);
		
		return $token;
	}
	
	/**
	 * Add to end of pile (below,down,bottom)
	 *
	 * @param Token $token
	 * @return $this
	 * @throws UserException
	 */
	public function add(Token $token): self {
		$lastToken = $this->getLast();
		$position = $lastToken ? $lastToken->getPosition() + 1 : 0;
		$this->synchronizeToken($token, $position);
		$this->tokens[] = $token;
		
		return $this;
	}
	
	/**
	 * Add to start of pile (above,up,top)
	 *
	 * @param Token $token
	 * @param bool $calculate False to calculate position manually while batching
	 * @return $this
	 * @see calculateAllPositions() Must be called !
	 */
	public function putOnTop(Token $token, bool $calculate = true): self {
		// If in array, remove it
		$previousPosition = array_search($token, $this->tokens);
		if( $previousPosition !== false ) {
			unset($this->tokens[$previousPosition]);
		}
		// Sync token
		$this->synchronizeToken($token, 0);
		// Insert as first
		array_unshift($this->tokens, $token);
		if( $calculate ) {
			// By default, auto-recalculate all positions, but you could disable it if you need to push multiple cards
			$this->calculateAllPositions();
		}
		
		return $this;
	}
	
	/**
	 * @param Token[] $tokens
	 * @param bool $calculate
	 * @return $this
	 */
	public function putListOnTop(array $tokens, bool $calculate = true): self {
		foreach( $tokens as $token ) {
			$this->putOnTop($token, false);
		}
		if( $calculate ) {
			// By default, auto-recalculate all positions, but you could disable it if you need to push multiple cards
			$this->calculateAllPositions();
		}
		
		return $this;
	}
	
	public function calculateAllPositions(): self {
		foreach( $this->tokens as $position => $token ) {
			$token->setPosition($position);
		}
		
		return $this;
	}
	
	/**
	 * @param Token[] $tokens
	 * @return $this
	 */
	public function addList(array $tokens): self {
		foreach( $tokens as $token ) {
			$this->add($token);
		}
		
		return $this;
	}
	
	/**
	 * Extract all cards and shuffle them
	 * Do not set position of tokens
	 *
	 * @return array
	 */
	public function getShuffledCards(): array {
		$tokens = $this->extractAll();
		shuffle($tokens);
		
		return $tokens;
	}
	
}
