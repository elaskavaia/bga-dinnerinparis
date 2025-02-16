/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

class LocaleService {
	
	getUserLocales() {
		return navigator.languages;
	}
	
}

export const localeService = new LocaleService();
