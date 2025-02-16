/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import { GameApp } from "./game/GameApp.js";

export const app = new GameApp();
GameApp.mainApp = app;
export default app;
window.app = app;

