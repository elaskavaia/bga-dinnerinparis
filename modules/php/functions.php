<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

function typeOf($value): string {
	$type = gettype($value);
	if( $type === 'object' ) {
		return get_class($value);
	}
	if( $type === 'array' ) {
		return sprintf('%s(%d)', $type, count($value));
	}
	
	return $type;
}

function stringify($value): string {
	return json_encode($value);
}

function dump(...$values) {
	foreach( $values as $value ) {
		echo sprintf("[%s] %s<br>\n", typeOf($value), stringify($value));
	}
}
