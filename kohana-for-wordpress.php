<?php
#     /*
#     Plugin Name: Kohana-for-Wordpress
#     Plugin URI: http://www.kerkness.ca/kohana
#     Description: Enables the integration of Kohana PHP Applications with Wordpress
#     Author: Ryan Kerk Mayberry
#     Version: 1.2
#     Author URI: http://www.kerkness.ca
#     */

ini_set('error_log','/var/log/php-error.log');

/**
 * Register Actions
 */
register_activation_hook( 'kohana-for-wordpress/kohana-for-wordpress.php', 'kohana_activate' );
register_deactivation_hook( 'kohana-for-wordpress/kohana-for-wordpress.php', 'kohana_deactivate' );
add_action('admin_menu', 'kohana_register_admin_menu');
add_action('widgets_init', create_function('', 'return register_widget("KohanaWidget");'));
add_action('wp_head', 'kohana_wp_head');

/**
 * Register Filters
 */
add_filter('request','kohana_request_filter');
add_filter('wp','kohana_wp_filter');
add_filter('the_content', 'kohana_the_content_filter');
add_filter('the_title','kohana_title_filter');
add_filter('single_post_title','kohana_title_filter');
add_filter('get_pages','kohana_page_filter');
add_filter('plugin_row_meta', 'set_plugin_meta', 10, 2);
add_filter('page_template','kohana_page_template_filter');

/**
 * Checks if a Kohana request is in progress.
 * Must be called after kohana_request_filter has been triggered.
 *
 * @return boolean
 */
function is_kohana_request()
{
	global $wp;
	return ($wp->kohana->request != null) ? true : false;
}

/**
 * Replaces the page_template with the one specified in kohana_page_template
 * if this is a kohana request.
 * @param string $template
 * @return string
 */
function kohana_page_template_filter($template) {
	if (is_kohana_request() && get_option('kohana_page_template')) {
		return locate_template(array(get_option('kohana_page_template')));
	}
	return $template;
}

/**
 * print any extra_head html that has been assigned to the Kohana request.
 */
function kohana_wp_head() {
    global $wp;
    if (is_kohana_request() AND $wp->kohana->extra_head) {
        print $wp->kohana->extra_head;
    }
}

/**
 * If plugin has already been set up
 * Include bootstrap.php which sets up the Kohana environment so
 * that it's ready for a request if given one.
 */
if( should_kohana_run() ){
	require 'kohana_index.php';
	if( get_option('kohana_bootstrap_path') ) {
		require get_option('kohana_bootstrap_path');
	} else {
		require 'kohana_bootstrap.php';
	}
}
require 'kohana_widget.php';

/**
 * Function is called when plugin is activated by wordpress
 *
 * Creates a wordpress page which will act as our Kohana frontloader
 * and creates the default kohana options.
 * @return
 */
function kohana_activate()
{
	error_log('activating kohana plugin');

	// Create a page in word press to act as the kohana frontloader
	$my_post = array();
	$my_post['post_title'] = 'Kohana';
	$my_post['post_content'] = '';
	$my_post['post_status'] = 'publish';
	$my_post['post_type'] = 'page';

	// Insert the post into the database
	$kohana_front_loader = wp_insert_post( $my_post );

	add_option('kohana_front_loader', $kohana_front_loader);
	add_option('kohana_default_placement','replace');
	add_option('kohana_process_all_uri', 1 );
	add_option('kohana_system_path', WP_PLUGIN_DIR . '/kohana-for-wordpress/kohana/system/');
	add_option('kohana_module_path', WP_PLUGIN_DIR . '/kohana-for-wordpress/kohana/modules/');
	add_option('kohana_application_path', WP_PLUGIN_DIR . '/kohana-for-wordpress/kohana/application/');
	add_option('kohana_bootstrap_path', '');
	add_option('kohana_ext', '.php' );
	add_option('kohana_modules', '');
	add_option('kohana_default_controller', '' );
	add_option('kohana_default_action', '' );
	add_option('kohana_default_id', '' );
	add_option('kohana_front_loader_in_nav', 0);
	add_option('kohana_page_template', '');
}

