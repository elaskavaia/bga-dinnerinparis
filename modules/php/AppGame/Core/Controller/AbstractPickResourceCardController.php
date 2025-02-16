<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Core\Controller;

use AppGame\Entity\Player;

abstract class AbstractPickResourceCardController extends AbstractController {
	
	protected function allowCancel(Player $player): bool {
		return $player->hasTurnFlag('action_0') && !$player->hasActionFlag(Player::FLAG_RESUME_PIGEON_CARD_DRAW_RESOURCE);
	}
	
}
