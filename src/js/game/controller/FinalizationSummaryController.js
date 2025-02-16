/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractController } from "../../core/controller/AbstractController.js";
import { domService } from "../../service/dom.service.js";

export default class FinalizationSummaryController extends AbstractController {
	
	start(args) {
		console.info('FinalizationSummaryController.start', args);
		this.initializeDialog(args.scores);
		this.openDialog();
	}
	
	end() {
		// If any player confirm this dialog, we should close all, game is removing our code
		this.removeDialog();
	}
	
	removeDialog() {
		// Close & clear dialog
		if( !this.dialog ) {
			return;
		}
		this.closeDialog();
		this.dialog.remove();
		this.dialog = null;
	}
	
	initializeDialog(scores) {
		this.dialog = this.app.createDialog({
			title: _('Summary of the game'),
			large: true,
			cancel: false,
			close: false,
			confirm: _('Confirm'),
			body: `
<p>${_('The game is now ended, here is the score summary.')}</p>
<div class="score-summary mt-3">
	<table class="score-summary-grid"></table>
</div>
			`,
		});
		const rows = [
			{content: 'label', useColor: true, bold: true},
			{content: 'score_restaurant'},
			{content: 'score_terrace'},
			{content: 'score_majority_1'},
			{content: 'score_majority_2'},
			{content: 'score_majority_3'},
			{content: 'score_objective'},
			{content: 'total', bold: true},
		];
		scores = Object.entries(scores).map(([playerId, playerScore]) => {
			playerScore.player = this.app.getPlayer(playerId);
			return playerScore;
		});
		scores = Array.from({...scores, length: 4});
		const $grid = this.dialog.$element.querySelector('.score-summary-grid');
		for( const row of rows ) {
			const $row = domService.createElement('tr');
			$row.append(domService.createElement('th'));
			for( const playerScore of scores ) {
				const $cell = domService.createElement('td');
				let text = '';
				let textColor = '#000000';
				let textWeight = 'normal';
				if( playerScore ) {
					text = playerScore[row.content];
					if( row.useColor && playerScore.player ) {
						textColor = '#' + playerScore.player.color;
					}
					if( row.bold ) {
						textWeight = 'bold';
					}
				}// Empty is not a real player's score
				const $content = domService.castElement(`
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100" >
	<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="${textColor}" font-weight="${textWeight}">${text}</text>
</svg>
				`);
				$cell.append($content);
				$row.append($cell);
			}
			$grid.append($row);
		}
		this.dialog.onConfirm()
			.then(() => this.confirm());
		this.dialog.onCancel()
			.then(() => this.confirm());
	}
	
	confirm() {
		// Check action
		if( !this.app.ui.checkAction('finalizeSummary') ) {
			console.warn('Not allowed to end game');
			return;
		}
		// Apply
		this.app.realizeAction('finalizeSummary');
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
	
}
