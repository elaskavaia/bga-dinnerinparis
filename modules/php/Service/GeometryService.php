<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Service;

use \Bga\Games\DinnerInParis\Core\Singleton;
use \Bga\Games\DinnerInParis\Game\GroupedPoint;
use \Bga\Games\DinnerInParis\Game\PointGroup;
//use \Bga\Games\DinnerInParis\Logger\BgaLogger;

class GeometryService {
	
	use Singleton;
	
	const PATTERN_ADJACENT = [[-1, 0], [1, 0], [0, -1], [0, 1]];
	const PATTERN_AROUND = [[-1, -1], [0, -1], [1, -1], [-1, 0], [1, 0], [-1, 1], [0, 1], [1, 1]];
	
	protected static $orientationPaths = [
		// Origin may be NOT kept, as we prefer top/left point as origin
		ORIENTATION_NORTH => [// This one is the most verified and we check all
			ORIENTATION_EAST  => ['invertXY', 'flipX'],
			ORIENTATION_SOUTH => ['flipX', 'flipY'],
			ORIENTATION_WEST  => ['flipX', 'invertXY'],
		],// For others, take care by using it, verify before !
		ORIENTATION_EAST  => [
			ORIENTATION_SOUTH => ['invertXY', 'flipX'],
			ORIENTATION_WEST  => ['flipX', 'flipY'],
			ORIENTATION_NORTH => ['flipX', 'invertXY'],
		],
		ORIENTATION_SOUTH => [
			ORIENTATION_WEST  => ['invertXY', 'flipX'],
			ORIENTATION_NORTH => ['flipX', 'flipY'],
			ORIENTATION_EAST  => ['flipX', 'invertXY'],
		],
		ORIENTATION_WEST  => [
			ORIENTATION_NORTH => ['invertXY', 'flipX'],
			ORIENTATION_EAST  => ['flipX', 'flipY'],
			ORIENTATION_SOUTH => ['flipX', 'invertXY'],
		],
	];
	
	/**
	 * @param array $scanPoints Non-indexed point list
	 * @param array $setPoints Indexed point list
	 * @return array
	 */
	public function getPointsLinking(array $scanPoints, array $setPoints): array {
		$linkedPoints = [];
		while( $point = array_pop($scanPoints) ) {
			// Test all adjacent point to establishment a link
			foreach( $this->getAdjacentPoints($point) as $adjacentPoint ) {
				$adjacentPointIndex = $this->getPointIndex($adjacentPoint);
				if( isset($setPoints[$adjacentPointIndex]) ) {
					unset($setPoints[$adjacentPointIndex]);
					$scanPoints[] = $adjacentPoint;
					$linkedPoints[$adjacentPointIndex] = $adjacentPoint;
				}
			}
		}
		
		// [Linked points, Non-linked points]
		return [$linkedPoints, $setPoints];
	}
	
	public function getAdjacentPoints(array $point): array {
		return $this->getPatternPointsRelativeTo($point, [[-1, 0], [1, 0], [0, -1], [0, 1]]);
	}
	
	public function formatPattern(array $patternTable): array {
		// Pattern describe by rows and columns if a cell contains a terrace, e.g. P01 => [[0, 1, 0], [1, 1, 1], [0, 1, 0], [0, 1, 0]]
		// Pattern list all points of pattern relative to origin, e.g. P01 => [[0, 0], [-1, 1], [0, 1], [1, 1], [0, 2], [0, 3]]
		// In table, there is no origin, so we build it using first cell, as origin must be an existing terrace
		$origin = null;// Origin relative point is always [0, 0], this contains the relative coordinate to table cell
		$pattern = [];
		foreach( $patternTable as $tableY => $rowCells ) {
			foreach( $rowCells as $tableX => $cell ) {
				if( $cell ) {
					if( !$origin ) {
						$origin = [$tableX, $tableY];// e.g. P01 => [0, 1]
					}
					$pattern[] = [$tableX - $origin[0], $tableY - $origin[1]];
				}// Ignore 0-cells
			}
		}
		
		//		BgaLogger::get()->log(sprintf('formatPattern() => "%s" with origin "%s"', json_encode($pattern), json_encode($origin)));
		
		return $pattern;
	}
	
