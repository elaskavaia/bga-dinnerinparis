/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractController } from "../../core/controller/AbstractController.js";
import { ButtonStyle } from "../board/ButtonStyle.js";

export default class PlayerActionChooseController extends AbstractController {
	
	start(args) {
		console.info("PlayerActionChooseController.start - Start player choose action", args);
		this.enabled = false;
		// For actions to choose, see state playerActionChoose, property possibleactions
		this.app.addActionButton('choosePickResourceCard', _('Draw a resource card'))
			.then(() => this.chooseAction('actionPickResourceCard'));
		if( args.allowBuildRestaurantAction ) {
			this.app.addActionButton('buildRestaurant', _('Build a restaurant'))
				.then(() => this.chooseAction('actionBuildRestaurant'));
		}
		if( args.allowPlaceTerraceAction ) {
			this.app.addActionButton('placeTerraces', _('Place terraces'))
				.then(() => this.chooseAction('actionPlaceTerraces'));
		}
		if( args.allowCompleteObjectiveAction ) {
			this.app.addActionButton('completeObjective', _('Complete an objective'))
				.then(() => this.chooseAction('actionCompleteObjective'));
		}
		if( args.allowDrawObjectivePigeonAction ) {
			this.app.addActionButton('drawObjective', _('🕊 Draw an objective'), ButtonStyle.SECONDARY)
				.then(() => this.chooseAction('pigeonDrawObjective'));
		}
		// Test only
		if( args.allowEndGame ) {
			this.app.addActionButton('endGame', _('End game'), ButtonStyle.ALERT)
				.then(() => this.chooseAction('actionEndGame'));
		}
		this.enabled = true;
	}
	
	chooseAction(action) {
		// Check action
		if( !this.app.ui.checkAction(action) ) {
			console.warn(`Not allowed to realize action "${action}"`);
			return;
		}
		// Check action
		if( !this.enabled ) {
			console.warn(`Not allowed to realize action "${action}" - action disabled, already pending`);
			return;
		}
		this.enabled = false;
		// Apply action
		this.app
			.realizeAction('playerActionChoose', {
				nextAction: action,
			})
			.catch(error => {
				// Allow user to choose action again
				console.warn('Error, start choosing action again', error);
				this.enabled = true;
			});
	}
	
}
