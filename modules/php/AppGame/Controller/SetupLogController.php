<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Controller;

use AppGame\Core\Controller\AbstractController;
use AppGame\Logger\SetupLogger;

class SetupLogController extends AbstractController {
	
	protected function initialize() {
		parent::initialize();
		
		//$this->logger = SetupLogger::get();
	}
	
	public function run() {
		//echo $this->logger->getLogs();
	}
	
}
