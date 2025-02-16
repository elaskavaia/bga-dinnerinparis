<?php
/**
 * @author https://www.php-fig.org/psr/psr-4/examples/
 */

/**
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {
	
	// project-specific namespace prefix
	//$prefix = 'Foo\\Bar\\';
	$prefix = '';
	
	// base directory for the namespace prefix
	$base_dir = __DIR__;
	
	// does the class use the namespace prefix?
	$len = strlen($prefix);
	if( strncmp($prefix, $class, $len) !== 0 ) {
		// no, move to the next registered autoloader
		return;
	}
	
	// get the relative class name
	$relative_class = substr($class, $len);
	
	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
	$file = $base_dir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
	
	// if the file exists, require it
	if( file_exists($file) ) {
		require $file;
	}
});

/*set_error_handler(function ($code, $text, $file, $line) {
	throw new ErrorException($text, 0, $code, $file, $line);
});*/

require_once 'functions.php';
require_once 'constants.php';
require_once 'polyfill.php';
