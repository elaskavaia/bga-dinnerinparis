/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

class ObjectService {
	
	clone(object) {
		return Object.create(Object.getPrototypeOf(object), Object.getOwnPropertyDescriptors(object));
	}
	
	isIterable(object) {
		return !!object?.[Symbol.iterator];
	}
	
	isObject(v) {
		return v !== null && typeof (v) === 'object';
	}
	
	isPureObject(v) {
		return this.isObject(v) && v.constructor === Object;
	}
	
}

export const objectService = new ObjectService();
