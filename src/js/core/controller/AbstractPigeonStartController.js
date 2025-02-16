/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractController } from "./AbstractController.js";

/**
 * @property {Function} attachDialog
 * @property {Function} openDialog
 * @property {Function} closeDialog
 */
export default class AbstractPigeonStartController extends AbstractController {
	
	constructor(app) {
		super(app);
		
		this.key = null;
	}
	
	start(args) {
		// Action buttons
		this.app.addActionButton('confirmContinue', _('Continue'))
			.then(() => this.confirm());
	}
	
	confirm() {
		// Check action
		if( !this.app.ui.checkAction('continue') ) {
			console.warn('Not allowed to continue');
			return;
		}
		// Confirm placing
		this.app.realizeAction("continuePigeon" + this.key)
			.catch(() => {
				// Allow user to retry
				this.openDialog();
			});
	}
	
	refresh() {
		this.cardList.refresh();
	}
	
}
