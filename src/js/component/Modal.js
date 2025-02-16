/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { BuildableElement } from "./BuildableElement.js";
import { domService } from "../service/dom.service.js";

export class Modal extends BuildableElement {
	
	static SIZE_NORMAL = 'normal';
	static SIZE_LARGE = 'large';
	
	constructor(container) {
		super();
		this.container = container;
		this.$body = null;
		this.$footer = null;
		this.title = null;
		this.blurClose = true;
		this.size = true;
		this.texts = {
			close: _('Close'),
		};
	}
	
	static make(container) {
		// Inside static, this refers to the class itself
		return new this(container);
	}
	
	setTitle(title) {
		this.title = title;
		
		return this;
	}
	
	setBody($body) {
		this.$body = $body;
		
		return this;
	}
	
	setFooter($footer) {
		this.$footer = $footer;
		
		return this;
	}
	
	setLarge() {
		this.size = Modal.SIZE_LARGE;
		this.refresh();
		
		return this;
	}
	
	refresh() {
		if( !this.$element ) {
			return;
		}
		const $modal = this.$element.querySelector('.modal');
		if( this.size === Modal.SIZE_NORMAL ) {
			$modal.classList.remove('size-lg');
		} else if( this.size === Modal.SIZE_LARGE ) {
			$modal.classList.add('size-lg');
		}
	}
	
	prepareElement() {
		this.$title = this.$element.querySelector('.dialog-title');
		this.$body = this.$element.querySelector('.dialog-body');
		this.$element.querySelectorAll('.action-close').forEach((element) => element.addEventListener('click', () => {
			this.hide();
		}));
		if( this.blurClose ) {
			this.container.getElement().addEventListener('click', event => {
				const outside = event.target === this.$element || event.target === event.currentTarget;
				if( outside ) {
					this.hide();
				}
			});
		}
	}
	
	buildElement() {
		const $element = domService.castElement(`
		<div class="modal-container">
			<div class="modal">
				<div class="modal-header">
					<h5 class="modal-title">${this.title || 'MISSING TITLE'}</h5>
					<button type="button" class="btn btn-close action-close" aria-label="${this.texts.close}"></button>
				</div>
				<div class="modal-body">
				</div>
			</div>
		</div>
		`);
		$element.querySelector('.modal-body').append(this.$body || 'MISSING BODY');
		return $element;
	}
	
	show() {
		// Create element each time
		this.initializeElement(true);
		this.$element.hidden = true;
		// Add element to DOM
		this.container.getElement().append(this.$element);
		// Show element
		this.$element.hidden = false;
		this.container.show();
		setTimeout(() => {
			this.$element.classList.add('show');
		}, 100);
	}
	
	hide(force) {
		const $element = this.getElement();
		$element.classList.remove('show');
		const apply = () => {
			if( !this.$element ) {
				// TODO Test why this is already removed
				console.trace('Please report trace and message to developers: the modal element is already removed');
				// We ignore if the element is already removed
				return;
			}
			// this.$element.hidden = true;
			this.$element.remove();
			this.$element = null;
			this.container.hide();
		}
		if( !force ) {
			window.setTimeout(apply, 500);
		} else {
			apply();
		}
	}
	
	getDialog() {
		return this.$element;
	}
	
	getTitle() {
		return this.$title;
	}
	
	getBody() {
		return this.$body;
	}
	
}
