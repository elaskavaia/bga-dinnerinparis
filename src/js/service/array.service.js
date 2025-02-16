/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

class ArrayService {
	
	removeElement(array, element) {
		return this.remove(array, array.indexOf(element));
	}
	
	remove(array, index) {
		if( index < 0 ) {
			return false;
		}
		const removed = array.splice(index, 1);
		
		return removed.length > 0;
	}
	
	sortNumeric(array) {
		return array.sort((a, b) => {
			return a - b;
		});
	}
	
}

export const arrayService = new ArrayService();
