/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { AbstractTokenList } from "./AbstractTokenList.js";
import { TileCell } from "../../board/TileCell.js";
import { domService } from "../../../service/dom.service.js";
import { ORIENTATION } from "../../constants.js";
import { RestaurantToken } from "../RestaurantToken.js";
import { Deferred } from "../../../event/Deferred.js";

/**
 * Ordered stacked card list
 *
 * @property {Array<TileCell>} cells
 */
export class CellGrid extends AbstractTokenList {
	
	/**
	 * @param {Element} $element
	 * @param {GameApp} app
	 * @param {Number} size
	 * @param {Number} model
	 */
	constructor($element, app, size, model) {
		super($element);
		
		this.app = app;
		this.rowSize = size;
		this.model = model;
		this.cells = [];
		this.materials = {
			'tile-pigeon': {type: 'tile', allowTerrace: true, allowRestaurant: false},
			'tile-bush': {type: 'tile', allowTerrace: false, allowRestaurant: false},// Bush is out but not marked
			'tile-out': {type: 'tile', allowTerrace: false, allowRestaurant: false},// Out is really out of board game
			'tile-rejected': {type: 'tile', allowTerrace: false, allowRestaurant: false},// Reject is on grid but out of game, marked
			'tile-musician': {type: 'tile', allowTerrace: false, allowRestaurant: false},
			'tile-fountain': {type: 'tile', allowTerrace: false, allowRestaurant: false},
			'tile-lamp': {type: 'tile', allowTerrace: false, allowRestaurant: false},
			'tile-flowerbed': {type: 'tile', allowTerrace: false, allowRestaurant: false},
			'tile-restaurant': {type: 'tile', allowTerrace: true, allowRestaurant: true},
			'tile-none': {type: 'tile', allowTerrace: true, allowRestaurant: false},
		};
	}
	
	getCellRestaurantOrientation(cell) {
		if( cell.x === this.getFirstIndex() ) {
			return ORIENTATION.EAST;
		}
		if( cell.y === this.getFirstIndex() ) {
			return ORIENTATION.SOUTH;
		}
		if( cell.x === this.getLastIndex() ) {
			return ORIENTATION.WEST;
		}
		if( cell.y === this.getLastIndex() ) {
			return ORIENTATION.NORTH;
		}
		throw "Cell allowed for restaurant placement but not in border lines";
	}
	
	/**
	 * @param {AbstractGameToken} token
	 * @param {TileCell} cell
	 */
	getEffectiveCell(token, cell) {
		// Only handle 1D tokens
		// Calculate effective cell to place token on grid without getting out
		const horizontal = cell.y === this.getFirstIndex() || cell.y === this.getLastIndex();
		const tokenSize = token.size();
		// console.log('getEffectiveCell - horizontal', horizontal, 'tokenSize', tokenSize);
		for( let i = 0; i < tokenSize; i++ ) {
			const testCell = this.getCell(cell.x + (horizontal ? i : 0), cell.y + (horizontal ? 0 : i));
			// Having a token or out of grid
			if( testCell.token || !testCell.material.allowRestaurant ) {
				// Add negative delta
				return this.getCell(cell.x + (horizontal ? (i - tokenSize) : 0), cell.y + (horizontal ? 0 : (i - tokenSize)));
			}
		}
		return cell;
	}
	
	setRestaurantPlacementTo(cell) {
		this.restaurantPlacementClone.setOrientation(this.getCellRestaurantOrientation(cell));
		cell.getElement().append(this.restaurantPlacementClone.getElement());
	}
	
	unselectRestaurantPlacement() {
		domService.detach(this.restaurantPlacementClone.getElement());
		this.restaurantPlacementSelectedCell = null;
	}
	
