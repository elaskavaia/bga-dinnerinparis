/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { ORIENTATION, ORIENTATION_REVERSE_MAP } from "../constants.js";
import { Token } from "./Token.js";
import { objectService } from "../../service/object.service.js";
import { AbstractSuperToken } from "./AbstractSuperToken.js";

export class AbstractGameToken extends AbstractSuperToken {
	
	static PURPOSE_NORMAL = 'normal';
	static PURPOSE_PREVIEW = 'preview';
	
	/**
	 * @param {object} data
	 * @param {Player} player
	 * @param {object} material
	 * @param {GameTable} table
	 */
	constructor(data, player, material, table = null) {
		super();
		this.data = data;
		this.player = player;
		this.material = material;
		this.table = table;
		this.purpose = AbstractGameToken.PURPOSE_NORMAL;
		this.translatable = true;
		this.initialize();
	}
	
	/**
	 * Reset object (used by constructor and clone)
	 */
	initialize() {
		this.$element = null;
		this.orientation = null;
		this.cells = null;
	}
	
	clone(purpose) {
		const clone = objectService.clone(this);
		clone.initialize();
		clone.purpose = purpose;
		return clone;
	}
	
	getParentElement() {
		let $parent = this.getElement().parentNode;
		// Allow only DOMElement and get over wrapper
		if( $parent instanceof Element && $parent.classList.contains(Token.WRAPPER_CLASS) ) {
			$parent = $parent.parentNode;
		}
		// Allow only DOMElement
		$parent = $parent instanceof Element ? $parent : null;
		return $parent;
	}
	
	onSelect() {
		// Start listening once
		if( !this.selectClickListener ) {
			this.selectClickListener = this.onClick()
				.then(() => this.toggleSelect());
			this.stateSelectListener = super.onSelect()
				.then(() => this.setSelected());
			this.stateUnselectListener = super.onUnselect()
				.then(() => this.notSelected());
			// Set state
			this.setSelectable();
		}
		
		return super.onSelect();
	}
	
	/**
	 * Disconnect select, all select listeners are removed as select is disabled
	 */
	offSelect() {
		if( !this.selectClickListener ) {
			return;
		}
		// Set state
		this.notSelectable();
		this.notSelected();
		// Remove event listeners
		super.off(this.selectClickListener);
		super.off(this.stateSelectListener);
		super.off(this.stateUnselectListener);
		super.offSelect();// Before unselect to NOT trigger events
		this.unselect();
		this.selectClickListener = null;
		this.stateSelectListener = null;
		this.stateUnselectListener = null;
	}
	
	isSelectable() {
		return this.getElement().classList.contains(this.CLASS_SELECTABLE);
	}
	
	setSelectable() {
		this.getElement().classList.add(this.CLASS_SELECTABLE);
		
		return this;
	}
	
	notSelectable() {
		this.getElement().classList.remove(this.CLASS_SELECTABLE);
		
		return this;
	}
	
	setSelected() {
		this.getElement().classList.add(this.CLASS_SELECTED);
		
		return this;
	}
	
	notSelected() {
		this.getElement().classList.remove(this.CLASS_SELECTED);
		
		return this;
	}
	
	getCloneElement() {
		const $clone = this.buildElement();
		const $element = this.getElement();
		try {
			// Temporarily set DOM element to clone and refresh to auto apply changes
			this.$element = $clone;
			this.refresh();
		} finally {
			// If an error occur while refreshing, we always want to revert
			this.$element = $element;
		}
		return $clone;
	}
	
	/**
	 * @returns {Element}
	 */
	buildElement() {
		const $element = document.createElement('div');
		if( this.id() ) {
			$element.dataset.id = this.id();
		}
		$element.classList.add('token', 'token-' + this.type(), this.materialKey());
		$element.token = this;
		if( this.size() ) {
			$element.classList.add('token-size-' + this.size());
		}
		if( this.orientation ) {
			$element.classList.add(this.getOrientationClass());
		}
		return $element;
	}
	
	id() {
		return this.data.id;
	}
	
	/**
	 * @param {GameTable} table
	 */
	calculate(table) {
		this.setOrientation(this.data.orientation ? ORIENTATION_REVERSE_MAP[this.data.orientation] : null);
	}
	
	refresh() {
	}
	
	inPlayerBoard() {
		return !!this.player;
		// return [Token.CONTAINER_PLAYER_BOARD, Token.CONTAINER_PLAYER_HAND, Token.CONTAINER_PLAYER_DISCARD].includes(this.container());
	}
	
	container() {
		return this.data.container;
	}
	
	variant() {
		return this.data.variant;
	}
	
	containerKey() {
		return Token.CONTAINER[this.container()];
	}
	
	label() {
		// Material label was declared to translation system using clienttranslate() in material.inc.php
		return this.visible() ? (this.material ? (this.translatable ? _(this.material.label) : this.material.label) : '{MaterialIssue}') : _('Hidden Card');
	}
	
	debugLabel() {
		return `"${this.label()}" #${this.id()}`;
	}
	
	position() {
		return this.data.position || 0;
	}
	
	size() {
		return this.material ? this.material.size : null;
	}
	
	materialKey() {
		return this.material ? this.material.key : this.type();
	}
	
	visible() {
		// The current player can see the face ?
		return this.data.visible;
	}
	
	setTable(table) {
		this.table = table;
	}
	
	/**
	 * Should return the int in the future
	 * @return {string}
	 */
	type() {
		throw new Error('Missing type() of token');
	}
	
	/**
	 * @return {string}
	 */
	typeKey() {
		return this.type();
	}
	
	tableLocation() {
		return {location: this.location(), position: this.position()};
	}
	
	location() {
		return (this.inPlayerBoard() ? 'player-' + this.player.id : 'board') + '-' + this.container() + '-' + this.data.type;
	}
	
	isHorizontal() {
		return this.orientation === ORIENTATION.NORTH || this.orientation === ORIENTATION.SOUTH;
	}
	
	isVertical() {
		return this.orientation === ORIENTATION.EAST || this.orientation === ORIENTATION.WEST;
	}
	
	getPlacementPattern() {
		// [{x: 0, y: 0}, {x: 1, y: 0}]
		// Main cell must be the most top/left one (lower x & y)
		const pattern = [];
		const xMax = this.isHorizontal() ? this.material.size : 1;
		const yMax = this.isVertical() ? this.material.size : 1;
		for( let x = 0; x < xMax; x++ ) {
			for( let y = 0; y < yMax; y++ ) {
				pattern.push({x: x, y: y});
			}
		}
		return pattern;
	}
	
	assignCells(cells) {
		this.cells = cells;
		cells.forEach(cell => cell.assignToken(this));
	}
	
	setOrientation(orientation) {
		// Remove previous class
		if( this.orientation ) {
			this.getElement().classList.remove(this.getOrientationClass());
		}
		this.orientation = orientation;
		// Set new class
		if( this.orientation ) {
			this.getElement().classList.add(this.getOrientationClass());
		}
	}
	
	getOrientationClass() {
		return 'token-face-' + this.orientation;
	}
}
