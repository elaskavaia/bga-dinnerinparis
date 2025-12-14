<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Game;

use \Bga\Games\DinnerInParis\Core\Exception\UserException;
use \Bga\Games\DinnerInParis\Entity\Terrace;
use \Bga\Games\DinnerInParis\Entity\Token;
use \Bga\Games\DinnerInParis\Service\GeometryService;

class TokenGrid extends AbstractTokenList {

	protected static $synchronizePlayer = false;

	private $size;

	private $model;

	/** @var GridDecor[] */
	private $decor;// Non-unique player in grid, so we don't force

	public function __construct(string $name, array $types, int $container, array $decor, array $tokens, int $size, int $model) {
		// Init slots
		parent::__construct($name, $types, $container, null, array_pad([], $size * $size, null));

		$this->size = $size;
		$this->model = $model;
		// Set decor
		$this->decor = [];
		foreach( $decor as [$decorElement, $decorPoints] ) {
			foreach( $decorPoints as $point ) {
				$this->setDecor($decorElement, $point);
			}
		}
		//		BgaLogger::get()->log('Grid decors', $this->decor);
		// Set tokens
		/** @var Token $token */
		foreach( $tokens as $token ) {
			$this->set($token, $token->getPosition());
		}
	}

	public function getTerraceCompletion(): float {
		return count($this->getTokenList(TOKEN_TYPE_TERRACE)) / $this->getTerraceCellsCount();
	}

	public function getTerraceCellsCount(): int {
		$size = $this->size + $this->model - 4;

		return $size * $size - 4;// Square less corners
	}

	public function isNorth(Terrace $terrace): bool {
		[, $y] = $this->getTokenPoint($terrace);

		return $y < $this->size / 2;
	}

	public function isWest(Terrace $terrace): bool {
		[$x,] = $this->getTokenPoint($terrace);

		return $x < $this->size / 2;
	}

	public function getTerraceQuarter(Terrace $terrace): string {
		if( $this->isNorth($terrace) ) { // North
			if( $this->isWest($terrace) ) { // WEST
				return QUARTER_NORTH_WEST;
			} else { // EAST
				return QUARTER_NORTH_EAST;
			}
		} else { // South
			if( $this->isWest($terrace) ) { // WEST
				return QUARTER_SOUTH_WEST;
			} else { // EAST
				return QUARTER_SOUTH_EAST;
			}
		}
	}

	/**
	 * Get set of decor by type
	 *
	 * @param string $type
	 * @return PointGroup[]
	 */
	public function getDecorPointGroups(string $type): array {
		// All points
		$typePoints = [];
		foreach( $this->decor as $position => $decor ) {
			if( $decor->getType() === $type ) {
				$typePoints[] = $this->formatPoint($position);
			}
		}
		//		BgaLogger::get()->log(sprintf('getDecorPointGroups(%s) - $typePoints "%s"', $type, json_encode($typePoints)));
		// Group points
		$geometryService = GeometryService::get();

		return $geometryService->getPointGroups($typePoints);
	}

	public function getFirstIndex(): int {
		return 4 - $this->model;
	}

	public function getLastIndex(): int {
		return $this->size - $this->getFirstIndex() - 1;
	}

	public function getTokenPoint(Token $token): array {
		return $this->formatPoint($token->getPosition());
	}

