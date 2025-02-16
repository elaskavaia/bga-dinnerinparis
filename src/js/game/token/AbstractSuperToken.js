/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { BuildableElement } from "../../component/BuildableElement.js";
import { eventService } from "../../service/event.service.js";

/**
 * Allow AbstractGameToken to call event service dynamic prototyping as super
 * @property {Function} off
 * @property {Function} on
 * @property {Function} onClick
 * @property {Function} clicked
 * @property {Function} onSelect
 * @property {Function} offSelect
 * @property {Function} select
 * @property {Function} onUnselect
 * @property {Function} unselect
 * @property {Function} toggleSelect
 */
export class AbstractSuperToken extends BuildableElement {
	
	constructor() {
		super();
		if( this.constructor === AbstractSuperToken ) {
			throw new Error("Abstract classes can't be instantiated");
		}
	}
	
}

eventService.useElementListener(AbstractSuperToken.prototype);
eventService.useOnClick(AbstractSuperToken.prototype);
eventService.useOnSelect(AbstractSuperToken.prototype);
