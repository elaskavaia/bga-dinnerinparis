/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { GameTable } from "./GameTable.js";
import { stringService } from "../service/string.service.js";
import { Dialog } from "../component/Dialog.js";
import { Container } from "../component/Container.js";
import { TokenFactory } from "./token/TokenFactory.js";
import { Player } from "./model/Player.js";
import { ButtonStyle } from "./board/ButtonStyle.js";
import { RestaurantToken } from "./token/RestaurantToken.js";
import { PropertyToken } from "./token/PropertyToken.js";
import { Deferred } from "../event/Deferred.js";
import { PlayerBoard } from "./PlayerBoard.js";
import { PlayerPanel } from "./PlayerPanel.js";
import { assetService } from "../service/asset.service.js";

export class GameApp {
	
	constructor() {
		/** @type {GameTable} */
		this.table = null;
		this.ui = null;
		this.dojo = null;
		this.tokenFactory = null;
		this.materials = null;
		this.controllers = {};
		// All tokens by ID
		/** @type {Object.<int, AbstractGameToken>} */
		this.tokens = {};
		/** @type {Object.<int, PlayerPanel>} */
		this.players = {};
		this.playerPanels = {};
		this.currentPlayer = null;
		this.gameData = null;
		this.build = {
			version: "[AIV]{version}[/AIV]",
			date: "[AIV]{date}[/AIV]",
		};
	}
	
	initialize(ui, dojo) {
		this.ui = ui;
		this.dojo = dojo;
		this.pageOverlayViewport = new Container(document.querySelector('.page-overlay'));
		this.table = new GameTable(document.getElementById('GameTable'), this);
		this.author();
		assetService.provideAssets();
	}
	
	author() {
		try {
			console.info("%cDinner In Paris", "color: #f7954e; font-size: 80px; padding-top: 24px");
			console.info("%cA game by Funnyfox", "color: #865c88; font-size: 16px;");
			console.info("%cThis digital adaptation was developed by Florent HAZARD <contact@sowapps.com>", "color: #865c88; font-size: 14px;");
			// Original https://www.npmjs.com/package/webpack-auto-inject-version is unmaintained and not compatible with webpack 5
			// We used a fork webpack-auto-inject-version-next => https://github.com/teodoradima/webpack-auto-inject-version
			// Dot and any special character must be out of [AIV] tag
			console.info("%c[AIV]Compiled version is {version} at {date}[/AIV].", "color: #865c88; font-size: 14px; padding-bottom: 24px");
		} catch( e ) {
			console.warn('Error while displaying author info', e);
		}
	}
	
	showCellCoords() {
		Object.values(this.table.grid.cells).forEach(cell => {
			/** @type {TileCell} cell */
			cell.forceCoords();
		});
	}
	
	// debugTokens(label, tokens) {
	// 	/** @var {AbstractGameToken} token */
	// 	// console.info(label + ' => ' + tokens.map(token => token.debugLabel()).join(', '));
	// 	return label + ' => ' + tokens.map(token => token.debugLabel()).join(', ');
	// }
	//
	// debugResourceDiscardPile() {
	// 	return this.debugTokens('Discard pile', this.table.resourceCardDiscardPile.getTokens());
	// }
	//
	// debugPlayerResourceCards() {
	// 	const player = this.getActivePlayer();
	// 	return this.debugTokens(`Players's hand (${player.id}) `, this.table.getPlayerBoard(player).resourceCardHand.getTokens());
	// }
	
	getSampleRestaurantToken(material, withProperty) {
		const restaurantToken = new RestaurantToken({}, this.currentPlayer, material, this.table);
		if( withProperty ) {
			restaurantToken.assignProperty(this.getSamplePropertyToken(material));
		}
		return restaurantToken;
	}
	
	getSamplePropertyToken(restaurantMaterial) {
		return new PropertyToken({}, this.currentPlayer, restaurantMaterial, this.table);
	}
	
	/**
	 * @param action
	 * @param parameters
	 * @see https://en.doc.boardgamearena.com/Game_interface_logic:_yourgamename.js#this.ajaxcall.28url.2C_parameters.2C_obj_callback.2C_callback.2C_callback_error.2C_ajax_method.29
	 */
	realizeAction(action, parameters) {
		if( !parameters ) {
			parameters = {};
		}
		parameters = Object.fromEntries(Object.entries(parameters).map(([key, value]) => [key, this.formatAjaxValue(value)]));
		if( !('lock' in parameters) ) {
			parameters.lock = true;
		}
		return new Promise((resolve, reject) => {
			try {
				this.ui.ajaxcall(`/dinnerinparis/dinnerinparis/${action}.html`, parameters, this, resolve, error => {
					if( error ) {
						console.error('Getting error running ajax', error, action, parameters);
						reject(error);
					}
				});
			} catch( exception ) {
				console.error('Getting exception running ajax', exception, action, parameters);
				reject(exception);
			}
		});
	}
	
