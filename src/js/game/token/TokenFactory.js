/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractGameToken } from "./AbstractGameToken.js";
import { ResourceCard } from "./ResourceCard.js";
import { Token } from "./Token.js";
import { TerraceToken } from "./TerraceToken.js";
import { ObjectiveCard } from "./ObjectiveCard.js";
import { PigeonCard } from "./PigeonCard.js";
import { MajorityCard } from "./MajorityCard.js";
import { RestaurantToken } from "./RestaurantToken.js";

export class TokenFactory {
	
	constructor(table, players, materials) {
		this.table = table;
		this.players = players;
		this.materials = materials;
		this.materialMapping = {};
		this.materialMapping[Token.TYPE_RESOURCE_CARD] = 'resourceCards';
		this.materialMapping[Token.TYPE_PIGEON_CARD] = 'pigeonCards';
		this.materialMapping[Token.TYPE_OBJECTIVE_CARD] = 'objectiveCards';
		this.materialMapping[Token.TYPE_MAJORITY_CARD] = 'majorityCards';
		this.materialMapping[Token.TYPE_RESTAURANT] = 'restaurants';
		this.materialMapping[Token.TYPE_TERRACE] = 'terraces';
	}
	
	/**
	 * @param {AbstractGameToken} token
	 * @param data
	 */
	update(token, data) {
		token.player = data.playerId ? this.players[data.playerId] : null;
		token.data = data;
		// Material could go from hidden to any visible
		let materialKey = this.materialMapping[data.type];
		if( !materialKey ) {
			console.error('Unknown materialKey for type ' + data.type);
		}
		token.material = materialKey ? this.materials[materialKey][data.variant !== undefined ? data.variant : 'hidden'] : null;
		
		token.calculate(this.table);
		// console.log('Updated', token.debugLabel(), 'is now visible ?', token.visible(), token.data, token.material, token);
		token.refresh();
	}
	
	/**
	 * @param data
	 * @returns {AbstractGameToken}
	 */
	create(data) {
		let token = null;
		const player = data.playerId ? this.players[data.playerId] : null;
		switch( data.type ) {
			case Token.TYPE_RESOURCE_CARD: {
				const material = this.materials.resourceCards[data.variant !== undefined ? data.variant : 'hidden'];
				if( !material ) {
					throw new Error(`Missing required material of type RESOURCE_CARD for variant ${data.variant}`);
				}
				token = new ResourceCard(data, player, material);
				break;
			}
			case Token.TYPE_RESTAURANT: {
				const material = this.materials.restaurants[data.variant];
				if( !material ) {
					throw new Error(`Missing required material of type RESTAURANT for variant ${data.variant}`);
				}
				token = new RestaurantToken(data, player, material);
				break;
			}
			case Token.TYPE_TERRACE: {
				token = new TerraceToken(data, player);
				break;
			}
			case Token.TYPE_PIGEON_CARD: {
				const material = this.materials.pigeonCards[data.variant !== undefined ? data.variant : 'hidden'];
				if( !material ) {
					throw new Error(`Missing required material of type PIGEON_CARD for variant ${data.variant}`);
				}
				token = new PigeonCard(data, player, material);
				break;
			}
			case Token.TYPE_OBJECTIVE_CARD: {
				const material = this.materials['objectiveCards'][data.variant !== undefined ? data.variant : 'hidden'];
				if( !material ) {
					throw new Error(`Missing required material of type OBJECTIVE_CARD for variant ${data.variant}`);
				}
				token = new ObjectiveCard(data, player, material);
				break;
			}
			case Token.TYPE_MAJORITY_CARD: {
				const material = this.materials['majorityCards'][data.variant !== undefined ? data.variant : 'hidden'];
				if( !material ) {
					throw new Error(`Missing required material of type MAJORITY_CARD for variant ${data.variant}`);
				}
				token = new MajorityCard(data, player, material);
				break;
			}
		}
		if( token ) {
			token.calculate(this.table);
		}
		return token;
	}
	
	/*
container: 2
id: "1"
parentTokenId: null
playerId: null
position: 22
type: 1
variant: 0
	 */
	
}
