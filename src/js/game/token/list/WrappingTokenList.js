/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { TokenList } from "./TokenList.js";

/**
 * Terrace list
 */
export class WrappingTokenList extends TokenList {
	
	initialize() {
		this.wrapping = true;
	}
	
}
