/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { domService } from "../service/dom.service.js";
import { eventService } from "../service/event.service.js";

/**
 * @property {(evenType: String, data) => DeferredPromise} trigger
 * @property {(evenType: String) => DeferredPromise} on
 * @property {() => Boolean} off
 */
export class Dialog {
	
	static CLASS_FOLD = 'fold';
	
	constructor($dialog, config, container) {
		this.$dialog = $dialog instanceof DocumentFragment ? $dialog.firstElementChild : $dialog;
		this.config = config;
		this.container = container;
		this.$title = this.$dialog.querySelector('.dialog-title');
		this.$body = this.$dialog.querySelector('.dialog-body');
		this.$element = this.$dialog;
		this.container.getElement().append($dialog);// DocumentFragment or Element
		this.hide(true);
		this.$title = this.$dialog.querySelector('.dialog-title');
		this.$closeButtons = this.$dialog.querySelectorAll('.btn-close');
		this.$minimizeButtons = this.$dialog.querySelectorAll('.btn-minimize');
		this.$cancelButton = this.$dialog.querySelector('.action-cancel');
		this.$confirmButton = this.$dialog.querySelector('.action-confirm');
		this.refresh();
	}
	
	static attachToController(prototype) {
		prototype = eventService.castPrototype(prototype);
		prototype.onStarted = function () {
			return this.on('started');
		};
		prototype.onEnd = function () {
			return this.on('end');
		};
		prototype.openDialog = function () {
			if( !this.dialog ) {
				return;
			}
			this.dialog.show();
		}
		prototype.closeDialog = function () {
			if( !this.dialog ) {
				return;
			}
			this.dialog.hide();
		}
		if( !prototype.onDialogAttached ) {
			prototype.onDialogAttached = function () {
			};
		}
		prototype.attachDialog = function () {
			if( this.dialogAttached ) {
				return;
			}
			this.dialogAttached = true;
			if( this.autoOpenDialog === undefined ) {
				this.autoOpenDialog = true;
			}
			this.onStarted()
				.then(() => {
					this.dialog = this.buildDialog();
					this.onDialogAttached();
					if( this.autoOpenDialog ) {
						this.openDialog();
					}
				});
			this.onEnd()
				.then(() => this.detachDialog());
		};
		prototype.detachDialog = function () {
			if( this.dialog ) {
				this.closeDialog();
				this.dialog.remove();
				this.dialog = null;
			} else {
				console.warn('End dialog controller with no dialog', this, this.dialog);
			}
			// Never really detach but don't attach again
		};
	}
	
	static build(data) {
		/* embed could be ['viewport', 'table'] */
		if( data.close === undefined ) {
			data.close = false;
		}
		if( data.close === true ) {
			data.close = _('Close');
		}
		if( data.cancel === true ) {
			data.cancel = _('Cancel');
		}
		if( data.minimize === undefined ) {
			data.minimize = true;
		}
		if( data.minimize === true ) {
			data.minimize = _('Minimize');
		}
		return domService.castElement(`
		<div class="dialog-container">
			<div class="dialog">
				<div class="dialog-header">
					<h5 class="dialog-title">${data.title}</h5>
					<div class="dialog-header-actions">
						<button type="button" class="btn-icon btn-minimize action-minimize" aria-label="${data.minimize}">
							<i class="icon-chevron-down"></i>
						</button>
						<button type="button" class="btn-icon btn-close action-close" aria-label="${data.close}"></button>
					</div>
				</div>
				<div class="dialog-body">
					${data.body || ''}
				</div>
				<div class="dialog-footer">
					<button class="btn btn-outline-secondary action-close action-cancel">${data.cancel}</button>
					<button class="btn btn-primary action-confirm">${data.confirm}</button>
				</div>
			</div>
		</div>
		`);
	}
	
	refresh() {
		domService.toggleClass(this.$title, 'action-minimize', this.config.minimize);
		this.$minimizeButtons.forEach($element => $element.hidden = !this.config.minimize);
		this.$closeButtons.forEach($element => $element.hidden = !this.config.close);
		this.$cancelButton.hidden = !this.config.cancel;
		this.$dialog.querySelectorAll('.action-close').forEach((element) => element.addEventListener('click', () => this.hide()));
		this.$dialog.querySelectorAll('.action-minimize').forEach((element) => element.addEventListener('click', () => this.toggleMinimize()));
	}
	
