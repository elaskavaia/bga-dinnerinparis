/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractTokenList } from "./AbstractTokenList.js";
import { domService } from "../../../service/dom.service.js";
import { tokenService } from "../../../service/token.service.js";
import { eventService } from "../../../service/event.service.js";
import { PigeonCard } from "../PigeonCard.js";
import { AbstractCard } from "../AbstractCard.js";

/**
 * Unordered restaurant pile for box
 *
 * @property {Function} on
 * @property {Function} off
 * @property {Function} trigger
 * @property {Function} onSelect
 * @property {Function} select
 * @property {Function} onUnselect
 * @property {Function} unselect
 * @property {Function} toggleSelect
 */
export class RestaurantPile extends AbstractTokenList {
	
	constructor($element, material, sampleRestaurantToken, table) {
		super($element);
		
		this.material = material;
		this.sampleRestaurantToken = sampleRestaurantToken;
		this.table = table;
		this.app = table.app;
		this.$innerElement = null;
	}
	
	previewBuildMode(build) {
		// Complete cards' id with tokens
		build.selectedTokens = build.cards.map(cardId => this.table.getToken(cardId));
		this.buildingRestaurant = build;
		this.buildingRestaurantIsPreview = true;
		// First display of resource cards for restaurant costs
		this.refreshBuildSelection();
		this.$panel.querySelector('.restaurant-build').hidden = false;
	}
	
	enableBuildMode(build) {
		if( !build.costTree ) {
			build.costTree = tokenService.getKeyCosts(build.costs);
		}
		this.buildingModeShowReady = false;
		this.buildingRestaurant = build;
		this.buildingRestaurantIsPreview = false;
		const playerResourceHand = this.getPlayerResourceHand();
		const playerPigeonHand = this.getPlayerPigeonHand();
		this.buildingRestaurant.selectedTokens = new Set;
		const availableCards = new Set;// All card usable to pay any restaurant of list
		
		// List all available (selectable) card for to build this restaurant
		build.costs.forEach(cost => {
			cost.cards.forEach(cardId => availableCards.add(this.table.getToken(cardId)));
		});
		// Use first selection from possible build solutions
		Object.values(build.costTree)[0].forEach(cardId => {
			this.buildingRestaurant.selectedTokens.add(this.table.getToken(cardId));
		})
		const hasPigeonCard = [...availableCards].some(token => token instanceof PigeonCard);
		if( playerResourceHand ) {
			this.$element.addEventListener('fold', this.foldListener = () => {
				playerResourceHand.stopPickingList();
				playerPigeonHand.stopPickingList();// Always try to stop, even if not selecting
			});
			this.$element.addEventListener('unfold', this.unfoldListener = () => {
				// Store to prevent change on selection after first startPickingList
				const selectedTokens = [...this.buildingRestaurant.selectedTokens];
				// Limit will be updated after each refresh to synchronize all lists
				playerResourceHand.startPickingList(availableCards, selectedTokens, this.buildingRestaurant.costs.length)
					.then(() => {
						if( this.buildingRestaurant ) {
							// Refresh display of resource cards for restaurant costs
							this.refreshBuildSelection();
						}// Else no more in building mode. When ending, everything is unselected
					});
				if( hasPigeonCard ) {
					playerPigeonHand.startPickingList(availableCards, selectedTokens, 1)
						.then(() => {
							if( this.buildingRestaurant ) {
								// Refresh display of resource cards for restaurant costs
								this.refreshBuildSelection();
							}// Else no more in building mode. When ending, everything is unselected
						});
				}
				this.buildingModeShowReady = true;
				// First display of resource cards for restaurant costs
				this.refreshBuildSelection();
			});
		}
		// First display of resource cards for restaurant costs
		this.$panel.querySelector('.restaurant-build').hidden = false;
		
		return this;
	}
	
