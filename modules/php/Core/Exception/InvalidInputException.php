<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace Bga\Games\DinnerInParis\Core\Exception;

class InvalidInputException extends UserException {
	
	public function __construct($message) {
		parent::__construct($message, FEX_bad_input_argument);
	}
	
}