	/**
	 * Add to specified position, for this cell and all other it's occupying
	 *
	 * @param Token $token
	 * @param int|array $point
	 * @return $this
	 * @throws UserException
	 */
	public function set(Token $token, $point): self {
		$position = is_array($point) ? $this->parsePoint($point) : $point;
		$point = is_array($point) ? $point : $this->formatPoint($position);
		//		BgaLogger::get()->log(sprintf('TokenGrid::setToken(%s, %s) - position = "%d"', $token->getEntityLabel(), json_encode($point), $position));
		$size = $token->getSize();
		[$x, $y] = $point;
		//		BgaLogger::get()->log(sprintf('Place %s at %s (type=%s)', $token->getEntityLabel(), json_encode($point), gettype($x)));
		//		BgaLogger::get()->log(sprintf('getFirstIndex=%d & getLastIndex=%d', $this->getFirstIndex(), $this->getLastIndex()));
		// 0 is Horizontal, 1 is Vertical
		// This is not the best way to do, but this is working for the overall game
		$direction = intval($x === $this->getFirstIndex() || $x === $this->getLastIndex());
		//		BgaLogger::get()->log(sprintf('Direction = %s', $direction));
		$positions = [];// May have multiple positions, we want to check it all before setting the token position
		for( $restPost = 0; $restPost < $size; $restPost++ ) {
			$cX = $x + ($direction ? 0 : $restPost);
			$cY = $y + ($direction ? $restPost : 0);
			//			BgaLogger::get()->log(sprintf('Get token at (%d, %d) = (%d, %d) + (%d, %d)', $cX, $cY, $x, $y, $direction ? 0 : $restPost, $direction ? $restPost : 0));
			$otherToken = $this->getTokenAt([$cX, $cY]);
			if( $otherToken ) {
				throw new UserException(sprintf('Unable to set token %s to cell (%d, %d), the token %s is already in slot',
					$token->getEntityLabel(), $cX, $cY, $otherToken->getEntityLabel()));
			}
			$positions[] = $this->parseCoordinates($cX, $cY);
		}
		foreach( $positions as $cellPosition ) {
			$this->tokens[$cellPosition] = $token;
		}
		$this->synchronizeToken($token, $position);

		return $this;
	}

	public function formatPoint(int $position): array {
		// intval() returns integer (while floor() is returning double)
		return [$position % $this->size, intval($position / $this->size)];
	}

	public function parsePoint(array $point): int {
		return $this->parseCoordinates($point[0], $point[1]);
	}

	public function parseCoordinates(int $x, int $y): int {
		return $y * $this->size + $x;
	}

	public function hasToken(array $point): bool {
		return !empty($this->tokens[$this->parsePoint($point)]);
	}

	public function has(array $point): bool {
		$first = $this->getFirstIndex();
		$last = $this->getLastIndex();

		return $first <= $point[0] && $point[0] <= $last
			&& $first <= $point[1] && $point[1] <= $last;
	}

	public function getOutDecor(): GridDecor {
		return new GridDecor([
			'key'    => TILE_OUT,
			'allows' => [],
		]);
	}

	/**
	 * Get decor and token for point
	 *
	 * @param array $point
	 * @return GridLocatable[] [?Decor, ?Token]
	 */
	public function get(array $point): array {
		if( !$this->has($point) ) {
			return [$this->getOutDecor(), null];
		}
		$position = $this->parsePoint($point);

		return [$this->decor[$position] ?? null, $this->tokens[$position] ?? null];
	}

	public function getTokenAt(array $point): ?Token {
		return $this->tokens[$this->parsePoint($point)] ?? null;
	}

	public function getDecor(array $point): ?GridDecor {
		return $this->decor[$this->parsePoint($point)] ?? null;
	}

	public function setDecor($decorElement, array $point, bool $ignoreExisting = false): TokenGrid {
		$position = $this->parsePoint($point);
		if( isset($this->decor[$position]) ) {
			if( $ignoreExisting ) {
				return $this;
			} else {
				throw new UserException(sprintf('Decor already present at %s', json_encode($point)));
			}
		}
		// Never replace another decor
		$this->decor[$position] = $decorElement;

		return $this;
	}


	/**
	 * Add to end of pile (below,down,bottom)
	 *
	 * @param Token $token
	 * @return $this
	 */
	//	public function add(Token $token): self {
	//		$lastToken = $this->getLast();
	//		$position = $lastToken ? $lastToken->getPosition() + 1 : 0;
	//		$this->synchronizeToken($token, $position);
	//		$this->tokens[] = $token;
	//
	//		return $this;
	//	}
	
}
