/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractTokenList } from "./AbstractTokenList.js";
import { domService } from "../../../service/dom.service.js";

/**
 * Ordered stacked card list
 */
export class CardPile extends AbstractTokenList {
	
	build() {
		const cards = this.getTokens();
		// Empty List
		this.$element.innerHTML = '';
		// Append cards
		cards.forEach((card, index) => {
			// Only show top 2
			if( index < 2 ) {
				// Reverse order
				this.$element.prepend(card.getElement());
				this.animateMove(card);
			}
			// Animate all
		});
		this.$counter = domService.castElement('<div class="item-counter"></div>');
		this.$element.append(this.$counter);
	}
	
	refresh() {
		this.build();
		this.$counter.innerText = this.getTokens().length;
	}
	
}
