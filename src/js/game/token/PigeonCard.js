/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractCard } from "./AbstractCard.js";

export class PigeonCard extends AbstractCard {
	
	type() {
		return 'pigeon';
	}
	
	initialize() {
		super.initialize();
		this.viewSize = PigeonCard.SIZE_NORMAL;
		this.allowClickToPreview = true;
	}
	
}
