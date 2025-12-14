<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Core\Controller\ArgumentBag;

class FinalizationSummaryController extends AbstractController {
	
	public function run() {
		//$this->logger->log(sprintf('FinalizationSummaryController()'));
		
		$this->app->useStateAction('endGame');
	}
	
	public function generateArguments(ArgumentBag $arguments): void {
		$scores = [];
		$scoreStats = ['score_restaurant', 'score_terrace', 'score_objective', 'score_majority', 'score_majority_1', 'score_majority_2', 'score_majority_3'];
		foreach( $this->app->getPlayers() as $player ) {
			$playerScore = [
				'label' => $player->getLabel(),
				'total' => $player->getScore(),
			];
			foreach( $scoreStats as $stat ) {
				$playerScore[$stat] = $this->app->getStat($stat, $player);
			}
			$scores[$player->getId()] = $playerScore;
		}
		$arguments->setPublicArgument('scores', $scores);
	}
	
}
