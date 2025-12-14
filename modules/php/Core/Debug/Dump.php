<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Core\Debug;

use \Bga\Games\DinnerInParis\Entity\Token;
use \Bga\Games\DinnerInParis\Game\AbstractTokenList;
use ReflectionClass;

class Dump {
	
	public static function shortName($object): string {
		return (new ReflectionClass($object))->getShortName();
	}
	
	public static function tokenDetailsList($list): string {
		$list = $list instanceof AbstractTokenList ? $list->getTokens() : $list;
		
		return json_encode(array_values(array_map(function (?Token $token) {
				return $token ? [$token->getId(), $token->getPosition(), $token->getMaterial()['key'], $token->getContainer(), $token->getPlayerId()] : null;
			}, $list))) . '(' . count($list) . ')';
	}
	
	public static function tokenIdList($list): string {
		$list = $list instanceof AbstractTokenList ? $list->getTokens() : $list;
		
		return json_encode(array_values(array_map(function (?Token $token) {
				return $token ? $token->getId() : 'null';
			}, $list))) . '(' . count($list) . ')';
	}
	
	public static function bool($value): string {
		return $value ? 'YES' : 'no';
	}
	
	public static function point(array $point): string {
		return sprintf('(%s, %s)', $point[0], $point[1]);
	}
	
}
