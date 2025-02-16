/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { arrayService } from "../../../service/array.service.js";
import { Deferred } from "../../../event/Deferred.js";
import { BuildableElement } from "../../../component/BuildableElement.js";
import { eventService } from "../../../service/event.service.js";
import { domService } from "../../../service/dom.service.js";
import { Token } from "../Token.js";

/**
 * @property {Function} onChange
 * @property {Function} changed
 */
export class AbstractTokenList extends BuildableElement {
	
	CLASS_SELECTABLE = 'selectable';
	CLASS_SELECTED = 'selected';
	
	/**
	 * @param {Element} $element
	 */
	constructor($element) {
		super();
		// 0 is the top
		this.$element = $element;
		this.list = [];
		this.orderedList = {};
		this.pickingListener = null;
		this.pickingDeferred = null;
		this.selectListener = null;
		this.selectDeferred = null;
		this.picking = false;
		this.wrapping = false;
		this.notifyAboutSelection = true;
		this.initialize();
	}
	
	initialize() {
		// Used to initialize children
	}
	
	size() {
		return this.list.length;
	}
	
	/**
	 * @returns {AbstractGameToken}
	 */
	getFirst() {
		return this.list[0];
	}
	
	getIdList() {
		return this.list.map(token => token.id());
	}
	
	/**
	 * @returns {Object}
	 */
	getOrderedTokens() {
		return this.orderedList;
	}
	
	/**
	 * @returns {Array}
	 */
	getTokens() {
		return Object.values(this.orderedList).flat();
	}
	
	/**
	 * Start picking one card from any in hand
	 * @returns {DeferredPromise}
	 */
	startPicking() {
		if( this.pickingDeferred ) {
			// TODO chain deferred to previous one using when() (to develop)
			return this.pickingDeferred.promise();
		}
		this.pickingDeferred = new Deferred();
		// Bind all elements
		this.pickingListener = event => {
			// On pick one
			this.pickingDeferred.resolve(event.currentTarget.token);
		};
		this.enablePicking();
		
		return this.pickingDeferred.promise();
	}
	
	togglePicking(enable) {
		if( enable ) {
			this.enablePicking();
		} else {
			this.disablePicking();
		}
	}
	
	/**
	 * Stop picking but keep listeners
	 */
	disablePicking() {
		if( !this.picking ) {
			return;
		}
		this.getTokens()
			.forEach(token => this.unbindPickingToken(token));
		this.$element.classList.remove('select-token');
		this.picking = false;
	}
	
	enablePicking() {
		if( this.picking ) {
			return;
		}
		this.getTokens()
			.forEach(token => this.bindPickingToken(token));
		this.$element.classList.add('select-token');
		this.picking = true;
	}
	
	stopPicking() {
		this.disablePicking();
		this.pickingListener = null;
		this.pickingDeferred = null;
	}
	
	/**
	 * @private
	 */
	bindPickingToken(token) {
		token.getElement().addEventListener('click', this.pickingListener);
	}
	
	/**
	 * @private
	 */
	unbindPickingToken(token) {
		token.getElement().removeEventListener('click', this.pickingListener);
	}
	
	isPicking() {
		return !!this.pickingListener;
	}
	
	isSelecting() {
		return !!this.selectableTokens;
	}
	
	stopPickingList() {
		// Called so much when initializing app, we fold all restaurants
		if( !this.isSelecting() ) {
			return;
		}
		// Unbind all
		this.getTokens()
			.forEach(token => this.unbindSelectToken(token));
		this.selectDeferred = null;
		this.selectionLimit = null;
		this.selectableTokens = null;
		this.selection = null;
	}
	
	/**
	 * Start picking a set of card from hand
	 * @param {Array|null} onlyTokens
	 * @param {Array|null} selection
	 * @param {Number|null} limit
	 */
	startPickingList(onlyTokens, selection = null, limit = null) {
		if( this.isSelecting() ) {
			// TODO chain deferred to previous one using when() (to develop)
			console.info('Already having a select deferred', this.selectDeferred);
			return this.selectDeferred.promise();
		}
		{
			// Format onlyTokens & selection
			const allTokens = this.getTokens();
			if( !onlyTokens || (!onlyTokens.length && !onlyTokens.size) ) {
				onlyTokens = allTokens;
			} else {
				// Filter any given array/Set
				onlyTokens = [...onlyTokens].filter(token => allTokens.includes(token));
			}
			if( !selection || (!selection.length && !selection.size) ) {
				selection = [];
			} else {
				// Filter any given array/Set
				selection = [...selection].filter(token => onlyTokens.includes(token));
			}
		}
		this.selectDeferred = new Deferred();
		this.selectionLimit = limit;
		this.selectableTokens = new Set(onlyTokens);
		this.selection = new Set();
		// Init selection as with pre-selected tokens - Tokens are not binded yet
		
		// Bind tokens
		this.refreshPickingList();
		
		this.notifyAboutSelection = false;
		selection.forEach(token => {
			token.select();
		});
		this.notifyAboutSelection = true;
		// Notify changes and refresh
		this.notifySelectionChanges();
		
		return this.selectDeferred.promise();
	}
	
	getSelection() {
		return this.selection ? [...this.selection] : [];
	}
	
	updateSelectionLimit(limit) {
		this.selectionLimit = Number.parseInt(limit);
		this.refreshPickingList();
	}
	
