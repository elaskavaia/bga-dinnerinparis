/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractController } from "../../core/controller/AbstractController.js";

export default class ActionPickResourceCardDiscardSurplusController extends AbstractController {
	
	start() {
		this.playerBoard = this.app.getCurrentAndActivePlayerBoard();
		console.log('ActionPickResourceCardDiscardSurplusController.start', this.args, 'this.playerBoard', this.playerBoard);
		this.startPicking();
	}
	
	end() {
		// Replay case
		this.stopPicking();
	}
	
	/**
	 * @param {ResourceCard} card
	 */
	discardCard(card) {
		// Check card
		if( !this.app.ui.checkAction('discardResourceCard') ) {
			console.warn('Not allowed to discard a card');
			return;
		}
		// Stop picking
		this.stopPicking();
		// Apply picking
		this.app.realizeAction('discardResourceCard', {
			cardId: card.id(),
		}).catch(error => {
			console.warn('Error discarding resource card', error);
			this.startPicking();
		});
	}
	
	startPicking() {
		// console.info('Start picking a card to discard - this.playerBoard', this.playerBoard);
		this.playerBoard.resourceCardHand.startPicking()
			.then(card => this.discardCard(card));
	}
	
	stopPicking() {
		// console.info('Stop picking a card to discard - this.playerBoard', this.playerBoard);
		this.playerBoard.resourceCardHand.stopPicking();
	}
	
}
