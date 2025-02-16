/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { BuildableElement } from "../component/BuildableElement.js";
import { domService } from "../service/dom.service.js";
import { PropertyToken } from "./token/PropertyToken.js";
import { Modal } from "../component/Modal.js";
import { VirtualTokenList } from "./token/list/VirtualTokenList.js";
import { TerraceList } from "./token/list/TerraceList.js";
import { WrappingTokenList } from "./token/list/WrappingTokenList.js";

export class PlayerBoard extends BuildableElement {
	
	/**
	 * @param {Player} player
	 */
	constructor(player) {
		super();
		this.player = player;
		this.initializeElement();// Force pre-build
		this.bindEvents();
	}
	
	prepareElement() {
		this.$balanceToken = this.$element.querySelector('.token-cash.cash-balance');
		this.terraceListCategories = [];
		this.terraceListCategories.push(new TerraceList(this.$element.querySelector('.track-category-1')));
		this.terraceListCategories.push(new TerraceList(this.$element.querySelector('.track-category-2')));
		this.terraceListCategories.push(new TerraceList(this.$element.querySelector('.track-category-3')));
		this.terraceListCategories.push(new TerraceList(this.$element.querySelector('.track-category-4')));
		this.resourceCardHand = new WrappingTokenList(this.$element.querySelector('.player-hand > .card-set.set-resource-card'));
		this.pigeonCardHand = new WrappingTokenList(this.$element.querySelector('.player-hand > .card-set.set-pigeon-card'));
		this.objectiveCardHand = new WrappingTokenList(this.$element.querySelector('.player-hand > .card-set.set-objective-card'));
		this.pigeonCardDiscard = new WrappingTokenList(this.$element.querySelector('.player-graveyard > .card-set.set-pigeon-card'));
		this.objectiveCardDiscard = new WrappingTokenList(this.$element.querySelector('.player-graveyard > .card-set.set-objective-card'));
		this.pendingList = new VirtualTokenList();
	}
	
	bindEvents() {
		this.player
			.onChange()
			.then(() => this.refresh());
		this.$element.querySelector('.action-view').addEventListener('click', () => {
			const $gameBoard = this.$element.querySelector('.game-player-board');
			const $gameBoardPreview = domService.castElement(`<div class="player-board-preview"></div>`);
			$gameBoardPreview.append($gameBoard.cloneNode(true));
			const modal = new Modal(app.pageOverlayViewport);
			modal
				.setTitle(_('Large Player Board'))
				.setLarge()
				.setBody($gameBoardPreview)
				.show();
		});
	}
	
	build() {
		// Rebuild all lists of board
		for( const terraceListCategory of this.terraceListCategories ) {
			terraceListCategory.build();
		}
		this.resourceCardHand.build();
		this.pigeonCardHand.build();
		this.pigeonCardDiscard.build();
		this.objectiveCardHand.build();
		this.objectiveCardDiscard.build();
		
		// Bind events
		this.pigeonCardDiscard.onChange()
			.then(() => this.checkGraveyard());
		this.objectiveCardDiscard.onChange()
			.then(() => this.checkGraveyard());
		
		this.refresh();
	}
	
	checkGraveyard() {
		this.$element.querySelector('.player-graveyard').hidden = !this.pigeonCardDiscard.size() && !this.objectiveCardDiscard.size();
	}
	
	/**
	 * @returns {Element}
	 */
	buildElement() {
		const $element = document.createRange().createContextualFragment(`
<div class="game-player-set">
	<div class="game-player-board">
		<div class="track-revenue">
			<div class="token token-cash cash-balance"></div>
			<div class="token token-cash cash-income"></div>
		</div>
		<div class="track-category-1"></div>
		<div class="track-category-2"></div>
		<div class="track-category-3"></div>
		<div class="track-category-4"></div>
		<div class="actions">
			<button class="btn action-view" type="button"><i class="fa fa-eye"></i></button>
		</div>
	</div>
	<div class="player-hand">
		<div class="card-set set-resource-card"></div>
		<div class="card-set set-pigeon-card"></div>
		<div class="card-set set-objective-card"></div>
	</div>
	<div class="player-graveyard" hidden>
		<h3 class="title">Discard</h3>
		<div class="card-set overlap size-79 set-pigeon-card"></div>
		<div class="card-set overlap size-79 set-objective-card"></div>
	</div>
</div>`).firstElementChild;
		$element.classList.add('player-' + this.player.id);
		return $element;
	}
	
	getPropertyToken(restaurantMaterial) {
		return new PropertyToken({}, this.player, restaurantMaterial);
	}
	
	calculateRevenueX(value) {
		// return 1 + parseInt(value / 13);
		return 1 + parseInt(value / 14);
	}
	
	calculateRevenueY(value) {
		// return value % 13;
		return 1 + (value - 1) % 13;
	}
	
	bound(number, min, max) {
		// Any falsy value is 0
		number = number ? parseInt(number) : 0;
		if( number < min ) {
			number = min;
		} else if( number > max ) {
			number = max;
		}
		
		return number;
	}
	
	refresh() {
		const $board = this.$element.firstElementChild;
		const income = this.bound(this.player.income, 1, 26);
		const balance = this.bound(this.player.balance, 0, 26);
		// The balance range is 0, 26. Null value is allowed, displayed as 0.
		domService.toggleClass(this.$balanceToken, 'on-break', !balance);
		domService.toggleClass($board, 'revenue-equality', balance === income);
		if( balance ) {
			$board.style.setProperty('--balance-x', this.calculateRevenueX(balance));
			$board.style.setProperty('--balance-y', this.calculateRevenueY(balance));
		} else {
			$board.style.removeProperty('--balance-x');
			$board.style.removeProperty('--balance-y');
		}
		// The income range is 1, 26
		$board.style.setProperty('--income-x', this.calculateRevenueX(income));
		$board.style.setProperty('--income-y', this.calculateRevenueY(income));
		this.checkGraveyard();
	}
	
	
}
