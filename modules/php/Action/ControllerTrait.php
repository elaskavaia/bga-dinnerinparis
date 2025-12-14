<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Action;

use \Bga\Games\DinnerInParis\Game\GameTable;
//use \Bga\Games\DinnerInParis\Logger\AbstractLogger;
use \Bga\Games\DinnerInParis\Service\BoardGameApp;
use \Bga\Games\DinnerInParis\Service\EntityService;
use Bga\Games\DinnerInParis\Game;

trait ControllerTrait {
	
	/** @var BoardGameApp */
	protected $app;
	
	/** @var GameTable */
	protected $table;
	
	/** @var Game */
	protected $game;
	
	/** @var EntityService */
	protected $entityService;
	
	/** @var AbstractLogger */
	//protected $logger;
	
}
