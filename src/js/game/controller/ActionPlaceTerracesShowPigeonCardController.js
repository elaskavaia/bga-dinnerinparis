/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { Dialog } from "../../component/Dialog.js";
import { AbstractController } from "../../core/controller/AbstractController.js";
import { CardRiver } from "../token/list/CardRiver.js";
import { AbstractCard } from "../token/AbstractCard.js";

/**
 * @property {Function} attachDialog
 * @property {Function} openDialog
 * @property {Function} closeDialog
 */
export default class ActionPlaceTerracesShowPigeonCardController extends AbstractController {
	
	initialize(args) {
		console.info('ActionPlaceTerraceShowPigeonCardController.initialize', args);
		// An attached dialog auto open and close
		this.attachDialog();
	}
	
	start(args) {
		console.info('ActionPlaceTerraceShowPigeonCardController.start', args);
		/** @type {Player} */
		this.player = this.app.getActivePlayer();
		this.card = this.table.getToken(args.card).clone();
		this.legend = _(args.legend);// Translate pigeon card description
		this.play = args.play;
		
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
		this.app.realizeAction('confirmShowPigeonCard');
	}
	
	buildDialog() {
		const usageNotice = this.play ? _('This card is playing now.') : _('You could play this card in a next turn.');
		const dialog = this.app.createDialog({
			title: this.play ? _('You are playing a pigeon card') : _('You drew a pigeon card'),
			large: true,
			cancel: false,
			close: false,
			minimize: true,
			confirm: _('Confirm'),
			body: `
<p>${this.legend}</p>
<p>${usageNotice}</p>
<div class="mt-3">
	<div class="card-river">
		<div class="card-slot"></div>
	</div>
</div>
			`,
		});
		dialog.onConfirm()
			.then(() => this.confirm());
		
		return dialog;
	}
	
	onDialogAttached() {
		// Load card river
		this.cardList = new CardRiver(this.dialog.getElement().querySelector('.card-river'));
		const card = this.card;
		card.viewSize = AbstractCard.SIZE_LARGE;
		card.data.position = 0;
		this.cardList.add(card);
		this.cardList.refresh();
	}
	
}

Dialog.attachToController(ActionPlaceTerracesShowPigeonCardController);
