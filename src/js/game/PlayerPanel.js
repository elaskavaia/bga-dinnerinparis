/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { domService } from "../service/dom.service.js";
import { attr } from "./formatter.js";

export class PlayerPanel {
	
	/**
	 * @param {Object} gameData
	 * @param {Player} player
	 * @param {PlayerBoard} playerBoard
	 * @param {GameApp} app
	 */
	constructor(gameData, player, playerBoard, app) {
		this.player = player;
		this.playerBoard = playerBoard;
		this.app = app;
		this.$panel = document.getElementById('player_board_' + player.id);
		this.$element = null;
		this.$income = null;
		this.$resourceCardCounter = null;
		this.$objectiveDoneCounter = null;
		this.majorities = gameData.majorities;
		this.$majorityCounters = {};
		this.build();
		this.bindEvents();
	}
	
	bindEvents() {
		this.playerBoard.resourceCardHand
			.onChange()
			.then(() => {
				this.refresh();
			});
		this.playerBoard.objectiveCardDiscard
			.onChange()
			.then(() => this.refresh());
		this.player
			.onChange()
			.then(previous => {
				if( previous.score !== this.player.score ) {
					// Score has changed, we update display
					// @see https://en.doc.boardgamearena.com/Game_interface_logic:_yourgamename.js#Update_players_score
					this.app.getScoreCounter(this.player).toValue(this.player.score);
				}
				this.refresh();
			});
	}
	
	build() {
		this.$element = domService.castElement(`
<div class="game-player-panel">
	<div class="player-info">
		<div class="panel-item item-income" title="${attr(_('Income'))}">
			<div class="item-value"></div>
			<div class="item-icon">
				<div class="resource resource-gold size-16"></div>
			</div>
		</div>
		<div class="panel-item item-resource-cards" title="${attr(_('Resource cards'))}">
			<div class="item-value"></div>
			<div class="item-icon">
				<div class="resource-card resource-card-C1Potatoes size-16"></div>
			</div>
		</div>
		<div class="panel-item item-completed-objectives" title="${attr(_('Completed objectives'))}">
			<div class="item-value"></div>
			<div class="item-icon">
				<div class="objective-card objective-card-hidden size-16"></div>
			</div>
		</div>
	</div>
	<div class="player-majority-ranking">
	</div>
</div>
`);
		this.$income = this.$element.querySelector('.item-income .item-value');
		this.$resourceCardCounter = this.$element.querySelector('.item-resource-cards .item-value');
		this.$objectiveDoneCounter = this.$element.querySelector('.item-completed-objectives .item-value');
		const majorityRanking = this.$element.querySelector('.player-majority-ranking');
		Object.entries(this.majorities).forEach(([majority, majorityMaterial]) => {
			const $element = domService.castElement(`
		<div class="panel-item item-majority-ranking" title="${attr(_(majorityMaterial.label))}">
			<div class="item-value"></div>
			<div class="item-icon">
				<div class="majority-icon majority-${majority} size-32"></div>
			</div>
		</div>
			`);
			this.$majorityCounters[majority] = $element.querySelector('.item-value');
			majorityRanking.append($element);
		});
		this.$panel.append(this.$element);
		this.refresh();
	}
	
	
	refresh() {
		this.$income.innerText = this.player.income;
		this.$resourceCardCounter.innerText = this.playerBoard.resourceCardHand.size();
		this.$objectiveDoneCounter.innerText = this.playerBoard.objectiveCardDiscard.size();
		Object.entries(this.$majorityCounters).forEach(([majority, $counter]) => {
			const result = this.player.majority[majority];
			$counter.innerText = result.valid ? result.position : '-';
		});
	}
	
	
}
