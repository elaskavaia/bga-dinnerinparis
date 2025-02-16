/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractCard } from "./AbstractCard.js";

export class ResourceCard extends AbstractCard {
	
	type() {
		return 'resource';
	}
	
	initialize() {
		super.initialize();
		this.viewSize = 56;
	}
	
}
