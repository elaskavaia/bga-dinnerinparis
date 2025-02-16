/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractController } from "../../core/controller/AbstractController.js";
import { ButtonStyle } from "../board/ButtonStyle.js";
import { stringService } from "../../service/string.service.js";
import { domService } from "../../service/dom.service.js";

export default class ActionPlaceTerracesPlaceController extends AbstractController {
	
	static restaurantErrors = {
		// See TerraceBuildResolver
		balance: _('Insufficient balance'),
		terrace: _('No more terrace in category'),
		pigeon_card: _('Restricted by pigeon card'),
		cell: _('No more available cell'),
	};
	
	start(args) {
		console.info('ActionPlaceTerraceController.start', args);
		/** @type {Player} */
		this.player = this.app.getActivePlayer();
		this.allowPlace = args.allowPlace;
		this.availableLocations = args.availableLocations;
		this.unavailableRestaurants = Object.entries(args.unavailableRestaurants).map(([restaurantId, code]) => {
			return {
				restaurant: this.table.getToken(restaurantId),
				message: ActionPlaceTerracesPlaceController.restaurantErrors[code],
			};
		});
		this.goldCards = args.resourceCards.map(tokenId => this.table.getToken(tokenId));
		this.pigeonGoldCards = args.goldPigeonCards.map(tokenId => this.table.getToken(tokenId));
		this.restaurants = Object.keys(this.availableLocations).map(tokenId => this.table.getToken(tokenId));
		this.selectedRestaurant = null;
		this.placing = false;
		
		if( !this.allowPlace ) {
			// No more coin or no more terrace available for built restaurants
			this.app.setTurnTitle(_('${you} can not build more terraces'));
		}
		
		// Action buttons
		if( args.allowConfirm ) {
			this.app.addActionButton('confirmTerracePlace', _('Finish'))
				.then(() => this.confirmPlacement());
		}
		if( args.allowAdjacentTerracePigeon ) {
			this.app.addActionButton('confirmAdjacentTerracePigeon', _('🕊 Place adjacent terraces'), ButtonStyle.SECONDARY)
				.then(() => this.useAdjacentTerracePigeonCard());
		}
		if( args.allowCancel ) {
			this.app.addActionButton('cancelTerracePlace', _('Cancel'), ButtonStyle.SECONDARY)
				.then(() => this.cancel());
		}
		
		this.restaurantClickListener = event => {
			const restaurant = event.currentTarget.token;
			this.selectRestaurant(restaurant);
		};
		
		this.cellClickListener = event => {
			const cell = event.currentTarget.cell;
			this.placeTerrace(cell);
		};
		
		if( this.player ) {
			// Allow gold card picking
			const playerBoard = this.table.getPlayerBoard(this.player);
			this.playerResourceHand = playerBoard.resourceCardHand;
			this.playerPigeonHand = playerBoard.pigeonCardHand;
			if( this.goldCards && this.goldCards.length ) {
				this.playerResourceHand.startPickingList(this.goldCards, null, 1)
					.then(cards => {
						this.useBonusCard([...cards]);
					});
			}
			if( this.requirePigeonCardPicking() ) {
				this.playerPigeonHand.startPickingList(this.pigeonGoldCards, null, 1)
					.then(cards => {
						this.useBonusCard([...cards]);
					});
			}
		}
		
		this.startPlacing();
		
		if( args.previousRestaurant ) {
			// Reopen previous restaurant with fresh data
			const restaurant = this.table.getToken(args.previousRestaurant);
			if( this.getRestaurantPlacement(restaurant) ) {
				// If user has enough cash to place another terrace on this restaurant
				this.selectRestaurant(restaurant);
			}
		}
		
		this.bindEvents();
	}
	