/**
 * Function is called when plugin is deactivated by wordpress
 *
 * Deletes the wordpress page that was acting as Kohana front loader
 * and removes all kohana options.
 * @return
 */
function kohana_deactivate()
{
	error_log('deactivating kohana plugin');

	wp_delete_post( get_option('kohana_front_loader') );

	delete_option('kohana_front_loader');
	delete_option('kohana_default_placement');
	delete_option('kohana_process_all_uri');
	delete_option('kohana_system_path');
	delete_option('kohana_modules_path');
	delete_option('kohana_application_path');
	delete_option('kohana_bootstrap_path');
	delete_option('kohana_ext');
	delete_option('kohana_front_loader_in_nav');
	delete_option('kohana_modules');
	delete_option('kohana_default_controller');
	delete_option('kohana_default_action');
	delete_option('kohana_default_id');
	delete_option('kohana_page_template');

	// remove all page routes
	$all_options = get_alloptions();
	foreach( $all_options as $op_name=>$op_value ){
		if( substr($op_name,0,12)=='kohana_route' ){
			delete_option( $op_name );
		}
	}
}
/**
 * Function adds the Kohana options page to wordpress dashboard
 * @return
 */
function kohana_register_admin_menu()
{
	add_options_page("Kohana", "Kohana", 1, "Kohana", "kohana_admin_menu");
}

/**
 * Function includes the Kohana options/admin page for diplay
 * @return
 */
function kohana_admin_menu()
{
	include_once dirname(__FILE__) . '/admin_menu.php';
}

/**
 * Add settings link to plugin admin page
 */
function set_plugin_meta($links, $file) {
	$plugin = plugin_basename(__FILE__);
	// create link
	if ($file == $plugin) {
		return array_merge(
		$links,
		array( sprintf( '<a href="options-general.php?page=%s">%s</a>',
		'Kohana', __('Settings') ) )
		);
	}
	return $links;
}


/**
 * Function returns false if Kohana is not set up
 * @return
 */
function should_kohana_run()
{
	// If options are not set then return false
	if( ! get_option('kohana_system_path') && ! get_option('kohana_application_path') ){
			return false;
	}
	// If main kohana class file is not found in system path return false
	if( ! is_file( get_option('kohana_system_path') . 'classes/kohana.php' ) )
		return false;

	// If default route not set return false
	if( ! get_option('kohana_default_controller') OR ! get_option('kohana_default_action') )
		return false;

	// We should be good to go, return true.
	return true;
}

/**
 * Function inspects the request as determined by wordpress and determines
 * if Kohana needs to handle any part of this request.
 *
 * Steps this method follows are:
 *
 * - Determine if the request is for a valid wordpress page/post
 * - If no : Determine if the query string contains a request for a valid Kohana controller
 * - If yes : Determine if the wordpress page has a Kohana routing option
 *
 * If a valid Kohana controller is found and there is no valid wordpress page being
 * called then the request is changed to the Kohana front loader.
 *
 * Details of the Kohana request is added to the global $wp class. Eg:
 * $wp->kohana->request = 'welcome/index'
 *
 * @return array $request
 * @param array $request
 */
