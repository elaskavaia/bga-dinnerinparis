/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { eventService } from "../../service/event.service.js";

/**
 * Sync with Player.php
 * @property {Function} onChange
 * @property {Function} changed
 */
export class Player {
	
	constructor(data, spectator = false) {
		this.id = data.id;
		this.spectator = spectator;
		this.setData(data);
	}
	
	setData(data) {
		const previous = {...this};
		this.name = data.name;
		this.position = data.position;
		this.score = data.score;
		this.color = data.color;
		this.colorKey = data.colorKey;
		this.avatar = data.avatar;
		this.income = data.income;
		this.pendingIncome = data.pendingIncome;
		this.balance = data.balance;
		this.majority = data.majority;
		
		this.changed(previous);
	}
	
	static createSpectator(id) {
		return new Player({
			id: id,
			color: '#00008B',
			colorKey: 'blue',
		}, true);
	}
	
}

eventService.useObjectListener(Player.prototype);
eventService.useOnChange(Player.prototype);