	end() {
		this.unbindEvents();
		if( this.placing ) {
			this.stopPlacing();
		}
		if( this.playerResourceHand ) {
			this.playerResourceHand.stopPickingList();
			this.playerResourceHand = null;
		}
		if( this.playerPigeonHand ) {
			if( this.requirePigeonCardPicking() ) {
				this.playerPigeonHand.stopPickingList();
			}
			this.playerPigeonHand = null;
		}
		this.availableLocations = null;
		this.restaurants = null;
		this.goldCards = null;
		this.pigeonGoldCards = null;
	}
	
	useAdjacentTerracePigeonCard() {
		// Check action
		if( !this.app.ui.checkAction('useAdjacentTerracePigeonCard') ) {
			console.warn('Not allowed to use AdjacentTerrace Pigeon Card');
			return;
		}
		// Stop placing restaurant
		this.stopPlacing();
		// Confirm placing
		this.app.realizeAction('useAdjacentTerracePigeonCard')
			.catch(error => {
				// Allow user to continue placing
				console.warn('Error, start placing terrace again', error);
				this.startPlacing();
			});
	}
	
	requirePigeonCardPicking() {
		return this.pigeonGoldCards && !!this.pigeonGoldCards.length
	}
	
	useBonusCard(cards) {
		if( !cards.length ) {
			// Ignore no selection, this may be an error case
			return;
		}
		const card = cards[0];
		this.useGoldCard(card);
	}
	
	bindEvents() {
		if( this.player ) {
			this.playerChangeListener = this.player.onChange()
				.then(() => this.refreshHeadContents());
		}
	}
	
	unbindEvents() {
		if( this.playerChangeListener ) {
			this.player.off(this.playerChangeListener);
		}
	}
	
	addHeadContents() {
		if( this.$headElement || !this.player ) {
			return;
		}
		const $container = document.getElementById('HeadContents');
		this.$headElement = domService.castElement(`
<div class="mt-3">
	<h5 class="title-basalt">${_('Place terraces')}</h5>
	<p class="placement-summary mt-2"></p>
</div>
		`);
		$container.append(this.$headElement);
		this.refreshHeadContents();
	}
	
	refreshHeadContents() {
		if( !this.$headElement ) {
			return;
		}
		const sentences = [];// All sentences must have a {more} variables.
		sentences.push(stringService.replace(_('You have {balance} coins left out of {income}. {more}'), {
			balance: '<b>' + this.player.balance + '</b>',
			income: this.player.income,
		}));
		if( !this.allowPlace ) {
			sentences.push(stringService.replace(_('You can no longer place terrace on the game board. {more}'), {
				count: this.goldCards.length,
			}));
			
		} else if( this.goldCards.length ) {
			sentences.push(stringService.replace(_('You own {count} gold cards. You can use it to get more terrace by clicking. {more}'), {
				count: this.goldCards.length,
			}));
		}
		if( this.player.pendingIncome ) {
			sentences.push(stringService.replace(_('You earned {pendingIncome} of income available next turn. {more}'), {
				pendingIncome: this.player.pendingIncome,
			}));
		}
		this.$headElement.querySelector('.placement-summary').innerHTML = stringService.composeSentence(sentences);
	}
	
	removeHeadContents() {
		if( !this.$headElement ) {
			return;
		}
		this.$headElement.remove();
		this.$headElement = null;
	}
	
	confirmPlacement() {
		// Check action
		if( !this.app.ui.checkAction('confirm') ) {
			console.warn('Not allowed to confirm placement');
			return;
		}
		// Stop placing restaurant
		this.stopPlacing();
		// Confirm placing
		this.app.realizeAction('confirmPlaceTerrace')
			.catch(() => {
				// Allow user to continue placing
				this.startPlacing();
			});
	}
	
	startPlacing() {
		// user must select a restaurant
		if( this.allowPlace ) {
			this.restaurants.forEach(restaurant => {
				restaurant.setSelectable().getElement()
					.addEventListener('click', this.restaurantClickListener);
			});
			this.unavailableRestaurants.forEach(error => {
				error.restaurant.getElement().title = error.message;
			});
		}
		this.addHeadContents();
		this.placing = true;
	}
	
