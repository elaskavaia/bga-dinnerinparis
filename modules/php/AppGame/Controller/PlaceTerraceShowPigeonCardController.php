<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractController;
use AppGame\Core\Controller\ArgumentBag;
use AppGame\Core\Exception\UserException;
use AppGame\Entity\PigeonCard;
use AppGame\Entity\Player;

class PlaceTerraceShowPigeonCardController extends AbstractController {
	
	public function run() {
		$this->game->checkAction('continue');
		
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('PlaceTerraceShowPigeonCardController() for player %s (pid=%s)', $player->getEntityLabel(), getmypid()));
		
		$pigeonCard = $this->getPigeonCard($player);
		$applyImmediate = $player->getTurnInfo('applyImmediate');
		
		$action = null;
		if( $applyImmediate && $pigeonCard->isImmediate() ) {
			$action = 'pigeon' . $pigeonCard->getKey();
		}
		
		$player->setTurnInfo(Player::FLAG_PLAYING_PIGEON_CARD, $pigeonCard->getId());
		$player->removeTurnInfo('applyImmediate');
		$player->removeTurnInfo('showPigeonCard');
		
		// Save
		$this->entityService->update($player);
		
		//$this->logger->log(sprintf('PlaceTerraceShowPigeonCardController() leads to action "%s"', $action));
		
		// Next state
		$this->app->useStateAction($action ?? 'continue');
	}
	
	public function generateArguments(ArgumentBag $arguments): void {
		$player = $this->app->getActivePlayer();
		$pigeonCard = $this->getPigeonCard($player);
		
		$arguments->setPlayerArgumentList($player, [
			'card'   => $pigeonCard->getId(),
			'legend' => $pigeonCard->getDescription(),
			'play'   => $pigeonCard->isImmediate(),
		]);
	}
	
	public function getPigeonCard(Player $player): PigeonCard {
		/** @var PigeonCard $pigeonCard */
		$pigeonCard = $this->app->getToken($player->getTurnInfo('showPigeonCard'));
		if( !$pigeonCard ) {
			throw new UserException('Unknown pigeon card');
		}
		if( $pigeonCard->getPlayerId() !== $player->getId() ) {
			throw new UserException('Wrong player owner');
		}
		
		return $pigeonCard;
	}
	
	
}