	formatAjaxValue(value) {
		const type = typeof value;
		if( type === 'object' ) {
			value = JSON.stringify(value);
		}
		return value;
	}
	
	/**
	 * @param {string} action Action string or buttonId
	 * @param {string} label
	 * @param {string|null} style
	 * @param {boolean|null} blinking
	 * @returns {Promise<unknown>}
	 * @see https://en.doc.boardgamearena.com/Game_interface_logic:_yourgamename.js#BGA_Buttons
	 */
	addActionButton(action, label, style = null, blinking = null) {
		const buttonId = action.startsWith('Button') ? action : ('Button' + stringService.capitalize(action));
		const clickDeferred = new Deferred();
		this.ui.addActionButton(buttonId, label, event => {
			clickDeferred.resolve();
		}, null, !!blinking, style || ButtonStyle.PRIMARY);
		if( style === ButtonStyle.DISABLED ) {
			this.disableActionButton(buttonId);
		}
		return clickDeferred.promise();
	}
	
	getActionButton(buttonId) {
		const $button = document.querySelector('#' + buttonId);
		if( !$button ) {
			throw new Error(`Button with ID "${buttonId}" not found in DOM`);
		}
		return $button;
	}
	
	enableActionButton(buttonId, style) {
		const $button = this.getActionButton(buttonId);
		$button.disabled = false;
		$button.classList.remove('disabled');
		style = style || ButtonStyle.PRIMARY;
		if( style === ButtonStyle.PRIMARY ) {
			$button.classList.add('bgabutton_blue');
		} else {
			throw new Error('Unknown style for enableActionButton');
		}
	}
	
	disableActionButton(buttonId) {
		const $button = this.getActionButton(buttonId);
		$button.disabled = true;
		$button.classList.remove('bgabutton_blue');
		$button.classList.add('disabled');
	}
	
	formatMaterials(materials) {
		// Add variant property to all materials
		Object.entries(materials).forEach(([type, typeMaterials]) => {
			Object.entries(typeMaterials).forEach(([variant, material]) => {
				material.type = type;
				material.variant = parseInt(variant);
			});
		});
		return materials;
	}
	
	load(gameData) {
		this.materials = this.formatMaterials(gameData.materials);
		this.materials.terraces = {};// No variant, no property for terraces
		
		// Keep natural order but place current player as first, if current is the 3rd : [3, 4, 1, 2]
		this.players = {};
		const orderedPlayers = [];
		const firstPlayers = [];
		let placedCurrent = false;
		const currentPlayerId = this.ui.player_id + "";
		for( const [playerId, playerData] of Object.entries(gameData.players) ) {
			const player = new Player(playerData);
			this.players[playerId] = player;
			if( playerId === currentPlayerId ) {
				placedCurrent = true;
			}
			if( placedCurrent ) {
				orderedPlayers.push(player);
			} else {
				firstPlayers.push(player);
			}
		}
		this.currentPlayer = this.getPlayer(this.ui.player_id);
		
		const players = orderedPlayers.concat(firstPlayers);
		// Build table
		// Require current player
		this.table.build(players, gameData.gridSize, gameData.gridModel);
		
		// Players' panel, require player boards
		this.createPlayerPanels(gameData);
		
		// Build tokens
		this.tokenFactory = new TokenFactory(this.table, this.players, this.materials);
		Object.entries(gameData.tokens).forEach(([, data]) => {
			const token = this.tokenFactory.create(data);
			if( token ) {
				// All tokens are associated to table
				this.table.addToken(token);
			}// Else ignore unknown tokens
		});
		
		// Move all tokens using TableLocation
		Object.values(this.table.tokens)
			.forEach(token => this.table.moveToken(token));
		
		// Build players' board
		Object.values(this.table.playerBoards)
			.forEach(playerBoard => playerBoard.build());
		
		this.table.refresh();
	}
	
	/**
	 * @param {Player} player
	 * @return {PlayerPanel}
	 */
	getPlayerPanels(player) {
		return this.playerPanels[player.id];
	}
	
