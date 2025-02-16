/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { stringService } from "../../service/string.service.js";

export class TileCell {
	
	static SELECTABLE_TYPE_CLASSES = {
		n: 'selectable-normal',
		a: 'selectable-adjacent',
		c: 'selectable-cover',
	};
	
	constructor(x, y, material, grid) {
		this.x = x;
		this.y = y;
		this.material = material;
		/** @type {CellGrid} */
		this.grid = grid;
		this.token = null;
		this.forcedCoords = false;
		this.$element = this.#buildElement();
	}
	
	forceCoords(value = true) {
		this.forcedCoords = value;
		this.refresh();
	}
	
	refresh() {
		const tokenChanged = !this.token || !this.$element.contains(this.token.getElement());
		if( tokenChanged ) {
			// Always empty
			this.$element.innerHTML = '';
			// Fill with token
			if( this.token ) {
				this.$element.append(this.token.getElement());
				this.grid.animateMove(this.token, 200);
			}
		}
		if( this.forcedCoords ) {
			this.$element.title = stringService.replace('({0}, {1})', [this.x, this.y]);
		}
	}
	
	setSelectable(type) {
		this.$element.classList.add('selectable');
		if( type && TileCell.SELECTABLE_TYPE_CLASSES[type] ) {
			this.$element.classList.add(TileCell.SELECTABLE_TYPE_CLASSES[type]);
		}
		
		return this;
	}
	
	notSelectable() {
		this.$element.classList.remove('selectable');
		// Remove any selectable type class
		Object.values(TileCell.SELECTABLE_TYPE_CLASSES)
			.forEach(cssClass => this.$element.classList.remove(cssClass));
		
		return this;
	}
	
	removeToken() {
		this.token = null;
		
		return this;
	}
	
	/**
	 * @param {AbstractGameToken} token
	 * @returns {TileCell}
	 */
	assignToken(token) {
		this.token = token;
		
		return this;
	}
	
	/**
	 * @returns {Element}
	 */
	getElement() {
		return this.$element;
	}
	
	/**
	 * @returns {Element}
	 */
	#buildElement() {
		const $element = document.createElement('div');
		$element.classList.add('tile', 'x-' + this.x, 'y-' + this.y, this.material.key);
		$element.cell = this;
		return $element;
	}
	
}
