/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { stringService } from "../service/string.service.js";

export class Deferred {
	/**
	 * @type {DeferredPromise}
	 */
	#promise
	
	constructor(type) {
		this.type = type;
		this.doneCallback = null;
		this.failCallback = null;
		this.pendingValue = null;
		// Bidirectional
		this.child = null;
		this.parent = null;
		// Unique ID
		this.id = stringService.generateId();
		this.#promise = new DeferredPromise(this);
	}
	
	/**
	 * @param {Array<DeferredPromise>} promises
	 * @return Deferred
	 */
	static any(promises) {
		const deferred = new Deferred(promises.length ? promises[0].type() : null);
		promises.forEach(promise => promise.then(
			data => deferred.resolve(data),
			data => deferred.reject(data)
		));
		return deferred;
	}
	
	attachCallbacks(doneCallback, failCallback) {
		this.doneCallback = doneCallback;
		this.failCallback = failCallback;
		if( this.pendingValue ) {
			// Resolve now a previous resolved value
			this.resolve(this.pendingValue);
		}
	}
	
	reject(value) {
		if( this.failCallback ) {
			value = this.failCallback.apply(null, [value]);
		}
		if( this.child ) {
			this.child.reject(value);
		}
		
		return this;
	}
	
	promise() {
		return this.#promise;
	}
	
	getRootDeferred() {
		return this.parent ? this.parent.getRootDeferred() : this;
	}
	
	castChild() {
		const child = new Deferred(this.type);
		child.parent = this;
		this.child = child;
		return child;
	}
	
	getListener() {
		if( !this.listener ) {
			this.listener = event => {
				this.resolve(event.detail, event);
			};
		}
		return this.listener;
	}
	
	resolve(value) {
		if( this.doneCallback ) {
			value = this.doneCallback.apply(null, [value]);
			this.pendingValue = null;
		} else {
			// Store non-captured events but only one
			this.pendingValue = value;
		}
		if( !this.pendingValue && this.child ) {
			this.child.resolve(value);
		}
		
		return this;
	}
}

export class DeferredPromise {
	/**
	 * @param {Deferred} deferred
	 */
	constructor(deferred) {
		this.deferred = deferred;
	}
	
	type() {
		return this.deferred.type;
	}
	
	getRootDeferred() {
		return this.deferred.getRootDeferred();
	}
	
	then(doneCallback, failCallback) {
		if( this.deferred.child ) {
			throw new Error('You could only chain call to "then", never re-use same deferred promise');
		}
		this.deferred.attachCallbacks(doneCallback, failCallback);
		
		const child = this.deferred.castChild();
		return child.promise();
	}
}