	refreshPickingList() {
		// Refresh even if not picking because a picked element could be incoming and in selectable state
		const selecting = this.isSelecting();
		const limitReached = this.isLimitReached();
		const selection = this.getSelection();
		// Coud unbind recently moved tokens
		this.getTokens()
			.forEach(token => {
				const selectable = selecting && this.selectableTokens.has(token) && (!limitReached || selection.includes(token));
				if( selectable ) {
					// Bind selection, expecting unselect
					this.bindSelectToken(token);
				} else {
					// Unbind selection
					this.unbindSelectToken(token);
				}
			});
	}
	
	notifySelectionChanges() {
		// On change selection
		this.refreshPickingList();
		this.selectDeferred.resolve(this.selection);
	}
	
	resetSelection() {
		if( !this.selection.size ) {
			return;
		}
		this.selection.forEach(token => this.unselectToken(token, false));
		this.notifySelectionChanges();
	}
	
	selectToken(token, notify = true) {
		// onSelect listener already mark token as selected
		if(
			!(this.isSelecting() && !this.selection.has(token) && this.selectableTokens.has(token)) &&
			!this.isPicking()
		) {
			return false;
		}
		if( this.isSelecting() ) {
			this.selection.add(token);
			if( notify && this.notifyAboutSelection ) {
				this.notifySelectionChanges();
			}
		}
		return true;
	}
	
	unselectToken(token, notify = true) {
		if(
			!(this.isSelecting() && this.selection.has(token) && this.selectableTokens.has(token)) &&
			!this.isPicking()
		) {
			return false;
		}
		token.notSelected();
		if( this.isSelecting() ) {
			this.selection.delete(token);// Set
			if( notify ) {
				this.notifySelectionChanges();
			}
		}
		return true;
	}
	
	isLimitReached() {
		// If limit is 0, limit is reached
		const selection = this.getSelection();
		return !this.selectionLimit || selection.length >= this.selectionLimit;
	}
	
	/**
	 * @param {AbstractGameToken} token
	 * @private
	 */
	bindSelectToken(token) {
		if( token.isSelectable() ) {
			// Already selectable
			return;
		}
		token.onSelect()
			.then(() => this.selectToken(token));
		token.onUnselect()
			.then(() => this.unselectToken(token));
	}
	
	/**
	 * @param {AbstractGameToken} token
	 * @private
	 */
	unbindSelectToken(token) {
		token.offSelect();
	}
	
	/**
	 * @param {AbstractGameToken} token
	 */
	add(token) {
		const position = token.position();
		if( !this.orderedList[position] ) {
			this.orderedList[position] = [];
		}
		this.orderedList[position].push(token);
		this.list.push(token);
		if( this.pickingListener ) {
			this.bindPickingToken(token);
		}
		// Move
		token.previousContainer = token.elementContainer;
		token.$previousParent = token.getParentElement();
		token.elementContainer = this;
		// Event
		this.changed();
	}
	
	animateMove(token, duration) {
		if( !token.$previousParent ) {
			// Ignore first move
			return;
		}
		if( !duration ) {
			duration = 500;
		}
		// See https://en.doc.boardgamearena.com/Game_interface_logic:_yourgamename.js#Animations
		const $element = token.getElement();
		app.ui.placeOnObject($element, token.$previousParent);
		const animation = app.ui.slideToObject($element, $element.parentNode, duration);
		app.dojo.connect(animation, 'onEnd', function () {
			$element.style.removeProperty('left');
			$element.style.removeProperty('top');
		});
		animation.play();
		// Now this is animated, detach previous parent to not play it again
		token.$previousParent = null;
	}
	
	getPreviousToken(token) {
		let previous = null;
		for( const loopToken of this.getTokens() ) {
			if( loopToken === token ) {
				return previous;
			}
			previous = loopToken;
		}
		// Last element
		return previous;
	}
	
	getTokenWrapper(token) {
		const $element = token.getElement();
		if( $element.parentNode.classList.contains(Token.WRAPPER_CLASS) ) {
			return $element.parentNode;
		}
		return $element;
	}
	
	/**
	 * @param {AbstractGameToken} token
	 * @return Element
	 */
	wrapToken(token) {
		if( !this.wrapping ) {
			return token.getElement();
		}
		const $wrapper = domService.createElement('div', Token.WRAPPER_CLASS);
		$wrapper.append(token.getElement());
		return $wrapper;
	}
	
	/**
	 * @param {AbstractGameToken} token
	 * @param {Number|undefined} position
	 * @returns {boolean}
	 */
	remove(token, position) {
		if( position === undefined ) {
			position = token.position();
		}
		// Remove from non-ordered list
		if( !arrayService.removeElement(this.list, token) ) {
			console.error(`Unable to find token's position ${position} for token`, token, 'in', this.orderedList);
			return false;
		}
		// Remove from ordered list
		if( !this.orderedList[position] ) {
			console.error(`Unable to find token's position ${position} for token`, token, 'in', this.orderedList);
			return false;
		}
		const removed = arrayService.removeElement(this.orderedList[position], token);
		if( !removed ) {
			console.error(`Unable to remove token from position ${position} for token`, token, 'in', this.orderedList, this.orderedList[position]);
			return false;
		}
		if( this.pickingListener ) {
			this.unbindPickingToken(token);
		}
		this.changed();
		return true;
	}
	
	refresh() {
		this.build();
		this.refreshPickingList();
	}
	
	build() {
		throw new Error(`Please implement build() method for ${this.constructor.name}`);
	}
}

eventService.useElementListener(AbstractTokenList.prototype);
eventService.useOnChange(AbstractTokenList.prototype);

