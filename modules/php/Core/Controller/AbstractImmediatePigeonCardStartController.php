<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Core\Controller;

use \Bga\Games\DinnerInParis\Core\Debug\Dump;
use \Bga\Games\DinnerInParis\Entity\PigeonCard;
use \Bga\Games\DinnerInParis\Entity\Player;

abstract class AbstractImmediatePigeonCardStartController extends AbstractController {
	
	/** @var string|null */
	protected $action = null;
	
	public function run() {
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('%s() for player %s (pid=%s)', Dump::shortName($this), $player->getEntityLabel(), getmypid()));

		// Inputs
		/** @var PigeonCard $pigeonCard */
		$pigeonCard = $this->app->getToken($player->getTurnInfo(Player::FLAG_PLAYING_PIGEON_CARD));
		$this->prepareCard($player, $pigeonCard);
		
		$this->entityService->update($player);
		
		$this->app->notifyPlayerUpdate($player, '${player_name} played a pigeon card "${card_name}"', [
			'player_name' => $player->getLabel(),
			'card_name'   => $pigeonCard->getLabel(),
		]);
		
		//$this->logger->log(sprintf('AbstractImmediatePigeonCardStartController::run() - Go action "%s"', $this->action));
		
		// Next state
		$this->app->useStateAction($this->action);
	}
	
	abstract public function prepareCard(Player $player, PigeonCard $pigeonCard): void;
	
}
