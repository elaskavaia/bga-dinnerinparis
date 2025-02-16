/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { Player } from "./model/Player.js";
import { Restaurant } from "./model/Restaurant.js";
import { PropertyToken } from "./token/PropertyToken.js";
import { RestaurantToken } from "./token/RestaurantToken.js";
import { TerraceToken } from "./token/TerraceToken.js";
import { ORIENTATION } from "./constants.js";

export class DemoTable {
	
	/**
	 * @param {GameTable} table
	 */
	constructor(table) {
		this.table = table;
	}
	
	generate() {
		const player1 = new Player('cyan');
		const player2 = new Player('red');
		
		this.addRestaurantTo(player1, 'creperie', 3, 2, 1, ORIENTATION.SOUTH);
		this.addRestaurantTo(player1, 'friterie', 2, 1, 4, ORIENTATION.EAST);
		this.addRestaurantTo(player2, 'grill', 4, 13, 20, ORIENTATION.NORTH);
		this.addRestaurantTo(player2, 'gastronomique', 5, 20, 7, ORIENTATION.WEST);
		this.addTerraceTo(player2, 19, 9);
		this.addTerraceTo(player2, 18, 9);
		this.addTerraceTo(player2, 17, 9);
		this.addTerraceTo(player2, 16, 9);
		this.addTerraceTo(player2, 19, 10);
	}
	
	addRestaurantTo(player, key, size, x, y, orientation) {
		const restaurant = new Restaurant(size, key);
		const propertyToken = new PropertyToken(this.table.getMaterial('property-s' + size), this.table, player, restaurant);
		const restaurantToken = new RestaurantToken(this.table.getMaterial('restaurant-s' + size), this.table);
		this.table.putTokenToCell(restaurantToken, this.table.getCellByCoordinates(x, y), orientation);
		restaurantToken.assignProperty(propertyToken);
	}
	
	addTerraceTo(player, x, y) {
		const propertyToken = new TerraceToken(this.table.getMaterial('terrace'), this.table, player);
		this.table.putTokenToCell(propertyToken, this.table.getCellByCoordinates(x, y));
	}
	
}
