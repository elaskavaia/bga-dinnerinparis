/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { Deferred } from "../event/Deferred.js";

class DomService {
	
	constructor() {
		this.resolver = eventObj => {
			resolve(eventObj);
		};
	}
	
	detach($element) {
		if( !$element.parentElement ) {
			return false;
		}
		$element.parentElement.removeChild($element);
		return true;
	}
	
	castElement(fragment) {
		return document.createRange().createContextualFragment(fragment).firstElementChild;
	}
	
	createElement(tag, className, attributes) {
		const $element = document.createElement(tag);
		if( className ) {
			$element.className = className;
		}
		if( attributes && typeof attributes === 'object' ) {
			Object.entries(attributes).forEach(([key, value]) => {
				$element.setAttribute(key, value);
			});
		}
		return $element;
	}
	
	/**
	 * @param {Element} $element
	 * @param {string|Array<string>} classList
	 * @param {boolean|null} toggle True to add, false to remove, null to invert
	 */
	toggleClass($element, classList, toggle) {
		if( typeof classList === 'string' ) {
			classList = classList.split(' ');
		}
		for( const cssClass of classList ) {
			$element.classList.toggle(cssClass, toggle);
		}
	}
	
	/**
	 * @param {Element} $element
	 * @param {string} event
	 */
	bind($element, event) {
		const deferred = new Deferred();
		$element.addEventListener(event, eventObj => {
			// Value is the eventObj, containing all information about DOM/Custom Event
			deferred.resolve(eventObj);
		});
		return deferred.promise();
	}
	
}

export class ButtonElement {
	static TYPE_SUBMIT = 'submit';
	static TYPE_BUTTON = 'button';
}

Object.freeze(ButtonElement);

export const domService = new DomService();
