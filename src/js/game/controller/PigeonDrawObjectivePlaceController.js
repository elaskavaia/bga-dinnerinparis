/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

import ActionCompleteObjectiveDrawController from "./ActionCompleteObjectiveDrawController.js";

export default class PigeonDrawObjectivePlaceController extends ActionCompleteObjectiveDrawController {
	
	getPlaceAction() {
		return 'placePigeonDrawObjective';
	}
	
}
