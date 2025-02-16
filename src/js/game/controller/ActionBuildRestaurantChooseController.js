/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractController } from "../../core/controller/AbstractController.js";
import { ButtonStyle } from "../board/ButtonStyle.js";

export default class ActionBuildRestaurantChooseController extends AbstractController {
	
	start(args) {
		console.info('ActionBuildRestaurantChooseController.start', args);
		// Init buttons
		this.confirmButtonId = 'ButtonConfirmBuildRestaurantChoose';
		this.availableRestaurants = args.availableRestaurants;
		this.app.addActionButton(this.confirmButtonId, _('Build'), ButtonStyle.DISABLED)
			.then(() => this.confirmSelection());
		this.app.addActionButton('cancelBuildRestaurantChoose', _('Cancel'), ButtonStyle.SECONDARY)
			.then(() => this.cancel());
		// Start picking
		this.startPicking();
	}
	
	end() {
		// Replay case
		this.stopPicking();
	}
	
	confirmSelection() {
		if( !this.selectedRestaurantBuild ) {
			console.warn('Could not confirm selection when nothing is selected');
			return;
		}
		// Check action
		if( !this.app.ui.checkAction('choose') ) {
			console.warn('Not allowed to choose a restaurant to build');
			return;
		}
		// Stop picking
		this.stopPicking();
		// Apply picking
		this.app
			.realizeAction("chooseRestaurantToBuild", {
				tokenId: this.selectedRestaurantBuild.restaurant.id,
				cards: this.selectedRestaurantBuild.selectedCost,
			})
			.catch(error => {
				// Allow user to pick again
				console.warn('Error, start picking restaurant again', error);
				this.startPicking();
			});
	}
	
	cancel() {
		// Check action
		if( !this.app.ui.checkAction('cancel') ) {
			console.warn('Not allowed to cancel')
			return;
		}
		// Stop picking
		this.stopPicking();
		// Apply cancel
		this.app.realizeAction("cancelBuildRestaurantChoose")
			.catch(error => {
				// Allow user to pick again
				console.warn('Error, start picking restaurant again', error);
				this.startPicking();
			});
	}
	
	unselectBuild() {
		if( this.selectedRestaurantBuild ) {
			this.selectedRestaurantBuild = null;
		}
		this.app.disableActionButton(this.confirmButtonId);
	}
	
	/**
	 * @param {Object} restaurantBuild
	 */
	selectBuild(restaurantBuild) {
		if( this.selectedRestaurantBuild ) {
			if( this.selectedRestaurantBuild === restaurantBuild ) {
				// Can not select already selected card
				return;
			} else {
				// Unselect previous one
				this.unselectBuild();
			}
		}
		this.selectedRestaurantBuild = restaurantBuild;
		
		this.app.enableActionButton(this.confirmButtonId);
	}
	
	startPicking() {
		// let anyRestaurantSelected = false;
		let selectedRestaurantPile = null;
		this.app.table.restaurantBox.forEach(restaurantPile => {
			const restaurantBuild = this.availableRestaurants.filter(restaurantBuild => restaurantPile.material.variant === restaurantBuild.restaurant.variant).shift();
			if( restaurantBuild ) {
				restaurantPile.onSelect()
					.then(build => this.selectBuild(build));
				restaurantPile.onUnselect()
					.then(() => this.unselectBuild());
				// After event listeners
				restaurantPile.enableBuildMode(restaurantBuild);
				if( !selectedRestaurantPile || restaurantPile.isActive() ) {
					selectedRestaurantPile = restaurantPile;
				}
				restaurantPile.fold();// Fold all
			} else {
				restaurantPile.disable();
			}
		});
		if( selectedRestaurantPile ) {
			// Auto select first pile
			selectedRestaurantPile.unfold();
		}
	}
	
	stopPicking() {
		this.app.table.restaurantBox.forEach(restaurantPile => {
			restaurantPile.disableBuildMode();
			restaurantPile.enable();
		});
	}
	
}
