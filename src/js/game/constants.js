/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 * @see constants.php
 */
export const ORIENTATION = {
	NORTH: 'north',
	EAST: 'east',
	SOUTH: 'south',
	WEST: 'west',
}
export const ORIENTATION_MAP = {
	north: 1,
	east: 2,
	south: 3,
	west: 4,
}
export const ORIENTATION_REVERSE_MAP = Object.fromEntries(Object.entries(ORIENTATION_MAP).map(a => a.reverse()))
