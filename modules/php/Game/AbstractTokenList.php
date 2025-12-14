<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game;

use \Bga\Games\DinnerInParis\Core\Debug\Dump;
use \Bga\Games\DinnerInParis\Core\Exception\UserException;
use \Bga\Games\DinnerInParis\Entity\Player;
use \Bga\Games\DinnerInParis\Entity\Token;
//use \Bga\Games\DinnerInParis\Logger\BgaLogger;

/**
 * Physical heap for tokens
 */
class AbstractTokenList {
	
	protected static $synchronizePlayer = true;
	
	/** @var string */
	protected $name;
	
	/** @var int[] */
	protected $types;
	
	/** @var int */
	protected $container;
	
	/** @var Player|null */
	protected $player;
	
	/**
	 * @var Token[]
	 * Some could be missing, but it should never be 2 on same spot
	 */
	protected $tokens;
	
	/**
	 * AbstractTokenList constructor
	 *
	 * @param string $name
	 * @param string|array $types Allowed types
	 * @param int $container
	 * @param Player|null $player
	 * @param array $tokens
	 */
	public function __construct(string $name, $types, int $container, ?Player $player, array $tokens = []) {
		$this->name = $name;
		$this->types = (array) $types;// convert scalar as array
		$this->container = $container;
		$this->player = $player;
		$this->tokens = $tokens;
	}
	
	/**
	 * @return array
	 */
	public function getTokensGroupedByVariant(): array {
		$variantTokens = [];
		foreach( $this->tokens as $token ) {
			$variant = $token->getVariant();
			if( !array_key_exists($variant, $variantTokens) ) {
				$variantTokens[$variant] = [];
			}
			$variantTokens[$variant][] = $token;
		}
		
		return $variantTokens;
	}
	
	public function extractAll(): array {
		$tokens = $this->tokens;
		foreach( $tokens as $tokenIndex => $token ) {
			$this->removeAt($tokenIndex);
		}
		
		return $tokens;
	}
	
	/**
	 * Only work if all cards are the same type
	 *
	 * @return array|null [count, variant, sampleToken] or null if there is no duplicates
	 */
	public function getMostDuplicated(): ?array {
		$variantsTokens = [];// variant -> same as return
		$mostDuplicated = null;
		foreach( $this->tokens as $token ) {
			$variant = $token->getVariant();
			if( !isset($variantsTokens[$variant]) ) {
				$variantsTokens[$variant] = [1, $variant, $token];
			} else {
				$variantsTokens[$variant][0]++;
			}
			if( !$mostDuplicated || $variantsTokens[$variant][0] > $mostDuplicated[0] ) {
				$mostDuplicated = $variantsTokens[$variant];
			}
		}
		
		return $mostDuplicated && $mostDuplicated[0] > 1 ? $mostDuplicated : null;
	}
	
	public function remove(Token $token): bool {
		// Does not consider the position
		$position = $this->indexOf($token);
		if( $position === null ) {
			//BgaLogger::get()->log(sprintf('WARNING - Expect to find token %s at position #%d while removing but was not in token list "%s"', $token->getEntityLabel(), $token->getPosition(), $this->getName()));
			
			return false;
		}
		$this->removeAt($position);
		
		return true;
	}
	
	public function removeAt(int $index) {
		if( isset($this->tokens[$index]) ) {
			$this->tokens[$index]->setList(null);
		}
		unset($this->tokens[$index]);
	}
	
	/**
	 * @param int|null $type
	 * @param Player|null $player
	 * @param Token|null $parent
	 * @return Token[]
	 */
	public function getTokenList(?int $type = null, ?Player $player = null, ?Token $parent = null): array {
		$tokens = array_filter(array_unique($this->tokens));
		if( $type || $player ) {
			$tokens = array_filter($tokens, function (Token $token) use ($type, $player, $parent) {
				if( $token === null ) {
					return false;
				}
				if( $type && $type !== $token->getType() ) {
					return false;
				}
				if( $player && $player->getId() !== $token->getPlayerId() ) {
					return false;
				}
				if( $parent && $parent->getId() !== $token->getParentTokenId() ) {
					return false;
				}
				
				return true;
			});
		}
		if( $type ) {
			//BgaLogger::get()->log(sprintf('getTokenList(%s, %s, %s) : %s',
			//	$type, $player ? $player->getEntityLabel() : 'NO PLAYER', $parent ? $parent->getEntityLabel() : 'NO PARENT', Dump::tokenIdList($tokens)));
		}
		
		return $tokens;
	}
	
	public function count(): int {
		return count($this->getTokenList());
	}
	
	public function isEmpty(): bool {
		return !$this->count();
	}
	
	public function contains(Token $token): bool {
		return $this->indexOf($token) !== null;
	}
	
	public function indexOf(Token $token): ?int {
		foreach( $this->tokens as $index => $loopToken ) {
			if( $token->equals($loopToken) ) {
				return $index;
			}
		}
		
		return null;
	}
	
	/**
	 * Get first by id
	 *
	 * @param int $id
	 * @return Token|null
	 */
	public function getTokenById(int $id): ?Token {
		foreach( $this->tokens as $token ) {
			if( $token->getId() === $id ) {
				return $token;
			}
		}
		
		return null;
	}
	
	public function getTokensByKey(string $key): array {
		$tokens = [];
		foreach( $this->tokens as $token ) {
			if( $token->getKey() === $key ) {
				$tokens[] = $token;
			}
		}
		
		return $tokens;
	}
	
	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}
	
	/**
	 * @return int[]
	 */
	public function getTypes(): array {
		return $this->types;
	}
	
	/**
	 * @return Player|null
	 */
	public function getPlayer(): ?Player {
		return $this->player;
	}
	
	/**
	 * @return int
	 */
	public function getContainer(): int {
		return $this->container;
	}
	
	protected function synchronizeToken(Token $token, int $position) {
		if( !in_array($token->getType(), $this->types) ) {
			throw new UserException(sprintf('Forbidden token %s of type %d against list of %d', $token->getEntityLabel(), $token->getType(), json_encode($this->types)));
		}
		$previousList = $token->getList();
		$token->setContainer($this->container);
		if( static::$synchronizePlayer ) {
			$token->setPlayer($this->player);
		}
		$token->setPosition($position);
		$token->setList($this);
		if( $previousList && $previousList !== $this ) {
			// Has previous list different from this one
			$previousList->remove($token);
		}
	}
	
	public function getFirst(): ?Token {
		if( $this->isEmpty() ) {
			return null;
		}
		$tokenList = array_slice($this->getUniqueTokens(), 0, 1);
		
		return $tokenList[0];
	}
	
	public function getLast(): ?Token {
		if( $this->isEmpty() ) {
			return null;
		}
		$tokenList = array_slice($this->getUniqueTokens(), -1, 1);
		
		return $tokenList[0];
	}
	
	public function at(int $index): ?Token {
		$tokenList = $this->getTokenList();
		
		return $tokenList[$index] ?? null;
	}
	
	/**
	 * @return Token[]
	 */
	public function getTokens(): array {
		return $this->tokens;
	}
	
	/**
	 * @return Token[]
	 */
	public function getUniqueTokens(): array {
		return array_unique(array_filter($this->tokens));
	}
	
	/**
	 * @return int[]
	 * ?deprecated User Dump::tokenIdList()
	 */
	public function getIdList(): array {
		return array_map(function (?Token $token) {
			return $token ? $token->getId() : 'null';
		}, $this->tokens);
	}
	
}
