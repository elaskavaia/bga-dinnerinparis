/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { ButtonStyle } from "../board/ButtonStyle.js";
import { Token } from "../token/Token.js";
import AbstractCompleteObjectiveController from "../../core/controller/AbstractCompleteObjectiveController.js";

export default class ActionCompleteObjectiveChooseController extends AbstractCompleteObjectiveController {
	
	start(args) {
		console.info('ActionCompleteObjectiveChooseController.start', args);
		super.start(args);
		this.allowCancel = args.allowCancel;
		this.cards = Object.fromEntries(args.cards.map(completableCard => [completableCard[0], {
			card: this.table.getToken(completableCard[0]),
			source: completableCard[1],
			solution: completableCard[2],
		}]));
		this.confirmButtonId = 'ButtonConfirmCompleteObjectiveChoose';
		this.app.addActionButton(this.confirmButtonId, _('Confirm'), ButtonStyle.DISABLED)
			.then(() => this.confirmSelection());
		this.app.addActionButton('cancelCompleteObjective', _('Cancel'), ButtonStyle.SECONDARY)
			.then(() => this.cancel());
		
		this.playerBoard = this.app.getCurrentAndActivePlayerBoard();
		/** @type {ObjectiveCard|null} */
		this.selectedCard = null;
		this.startPicking();
	}
	
	end() {
		this.stopPicking();
		this.closePigeonObjectiveRequest();
	}
	
	async confirmSelection() {
		// Check action
		if( !this.app.ui.checkAction('choose') ) {
			console.warn('Not allowed to choose an objective card');
			return;
		}
		// Use pigeon card ?
		let usePigeonCard = false;
		if( this.allowUsePigeonCard && this.selectedCard.container() !== Token.CONTAINER_PLAYER_HAND ) {
			// Has pigeon card and not playing a card from hand (cards from hand require one more step)
			usePigeonCard = await this.requestAnotherPigeonObjective();
		}
		// Apply
		this.app.realizeAction('chooseObjectiveToComplete', {
			card: this.selectedCard.id(),
			usePigeonCard: usePigeonCard,
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
		// Reset data
		this.selectedCard = null;
		// Apply cancel
		this.app.realizeAction('cancelCompleteObjective')
			.catch(error => {
				// Allow user to choose a card again
				console.warn('Error, start choosing objective card again', error);
				this.startPicking();
			});
	}
	
	stopPicking() {
		// console.log('ActionCompleteObjectiveChooseController-stopPicking');
		if( !this.picking ) {
			return;
		}
		this.getPlayerObjectiveHand().stopPickingList();
		this.getBoardObjectiveRiver().stopPickingList();
		this.picking = false;
	}
	
	getPlayerObjectiveHand() {
		return this.playerBoard.objectiveCardHand;
	}
	
	getBoardObjectiveRiver() {
		return this.table.objectiveCardRiver;
	}
	
	startPicking() {
		if( this.picking ) {
			console.warn('Already picking objective to complete');
			return;
		}
		this.picking = true;
		const availableCards = Object.values(this.cards).map(completableCard => completableCard.card);
		this.getPlayerObjectiveHand().startPickingList(availableCards, null, 1)
			.then(selection => {
				selection = [...selection];
				if( selection && selection.length ) {
					this.select(selection[0]);
				} else {
					this.unselect();
				}
			});
		this.getBoardObjectiveRiver().startPickingList(availableCards, null, 1)
			.then(selection => {
				selection = [...selection];
				if( selection && selection.length ) {
					this.select(selection[0]);
				} else {
					this.unselect();
				}
			});
	}
	
	unselect() {
		if( this.selectedCard ) {
			const selectedCard = this.selectedCard;
			this.selectedCard = null;
			if( selectedCard.container() === Token.CONTAINER_PLAYER_HAND ) {
				this.getPlayerObjectiveHand().resetSelection();
			} else {
				this.getBoardObjectiveRiver().resetSelection();
			}
		}
		this.app.disableActionButton(this.confirmButtonId);
	}
	
	select(card) {
		if( this.selectedCard ) {
			if( this.selectedCard === card ) {
				// Can not select already selected card
				return;
			} else {
				// Unselect previous one
				this.unselect();
			}
		}
		this.selectedCard = card;
		
		this.app.enableActionButton(this.confirmButtonId);
	}
	
}
