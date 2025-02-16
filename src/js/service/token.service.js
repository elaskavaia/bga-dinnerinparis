/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { arrayService } from "./array.service.js";

class TokenService {
	
	/**
	 * @param {Array|Set} tokens
	 * @returns {Number[]}
	 */
	getTokensId(tokens) {
		return [...tokens].map(token => token.id());
	}
	
	generateTokensKey(tokenIds) {
		return arrayService.sortNumeric([...tokenIds]).join('-');
	}
	
	getKeyCosts(costs) {
		const keyCosts = {};
		const tree = this.getCostCardsTree([...costs]);
		tree.forEach(tokenIds => {
			// Several Set could have the same cards if costing several times the same resource
			// Any card set in a different order, we don't care, we only keep the last one
			keyCosts[this.generateTokensKey(tokenIds)] = tokenIds;
		});
		return keyCosts;
	}
	
	getCostCardsTree(costs) {
		const cost = costs.shift();
		if( !costs.length ) {
			return cost.cards.map(tokenId => [tokenId]);
		}
		const tree = [];
		this.getCostCardsTree(costs).forEach(tokenIds => {
			// For any possibilities of child
			cost.cards.forEach(tokenId => {
				if( !tokenIds.includes(tokenId) ) {// Prevent duplicate usage of one card
					tree.push([tokenId, ...tokenIds]);// Add first to Shallow copy
				}
			});
		});
		return tree;
	}
	
}

export const tokenService = new TokenService();
