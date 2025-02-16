/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractController } from "../../core/controller/AbstractController.js";

export default class ActionPickResourceCardPickNewController extends AbstractController {
	
	start(args) {
		this.startPicking();
		if( args.allowCancel ) {
			this.app.addActionButton('cancelPickResourceCard', _('Cancel'))
				.then(() => this.cancel());
		}
	}
	
	end() {
		// Replay case
		this.stopPicking();
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
		this.app.realizeAction('cancelPickResourceCardAction')
			.catch(error => {
				// Allow user to draw a resource card again
				console.warn('Error, start drawing resource card again', error);
				this.startPicking();
			});
	}
	
	/**
	 * @param {ResourceCard} card
	 */
	pickCard(card) {
		// Check action
		if( !this.app.ui.checkAction('pickResourceCard') ) {
			console.warn('Not allowed to draw a card')
			return;
		}
		// Stop picking
		this.stopPicking();
		// Apply picking
		this.app.realizeAction("pickResourceCard", {
			cardId: card.id(),
		}).catch(error => {
			// Allow user to select another card
			console.warn('Error, start drawing resource card again', error);
			this.startPicking();
		});
	}
	
	startPicking() {
		this.app.table.resourceCardRiver.startPicking()
			.then(card => this.pickCard(card));
		this.app.table.resourceCardDrawPile.startPicking()
			.then(card => this.pickCard(card));
	}
	
	stopPicking() {
		this.app.table.resourceCardRiver.stopPicking();
		this.app.table.resourceCardDrawPile.stopPicking();
	}
	
}
