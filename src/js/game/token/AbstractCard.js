/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractGameToken } from "./AbstractGameToken.js";
import { Modal } from "../../component/Modal.js";
import { domService } from "../../service/dom.service.js";

export class AbstractCard extends AbstractGameToken {
	
	static SIZE_SMALL = 56;// Fixed Height (80px)
	static SIZE_NORMAL = 79;// Fixed Width
	static SIZE_LARGE = 237;// Fixed Width
	
	initialize() {
		super.initialize();
		this.viewSize = null;
		this.$actions = null;
		this.allowClickToPreview = false;
		this.previewClickListener = false;
		this.previewClickButtonListener = null;
	}
	
	prepareElement() {
		if( this.allowClickToPreview ) {
			this.bindPreviewToElement();
		}
	}
	
	bindPreviewToElement() {
		if( !this.allowClickToPreview || this.previewClickListener ) {
			return;
		}
		if( this.previewClickButtonListener ) {
			this.unbindPreviewFromButton();
		}
		this.previewClickListener = this.onClick()
			.then(() => this.openPreview());
	}
	
	unbindPreviewFromElement() {
		if( !this.allowClickToPreview || !this.previewClickListener ) {
			return;
		}
		this.off(this.previewClickListener);
		this.previewClickListener = null;
	}
	
	bindPreviewToButton() {
		if( !this.allowClickToPreview || this.previewClickButtonListener ) {
			return;
		}
		if( this.previewClickListener ) {
			this.unbindPreviewFromElement();
		}
		/**
		 * @param {Event} event
		 */
		this.previewClickButtonListener = event => {
			event.preventDefault();
			event.stopImmediatePropagation();
			this.openPreview();
		};
		this.$actions = domService.castElement(`
		<div class="actions">
			<button class="btn action-view" type="button"><i class="fa fa-eye"></i></button>
		</div>`);
		this.getOverlay().append(this.$actions);
		this.showOverlay();
		this.getViewButton().addEventListener('click', this.previewClickButtonListener);
	}
	
	getViewButton() {
		return this.$actions.querySelector('.btn.action-view');
	}
	
	unbindPreviewFromButton() {
		if( !this.allowClickToPreview || !this.previewClickButtonListener ) {
			return;
		}
		this.getViewButton().removeEventListener('click', this.previewClickButtonListener);
		this.previewClickButtonListener = null;
		domService.detach(this.$actions);
	}
	
	onSelect() {
		if( this.allowClickToPreview ) {
			this.bindPreviewToButton();
		}
		
		return super.onSelect();
	}
	
	offSelect() {
		super.offSelect();
		if( this.allowClickToPreview ) {
			this.bindPreviewToElement();
		}
	}
	
	openPreview() {
		const modal = new Modal(app.pageOverlayViewport);
		const $body = domService.castElement(`<div class="card-preview"></div>`);
		const previewCard = this.clone(AbstractGameToken.PURPOSE_PREVIEW);
		previewCard.allowClickToPreview = false;
		previewCard.viewSize = AbstractCard.SIZE_LARGE;
		$body.append(previewCard.getElement());
		modal
			.setTitle(this.label())
			.setBody($body)
			.show();
	}
	
	setDisabledText(text) {
		const $element = this.getElement();
		$element.style.setProperty('--text', '"' + text + '"');
		$element.classList.toggle('disabled', !!text);
	}
	
	getOverlay() {
		return this.getElement().querySelector('.overlay');
	}
	
	showOverlay() {
		this.getOverlay().hidden = false;
	}
	
	hideOverlay() {
		this.getOverlay().hidden = true;
	}
	
	/**
	 * @returns {Element}
	 */
	buildElement() {
		const $element = domService.castElement(`
<div>
	<div class="game-card-inner">
		<div class="face front"></div>
		<div class="face back"></div>
		<div class="overlay" hidden></div>
	</div>
</div>`);
		if( this.id() ) {
			$element.dataset.id = this.id();
		}
		$element.token = this;
		return $element;
	}
	
	getViewSize() {
		return this.viewSize;
	}
	
	refresh() {
		if( !this.$element ) {
			return;
		}
		// Rebuild all class list
		this.$element.classList.value = '';
		this.$element.classList.add('game-card', 'card-' + this.type(), 'card-' + this.type() + '-' + this.materialKey());
		const viewSize = this.getViewSize();
		if( viewSize ) {
			this.$element.classList.add('size-' + viewSize);
		}
		// Is card visible ?
		if( this.visible() ) {
			this.$element.classList.remove('card-hidden', 'face-back');
		} else {
			this.$element.classList.add('card-hidden', 'face-back');
		}
	}
	
}
