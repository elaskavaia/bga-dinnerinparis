import { AbstractController } from "../../core/controller/AbstractController.js";
import { ButtonStyle } from "../board/ButtonStyle.js";
import { ORIENTATION_MAP } from "../constants.js";

export default class ActionBuildRestaurantPlaceController extends AbstractController {
	
	start(args) {
		console.info('ActionBuildRestaurantPlaceController.start', args);
		this.build = args.build;
		this.availableLocations = args.availableLocations;
		/** @var {RestaurantToken} */
		this.restaurant = this.app.table.getToken(this.build.restaurant);
		
		// Action buttons
		this.app.addActionButton('confirmBuildRestaurantPlace', _('Confirm'))
			.then(() => this.confirmSelection());
		this.$confirmButton = document.getElementById('ButtonConfirmBuildRestaurantPlace');
		this.setSelection(null);
		this.app.addActionButton('cancelBuildRestaurantPlace', _('Cancel'), ButtonStyle.SECONDARY)
			.then(() => {
				if( this.selectedLocation ) {
					this.cancelSelection();
				} else {
					this.cancel();
				}
			});
		
		this.startPlacingRestaurant();
	}
	
	end() {
		// Replay case
		this.stopPlacingRestaurant();
	}
	
	confirmSelection() {
		// Check action
		if( !this.app.ui.checkAction('place') ) {
			console.warn('Not allowed to place a restaurant');
			return;
		}
		// Stop placing restaurant
		this.stopPlacingRestaurant();
		// Apply picking
		this.app.realizeAction("placeRestaurantToBuild", {
			x: this.selectedLocation.cell.x,
			y: this.selectedLocation.cell.y,
			orientation: this.selectedLocation.orientation,
		}).catch(error => {
			// Allow user to select another place
			this.startPlacingRestaurant();
		});
	}
	
	cancel() {
		// Check action
		if( !this.app.ui.checkAction('cancel') ) {
			console.warn('Not allowed to cancel')
			return;
		}
		// Stop picking
		this.stopPlacingRestaurant();
		// Apply cancel
		this.app.realizeAction("cancelPickResourceCardAction")
			.catch(error => {
				// Allow user to place restaurant again
				console.warn('Error, start placing restaurant again', error);
				this.startPlacingRestaurant();
			});
	}
	
	/**
	 * @param {Object|null} location
	 */
	setSelection(location) {
		this.selectedLocation = location;
		this.$confirmButton.hidden = !location;
	}
	
	cancelSelection() {
		this.setSelection(null);
		this.app.table.grid.unselectRestaurantPlacement();
	}
	
	startPlacingRestaurant() {
		this.startPreviewRestaurantBuilding();
		this.app.table.grid.startRestaurantPlacement(this.restaurant, this.availableLocations)
			.then(cell => {
				if( !cell ) {
					// Ignore empty cell (should not happen), it means the celle is the same as previous
					return;
				}
				const orientation = this.app.table.grid.getCellRestaurantOrientation(cell);
				this.setSelection({cell: cell, orientation: ORIENTATION_MAP[orientation]});
			});
	}
	
	stopPlacingRestaurant() {
		// console.log('stopPlacingRestaurant()');
		this.app.table.grid.stopRestaurantPlacement();
		this.stopPreviewRestaurantBuilding();
	}
	
	startPreviewRestaurantBuilding() {
		// const restaurantPile = this.app.table.restaurantBox[this.restaurant.variant()];
		this.app.table.restaurantBox.forEach((restaurantPile, variant) => {
			if( variant === this.restaurant.variant() ) {
				restaurantPile.previewBuildMode(this.build);
				restaurantPile.unfold();
			} else {
				restaurantPile.disable();
			}
		});
	}
	
	stopPreviewRestaurantBuilding() {
		this.app.table.restaurantBox.forEach((restaurantPile, variant) => {
			restaurantPile.disableBuildMode();
			restaurantPile.enable();
		});
	}
	
}