	stopPlacing() {
		this.restaurants.forEach(restaurant => {
			restaurant.notSelectable().getElement()
				.removeEventListener('click', this.restaurantClickListener);
		});
		this.unavailableRestaurants.forEach(error => {
			error.restaurant.refresh(); // Reset title
		});
		this.unselectRestaurant();
		this.removeHeadContents();
		this.placing = false;
	}
	
	getRestaurantAvailableCells(restaurant) {
		return this.getRestaurantPlacement(restaurant).cells.map(([point, buildPermission]) => [this.table.grid.getCell(point), buildPermission]);
	}
	
	getRestaurantPlacement(restaurant) {
		restaurant = restaurant || this.selectedRestaurant;
		return this.availableLocations[restaurant.id()];
	}
	
	/**
	 * @param {RestaurantToken} restaurant
	 */
	selectRestaurant(restaurant) {
		if( this.selectedRestaurant && this.selectedRestaurant === restaurant ) {
			return;
		}
		// Unselect previous
		this.unselectRestaurant();
		// Select new one
		restaurant.setSelected();
		this.selectedRestaurant = restaurant;
		const placement = this.getRestaurantPlacement(restaurant);
		this.getRestaurantAvailableCells()
			.forEach(([cell, buildPermission]) => {
				const $element = cell.setSelectable(buildPermission)
					.getElement();
				$element.title = stringService.replace(placement.income ? _('Cost {cost} and give {income} income') : _('Cost {cost} and give {score} score'), placement);
				$element.addEventListener('click', this.cellClickListener);
			});
		// Show cost in front of restaurant
		const $placementDetails = domService.castElement(`
<div class="token-overlay overlay-purchase-details">
	<div class="fix-rotate-north">
		<div class="icon-cost cost-${placement.cost} size-32"></div>
		<div class="cost-free">${_('Free')}</div>
	</div>
</div>`);
		if( placement.cost ) {
			$placementDetails.querySelector('.cost-free').remove();
		} else {
			$placementDetails.querySelector('.icon-cost').remove();
		}
		restaurant.getElement().append($placementDetails);
	}
	
	unselectRestaurant() {
		if( !this.selectedRestaurant ) {
			return;
		}
		this.selectedRestaurant.notSelected();
		this.getRestaurantAvailableCells().forEach(([cell,]) => {
			const $element = cell.notSelectable()
				.getElement();
			$element.removeAttribute('title');
			$element.removeEventListener('click', this.cellClickListener);
		});
		
		// Remove any purchase overlay
		const $placementDetails = this.selectedRestaurant.getElement().querySelector('.overlay-purchase-details');
		$placementDetails.remove();
		
		this.selectedRestaurant = null;
	}
	
	/**
	 * @param {TileCell} cell
	 */
	placeTerrace(cell) {
		// Check action
		if( !this.app.ui.checkAction('place') ) {
			console.warn('Not allowed to place')
			return;
		}
		// Apply cancel
		this.app.realizeAction('placeTerrace', {
			x: cell.x,
			y: cell.y,
			restaurant: this.selectedRestaurant.id(),
		});
	}
	
	/**
	 * @param {ResourceCard} card
	 */
	useGoldCard(card) {
		// Check action
		if( !this.app.ui.checkAction('useGoldCard') ) {
			console.warn('Not allowed to use a gold card')
			return;
		}
		// Apply cancel
		this.app.realizeAction('useGoldCard', {
			card: card.id(),
		});
	}
	
	cancel() {
		// Check action
		if( !this.app.ui.checkAction('cancel') ) {
			console.warn('Not allowed to cancel')
			return;
		}
		this.stopPlacing();
		// Apply cancel
		this.app.realizeAction('cancelPlaceTerrace')
			.catch(error => {
				// Allow user to place terrace again
				console.warn('Error, start placing terrace again', error);
				this.startPlacing();
			});
	}
	
}
