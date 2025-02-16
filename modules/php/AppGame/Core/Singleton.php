<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Core;

trait Singleton {
	
	/** @var static */
	private static $instance = null;
	
	/**
	 * @return static
	 */
	public static function get(): self {
		if( !static::$instance ) {
			static::$instance = new static();
		}
		
		return static::$instance;
	}
	
	protected static function setInstance($instance) {
		static::$instance = $instance;
	}
	
}
