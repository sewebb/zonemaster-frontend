<?php
error_reporting( E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR );

/**
 * Helpful log to see what is goning on. Shows up in /wp-content/ as debug.log
 * @return void
 */
function _log() {
	if ( WP_DEBUG === true ) {
		$args = func_get_args();
		error_log( print_r( $args, true ) );
	}
}

/**
 * Add language
 * @return void
 */
function zm_theme_setup() {
	load_theme_textdomain( 'zm_text', get_template_directory() . '/languages' );

	$locale = get_locale();
	$locale_file = get_template_directory() . "/languages/$locale.php";

	if ( is_readable( $locale_file ) ) {
		require_once( $locale_file );
	}
}
add_action( 'after_setup_theme', 'zm_theme_setup' );

require_once 'menuparts/menu.php';

require_once 'zonemaster/class-zonemaster-settings.php';
require_once 'zonemaster/class-zonemaster.php';

require_once 'inc/scripts.php';

// ACF options api-server
$pageapiserver = array(

	/* (string) The title displayed on the options page. Required. */
	'page_title' => 'API-server',

	/* (string) The capability required for this menu to be displayed to the user. Defaults to edit_posts.
	Read more about capability here: http://codex.wordpress.org/Roles_and_Capabilities */
	'capability' => 'manage_options',

	/* (string) The icon url for this menu. Defaults to default WordPress gear */
	'icon_url' => 'dashicons-admin-site',

	/* (int|string) The '$post_id' to save/load data to/from. Can be set to a numeric post ID (123), or a string ('user_2').
	Defaults to 'options'. Added in v5.2.7 */
	'post_id' => 'apiserver',

);
if ( function_exists( 'acf_add_options_page' ) ) {
	acf_add_options_page( $pageapiserver );
}

/**
 * Delete version number on enqued stylesheets and javascript
 *
 * @param  string $src Url to file
 * @return string      new url to file
 */
function remove_src_version( $src ) {
	global $wp_version;
	$version_str            = '?ver=' . $wp_version;
	$version_str_with_query = 'ver=' . $wp_version;
	$src                    = str_replace( $version_str, '', $src );
	$src                    = str_replace( $version_str_with_query, '', $src );
	return $src;
}
add_filter( 'script_loader_src', 'remove_src_version' );
add_filter( 'style_loader_src', 'remove_src_version' );