	startRestaurantPlacement(restaurant, availableLocations) {
		this.restaurantPlacementDeferred = new Deferred();
		if( !this.restaurantPlacementCellInListener ) {
			this.restaurantPlacementCellInListener = event => {
				if( this.restaurantPlacementSelectedCell ) {
					return;// Ignore if a cell was selected
				}
				const cell = event.currentTarget.cell;
				const effectiveCell = this.getEffectiveCell(this.restaurantPlacementClone, cell);
				this.setRestaurantPlacementTo(effectiveCell);
			}
			this.restaurantPlacementCellOutListener = () => {
				if( this.restaurantPlacementSelectedCell ) {
					return;// Ignore if a cell was selected
				}
				this.unselectRestaurantPlacement();
			}
			this.restaurantPlacementCellClickListener = event => {
				const cell = event.currentTarget.cell;
				const effectiveCell = this.getEffectiveCell(this.restaurantPlacementClone, cell);
				// If same cell as previous one, we do nothing
				this.restaurantPlacementSelectedCell = this.restaurantPlacementSelectedCell === effectiveCell ? null : effectiveCell;
				if( this.restaurantPlacementSelectedCell ) {
					this.setRestaurantPlacementTo(this.restaurantPlacementSelectedCell);
					// Only if the cell changed
					this.restaurantPlacementDeferred.resolve(this.restaurantPlacementSelectedCell);
				}
			}
		}
		this.restaurantPlacementClone = new RestaurantToken(restaurant.data, restaurant.player, restaurant.material);
		this.restaurantPlacementClone.getElement().classList.add('clone');
		this.restaurantPlacementClone.assignProperty(this.app.getSamplePropertyToken(restaurant.material));
		this.restaurantPlacementCells = availableLocations.map(location => this.getCell(location[0], location[1]));
		this.restaurantPlacementCells.forEach(cell => {
			cell.setSelectable();
			const $cell = cell.getElement();
			$cell.addEventListener('mouseenter', this.restaurantPlacementCellInListener);
			$cell.addEventListener('mouseleave', this.restaurantPlacementCellOutListener);
			$cell.addEventListener('click', this.restaurantPlacementCellClickListener);
		});
		
		return this.restaurantPlacementDeferred.promise();
	}
	
	stopRestaurantPlacement() {
		if( !this.restaurantPlacementDeferred ) {
			return false;
		}
		// Stop all events
		this.restaurantPlacementClone.getElement().remove();
		this.restaurantPlacementDeferred.reject();
		this.restaurantPlacementCells.forEach(cell => {
			cell.notSelectable();
			const $cell = cell.getElement();
			$cell.removeEventListener('mouseenter', this.restaurantPlacementCellInListener);
			$cell.removeEventListener('mouseleave', this.restaurantPlacementCellOutListener);
			$cell.removeEventListener('click', this.restaurantPlacementCellClickListener);
		});
		// Erase all data
		this.restaurantPlacementDeferred = null;
		this.restaurantPlacementCellInListener = null;
		this.restaurantPlacementCellOutListener = null;
		this.restaurantPlacementCellClickListener = null;
		this.restaurantPlacementClone = null;
		this.restaurantPlacementCells = null;
		this.restaurantPlacementSelectedCell = null;
		
		return true;
	}
	
	/**
	 * @param {Number|Array} x
	 * @param {Number|null} y
	 * @returns {TileCell|null}
	 */
	getCell(x, y = null) {
		if( Array.isArray(x) ) {
			y = x[1];
			x = x[0];
		}
		if( this.cells[x + '-' + y] === undefined ) {
			throw new Error(`Undefined cell (${x}, ${y})`);
		}
		return this.cells[x + '-' + y] || null;
	}
	
	getMaterial(materialKey) {
		if( !this.materials[materialKey] ) {
			throw new Error(`Unable to find material with key "${materialKey}"`);
		}
		const material = this.materials[materialKey];
		material.key = materialKey;
		return material;
	}
	
	/**
	 * Should build cells once only
	 */
	build() {
		// Empty grid
		this.$element.innerHTML = '';
		const modelGrid = this.getModelGrid();
		const xMax = this.rowSize, yMax = this.rowSize;
		for( let y = 0; y < yMax; y++ ) {
			for( let x = 0; x < xMax; x++ ) {
				const cellTile = modelGrid[x + '-' + y];// May be undefined
				const cell = new TileCell(x, y, this.getMaterial(cellTile ? cellTile.material : 'tile-none'), this);
				const $cell = cell.getElement();
				// Each previous cell must be over the next ones
				const zIndexBonus = cell.material.key === 'tile-restaurant' ? 2 : 0;
				$cell.style.zIndex = (xMax - x) + (yMax - y) + zIndexBonus;
				this.$element.append($cell);
				this.cells[x + '-' + y] = cell;
			}
		}
	}
	
