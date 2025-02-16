/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { TileCell } from "./board/TileCell.js";
import { CardPile } from "./token/list/CardPile.js";
import { CardRiver } from "./token/list/CardRiver.js";
import { PlayerBoard } from "./PlayerBoard.js";
import { Token } from "./token/Token.js";
import { RestaurantPile } from "./token/list/RestaurantPile.js";
import { CellGrid } from "./token/list/CellGrid.js";

export class GameTable {
	
	/**
	 * @param {Element} $element
	 * @param {GameApp} app
	 */
	constructor($element, app) {
		this.app = app;
		this.$element = $element;
		/** @type {Object.<int, PlayerBoard>} */
		this.playerBoards = {};
		this.locations = {};
		this.cells = {};
		/** @type {Object.<string, AbstractGameToken>} */
		this.tokens = {};
		// Materials not in server side
	}
	
	refresh() {
		// Refresh players' board
		Object.values(this.playerBoards)
			.forEach(playerBoard => playerBoard.refresh());
		
		// Rebuild all piles
		this.grid.refresh();
		this.objectiveCardDrawPile.refresh();
		this.objectiveCardRiver.refresh();
		this.pigeonCardDrawPile.refresh();
		this.resourceCardDiscardPile.refresh();
		this.resourceCardDrawPile.refresh();
		this.resourceCardRiver.refresh();
		this.majorityCardSlot.refresh();
		
		// Refresh restaurants' pile
		Object.values(this.restaurantBox)
			.forEach(restaurantPile => restaurantPile.refresh());
	}
	
	build(players, gridSize, gridModel) {
		if( this.resourceCardRiver ) {
			throw new Error('Already built table');
		}
		// Game board is handled by table
		// All subcomponent are handled by their component's class
		this.resourceCardRiver = new CardRiver(this.$element.querySelector('.card-resource-picker > .card-river'));
		this.resourceCardDrawPile = new CardPile(this.$element.querySelector('.card-resource-picker > .draw-pile'));
		this.resourceCardDiscardPile = new CardPile(this.$element.querySelector('.card-resource-picker > .discard-pile'));
		this.objectiveCardDrawPile = new CardPile(this.$element.querySelector('.card-objective-picker > .draw-pile'));
		this.objectiveCardRiver = new CardRiver(this.$element.querySelector('.card-objective-picker > .card-river'), {
			overlapMin: 5,
		});
		this.pigeonCardDrawPile = new CardPile(this.$element.querySelector('.card-pigeon-picker > .draw-pile'));
		this.majorityCardSlot = new CardRiver(this.$element.querySelector('.card-majority-picker'));
		this.$playerBoardList = this.$element.querySelector('.game-player-board-list');
		// Declare all table locations
		this.setLocation(this.resourceCardRiver, Token.CONTAINER_BOARD_RIVER, Token.TYPE_RESOURCE_CARD);
		this.setLocation(this.resourceCardDrawPile, Token.CONTAINER_BOARD_DECK, Token.TYPE_RESOURCE_CARD);
		this.setLocation(this.resourceCardDiscardPile, Token.CONTAINER_BOARD_DISCARD, Token.TYPE_RESOURCE_CARD);
		this.setLocation(this.objectiveCardDrawPile, Token.CONTAINER_BOARD_DECK, Token.TYPE_OBJECTIVE_CARD);
		this.setLocation(this.objectiveCardRiver, Token.CONTAINER_BOARD_RIVER, Token.TYPE_OBJECTIVE_CARD);
		this.setLocation(this.pigeonCardDrawPile, Token.CONTAINER_BOARD_DECK, Token.TYPE_PIGEON_CARD);
		this.setLocation(this.majorityCardSlot, Token.CONTAINER_BOARD_RIVER, Token.TYPE_MAJORITY_CARD);
		// Then put first players in order to the end and load player board in this order
		this.createPlayerBoards(players);
		// Build grid
		this.grid = new CellGrid(this.$element.querySelector('.tile-grid'), this.app, gridSize, gridModel);
		this.grid.build();// Available now
		// Declare all locations of the grid, same grid should serve several locations
		this.setLocation(this.grid, Token.CONTAINER_BOARD_GRID, Token.TYPE_RESTAURANT);
		this.setLocation(this.grid, Token.CONTAINER_BOARD_GRID, Token.TYPE_TERRACE);
		// All restaurant piles in box
		/** @type {RestaurantPile[]} */
		this.restaurantBox = [];
		const $restaurantBoxList = this.$element.querySelector(`.game-box .list-restaurant`);
		const $restaurantPanelList = this.$element.querySelector(`.game-box .list-restaurant-panel`);
		this.app.materials.restaurants.forEach((material, variant) => {
			const pile = new RestaurantPile(null, material, this.app.getSampleRestaurantToken(material, true), this);
			$restaurantBoxList.append(pile.getElement());
			$restaurantPanelList.append(pile.$panel);
			pile.bindEvents();
			this.restaurantBox[variant] = pile;
			this.setLocation(pile, Token.CONTAINER_BOX, Token.TYPE_RESTAURANT, null, variant);
		});
	}
	
	hideRestaurantDetails() {
		this.restaurantBox.forEach(pile => pile.fold());
	}
	
	showRestaurantDetails(variant) {
		this.hideRestaurantDetails();
		this.restaurantBox[variant].unfold();
	}
	
