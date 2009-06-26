<?php

	// Recreate the Kohana index.php process 
	$application = get_option('kohana_application_path');
	$modules = get_option('kohana_module_path');
	$system = get_option('kohana_system_path');
	define( 'EXT', get_option('kohana_ext') );
	// Set the full path to the docroot
	define('DOCROOT', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR);
	// Make the application relative to the docroot
	if ( ! is_dir($application) AND is_dir(DOCROOT.$application))
		$application = DOCROOT.$application;
	// Make the modules relative to the docroot
	if ( ! is_dir($modules) AND is_dir(DOCROOT.$modules))
		$modules = DOCROOT.$modules;
	// Make the system relative to the docroot
	if ( ! is_dir($system) AND is_dir(DOCROOT.$system))
		$system = DOCROOT.$system;
	// Define the absolute paths for configured directories
	define('APPPATH', realpath($application).DIRECTORY_SEPARATOR);
	define('MODPATH', realpath($modules).DIRECTORY_SEPARATOR);
	define('SYSPATH', realpath($system).DIRECTORY_SEPARATOR);
	// Clean up the configuration vars
	unset($application, $modules, $system);
		
	// Define the start time of the application
	define('KOHANA_START_TIME', microtime(TRUE));
	//require get_option('kohana_system_path').'base'.get_option('kohana_ext');
	require get_option('kohana_system_path').'classes/kohana'.get_option('kohana_ext');
	// Bootstrap process
	date_default_timezone_set( get_option('kohana_default_time_zone') );
	spl_autoload_register(array('Kohana', 'auto_load'));
	set_exception_handler(array('Kohana', 'exception_handler'));
	set_error_handler(array('Kohana', 'error_handler'));

	
	Kohana::init(array('charset' => 'utf-8', 'base_url' => get_option('kohana_base_url') ));
	$k_mods = explode(',', get_option('kohana_modules') );
	foreach( $k_mods as $km ){
		$mods[trim($km)] = MODPATH.trim($km);
	}
	Kohana::modules($mods);
	Kohana::$log->attach(new Kohana_Log_File(APPPATH.'logs'));

	Route::set('default', '(<controller>(/<action>(/<id>)))')
		->defaults(array(
			'controller' => get_option('kohana_default_controller'),
			'action' => get_option('kohana_default_action'),
			'id' => get_option('kohana_default_id')));	
	// Load the base, low-level functions