	/**
	 * @param PointGroup $group
	 * @param array $points
	 * @param bool $includeDiagonals
	 * @return int[][]
	 */
	public function getGroupAroundPoints(PointGroup $group, array $points, bool $includeDiagonals): array {
		$points = $this->indexPoints($points);
		$pattern = $includeDiagonals ? self::PATTERN_AROUND : self::PATTERN_ADJACENT;
		$aroundPoints = [];
		foreach( $group->getPoints() as $groupedPoint ) {
			foreach( $points as $pointKey => $point ) {
				if( $this->inPattern($groupedPoint->getPoint(), $point, $pattern) ) {
					$aroundPoints[$pointKey] = $point;
					unset($points[$pointKey]);
				}
			}
		}
		
		return $aroundPoints;
	}
	
	/**
	 * Test if $point is in $pattern using $origin
	 *
	 * @param array $origin
	 * @param array $point Point to search in pattern around origin
	 * @param array $pattern
	 * @return bool
	 */
	public function inPattern(array $origin, array $point, array $pattern): bool {
		$points = $this->getPatternPointsRelativeTo($origin, $pattern);
		$points = $this->indexPoints($points);
		$searchIndex = $this->getPointIndex($point);
		
		return isset($points[$searchIndex]);
	}
	
	//	public function areAround($pointA, $pointB): bool {
	//		return $pointA[0] === ($pointB[0] - 1) || $pointB[0] === ($pointA[0] - 1) ||
	//			$pointA[1] === ($pointB[1] - 1) || $pointB[1] === ($pointA[1] - 1);
	//	}
	
	//	public function areAdjacent($pointA, $pointB): bool {
	//		return $pointA[0] === ($pointB[0] - 1) || $pointB[0] === ($pointA[0] - 1) ||
	//			$pointA[1] === ($pointB[1] - 1) || $pointB[1] === ($pointA[1] - 1);
	//	}
	
	/**
	 * @param array $points
	 * @return PointGroup[]
	 */
	public function getPointGroups(array $points): array {
		// Indexed points ["0-1"=>[0, 1]]
		$points = $this->indexPoints($points);
		$pattern = self::PATTERN_ADJACENT;
		$groups = [];
		$groupedPoints = [];
		foreach( $points as $pointKey => $point ) {
			$patternPoints = $this->getPatternPointsRelativeTo($point, $pattern);
			//			BgaLogger::get()->log(sprintf('getPointGroups()- Point %s - $patternPoints "%s"', $pointKey, json_encode($patternPoints)));
			$adjacentPoints = $this->getIndexedPatternPoint($points, $patternPoints, false);
			// Add current to adjacent list
			$adjacentPoints[$pointKey] = $point;
			//			BgaLogger::get()->log(sprintf('getPointGroups()- Point %s - adjacents "%s"', $pointKey, json_encode($adjacentPoints)));
			// Try to identify adjacent groups
			// Another point could have no group, a shared group or a different group
			/** @var PointGroup $mainGroup */
			$mainGroup = null;
			// Retrieve main group or default and format adjacent points
			foreach( $adjacentPoints as $adjacentPointKey => &$adjacentPoint ) {
				$adjacentPoint = $groupedPoints[$adjacentPointKey] ?? new GroupedPoint($adjacentPointKey, $adjacentPoint);
				if( !$mainGroup ) {
					$mainGroup = $adjacentPoint->getGroup();
				}
			}
			if( !$mainGroup ) {
				// Default to new
				$mainGroup = new PointGroup();
			}
			//			BgaLogger::get()->log(sprintf('getPointGroups()- Point %s - $mainGroup "%s"', $pointKey, json_encode($mainGroup)));
			//			$adjacentGroups = [];
			unset($adjacentPoint);
			foreach( $adjacentPoints as $adjacentPointKey => $adjacentPoint ) {
				$removedGroup = $mainGroup->add($adjacentPoint);
				if( $removedGroup ) {
					unset($groups[$removedGroup->getId()]);
				}
				//				$groupedPoint = $groupedPoints[$adjacentPointKey] ?? new GroupedPoint($adjacentPointKey, $adjacentPoint);
				//				if( $mainGroup ) {
				//					// Add point to group, if it has a previous group, merge it
				//					$mainGroup->add($groupedPoint);
				//				} else {
				//					$pointGroup = $groupedPoint->getGroup();
				//					if( $pointGroup ) {
				//						// Assign point group as main
				//						$mainGroup = $pointGroup;
				//					} else {
				//						// Neither current and main are having a group
				//						$mainGroup = new PointGroup();
				//						$mainGroup->add($groupedPoint);
				//					}
				//				}
				$groupedPoints[$adjacentPointKey] = $adjacentPoint;
			}
			//			BgaLogger::get()->log(sprintf('getPointGroups()- Point %s - $mainGroup "%s"', $pointKey, json_encode($mainGroup)));
			$groups[$mainGroup->getId()] = $mainGroup;
		}
		
		//		BgaLogger::get()->log(sprintf('getPointGroups()- all groups "%s"', json_encode($groups)));
		
		return array_filter($groups, function (PointGroup $group) {
			return $group->isRoot();
		});
	}
	