	isMinimized() {
		const $component = this.getElement();
		return $component.classList.contains(Dialog.CLASS_FOLD);
	}
	
	toggleMinimize() {
		if( this.isMinimized() ) {
			this.maximize();
		} else {
			this.minimize();
		}
	}
	
	minimize() {
		const $component = this.getElement();
		this.container.disable();
		$component.classList.add(Dialog.CLASS_FOLD);
	}
	
	maximize() {
		const $component = this.getElement();
		this.container.enable();
		$component.classList.remove(Dialog.CLASS_FOLD);
	}
	
	disableButtons() {
		this.getElement().querySelectorAll('.action-cancel,.action-close,.action-confirm').forEach($element => $element.disabled = true);
	}
	
	enableButtons() {
		this.getElement().querySelectorAll('.action-cancel,.action-close,.action-confirm').forEach($element => $element.disabled = false);
	}
	
	listenConfirm() {
		if( this.listeningEvents ) {
			return;
		}
		this.listeningEvents = true;
		const $element = this.getElement();
		eventService
			.onClick($element.querySelectorAll('.action-cancel,.action-close'))
			.then(() => {
				this.trigger('cancel');
				this.trigger('result', {result: false});
			});
		eventService
			.onClick($element.querySelectorAll('.action-confirm'))
			.then(() => {
				this.trigger('confirm');
				this.trigger('result', {result: true});
			});
	}
	
	onResult() {
		this.listenConfirm();
		return this.on('result');
	}
	
	onConfirm() {
		this.listenConfirm();
		return this.on('confirm');
	}
	
	onCancel() {
		this.listenConfirm();
		return this.on('cancel');
	}
	
	static make(config, container) {
		// Inside static, this refers to the class itself
		return new this(this.build(config), config, container);
	}
	
	show() {
		this.container.show();
		const $component = this.getElement();
		$component.style.display = '';
		setTimeout(() => {
			$component.classList.add('show');
		}, 100);
	}
	
	hide(force) {
		const $component = this.getElement();
		const apply = () => {
			$component.style.display = 'none';
			if( force ) {
				// Forcing when init dialog, dialog did not show the container, so we don't want to decrement dialog counter
				this.container.hide();
			}
		}
		$component.classList.remove('show');
		if( !force ) {
			window.setTimeout(apply, 500);
		} else {
			apply();
		}
	}
	
	remove() {
		this.hide(true);
		domService.detach(this.$element);
	}
	
	getElement() {
		return this.$element;
	}
	
	getDialog() {
		return this.$dialog;
	}
	
	getTitle() {
		return this.$title;
	}
	
	getBody() {
		return this.$body;
	}
	
}

Dialog.EMBED_VIEWPORT = 'viewport';
Dialog.EMBED_TABLE = 'table';

eventService.useElementListener(Dialog.prototype);

/*
	<div class="dialog-container show">
		<div class="dialog">
			<div class="dialog-header">
				<h5 class="dialog-title">Modal title</h5>
				<button type="button" class="btn-close action-close" aria-label="Close"></button>
			</div>
			<div class="dialog-body">
				<p>
					Lorem ipsum dolor sit amet, consectetur adipiscing elit.
					Maecenas id neque ante. Nam nec tortor bibendum, fermentum felis vel, fringilla lectus. Duis convallis, leo vel pulvinar consequat, turpis est imperdiet arcu, non dictum lorem justo vitae ipsum. Mauris ante tellus, convallis et viverra nec, porttitor id dui. Duis ultrices posuere libero, sed scelerisque turpis rhoncus nec. Cras rutrum dolor sed dolor ultricies, lobortis facilisis dui ornare.
				</p>
			</div>
			<div class="dialog-footer">
				<button class="btn btn-outline-secondary">Cancel</button>
				<button class="btn btn-primary">Confirm selection</button>
			</div>
		</div>
	</div>
 */
