<?php
/**
 * Enqueue scripts
 *
 * @param string $handle Script name
 * @param string $src Script url
 * @param array $deps (optional) Array of script names on which this script depends
 * @param string|bool $ver (optional) Script version (used for cache busting), set to null to disable
 * @param bool $in_footer (optional) Whether to enqueue the script before </head> or before </body>
 */
function zonemaster_scripts() {
	wp_enqueue_style( 'open-sans', '//fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700' );
	wp_enqueue_style( 'app-css', get_template_directory_uri() . '/css/app.8bac502f.min.css', array(), null, 'all' );

	wp_deregister_script( 'jquery' );
	wp_enqueue_script( 'app-js', get_template_directory_uri() . '/js/app.269a32a4.min.js', array(), null, true );

	$zm = Zonemaster::get_instance();
	// if ( function_exists( 'pll_current_language' ) ) {
	// 	$selected_lang = pll_current_language();
	// }
	// if ( ! $selected_lang ) {
	// 	return;
	// }

	// Settings transfered to javascript, and language
	$defs = array(
		'waitingOnResult'  => __( 'Test done. Waiting on results', 'zm_text' ),
		'required_text'    => __( 'This field is required', 'zm_text' ),
		'fetching_data'    => __( 'Fetching data..', 'zm_text' ),
		'error_parent'     => __( 'Error then trying to get parent zone data', 'zm_text' ),
		'error_ipaddress'  => __( 'Incorrect IP-address:', 'zm_text' ),
		'start_test'       => __( 'Starting test..', 'zm_text' ),
		'running_test'     => __( 'Running test..', 'zm_text' ),
		'error_text'       => __( 'Something actually went wrong. Error :(', 'zm_text' ),
		'copy_done'        => __( 'Copied!', 'zm_text' ),
		'copy_press'       => __( 'Press', 'zm_text' ),
		'copy_to'          => __( 'to', 'zm_text' ),
		'copy'             => __( 'Copy', 'zm_text' ),
		'polling_interval' => absint( $zm->settings( 'polling_interval' ) ),
		'debug'            => sanitize_text_field( $zm->settings( 'debug' ) ),
		'current_language' => get_locale(),
	);
	wp_localize_script(
		'app-js',
		'zmDefs',
		$defs
	);
}

add_action( 'wp_enqueue_scripts', 'zonemaster_scripts' );