	/**
	 * Get all origin points for pattern
	 *
	 * @param int[][] $points
	 * @param int[][] $pattern
	 * @param bool $includeAllPoints
	 * @param bool $directionOnly Horizontal & vertical only
	 * @return int[][]
	 */
	public function getPointsMatchingPattern(array $points, array $pattern, bool $includeAllPoints = false, bool $directionOnly = false): array {
		// In a pattern, all coordinates are relative to the origin, the first point
		$points = $this->indexPoints($points);
		$matchingPoints = [];
		$orientations = $directionOnly ? $this->getDirectionOrientations() : $this->getAllOrientations();
		// Pre-calculate patterns for all orientations
		$orientationPatterns = [];
		foreach( $orientations as $orientation ) {
			$orientationPatterns[$orientation] = $this->rotatePattern($pattern, ORIENTATION_NORTH, $orientation);
		}
		// For all points
		foreach( $points as $pointKey => $point ) {
			// For all orientation
			foreach( $orientations as $orientation ) {
				// Get all points for this orientation's pattern
				$patternPoints = $this->getPatternPointsRelativeTo($point, $orientationPatterns[$orientation]);
				// Check this orientation matches the existing points
				$indexedPatternPoints = $this->getIndexedPatternPoint($points, $patternPoints);
				// Return origin points OR all usable points (include other points)
				if( $indexedPatternPoints ) {
					//					$orientationPoints = $includeAllPoints ? $indexedPatternPoints : [$point];
					
					if( $includeAllPoints ) {
						$matchingPoints += $indexedPatternPoints;
					} else {
						$matchingPoints[$pointKey] = &$point;
					}
				}
			}
		}
		
		return $matchingPoints;
	}
	
	/**
	 * Get valid indexed pattern point list
	 *
	 * @param array $points
	 * @param array $patternPoints
	 * @param bool $requireAll
	 * @return array|null
	 */
	protected function getIndexedPatternPoint(array $points, array $patternPoints, bool $requireAll = true): ?array {
		$indexedPatternPoints = [];
		$valid = true;
		foreach( $patternPoints as $point ) {
			$key = $this->getPointIndex($point);
			if( !array_key_exists($key, $points) ) {
				if( $requireAll ) {
					$valid = false;
					break;
				} else {
					continue;
				}
			}
			$indexedPatternPoints[$key] = $point;
		}
		
		return $valid ? $indexedPatternPoints : null;
	}
	
