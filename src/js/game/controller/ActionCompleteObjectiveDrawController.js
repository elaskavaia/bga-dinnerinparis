/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { CardRiver } from "../token/list/CardRiver.js";
import AbstractCompleteObjectiveController from "../../core/controller/AbstractCompleteObjectiveController.js";

export default class ActionCompleteObjectiveDrawController extends AbstractCompleteObjectiveController {
	
	start(args) {
		console.info('ActionCompleteObjectiveDrawController.start', args);
		super.start(args);
		this.card = this.table.getToken(args.card).clone();
		this.initializeDialog();
		this.openDialog();
	}
	
	end() {
		if( this.dialog ) {
			this.closeDialog();
			this.dialog.remove();
		}
		this.closePigeonObjectiveRequest();
		this.reset();
	}
	
	reset() {
		this.card = null;
		this.dialog = null;
		this.cardList = null;
	}
	
	initializeDialog() {
		const legend = _('Do you wish to keep this objective card in your hand ?<br>' +
			'If so, remember that an uncompleted objective will get you negative points at the end of the game.');
		this.dialog = this.app.createDialog({
			title: _('You just picked up a new objective card'),
			close: false,
			cancel: _('Let it go'),
			confirm: _('Keep'),
			body: `
<p>${legend}</p>
<div class="card-objective-picker mt-3 px-2">
	<div class="card-river">
		<div class="card-slot"></div>
	</div>
</div>
			`,
		});
		this.dialog.onConfirm()
			.then(() => this.confirmChoice(true));
		this.dialog.onCancel()
			.then(() => this.confirmChoice(false));
		// Load card river
		this.cardList = new CardRiver(this.dialog.getElement().querySelector('.card-river'));
		const card = this.card;
		card.viewSize = 237;
		this.cardList.add(card);
		this.refresh();
	}
	
	disableForm() {
		if( this.dialog ) {
			this.dialog.disableButtons();
		}
	}
	
	enableForm() {
		if( this.dialog ) {
			this.dialog.enableButtons();
		}
	}
	
	async confirmChoice(keep) {
		// Check action
		if( !this.app.ui.checkAction(keep ? 'keep' : 'reject') ) {
			console.warn('Not allowed to keep or reject an objective card');
			return;
		}
		// Apply
		this.disableForm();
		// Use pigeon card ?
		let usePigeonCard = false;
		if( this.allowUsePigeonCard ) {
			// Has pigeon card (no other condition, this is the last case)
			usePigeonCard = await this.requestAnotherPigeonObjective();
		}
		this.app
			.realizeAction(this.getPlaceAction(), {
				keep: keep,
				usePigeonCard: usePigeonCard
			})
			.catch(error => {
				// Allow user to choose again
				console.warn('Error, start choosing again', error);
				this.enableForm();
			});
	}
	
	getPlaceAction() {
		return 'placeObjectiveToComplete';
	}
	
	openDialog() {
		if( !this.dialog ) {
			return;
		}
		this.dialog.show();
	}
	
	closeDialog() {
		if( !this.dialog ) {
			return;
		}
		this.dialog.hide();
	}
	
	refresh() {
		this.cardList.refresh();
	}
	
}
