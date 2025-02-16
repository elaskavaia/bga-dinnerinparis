/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractGameToken } from "./AbstractGameToken.js";
import { Token } from "./Token.js";

export class RestaurantToken extends AbstractGameToken {
	
	constructor(data, player, material, table = null) {
		super(data, player, material, table);
		this.propertyToken = null;
		this.translatable = false;
	}
	
	refresh() {
		super.refresh();
		const $element = this.getElement();
		
		if( this.container() === Token.CONTAINER_BOARD_GRID ) {
			$element.classList.add('clickable');
			$element.addEventListener('click', this.clickListener = () => {
				this.table.showRestaurantDetails(this.variant());
			})
			$element.title = this.label();
		} else {
			$element.classList.remove('clickable');
			$element.removeAttribute('title');
			$element.removeEventListener('click', this.clickListener);
		}
	}
	
	calculate(table) {
		super.calculate(table);
		
		let propertyToken = null;
		if( this.player ) {
			const playerBoard = table.getPlayerBoard(this.player);
			if( playerBoard ) {
				propertyToken = playerBoard.getPropertyToken(this.material);
			}
		}
		this.assignProperty(propertyToken);
	}
	
	inPlayerBoard() {
		return false;// Can never be in player board, only on table board
	}
	
	type() {
		return 'restaurant';
	}
	
	location() {
		let location = super.location();
		if( this.container() === Token.CONTAINER_BOX ) {
			location += '-' + this.variant();
		}
		return location;
	}
	
	/**
	 * @param {PropertyToken} token
	 */
	assignProperty(token) {
		// you may want to use refresh() ?
		this.propertyToken = token;
		if( token ) {
			this.getElement().append(token.getElement());
		} else {
			this.getElement().innerHTML = '';
		}
	}
	
}