	disableBuildMode() {
		if( !this.buildingRestaurant ) {
			return;
		}
		this.buildingRestaurant = null;
		const $cardList = this.$panel.querySelector('.restaurant-build .build-resource-cards');
		$cardList.innerHTML = '';// Empty
		this.$panel.querySelector('.restaurant-build').hidden = true;
		const playerResourceHand = this.getPlayerResourceHand();
		if( playerResourceHand ) {
			playerResourceHand.stopPickingList();
		}
		const playerPigeonHand = this.getPlayerPigeonHand()
		if( playerPigeonHand ) {
			playerPigeonHand.stopPickingList();
		}
		this.foldListener && this.$element.removeEventListener('fold', this.foldListener);
		this.unfoldListener && this.$element.removeEventListener('unfold', this.unfoldListener);
	}
	
	refreshBuildSelection() {
		if( !this.buildingRestaurant ) {
			console.warn('refreshBuildSelection() while no build');
			return;
		}
		// Hands
		const playerResourceHand = this.getPlayerResourceHand();
		// Re-calculate selection
		if( this.buildingModeShowReady ) {
			const playerPigeonHand = this.getPlayerPigeonHand();
			const resourceCardSelection = playerResourceHand.getSelection();
			const pigeonCardSelection = playerPigeonHand.getSelection();
			// Complete selection
			this.buildingRestaurant.selectedTokens = resourceCardSelection.concat(pigeonCardSelection);
			// Update list limits
			if( playerPigeonHand.isSelecting() ) {
				const required = this.buildingRestaurant.costs.length;
				const current = resourceCardSelection.length + pigeonCardSelection.length;
				const missing = required - current;
				playerResourceHand.updateSelectionLimit(Math.min(required, missing + resourceCardSelection.length));
				playerPigeonHand.updateSelectionLimit(Math.min(required, missing + pigeonCardSelection.length));
			}// Else we don't update a lone list
		}// Else use previous value
		// Content
		this.$panel.querySelectorAll('.mode-read').forEach($element => $element.hidden = !this.buildingRestaurantIsPreview);
		this.$panel.querySelectorAll('.mode-edit').forEach($element => $element.hidden = this.buildingRestaurantIsPreview);
		// Cost list
		const $cardList = this.$panel.querySelector('.restaurant-build .build-resource-cards');
		$cardList.innerHTML = '';// Empty
		this.buildingRestaurant.selectedTokens.forEach(card => {
			/** @type {AbstractCard} */
			const cardClone = card.clone();
			cardClone.viewSize = AbstractCard.SIZE_SMALL;
			const $choice = domService.castElement(`<button class="btn btn-card card-resource-container" type="button"></button>`);
			if( this.buildingRestaurantIsPreview || !this.buildingModeShowReady ) {
				$choice.classList.add('readonly');
			} else if( playerResourceHand ) {
				cardClone.setSelectable();
				$choice.title = _('Click to remove this card from selection');
				$choice.addEventListener('click', event => {
					event.preventDefault();
					playerResourceHand.unselectToken(card);
				});
			}
			$choice.append(cardClone.getElement());
			$cardList.append($choice);
		});
		
		if( !this.buildingRestaurantIsPreview ) {
			// Is selection valid and unfold ?
			let validSelection = !this.isFold() && !!this.buildingRestaurant.costs.length;
			const selectionKey = tokenService.generateTokensKey(tokenService.getTokensId(this.buildingRestaurant.selectedTokens));
			if( validSelection ) {
				// If right number of resource card, we look if cards match the cost
				const keyCosts = this.buildingRestaurant.costTree;
				validSelection = selectionKey in keyCosts;
				if( validSelection ) {
					this.buildingRestaurant.selectedCost = keyCosts[selectionKey];
				}
			}
			
			if( validSelection ) {
				this.select(this.buildingRestaurant);
			} else {
				this.unselect();
			}
		}
	}
	
	getPlayerResourceHand() {
		const playerBoard = this.app.getCurrentAndActivePlayerBoard();
		return playerBoard ? playerBoard.resourceCardHand : null;
	}
	
	getPlayerPigeonHand() {
		const playerBoard = this.app.getCurrentAndActivePlayerBoard();
		return playerBoard ? playerBoard.pigeonCardHand : null;
	}
	