	getScoreCounter(player) {
		// Not loading during setup, so could not pass object when initializing UI
		return this.ui.scoreCtrl[player.id];
	}
	
	createPlayerPanels(gameData) {
		this.playerBoards = {};
		for( const player of Object.values(this.players) ) {
			this.playerPanels[player.id] = new PlayerPanel(gameData, player, this.table.getPlayerBoard(player), this);
		}
	}
	
	setup(gameData) {
		// Setup game board
		this.load(gameData);
		this.gameData = gameData;
		
		if( gameData.game ) {
			this.onGameUpdate(gameData.game);
		}
		
		this.setupUserInterface();
		
		// Setup game notifications to handle
		this.setupNotifications();
		
		// Test JS translations
		// console.log('TEST - TRANSLATIONS - MODULES/JS', _('This is a test translation string - MODULES/JS'));
	}
	
	setupUserInterface() {
		// Supports zoom
		let zoomSupported = false;
		try {
			zoomSupported = CSS.supports("zoom: 2");
		} catch( e ) {
			console.warn("Error checking support (normal on old browser)", e);
		}
		const $body = document.querySelector(".responsive-layer");
		$body.classList.add(zoomSupported ? "css-zoom-ok" : "css-zoom-ns");
	}
	
	setTurnTitle(title) {
		this.gameData.gamestate.descriptionmyturn = title;
		this.ui.updatePageTitle();
	}
	
	onGameUpdate(data) {
		if( !data || !data.state ) {
			return;
		}
		if( data.state === 'ending' ) {
			document.querySelector('.page-wrapper > .system-alert').hidden = false;
		}
	}
	
	/**
	 * In this method, you associate each of your game notifications with your local method to handle it.
	 * Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in your dinnerinparis.game.php file.
	 */
	setupNotifications() {
		
		this.subscribeNotification('tokensUpdate', notification => {
			console.log('tokensUpdate - Got event tokensUpdate with data', notification);
			try {
				const data = notification.args;
				// let affectedList = [];
				const locations = new Set();
				data.items.forEach(tokenData => {
					this.updateToken(tokenData).forEach(locations.add, locations);
				});
				// console.log('tokensUpdate - MOVED - locations', locations);
				locations.forEach(location => location.refresh());
			} catch( error ) {
				console.error('Error processing "tokensUpdate" notification', error)
			}
		});
		// Server requested to client to ignore generic notification for active player through exclude_player_id, else exclude_player_id is undefined
		// https://en.doc.boardgamearena.com/Main_game_logic:_yourgamename.game.php#Excluding_some_players
		// https://en.doc.boardgamearena.com/Game_interface_logic:_yourgamename.js#Ignoring_notifications
		this.ignoreNotification('tokensUpdate', notification => notification.args.exclude_player_id === this.currentPlayer.id)
		
		this.subscribeNotification('playerUpdate', notification => {
			try {
				const data = notification.args.item;
				const player = this.getPlayer(data.id);
				player.setData(data);
			} catch( error ) {
				console.error('Error processing "playerUpdate" notification', error)
			}
		});
		
		this.subscribeNotification('gameUpdate', notification => {
			try {
				const data = notification.args.item;
				this.onGameUpdate(data);
			} catch( error ) {
				console.error('Error processing "gameUpdate" notification', error)
			}
		});
	}
	
	updateToken(data) {
		const token = this.table.tokens[data.id];
		try {
			// Keep previous state
			const fromTableLocation = token.tableLocation();
			// Set data
			this.tokenFactory.update(token, data);
			// Move token
			return this.table.moveToken(token, fromTableLocation);
		} catch( error ) {
			console.error(`Error updating token ${token.debugLabel()}`, error);
		}
		return new Set;
	}
	
	/**
	 * @param event
	 * @param callback
	 * @param {Boolean|Number|undefined} synchronous
	 */
	subscribeNotification(event, callback, synchronous = false) {
		this.dojo.subscribe(event, callback);
		if( synchronous ) {
			this.ui.notifqueue.setSynchronous(event, synchronous === true ? null : synchronous);
		}
	}
	
	ignoreNotification(event, filterCallback) {
		this.ui.notifqueue.setIgnoreNotificationCheck(event, filterCallback);
	}
	
	/**
	 * @returns {PlayerBoard}
	 */
	getCurrentAndActivePlayerBoard() {
		return this.isCurrentPlayerActive() ? this.getCurrentPlayerBoard() : null;
	}
	
