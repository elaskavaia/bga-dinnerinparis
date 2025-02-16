/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractTokenList } from "./AbstractTokenList.js";

/**
 * Non-ordered horizontal token list
 */
export class TokenList extends AbstractTokenList {
	
	build() {
		// Empty List
		this.$element.innerHTML = '';
		// Append cards
		this.getTokens().forEach(token => {
			// No source or this container
			const $wrap = this.wrapToken(token);
			this.$element.append($wrap);
			this.animateMove(token);
		})
	}
	
}
