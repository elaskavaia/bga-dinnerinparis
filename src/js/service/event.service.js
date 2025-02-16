/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */
// noinspection JSUnusedGlobalSymbols

import { Deferred, DeferredPromise } from "../event/Deferred.js";
import { objectService } from "./object.service.js";

// noinspection JSPotentiallyInvalidUsageOfClassThis
class EventService {
	
	isDomElement(object) {
		return object instanceof Element;
	}
	
	onClick(object) {
		return this.on(object, 'click');
	}
	
	/**
	 * @param {NodeList|Array|Element|Object} object
	 * @param {String} eventType
	 * @returns {DeferredPromise}
	 */
	on(object, eventType) {
		if( objectService.isIterable(object) ) {
			const promises = [];
			for( var item of object ) {
				promises.push(this.on(item, eventType));
			}
			return Deferred.any(promises).promise();
		}
		const deferred = new Deferred(eventType);
		if( this.isDomElement(object) ) {
			object.addEventListener(eventType, deferred.getListener());
		} else {
			if( !object._events ) {
				object._events = {};
			}
			if( !object._events[eventType] ) {
				object._events[eventType] = [];
			}
			object._events[eventType].push(deferred);
		}
		return deferred.promise();
	}
	
	/**
	 * @param {Element|Object} object
	 * @param {DeferredPromise} promise
	 * @returns {Boolean}
	 */
	off(object, promise) {
		const deferred = promise.getRootDeferred();
		if( this.isDomElement(object) ) {
			object.removeEventListener(deferred.type, deferred.getListener());
		} else {
			if( !object._events || !object._events[eventType] ) {
				return false;
			}
			const index = object._events[eventType].indexOf(deferred);
			if( index < 0 ) {
				return false;
			}
			object._events[eventType].splice(index, 1);
		}
		return true;
	}
	
	/**
	 * Prototype of BuildableElement
	 * @param prototype
	 * @param {Boolean} useNativeEvents
	 */
	useListener(prototype, useNativeEvents) {
		prototype = this.castPrototype(prototype);
		if( useNativeEvents === undefined ) {
			useNativeEvents = !!prototype.getElement;
		}
		prototype._useNativeEvents = useNativeEvents;
		prototype.on = function (eventType) {
			const deferred = new Deferred(eventType);
			const object = this;
			if( !object._events ) {
				object._events = {};
			}
			if( !object._events[eventType] ) {
				object._events[eventType] = [];
			}
			object._events[eventType].push(deferred);
			if( useNativeEvents ) {
				/** @type {Element} */
				const $element = this.getElement();
				$element.addEventListener(eventType, deferred.getListener());
			}
			return deferred.promise();
		};
		/**
		 * @param {DeferredPromise|String} promiseOrType
		 * @returns {Boolean}
		 */
		prototype.off = function (promiseOrType) {
			const object = this;
			let eventType, deferred,
				justOne = promiseOrType instanceof DeferredPromise;
			if( justOne ) {
				deferred = promiseOrType.getRootDeferred();
				eventType = deferred.type;
			} else {
				eventType = promiseOrType;
			}
			if( !object._events || !object._events[eventType] ) {
				return false;
			}
			if( justOne ) {
				// Remove one
				const index = object._events[eventType].indexOf(deferred);
				if( index < 0 ) {
					return false;
				}
				// Remove from native events
				if( useNativeEvents ) {
					/** @type {Element} */
					const $element = this.getElement();
					$element.removeEventListener(deferred.type, deferred.getListener());
				}
				// Remove from object
				object._events[eventType].splice(index, 1);
				return true;
			} else {
				// Remove all by type
				// Remove from native events
				if( useNativeEvents ) {
					/** @type {Element} */
					const $element = this.getElement();
					object._events[eventType].forEach(deferred => $element.removeEventListener(deferred.type, deferred.getListener()));
				}
				// Remove from object
				delete object._events[eventType];
			}
			return true;
		};
		prototype.trigger = function (eventType, data = null) {
			if( !objectService.isPureObject(data) ) {
				data = {};
			}
			data.target = this;
			if( useNativeEvents ) {
				// Trigger native events
				/** @type {Element} */
				const $element = this.getElement();
				$element.dispatchEvent(new CustomEvent(eventType, {
					detail: data
				}));
			} else if( this._events && this._events[eventType] && Array.isArray(this._events[eventType]) ) {
				// Trigger from object
				this._events[eventType].forEach(deferred => deferred.resolve(data));
			}
		};
	}
	
	useObjectListener(prototype) {
		this.useListener(prototype, false);
	}
	
	/**
	 * Prototype of BuildableElement
	 * @param prototype
	 */
	useElementListener(prototype) {
		this.useListener(prototype, true);
	}
	
	useOnChange(prototype) {
		prototype = this.castPrototype(prototype);
		prototype.onChange = function () {
			return this.on('change');
		};
		prototype.changed = function (data = null) {
			this.trigger('change', data);
		};
	}
	
	useOnClick(prototype) {
		prototype = this.castPrototype(prototype);
		prototype.onClick = function () {
			return this.on('click');
		};
		prototype.clicked = function () {
			this.trigger('click');
		};
	}
	
	castPrototype(classOrPrototype) {
		if( typeof classOrPrototype === 'function' ) {
			// Giving class instead of prototype
			classOrPrototype = classOrPrototype.prototype;
		}
		return classOrPrototype;
	}
	
	useOnSelect(prototype) {
		prototype = this.castPrototype(prototype);
		prototype.CLASS_SELECTABLE = 'selectable';
		prototype.CLASS_SELECTED = 'selected';
		prototype.toggleSelect = function () {
			if( this.selected ) {
				this.unselect();
			} else {
				this.select();
			}
		};
		prototype.offSelect = function () {
			this.off('select');
			this.off('unselect');
			return true;
		};
		prototype.onSelect = function () {
			return this.on('select');
		};
		prototype.select = function (data) {
			this.selected = true;
			this.trigger('select', data);
		};
		prototype.onUnselect = function () {
			return this.on('unselect');
		};
		prototype.unselect = function () {
			this.selected = false;
			this.trigger('unselect');
		};
		prototype.selected = false;
	}
	
}

export const eventService = new EventService();
