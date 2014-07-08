<?php

// Recreate the Kohana application/bootstrap.php process with some modifications
	
//-- Environment setup --------------------------------------------------------

/**
 * Set the default time zone.
 *
 * @see  http://docs.kohanaphp.com/features/localization#time
 * @see  http://php.net/timezones
 */
date_default_timezone_set(get_option('timezone_string'));

/**
 * Enable the Kohana auto-loader.
 *
 * @see  http://docs.kohanaphp.com/features/autoloading
 * @see  http://php.net/spl_autoload_register
 */
spl_autoload_register(array('Kohana', 'auto_load'));

/**
 * Enable Kohana exception handling, adds stack traces and error source.
 *
 * @see  http://docs.kohanaphp.com/features/exceptions
 * @see  http://php.net/set_exception_handler
 */
if (method_exists('Kohana_Exception', 'handler')) {
	set_exception_handler(array('Kohana_Exception', 'handler'));
} else {
	set_exception_handler(array('Kohana', 'exception_handler'));
}


/**
 * Enable Kohana error handling, converts all PHP errors to exceptions.
 *
 * @see  http://docs.kohanaphp.com/features/exceptions
 * @see  http://php.net/set_error_handler
 */
set_error_handler(array('Kohana', 'error_handler'));

//-- Kohana configuration -----------------------------------------------------

/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 * - base_url:   path, and optionally domain, of your application
 * - index_file: name of your index file, usually "index.php"
 * - charset:    internal character set used for input and output
 * - profile:    enable or disable internal profiling
 * - caching:    enable or disable internal caching
 */

$kohana_base_url = str_replace(get_option('home'),'',get_option('siteurl') );
if( ! $kohana_base_url ) {
	$kohana_base_url = '/';
}
Kohana::init(array('charset' => 'utf-8', 'base_url' => $kohana_base_url ));

/**		**** Enable modules as defined in plugin settings
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
$k_mods = explode(',', get_option('kohana_modules') );
foreach( $k_mods as $km ){
	$mods[trim($km)] = MODPATH.trim($km);
}
Kohana::modules($mods);

/**
* Attach the file write to logging. Multiple writers are supported.
*/
Kohana::$log->attach(new Kohana_Log_File(APPPATH.'logs'));
 
/**
* Attach a file reader to config. Multiple readers are supported.
*/
Kohana::$config->attach(new Kohana_Config_File);

/**
 * Set the routes. Each route must have a minimum of a name, a URI and a set of
 * defaults for the URI.
 */
Route::set('default', '(<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => get_option('kohana_default_controller'),
		'action' => get_option('kohana_default_action'),
		'id' => get_option('kohana_default_id')));	
