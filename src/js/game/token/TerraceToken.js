/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractGameToken } from "./AbstractGameToken.js";
import { Token } from "./Token.js";

export class TerraceToken extends AbstractGameToken {
	
	constructor(data, player) {
		super(data, player);
	}
	
	getCategoryIndex() {
		return parseInt(this.position() / 20);
	}
	
	getCategory() {
		return parseInt(this.position() / 20) + 1;
	}
	
	inPlayerBoard() {
		return this.container() === Token.CONTAINER_PLAYER_BOARD;
	}
	
	location() {
		let location = super.location();
		if( this.inPlayerBoard() ) {
			location += '-' + this.getCategory();
		}
		return location;
	}
	
	buildElement() {
		const element = super.buildElement();
		element.classList.add(`terrace-${this.player.colorKey}`);
		return element;
	}
	
	type() {
		return 'terrace';
	}
	
}
