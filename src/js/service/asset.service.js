/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */
import { localeService } from "./locale.service.js";

class AssetService {
	
	availableLocales = ['fr', 'en'];
	defaultLocale = 'en';
	#locale;
	
	constructor() {
		this.#locale = this.#calculateAssetLocale();
	}
	
	#calculateAssetLocale() {
		return localeService.getUserLocales().find(locale => this.availableLocales.includes(locale)) || this.defaultLocale;
	}
	
	provideAssets() {
		document.body.classList.add('asset-locale-' + this.#locale);
	}
	
	
}

export const assetService = new AssetService();