function kohana_request_filter($request)
{
	// if kohana isn't set up skip
	if( ! should_kohana_run() ) return $request;

	global $wp;
	global $wpdb;

	// Get the wordpress page_name of our kohana front loader
	$wp->kohana->front_loader_slug = $wpdb->get_var("SELECT post_name FROM $wpdb->posts WHERE ID = " . get_option('kohana_front_loader') );

	// attempt to validate the request by looking for a post or page id
	$requested_post_id = kohana_validate_wp_request($request);
	//error_log( "Found the post/page id of $requested_post_id" );

	// If request is not for a valid word press page. Look for valid kohana request
	if( ! $requested_post_id && get_option('kohana_process_all_uri') ){
		//error_log( "No page found and process all uri is enabled. Examining uri for Kohana controller request" );
		// Parse query string and look for kohana type requests
		$kohana_request = kohana_parse_request();

		if( $kohana_request ){
			$wp->kohana->request = $kohana_request;
			$wp->kohana->placement = get_option('kohana_default_placement');
			// Set request to our kohana front loader
			$request = array();
			$request['page_id'] = get_option('kohana_front_loader');

		}
	// Request is for our wordpress kohana front loader
	} else if( $requested_post_id == get_option('kohana_front_loader') ) {
		//error_log( "Request for Kohana front loader provided. Examine URI for Kohana controller request" );
		$kohana_request = kohana_parse_request();
		error_log("Kohana request is $kohana_request");
		$wp->kohana->request = ( $kohana_request ) ? $kohana_request : 'wp_kohana_default_request';
		$wp->kohana->placement = get_option('kohana_default_placement');
		// Just because we found the front loader, wp may still think this is a 404
		// Force page_id into the request array.
		$request = array();
		$request['page_id'] = get_option('kohana_front_loader');
	} else { // Look for Kohana Routing Option
		if( get_option('kohana_route::'.$requested_post_id) != '' ){
			$arr = explode( '::', get_option('kohana_route::'.$requested_post_id) );
			$wp->kohana->request = $arr[0];
			$wp->kohana->placement = $arr[1];
		}
	}
	return $request;
}

/**
 * Returns the post id if the request is for a valid wordpress page/post.
 * Returns false or 0 if the request is going to result in a wordpress 404.
 *
 * @return post id
 * @param array $request
 */
function kohana_validate_wp_request( $request )
{
	global $wpdb;
	global $wp;

	// Check to see if we are requesting the wordpress homepage
	if( is_wp_homepage( 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] ) ){
		// Check to see if the home page is a wordpress page or blog listings
		if( get_option('page_on_front') ) {
			// return the ID of the page
			return get_option('page_on_front');
		}
	}

	//request contains a page id or a post id
	if( $request['page_id'] || $request['p'] ) {
		return ( $request['page_id'] ) ? $request['page_id'] : $request['p'] ;
	}
	// request contains a 'pagename' or 'name' (permalinks)
	if( $request['pagename'] || $request['name'] ) {
		$name = ($request['pagename']) ? $request['pagename'] : $request['name'];
		$has_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name = '$name'");
		return ( $has_id )  ? $has_id : 0 ;
	}

	// This could be a request for our front loader with a Kohana Controller URI appended
	$full_uri = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	$wp_uri = substr( $full_uri, strlen( get_option('home').'/' ) );
	if( $wp->kohana->front_loader_slug == substr( $wp_uri, 0, strlen($wp->kohana->front_loader_slug) ) ){
		return get_option('kohana_front_loader');
	}
	return 0;
}

/**
 * Function returns true if the current request is for the wordpress homepage.
 * @return Boolean
 * @param string $full_uri
 */
function is_wp_homepage( $full_uri ) {
	// Check to see if the request ends in a trailing slash
	if( substr( $full_uri, -1 ) == '/' ){
		$full_uri = substr( $full_uri, 0, -1 );
	}
	return ( $full_uri == get_option('home') ) ? true : false;
}

/**
 * Function parses the query string to determine if a kohana request is being made.
 *
 * Function first looks for values assigned to 'kr' in the query string
 * eg:  example.com/index.php?kr=examples/pagination
 *
 * If nothing is found in $_GET['kr'] function parses the _SERVER['REQUEST_URI'] and
 * looks for possible request in standard Kohana format
 * eg: example.com/examples/pagination
 *
 * Function then checks to make sure that Kohana has a valid controller. If found the
 * Kohana request is returned if not a blank string is returned.
 *
 * @return string $kr
 */
