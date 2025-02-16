<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Action;

use AppGame\Game\GameTable;
//use AppGame\Logger\AbstractLogger;
use AppGame\Service\BoardGameApp;
use AppGame\Service\EntityService;
use DinnerInParis;

trait ControllerTrait {
	
	/** @var BoardGameApp */
	protected $app;
	
	/** @var GameTable */
	protected $table;
	
	/** @var DinnerInParis */
	protected $game;
	
	/** @var EntityService */
	protected $entityService;
	
	/** @var AbstractLogger */
	//protected $logger;
	
}
