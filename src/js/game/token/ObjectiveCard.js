/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractCard } from "./AbstractCard.js";

export class ObjectiveCard extends AbstractCard {
	
	type() {
		return 'objective';
	}
	
	initialize() {
		super.initialize();
		this.viewSize = ObjectiveCard.SIZE_NORMAL;
		this.allowClickToPreview = true;
	}
	
}