	add(token) {
		super.add(token);
		
		const point = this.parsePosition(token.position());
		try {
			const cell = this.getCell(point);
			cell.assignToken(token);
		} catch( error ) {
			console.error('Unable to add token due to', error, 'position', token.position(), 'point', point, 'id', token.id(), 'token', token);
		}
	}
	
	parsePosition(position) {
		return [position % this.rowSize, parseInt(position / this.rowSize)];
	}
	
	refresh() {
		Object.entries(this.cells).forEach(([, cell]) => cell.refresh());
	}
	
	getFirstIndex() {
		return 4 - this.model;
	}
	
	getLastIndex() {
		return (this.rowSize - 1 - 4) + this.model;
	}
	
	getModelGrid() {
		// Temp generate fake 4p grid
		const firstIndex = this.getFirstIndex();
		const lastIndex = this.getLastIndex();
		const grid = {};
		for( let i = firstIndex + 1; i <= lastIndex - 1; i++ ) {
			for( let j of [firstIndex, lastIndex] ) {
				grid[i + '-' + j] = {material: 'tile-restaurant'};
				grid[j + '-' + i] = {material: 'tile-restaurant'};
			}
		}
		for( let quarterX of [1, 18] ) {
			for( let quarterY of [1, 18] ) {
				for( let coordinates of [[3, 3], [7, 3], [3, 7], [6, 7], [8, 8]] ) {
					const x = quarterX > 1 ? quarterX - (coordinates[0] - 1) : quarterX + (coordinates[0] - 1);
					const y = quarterY > 1 ? quarterY - (coordinates[1] - 1) : quarterY + (coordinates[1] - 1);
					grid[x + '-' + y] = {material: 'tile-pigeon'};
				}
				for( let coordinates of [[8, 9], [9, 8], [9, 9]] ) {
					const x = quarterX > 1 ? quarterX - (coordinates[0] - 1) : quarterX + (coordinates[0] - 1);
					const y = quarterY > 1 ? quarterY - (coordinates[1] - 1) : quarterY + (coordinates[1] - 1);
					grid[x + '-' + y] = {material: 'tile-fountain'};
				}
			}
		}
		// Add external out tiles
		for( let depth = 0; depth < firstIndex; depth++ ) {
			const start = depth;
			const end = this.rowSize - depth - 1;
			for( let i = start; i <= end; i++ ) {
				grid[i + '-' + start] = {material: 'tile-rejected'};// First row
				grid[i + '-' + end] = {material: 'tile-rejected'};// Last row
				grid[start + '-' + i] = {material: 'tile-rejected'};// First column
				grid[end + '-' + i] = {material: 'tile-rejected'};// Last column
			}
		}
		// Add corner out tiles
		for( let x of [firstIndex, lastIndex] ) {
			for( let y of [firstIndex, lastIndex] ) {
				grid[x + '-' + y] = {material: 'tile-rejected'};
			}
		}
		// Add corner bush tiles (replace previous)
		for( let x of [0, 19] ) {
			for( let y of [0, 19] ) {
				grid[x + '-' + y] = {material: 'tile-bush'};
			}
		}
		for( let coordinates of [[15, 7], [4, 12]] ) {
			grid[coordinates[0] + '-' + coordinates[1]] = {material: 'tile-lamp'};
		}
		for( let coordinates of [[4, 5], [5, 5], [14, 14], [15, 14]] ) {
			grid[coordinates[0] + '-' + coordinates[1]] = {material: 'tile-musician'};
		}
		for( let coordinates of [[9, 4], [10, 4], [11, 4], [8, 15], [9, 15], [10, 15]] ) {
			grid[coordinates[0] + '-' + coordinates[1]] = {material: 'tile-flowerbed'};
		}
		
		return grid;
	}
}