	setLocation(list, container, type, player, containerPosition) {
		// board-{container}-{type} or player-{playerId}-{container}-{type}
		// See AbstractGameToken.getLocation()
		const key = (player ? 'player-' + player.id : 'board') + '-' + container + '-' + type + (containerPosition !== undefined ? ('-' + containerPosition) : '');
		this.locations[key] = list;
	}
	
	/**
	 * @param {Player} player
	 * @return {PlayerBoard}
	 */
	getPlayerBoard(player) {
		return this.playerBoards[player.id];
	}
	
	/**
	 * @param {Array<Player>} players
	 */
	createPlayerBoards(players) {
		for( const player of players ) {
			const playerBoard = new PlayerBoard(player);
			this.playerBoards[player.id] = playerBoard;
			this.setLocation(playerBoard.resourceCardHand, Token.CONTAINER_PLAYER_HAND, Token.TYPE_RESOURCE_CARD, player);
			this.setLocation(playerBoard.objectiveCardHand, Token.CONTAINER_PLAYER_HAND, Token.TYPE_OBJECTIVE_CARD, player);
			this.setLocation(playerBoard.objectiveCardDiscard, Token.CONTAINER_PLAYER_DISCARD, Token.TYPE_OBJECTIVE_CARD, player);
			this.setLocation(playerBoard.pigeonCardHand, Token.CONTAINER_PLAYER_HAND, Token.TYPE_PIGEON_CARD, player);
			this.setLocation(playerBoard.pigeonCardDiscard, Token.CONTAINER_PLAYER_DISCARD, Token.TYPE_PIGEON_CARD, player);
			this.setLocation(playerBoard.terraceListCategories[0], Token.CONTAINER_PLAYER_BOARD, Token.TYPE_TERRACE, player, 1);
			this.setLocation(playerBoard.terraceListCategories[1], Token.CONTAINER_PLAYER_BOARD, Token.TYPE_TERRACE, player, 2);
			this.setLocation(playerBoard.terraceListCategories[2], Token.CONTAINER_PLAYER_BOARD, Token.TYPE_TERRACE, player, 3);
			this.setLocation(playerBoard.terraceListCategories[3], Token.CONTAINER_PLAYER_BOARD, Token.TYPE_TERRACE, player, 4);
			this.setLocation(playerBoard.pendingList, Token.CONTAINER_PLAYER_BOARD, Token.TYPE_OBJECTIVE_CARD, player);
			this.$playerBoardList.append(playerBoard.getElement());
		}
	}
	
	/**
	 * @param {AbstractGameToken} token
	 */
	addToken(token) {
		this.tokens[token.id()] = token;
		token.setTable(this);
	}
	
	getToken(id) {
		return this.tokens[id] || null;
	}
	
	resolveLocation(location) {
		if( !location || !this.locations[location] ) {
			return null;
		}
		return this.locations[location];
	}
	
	/**
	 * @param {AbstractGameToken} token
	 * @param {Object} from
	 * @returns {Set<any>}
	 */
	moveToken(token, from) {
		const locations = new Set();
		let fromLocation;
		if( from && (typeof from === 'object') ) {
			fromLocation = this.resolveLocation(from.location);
		} else {
			fromLocation = null;
		}
		let toLocation = this.resolveLocation(token.location());
		if( !toLocation ) {
			if( token.container() !== Token.CONTAINER_BOX ) {
				// Only Ignore tokens into the game box
				console.error(`Error about token ${token.debugLabel()} {container=${token.containerKey()}#${token.container()}, type=${token.type()}#${token.data.type}, player=${token.player ? token.player.id : 'NONE'}}`, error);
			}
			return locations;
		}
		// Move
		// Remove from previous location
		if( fromLocation ) {
			if( fromLocation === toLocation && from.position === token.position() ) {
				// Reject move to same place
				console.warn('moveToken - Reject move to same list and same position', token, from.location, from.position)
				return locations;
			}
			fromLocation.remove(token, from.position);
			locations.add(fromLocation);
		}
		// Add to next location
		toLocation.add(token);
		locations.add(toLocation);
		return locations;
	}
	
	getCellElement(cell) {
		return this.$terraceGrid.querySelector(`.x-${cell.x}.y-${cell.y}`);
	}
	
	getCellByCoordinates(x, y) {
		return this.cells[x + '-' + y];
	}
	
	addUpCoordinates(coord1, coord2) {
		return {x: coord1.x + coord2.x, y: coord1.y + coord2.y};
	}
	
	getPatternCells(mainCell, pattern) {
		const cells = [];
		for( const coordinates of pattern ) {
			const cellCoordinates = this.addUpCoordinates(mainCell, coordinates);
			const cell = this.getCellByCoordinates(cellCoordinates.x, cellCoordinates.y);
			if( !cell ) {
				throw new Error(`Cell with coordinates {x:${cellCoordinates.x}, y:${cellCoordinates.y}} not found`);
			}
			cells.push(cell);
		}
		return cells;
	}
	
	/**
	 * @param {AbstractGameToken} token
	 * @param {TileCell} mainCell
	 * @param {string} orientation
	 */
	putTokenToCell(token, mainCell, orientation) {
		token.setOrientation(orientation);
		const $cell = this.getCellElement(mainCell);
		
		$cell.append(token.getElement());
		token.assignCells(this.getPatternCells(mainCell, token.getPlacementPattern()))
	}
	
}
