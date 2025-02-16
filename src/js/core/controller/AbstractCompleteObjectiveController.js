/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractController } from "./AbstractController.js";
import { CardRiver } from "../../game/token/list/CardRiver.js";
import { Deferred } from "../../event/Deferred.js";
import { AbstractCard } from "../../game/token/AbstractCard.js";

export default class AbstractCompleteObjectiveController extends AbstractController {
	
	start(args) {
		this.allowUsePigeonCard = args.allowUsePigeonCard;
		this.objectivePigeonCard = this.allowUsePigeonCard ? this.table.getToken(args.objectivePigeonCard).clone() : null;
	}
	
	buildPigeonObjectiveDialog() {
		return this.app.createDialog({
			title: _('You own an objective pigeon card'),
			large: false,
			cancel: true,
			close: false,
			minimize: true,
			confirm: _('Play card'),
			body: `
<p>${_('Would you play a pigeon card to complete another objective ?')}</p>
<div class="mt-3">
	<div class="card-river">
		<div class="card-slot"></div>
	</div>
</div>
			`,
		});
	}
	
	closePigeonObjectiveRequest() {
		if( !this.pigeonObjectiveDialog ) {
			return;
		}
		this.pigeonObjectiveDialog.hide();
		// Clear in any way
		this.pigeonObjectiveDialog.remove();
		this.pigeonObjectiveDialog = null;
	}
	
	requestAnotherPigeonObjective() {
		if( !this.pigeonObjectiveDialog ) {
			this.pigeonObjectiveDialog = this.buildPigeonObjectiveDialog();
		}
		this.refreshPigeonObjectiveRequestDialog();
		this.pigeonObjectiveDialog.show();
		const deferred = new Deferred();
		this.pigeonObjectiveDialog.onResult()
			.then(dialogResult => {
				this.closePigeonObjectiveRequest();
				deferred.resolve(dialogResult.result);
			});
		return deferred.promise();
	}
	
	refreshPigeonObjectiveRequestDialog() {
		// Load card river
		const dialog = this.pigeonObjectiveDialog;
		const card = this.objectivePigeonCard;
		this.cardList = new CardRiver(dialog.getElement().querySelector('.card-river'));
		card.viewSize = AbstractCard.SIZE_LARGE;
		card.data.position = 0;
		this.cardList.add(card);
		this.cardList.refresh();
	}
	
}
