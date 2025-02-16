/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

export class BuildableElement {
	
	constructor() {
		this.$element = null;
	}
	
	initializeElement(force) {
		if( this.$element && !force ) {
			return;
		}
		// Build element and assign it
		this.$element = this.buildElement();
		// Initialize this object from new element (as referenced elements)
		this.prepareElement();
		// Refresh content, referenced elements or element itself
		this.refresh();
	}
	
	bindEvents() {
	}
	
	/**
	 * @returns {Element}
	 */
	getElement() {
		this.initializeElement();
		return this.$element;
	}
	
	
	/**
	 * @returns {Element}
	 */
	buildElement() {
		throw new Error(`Please implement buildElement() method for ${this.constructor.name}`);
	}
	
	/**
	 * Initialize object with element
	 */
	prepareElement() {
	}
	
	/**
	 * Refresh element when a changes occur
	 */
	refresh() {
	}
}