	isDisabled() {
		return this.$innerElement.classList.contains('disabled');
	}
	
	disable() {
		if( this.isActive() ) {
			// When disabled, it can't be activated
			this.fold();
		}
		this.$innerElement.classList.add('disabled');
	}
	
	enable() {
		this.$innerElement.classList.remove('disabled');
	}
	
	buildElement() {
		return domService.castElement(`
<div class="col-auto">
	<button type="button" class="btn-restaurant restaurant-pile"></button>
</div>`);
	}
	
	prepareElement() {
		super.prepareElement();
		this.$innerElement = this.$element.querySelector('.restaurant-pile');
		this.$panel = this.buildPanel();
		
		this.$innerElement.classList.add('item-' + this.material.key);
		const $title = domService.createElement('div', 'item-title');
		$title.innerText = this.material.label;// Never translate restaurant's name
		this.$model = domService.createElement('div', 'item-model');
		this.$model.appendChild(this.sampleRestaurantToken.getElement());
		this.$counter = domService.createElement('div', 'item-counter');
		
		this.$innerElement.appendChild($title);
		this.$innerElement.appendChild(this.$model);
		this.$innerElement.appendChild(this.$counter);
		this.disableBuildMode();
	}
	
	buildPanel() {
		// Never translate restaurant's name
		const $element = domService.castElement(`
		<div class="restaurant-panel item-${this.material.key} mt-2" hidden>
			<div class="restaurant-description">
				<h5>${this.material.label}</h5>
				<div class="list-info">
					<div class="item-info">
						<div class="medal medal-score size-16"></div>
						${_('+{points} points').replace('{points}', this.material.score)}
					</div>
					<div class="item-info">
						<div class="resource resource-gold size-16"></div>
						${_('+{income} income').replace('{income}', this.material.income)}
					</div>
				</div>
				<div class="item-cost mt-2"></div>
			</div>
			<div class="restaurant-build">
				<h6 class="mode-read" hidden>${_('Resource cards used to build restaurant')}</h6>
				<h6 class="mode-edit" hidden>${_('Please, select resource cards to build restaurant')}</h6>
				<div class="build-resource-cards card-set"></div>
			</div>
		</div>`);
		const $costList = $element.querySelector('.item-cost');
		$costList.innerHTML = '';
		Object.entries(this.material.cost).forEach(([resource, quantity]) => {
			for( let i = 0; i < quantity; i++ ) {
				$costList.append(domService.createElement('span', 'resource size-48 resource-' + resource));
			}
		});
		return $element;
	}
	
	bindEvents() {
		this.$element.parentElement.addEventListener('foldAll', () => this.fold());
		this.$innerElement.addEventListener('click', event => {
			event.stopPropagation();
			if( this.isFold() ) {
				this.unfold();
			} else {
				this.fold();
			}
		});
		this.$element.on
	}
	
	isActive() {
		return !this.isFold();
	}
	
	isFold() {
		return !this.$innerElement.classList.contains('active');
	}
	
	fold() {
		if( this.isFold() ) {
			// Already fold
			return;
		}
		this.$innerElement.classList.remove('active');
		this.$panel.hidden = true;
		this.$element.dispatchEvent(new CustomEvent('fold'));
	}
	
	unfold() {
		// console.log('RestaurantPile - unfold', this);
		if( !this.isFold() ) {
			// Already unfold
			return;
		}
		this.$element.parentElement.dispatchEvent(new CustomEvent('foldAll'));
		this.$innerElement.classList.add('active');
		this.$panel.hidden = false;
		this.$element.dispatchEvent(new CustomEvent('unfold'));
	}
	
	key() {
		return this.material.key;
	}
	
	refresh() {
		const tokens = this.list;
		this.$counter.innerText = _('x{count}').replace('{count}', tokens.length);
	}
	
}

eventService.useElementListener(RestaurantPile);
eventService.useOnSelect(RestaurantPile);
