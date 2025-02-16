/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

/**
 * Shown means the container contents is displayed
 * If hidden, we don't care this is enabled or not
 * Enabled means the container is displayed on foreground
 * Disabled means the container is displayed but minimized
 */
export class Container {
	
	constructor($container) {
		this.$element = $container;
		this.shown = 0;
		this.enabled = true;
		this.refresh();
	}
	
	getElement() {
		return this.$element;
	}
	
	disable() {
		this.enabled = false;
		this.refresh();
	}
	
	enable() {
		this.enabled = true;
		this.refresh();
	}
	
	show() {
		this.shown++;
		this.enabled = true;
		this.refresh();
	}
	
	hide() {
		if( !this.shown ) {
			return;
		}
		this.shown--;
		if( !this.isShown() ) {
			this.enabled = false;
		}
		this.refresh();
	}
	
	isShown() {
		return this.shown > 0;
	}
	
	refresh() {
		const foreground = this.isShown() && this.enabled;
		this.$element.style.display = this.isShown() ? '' : 'none';
		// Only if container contents in on foreground
		this.$element.style['pointer-events'] = foreground ? 'auto' : 'none';
		document.body.style.overflow = foreground ? 'hidden' : '';
	}
	
}
