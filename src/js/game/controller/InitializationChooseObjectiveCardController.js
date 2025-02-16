/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractController } from "../../core/controller/AbstractController.js";
import { CardRiver } from "../token/list/CardRiver.js";

export default class InitializationChooseObjectiveCardController extends AbstractController {
	
	start(args) {
		console.info('InitializationChooseObjectiveCardController.start', args);
		this.allowChoose = args.allowChoose;
		this.cards = args.cards.map(cardId => this.table.getToken(cardId)).map(card => card.clone());
		this.reset();
		if( args.selected ) {
			this.selectedCard = this.cards.filter(card => card.id() === args.selected).shift();
		}
		this.initializeDialog();
		if( this.selectedCard ) {
			this.waitOthers();
		} else {
			this.startPicking();
		}
		
		/*
		States: ("|" is terminated)
		pick -> [disabled]
		disabled -> [pick, wait]
		wait -> [stop]
		stop -> |
		 */
	}
	
	end() {
		this.stopPicking();
		this.reset();
	}
	
	initializeDialog() {
		this.dialog = this.app.createDialog({
			title: _('Draw an objective card'),
			large: true,
			cancel: false,
			close: false,
			minimize: true,
			confirm: _('Confirm'),
			body: `
<p>${_('Among these two objective cards, you have to draw one to complete your hand. The objective of the remaining one could be completed by any player.')}</p>
<p class="state-edit">${_('Now, choose wisely!')}</p>
<p class="state-read">${_('Wait for others to choose their card.')}</p>
<div class="card-objective-picker mt-3 px-2">
	<div class="card-river">
		<div class="card-slot"></div>
		<div class="card-slot"></div>
	</div>
</div>
			`,
		});
		this.dialog.$confirmButton.addEventListener('click', () => this.confirmSelection());
		// Load card river
		this.cardList = new CardRiver(this.dialog.getElement().querySelector('.card-river'));
		this.cards.forEach(card => {
			card.viewSize = 237;
			card.allowClickToPreview = false;
			this.cardList.add(card);
		});
		if( this.allowChoose ) {
			this.cardList.startPicking().then(card => this.select(card));
		}
		this.refresh();
	}
	
	refresh() {
		if( this.dialog ) {
			this.dialog.getElement().querySelector('.state-edit').hidden = !this.allowChoose;
			this.dialog.getElement().querySelector('.state-read').hidden = this.allowChoose;
			this.dialog.$confirmButton.disabled = !this.selectedCard || this.processing || this.processed;
			this.cardList.togglePicking(!this.processing && !this.processed);
			this.cardList.refresh();
		}
	}
	
	select(card) {
		if( this.selectedCard ) {
			this.selectedCard.setDisabledText(null);
			this.cardList.unselectToken(this.selectedCard);
		}
		this.selectedCard = card;
		this.cardList.selectToken(card);
		this.selectedCard.setDisabledText(_('Selected'));
		this.refresh();
	}
	
	confirmSelection() {
		// Check action
		if( !this.app.ui.checkAction('chooseObjectiveCard') ) {
			console.warn('Not allowed to choose an objective card');
			return;
		}
		this.setProcessing(true);
		// Apply picking
		this.app
			.realizeAction('chooseObjectiveCard', {
				cardId: this.selectedCard.id(),
			})
			.then(() => {
				this.setProcessing(false);
				this.waitOthers();
			})
			.catch((error) => {
				// Allow user to select another place
				console.warn('Error, choose objective card again', error);
				this.setProcessing(false);
			});
	}
	
	setProcessing(processing) {
		this.processing = processing;
		this.refresh();
	}
	
	waitOthers() {
		this.processed = true;
		this.allowChoose = false;
		this.openDialog();
		
		if( this.selectedCard ) {
			this.selectedCard.setDisabledText(_('Your hand'));
		}
		this.getNonSelected().forEach(card => card.setDisabledText(_('Game board')));
		this.refresh();
		// Can not come back to a previous state
	}
	
	getNonSelected() {
		return this.cards.filter(card => card !== this.selectedCard);
	}
	
	openDialog() {
		if( !this.dialog ) {
			return;
		}
		this.dialog.show();
		if( this.processed ) {
			this.dialog.minimize();
		}
	}
	
	closeDialog() {
		if( !this.dialog ) {
			return;
		}
		this.dialog.hide();
	}
	
	startPicking() {
		this.openDialog();
	}
	
	stopPicking() {
		this.closeDialog();
		this.dialog.remove();
		this.dialog = null;
	}
	
	reset() {
		this.selectedCard = null;
		this.processing = false;
		this.processed = false;
	}
	
}