	/**
	 * Get all pattern points matching available points
	 *
	 * @param int[][] $points
	 * @param int[][] $pattern
	 * @param int|null $limit
	 * @return int[][][]
	 */
	public function getMatchingPatternPoints(array $points, array $pattern, ?int $limit = null): array {
		// In a pattern, all coordinates are relative to the origin, the first point
		$points = $this->indexPoints($points);
		$matchingPatternPoints = [];
		$orientations = $this->getAllOrientations();
		// Pre-calculate patterns for all orientations
		$orientationPatterns = [];
		foreach( $orientations as $orientation ) {
			$orientationPatterns[$orientation] = $this->rotatePattern($pattern, ORIENTATION_NORTH, $orientation);
		}
		//		BgaLogger::get()->log(sprintf('getMatchingPatternPoints() - $points "%s" - $orientationPatterns "%s"', json_encode($points), json_encode($orientationPatterns)));
		// For all points
		foreach( $points as $point ) {
			// For all orientation
			foreach( $orientations as $orientation ) {
				// Get all points for this orientation's pattern
				$patternPoints = $this->getPatternPointsRelativeTo($point, $orientationPatterns[$orientation]);
				//				BgaLogger::get()->log(sprintf('getMatchingPatternPoints() - Orientation "%d" - $patternPoints "%s"', $orientation, json_encode($patternPoints)));
				// Check this orientation matches the existing points
				$indexedPatternPoints = $this->getIndexedPatternPoint($points, $patternPoints);
				//				BgaLogger::get()->log(sprintf('getMatchingPatternPoints() - $indexedPatternPoints "%s"', json_encode($indexedPatternPoints)));
				if( $indexedPatternPoints ) {
					$matchingPatternPoints[] = $patternPoints;
					if( $limit !== null && count($matchingPatternPoints) >= $limit ) {
						return $matchingPatternPoints;
					}
				}
			}
		}
		
		return $matchingPatternPoints;
	}
	
	public function equalsPoint($pointA, $pointB): bool {
		return $pointA[0] === $pointB[0] && $pointA[1] === $pointB[1];
	}
	
	/**
	 * Get point list of oriented pattern relative to $point
	 *
	 * @param array $point
	 * @param array $pattern
	 * @param int $orientation
	 * @return array
	 */
	public function getOrientedPatternPoints(array $point, array $pattern, int $orientation): array {
		$orientedPattern = $this->rotatePattern($pattern, ORIENTATION_NORTH, $orientation);
		//BgaLogger::get()->log(sprintf('getOrientedPatternPoints() - Origin point = %s while pattern = %s and orientation = %d => pattern: %s',
		//	json_encode($point), json_encode($pattern), $orientation, json_encode($orientedPattern)));
		
		return $this->getPatternPointsRelativeTo($point, $orientedPattern);
	}
	
	public function getPatternPointsRelativeTo(array $originPoint, array $pattern): array {
		$points = [];
		// Point [0, 0] may be absent, we don't care
		foreach( $pattern as $patternPoint ) {
			$points[] = $this->addPoints($originPoint, $patternPoint);
		}
		
		return $points;
	}
	
	public function addPoints(array $pointA, array $pointB): array {
		return [$pointA[0] + $pointB[0], $pointA[1] + $pointB[1]];
	}
	
	public function rotatePattern(array $pattern, int $from, int $to): array {
		// Origin point for rotation is always [0, 0]
		$rotated = $pattern;
		$path = static::$orientationPaths[$from][$to] ?? null;
		if( $path ) {
			foreach( $path as $function ) {
				$rotated = $this->$function($rotated);
			}
		}
		
		return $rotated;
	}
	
	public function flipX(array $points): array {
		$result = [];
		foreach( $points as [$x, $y] ) {
			$result[] = [-$x, $y];
		}
		
		return $result;
	}
	
	public function flipY(array $points): array {
		$result = [];
		foreach( $points as [$x, $y] ) {
			$result[] = [$x, -$y];
		}
		
		return $result;
	}
	
	public function invertXY(array $points): array {
		$result = [];
		foreach( $points as [$x, $y] ) {
			$result[] = [$y, $x];
		}
		
		return $result;
	}
	
	public function getPointIndex(array $point): string {
		return $point[0] . '-' . $point[1];
	}
	
	public function indexPoints(array $points): array {
		$indexed = [];
		foreach( $points as $point ) {
			$indexed[$this->getPointIndex($point)] = $point;
		}
		
		return $indexed;
	}
	
	public function getPatternFromSize(int $size): array {
		$pattern = [];
		for( $i = 0; $i < $size; $i++ ) {
			$pattern[] = [$i, 0];
		}
		
		return $pattern;
	}
	
	public function getAllOrientations(): array {
		return [ORIENTATION_NORTH, ORIENTATION_EAST, ORIENTATION_SOUTH, ORIENTATION_WEST];
	}
	
	public function getDirectionOrientations(): array {
		return [ORIENTATION_SOUTH, ORIENTATION_EAST];
	}
	
}