function kohana_parse_request()
{
	global $wp;

	$kr = '';

	if( $_GET['kr'] ) {
		$kr = $_GET['kr'];
	} else {
		$kr = str_replace('?'.$_SERVER['QUERY_STRING'],'',$_SERVER['REQUEST_URI']);
		if( get_option('kohana_base_url') != '/' ){
			$kr = str_replace(get_option('kohana_base_url'),'',$kr);
		}
	}
	// Remove index.php from our string
	$kr = str_replace('/index.php','',$kr);

	//error_log("Starting point Examining KR: $kr");

	// Remove slash from front kr string
	if( substr($kr,0,1) == '/' ){
		$kr = substr($kr,1);
	}
	//error_log("Removed trailing slash Examining KR: $kr");
	// Remove slash from end of kr string
	if( substr($kr,-1) == '/' ){
		$kr = substr( $kr,0,-1 );
	}
	//error_log("Removed starting slash Examining KR: $kr");

	// check for presense of the kohana front loader slug
	if( $wp->kohana->front_loader_slug == substr( $kr, 0, strlen($wp->kohana->front_loader_slug) ) ){
		$kr = substr($kr, strlen($wp->kohana->front_loader_slug.'/') );
	}
	//error_log("Removed front loader slug Examining KR: $kr");

	// Get the controller name.
	if( strpos($kr,'/') ){
		$k_controller = substr( $kr, 0, strpos($kr,'/') );
	} else {
		$k_controller = $kr;
	}
	if( $k_controller && ! $kr ) $kr = 'index';

	//error_log("Found Controller = $k_controller :: Examining: $kr");
	// Check for the presence of a kohana controller for current request
	if( $kr && is_file( get_option('kohana_application_path') .'classes/controller/'.$k_controller.get_option('kohana_ext') ) ){
		return $kr;
	}

	// Look for a defined route
	if( $kr )
	{
		try
		{
			if( Route::get($k_controller) ){
				return $kr;
			}
		}
		catch ( Kohana_Exception $e ) {
			// Do nothing on exception
		}
	}


	return '';
}

/**
 * Function provides a filter on the wordpress list of pages typically used to build
 * navigation in templates. Function will remove the Kohana front loader unless the option
 * to include this page is present.
 *
 * @param array $pages
 * @return array
 */
function kohana_page_filter($pages)
{
	// if we are in the dashboard skip this filter
	if( is_admin() ) return $pages;

	foreach($pages as $i=>$page){
		if( $page->ID == get_option('kohana_front_loader') && ! get_option('kohana_front_loader_in_nav') ){
			unset($pages[$i]);
		}
	}
	return $pages;
}

/**
 * Function provides a filter on the main wp class.
 * This filter is called after wp has been completely loaded and created
 * but before any content is loaded.
 *
 * If a Kohana request was found when filtering the wp request then this is where we
 * create the first Kohana Request object via the kohana_request() function.
 *
 * Output from the Kohana request is placed into the wp class so that it is available
 * when it comes time to display the combined wp and Kohana results.
 *
 * @param stdClass $wp
 * @return stdClass
 */
function kohana_wp_filter($wp)
{
	// if kohana isn't set up skip
	if( ! should_kohana_run() ) return $wp;

	if( $wp->kohana->request ) {
		$wp->kohana->content = kohana_page_request( $wp->kohana->request );
	}
	return $wp;
}

/**
 * Function provides a filter on the wordpress content before being displayed
 *
 * If content has been loaded from a Kohana controller this is where it is added
 * to or replaces the wordpress content.
 *
 * @param string $content
 * @return string
 */
