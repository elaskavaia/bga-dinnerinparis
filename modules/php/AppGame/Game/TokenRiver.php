<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Game;

use AppGame\Core\Exception\UserException;
use AppGame\Entity\Player;
use AppGame\Entity\Token;
//use AppGame\Logger\BgaLogger;
use RuntimeException;

class TokenRiver extends AbstractTokenList {
	
	public function __construct(string $name, int $type, int $container, ?Player $player, array $tokens, int $slots) {
		// Init slots
		parent::__construct($name, $type, $container, $player, array_pad([], $slots, null));
		// Fill slots
		/** @var Token $token */
		foreach( $tokens as $token ) {
			$this->set($token, $token->getPosition());
		}
	}
	
	/**
	 * Pick up from top of pile
	 *
	 * @param Token $token
	 * @return Token
	 */
	public function pick(Token $token): Token {
		// If position differs, we use the real position in river
		$position = $this->indexOf($token);
		// Empty the slot
		$this->removeAt($position);
		
		return $token;
	}
	
	/**
	 * Add to specified slot
	 *
	 * @param Token $token
	 * @param int|null $position
	 * @return $this
	 * @throws UserException
	 */
	public function set(Token $token, ?int $position = null): self {
		$position = $position ?? $token->getPosition();
		if( !array_key_exists($position, $this->tokens) ) {
			throw new RuntimeException(sprintf('Unable to set token %s at position %d, this slot does not exist',
				$token->getEntityLabel(), $position));
		}
		if( !empty($this->tokens[$position]) ) {
			throw new RuntimeException(sprintf('Unable to set token %s at position %d, the token %s is already in slot',
				$token->getEntityLabel(), $position, $this->tokens[$position]->getEntityLabel()));
		}
		$this->synchronizeToken($token, $position);
		$this->tokens[$position] = $token;
		
		return $this;
	}
	
	/**
	 * Add to next available slot
	 *
	 * @param Token $token
	 * @return $this
	 */
	public function add(Token $token): self {
		$slot = $this->getNextAvailableSlot();
		//BgaLogger::get()->log(sprintf('Token river "%s" - add new token %s to slot %d', $this->name, $token->getEntityLabel(), $slot ?? '{{NULL}}'));
		if( $slot === null ) {
			throw new RuntimeException(sprintf('No more available slot in %s', $this->name));
		}
		
		return $this->set($token, $slot);
	}
	
	public function getNextAvailableSlot(): ?int {
		return $this->getEmptySlots()[0] ?? null;
	}
	
	public function countSlots(): int {
		return count($this->tokens);
	}
	
	public function getEmptySlots(): array {
		$emptySlots = [];
		foreach( $this->tokens as $slot => $token ) {
			if( !$token ) {
				$emptySlots[] = $slot;
			}
		}
		
		return $emptySlots;
	}
	
	public function removeAt(int $index) {
		//		BgaLogger::get()->log('CardRiver::removeAt('.$index.')');
		if( $this->tokens[$index] ) {
			$this->tokens[$index]->setList(null);
		}
		$this->tokens[$index] = null;
	}
	
}
