/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

export class Token {
	static CONTAINER_BOX = 0;
	static CONTAINER_BOARD_RIVER = 1;
	static CONTAINER_BOARD_DECK = 2;
	static CONTAINER_BOARD_DISCARD = 3;
	static CONTAINER_BOARD_GRID = 4;
	static CONTAINER_PLAYER_BOARD = 6;
	static CONTAINER_PLAYER_HAND = 7;
	static CONTAINER_PLAYER_DISCARD = 8;
	
	static CONTAINER = {
		0: 'box',
		1: 'board-river',
		2: 'board-deck',
		3: 'board-discard',
		4: 'board-grid',
		6: 'player-board',
		7: 'player-hand',
		8: 'player-discard',
	}
	
	static TYPE_RESOURCE_CARD = 1;
	static TYPE_RESTAURANT = 2;
	static TYPE_PROPERTY = 3;
	static TYPE_TERRACE = 4;
	static TYPE_PIGEON_CARD = 5;
	static TYPE_OBJECTIVE_CARD = 6;
	static TYPE_MAJORITY_CARD = 7;
	
	static VARIANT_RESOURCE_ = 7;
	
	static WRAPPER_CLASS = 'token-wrapper';
}

Object.freeze(Token);
