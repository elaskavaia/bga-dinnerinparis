/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractTokenList } from "./AbstractTokenList.js";

/**
 * virtual token list, no DOM element
 */
export class VirtualTokenList extends AbstractTokenList {
	
	constructor() {
		super(null);
	}
	
	build() {
	}
	
	changed() {
	}
	
}
