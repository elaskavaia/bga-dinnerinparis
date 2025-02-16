/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractCard } from "./AbstractCard.js";

export class MajorityCard extends AbstractCard {
	
	type() {
		return 'majority';
	}
	
	initialize() {
		super.initialize();
		this.viewSize = MajorityCard.SIZE_NORMAL;
		this.allowClickToPreview = true;
	}
	
}