	/**
	 * @returns {PlayerBoard}
	 */
	getCurrentPlayerBoard() {
		return this.table.getPlayerBoard(this.currentPlayer);
	}
	
	/**
	 * @returns {Array<Player>}
	 */
	getActivePlayers() {
		return this.ui.getActivePlayers().map(playerId => this.getPlayer(playerId));
	}
	
	/**
	 * @returns {Player}
	 */
	getActivePlayer() {
		return this.getActivePlayers()[0];
	}
	
	/**
	 * @returns {boolean}
	 */
	isCurrentPlayerActive() {
		return this.ui.isCurrentPlayerActive();
	}
	
	/**
	 * @param id
	 * @returns {Player}
	 */
	getPlayer(id) {
		if( this.players[id] ) {
			return this.players[id];
		}
		if( this.isReadOnly() ) {
			return Player.createSpectator(id);
		}
		throw new Error(`Unknown case for player #${id}, not a known table player and not a spectator`);
	}
	
	isReadOnly() {
		return this.ui.isSpectator || typeof g_replayFrom != 'undefined' || g_archive_mode;
	}
	
	/**
	 * @param name
	 * @returns {Promise<AbstractController>}
	 */
	async getController(name) {
		if( !(name in this.controllers) ) {
			const className = name.split('.').map(part => stringService.capitalize(part)).join('') + 'Controller';
			try {
				const {default: ControllerClass} = await import(
					// https://webpack.js.org/api/module-methods/#magic-comments
					// Eager mode: the file contains all controllers and load it without any additional http request
					/* webpackInclude: /\.js$/ */
					/* webpackExclude: /AbstractController\.js$/ */
					/* webpackMode: "eager" */
					/* webpackPrefetch: true */
					/* webpackPreload: true */
					`./controller/${className}.js`);
				if( !(name in this.controllers) ) {
					// First sync only, may be imported multiple, it happens with initialize/start
					// We want to use only one controller for all need about one state, regardless of how many times it's used
					this.controllers[name] = new ControllerClass(this);
				}
			} catch( error ) {
				if( error.code !== 'MODULE_NOT_FOUND' ) {
					console.error('Error importing controller module', className, error);
				}
				this.controllers[name] = null;
			}
		}
		return this.controllers[name];
	}
	
	formatArgs(inputArgs) {
		const args = {...inputArgs};
		const privateArgs = args._private;
		if( privateArgs ) {
			delete args._private;
			// Merge private args and public args
			Object.assign(args, privateArgs);
		}
		
		return args;
	}
	
	async startState(stateName, state) {
		const args = this.formatArgs(state.args);
		const controller = await this.getController(stateName);
		if( !controller ) {
			return;
		}
		
		if( this.isCurrentPlayerActive() ) {
			// console.log(`Current player active for state "${stateName}"`);
			if( args.stateTitle ) {
				this.setTurnTitle(args.stateTitle);
			}
			controller.start(args);
			controller.trigger('started');
		} else {
			controller.spectate(args);
		}
	}
	
	async endState(stateName) {
		const controller = await this.getController(stateName);
		if( !controller ) {
			return;
		}
		controller.trigger('end');
		controller.end();
	}
	
	async initializeState(stateName, args) {
		const controller = await this.getController(stateName)
		if( !controller ) {
			return;
		}
		controller.initialize(args);
	}
	
	/**
	 * @param config
	 * @param showNow
	 * @returns {Dialog}
	 */
	createDialog(config, showNow) {
		const overlay = this.pageOverlayViewport;
		const dialog = Dialog.make(config, overlay);
		if( showNow ) {
			dialog.show();
		}
		return dialog;
	}
	
	openMockupDialog() {
		const dialog = Dialog.make({
			title: 'Mockup popin',
			cancel: 'Cancel',
			close: 'Close',
			confirm: 'Save',
			body: `
<p>
	Lorem ipsum dolor sit amet, consectetur adipiscing elit.
	Maecenas id neque ante. Nam nec tortor bibendum, fermentum felis vel, fringilla lectus. Duis convallis, leo vel pulvinar consequat, turpis est imperdiet arcu, non dictum lorem justo vitae ipsum. Mauris ante tellus, convallis et viverra nec, porttitor id dui. Duis ultrices posuere libero, sed scelerisque turpis rhoncus nec. Cras rutrum dolor sed dolor ultricies, lobortis facilisis dui ornare.
</p>
			`,
		}, this.pageOverlayViewport);
		dialog.show();
	}
	
}
