/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { eventService } from "../../service/event.service.js";

/**
 * @property {(evenType: String, data) => DeferredPromise} trigger
 * @property {(evenType: String) => DeferredPromise} on
 * @property {() => Boolean} off
 */
export class AbstractController {
	
	/**
	 * @param {GameApp} app
	 */
	constructor(app) {
		this.app = app;
		this.table = app.table;
		this.args = null;
	}
	
	/**
	 * Common to all
	 * @param args
	 */
	initialize(args) {
		this.args = args;
	}
	
	start() {
		// Only active player
	}
	
	onStarted() {
		return this.on('started');
	}
	
	spectate() {
		// Only non-active player or anonymous
	}
	
	end() {
	}
	
}

eventService.useObjectListener(AbstractController.prototype);