function kohana_the_content_filter($content)
{
	// if kohana isn't set up skip
	if( ! should_kohana_run() ) return $content;

	global $wp;
	if( $wp->kohana->content ) {
		switch( $wp->kohana->placement ){
			case 'before':
				$content = $wp->kohana->content . $content;
			break;
			case 'after':
				$content = $content . $wp->kohana->content;
				break;
			case 'replace':
				$content = $wp->kohana->content;
				break;
		}
	}


	// Look for any Kohana requests that are dropped directly into the content
	$tag = "/\[request(.*?)\\]/";
	$matches = array();
	if(preg_match_all($tag, $content, $matches))
	{
		foreach( $matches[1] as $i=>$match ) {
			$content = str_replace('[request '.trim($match).']', kohana_request( trim($match) ), $content);
		}
	}

	return $content;
}


/**
 * Function provides a filter on the title of the wordpress our post/page.
 * When necessary the wordpress title is replaced with the Kohana title.
 *
 * NOTE: This function handles both wordpress filters 'the_title' and 'single_post_title'
 *
 * @param string $title
 * @return string
 */
function kohana_title_filter($title)
{
	// if kohana isn't set up skip
	if( ! should_kohana_run() ) return $title;

	global $wp;
	global $post;
	if( $wp->kohana->title && $title == $post->post_title && $post->ID == get_option('kohana_front_loader') ){
		$title = $wp->kohana->title;
	}
	return $title;
}

/**
 * This function creates and executes a Kohana Request object.
 * If this request has a title defined this is added to the wp global object
 *
 * @param string $kr
 * @return string  The response from the Kohana Request
 */
function kohana_page_request($kr)
{
	if( ! should_kohana_run() ) return '';
	global $wp;

	$kr = ($kr=='wp_kohana_default_request') ? '' : $kr ;

	try {
		if (method_exists('Request', 'instance')) {
			$req = Request::instance($kr);
			$req = $req->execute();
		} if (method_exists('Request', 'current')) {
			$req = Request::factory($kr)->execute();
		} else {
			throw new Exception("Unable to get request");
		}
	}
	catch( Exception $e )
	{
		if( $req->status == 404 ) {
			global $wp_query;
			$wp_query->set_404();
			return 'Page Not Found';
		}
		throw $e;
	}

	if( $req->title ){
		$wp->kohana->title = $req->title;
	}
    if( $req->extra_head ){
        $wp->kohana->extra_head = $req->extra_head;
    }
	return $req->response;
}

/**
 * Function intended to be used by template files. Returns the response from a Kohana request
 *
 * @param string $kr
 * @return string
 */
function kohana_request( $kr )
{
	if( ! $kr ) return '';
	return Request::factory($kr)->execute()->response;
}

/**
 * For use with template files. Echo's the result of kohana_request
 * @param string $kr
 */
function kohana( $kr ){
	echo kohana_request( $kr );
}


/**
 * This is a replication of the Kohana magic function for i18n translations.
 * For use in application/views if you're leaving Wordpress i10n class to handle translations.
 *
 * Currently by default a site running this plugin will use Wordpress' i10n
 * class and the wordpress __() method for language translation.
 *
 * @param string $string
 * @param array $values
 * @return string
 */
function __k($string, array $values = NULL, $lang = 'en-us')
{
	if ($lang !== I18n::$lang)
	{
		// The message and target languages are different
		// Get the translation for this message
		$string = I18n::get($string);
	}

	return empty($values) ? $string : strtr($string, $values);
}
/**
 * Enable Kohana translations to be default.
 * Comment out the method __() in wp-includes/i10n.php
 */
if( ! function_exists('__') ){


	function __($string, $values = NULL, $lang = 'en-us')
	{
		if( ! is_array( $values ) ){
			$temp = $values;
			$values = array();
			$values[] = $temp;
		}

		if ($lang !== I18n::$lang)
		{
			// The message and target languages are different
			// Get the translation for this message
			$string = I18n::get($string);
		}

		return empty($values) ? $string : strtr($string, $values);
	}


}
