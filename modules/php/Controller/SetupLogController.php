<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Controller;

use \Bga\Games\DinnerInParis\Core\Controller\AbstractController;
use \Bga\Games\DinnerInParis\Logger\SetupLogger;

class SetupLogController extends AbstractController {
	
	protected function initialize() {
		parent::initialize();
		
		//$this->logger = SetupLogger::get();
	}
	
	public function run() {
		//echo $this->logger->getLogs();
	}
	
}
