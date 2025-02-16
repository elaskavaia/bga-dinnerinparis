/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractGameToken } from "./AbstractGameToken.js";

export class PropertyToken extends AbstractGameToken {
	
	buildElement() {
		const $element = super.buildElement();
		$element.classList.add(`property-${this.player.colorKey}-${this.materialKey()}`);
		return $element;
	}
	
	type() {
		return 'property';
	}
	
}
