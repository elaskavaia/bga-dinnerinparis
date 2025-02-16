/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

/**
 * dinnerinparis.js
 *
 * DinnerInParis user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 */

function isBrowserSupported() {
	try {
		// Supporting ES class & static ?
		eval('"use strict"; class foo { static foo = {test: "compatibility"}}');
	} catch( e ) {
		return false;
	}
	return true;
}

if( !isBrowserSupported() ) {
	console.error('This browser does not support this game');
	alert('This browser does not support this game, please consider updating or using a compatible browser, as Chrome or Firefox.');
}

define([
		"dojo", "dojo/_base/declare",
		// BGA won't resolve translations in modules/dist, only in modules
		g_gamethemeurl + "modules/js/main.bundle.js",
		"ebg/core/gamegui",
		"ebg/counter"
	],
	function (dojo, declare, app) {
		// console.log('app', app, 'dojo', dojo, 'declare', declare);
		return declare("bgagame.dinnerinparis", ebg.core.gamegui, {
			constructor: function () {
				// console.log('dinnerinparis constructor, parent is', ebg.core.gamegui, 'dojo', dojo, this.slideToObject);
				app.initialize(this, dojo);
			},
			
			/**
			 * This method must set up the game user interface according to current game situation specified
			 * in parameters.
			 *
			 * The method is called each time the game interface is displayed to a player, ie:
			 * _ when the game starts
			 * _ when a player refreshes the game page (F5)
			 *
			 * "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
			 *
			 * @param gameData
			 */
			setup: function (gameData) {
				try {
					// console.log('Setup game', gameData);
					app.setup(gameData);
				} catch( error ) {
					console.error('Error while running setup', error);
				}
			},
			
			/**
			 * This method is called each time we are entering into a new game state.
			 * You can use this method to perform some user interface changes at this moment.
			 * @param stateName
			 * @param args
			 */
			onEnteringState: function (stateName, args) {
				// console.log('onEnteringState', stateName, args);
				app.startState(stateName, args);
			},
			
			/**
			 * this method is called each time we are leaving a game state.
			 * You can use this method to perform some user interface changes at this moment.
			 * @param stateName
			 */
			onLeavingState: function (stateName) {
				// console.log('onLeavingState', stateName);
				app.endState(stateName);
			},
			
			/**
			 * With this method you can manage "action buttons" that are displayed in the action status bar (ie: the HTML links in the status bar).
			 * @param stateName
			 * @param args
			 */
			onUpdateActionButtons: function (stateName, args) {
				app.initializeState(stateName, args);
			},
			
		});
	});
