<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Bga\Games\DinnerInParis\Core\Controller;

use \Bga\Games\DinnerInParis\Entity\PigeonCard;
use \Bga\Games\DinnerInParis\Entity\Player;
use Bga\Games\DinnerInParis\Game;

abstract class AbstractImmediatePigeonCardEndController extends AbstractController {

	public function __construct(Game $table) {
		parent::__construct($table);
	}
	
	public function run() {
		$player = $this->app->getActivePlayer();
		//$this->logger->log(sprintf('%s() for player %s (pid=%s)', Dump::shortName($this), $player->getEntityLabel(), getmypid()));
		
		// Inputs
		/** @var PigeonCard $pigeonCard */
		$pigeonCardId = $player->getTurnInfo(Player::FLAG_PLAYING_PIGEON_CARD);
		
		$movedTokens = null;
		if( !$pigeonCardId ) {
			// TODO Should we remove this case after bug is fixed ? (#692)
			// There is no playing pigeon card (another has overridden it and ended)
			// We want to continue the game, however user will keep it in hand (as unplayable pigeon card)
			//$this->logger->log('No playing pigeon card to discard');
			$this->app->sendSystemMessage("No playing pigeon card to discard, please report this issue.");
			
		} else {
			$pigeonCard = $this->app->getToken($pigeonCardId);
			//$this->logger->log(sprintf('%s() with pigeon card %s', Dump::shortName($this), $pigeonCard->getEntityLabel()));
			
			// Reset card turn info
			$player->removeTurnInfo(Player::FLAG_PLAYING_PIGEON_CARD);
			$this->finalizeCard($player, $pigeonCard);
			
			// Discard pigeon card
			$playerPigeonCardDiscard = $this->table->getPigeonCardPlayerDiscard($player);
			$playerPigeonCardDiscard->add($pigeonCard);
			
			$movedTokens = $playerPigeonCardDiscard->getTokens();
			//$this->logger->log(sprintf('%s() with discard %s', Dump::shortName($this), Dump::tokenDetailsList($movedTokens)));
		}
		
		// Save
		$this->entityService->startBatching();
		$this->entityService->update($player);
		if( $movedTokens ) {
			$this->entityService->updateList($movedTokens);
		}
		$this->entityService->applyBatching();
		
		// Notify players
		if( $movedTokens ) {
			$this->app->notifyTokenUpdate($movedTokens, $this->getNotificationMessage(), [
				'player_name' => $player->getLabel(),
			]);
		}
		
		//$this->logger->log(sprintf('%s() ends and continue game, player turn data=%s', Dump::shortName($this), json_encode($player->getTurnData())));
		
		$action = null;
		if( $player->getTurnInfo('showPigeonCard') ) {
			// We ended the current pigeon card, so if it leads to another card, we show up the new one
			$action = 'showPigeonCard';
		}
		
		// Next state
		$this->app->useStateAction($action ?? 'continue');
	}
	
	abstract protected function getNotificationMessage(): string;
	
	abstract protected function finalizeCard(Player $player, PigeonCard $pigeonCard): void;
	
}
