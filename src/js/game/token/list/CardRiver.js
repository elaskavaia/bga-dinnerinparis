/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractTokenList } from "./AbstractTokenList.js";
import { domService } from "../../../service/dom.service.js";

/**
 * Ordered horizontal card list
 */
export class CardRiver extends AbstractTokenList {
	
	/**
	 * @param {Element} $element
	 * @param {Object} config
	 */
	constructor($element, config = {}) {
		super($element);
		this.$slots = $element.querySelectorAll('.card-slot');
		this.overlap = config.overlap || (config.overlapMin !== undefined ? 'auto' : false);// true, false or 'auto'. Default is 'auto' if overlapMin is defined, else false
		this.overlapMin = config.overlapMin;
	}
	
	build() {
		const cards = this.getOrderedTokens();
		const cardCount = this.list.length;
		const overlappingCards = (this.overlap === 'auto' && cardCount >= this.overlapMin) || this.overlap === true;
		domService.toggleClass(this.$element, 'overlap', overlappingCards);
		if( cardCount > this.$slots.length ) {
			console.warn(`Too much card for this river (${cardCount} of ${this.$slots.length})`, this.$element);
		}
		let findNext = true;
		this.$slots.forEach(($element, index) => {
			// cards list contains a dictionary (object) of arrays, with only one value
			const slotContents = cards[index];
			const token = slotContents && slotContents.length ? slotContents[0] : null;
			$element.innerHTML = '';
			if( $element.dataset.tempClasses ) {
				$element.classList.remove($element.dataset.tempClasses);
			}
			const cssClasses = [];
			if( token ) {
				$element.append(token.getElement());
				const viewSize = token.getViewSize();
				// Only handle one class for now, else use an array and domService
				cssClasses.push('state-populated');
				if( viewSize ) {
					cssClasses.push('size-' + viewSize);
				}
				this.animateMove(token);
			} else {
				cssClasses.push('state-empty');
				if( findNext ) {
					cssClasses.push('state-next');
					findNext = false;
				}
			}
			$element.dataset.tempClasses = cssClasses;
			if( cssClasses.length ) {
				$element.classList.add(...cssClasses);
			}
		});
	}
	
}
