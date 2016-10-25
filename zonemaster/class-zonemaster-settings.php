<?php
// class name outside class
$zm_settings = ZonemasterSettings::get_instance();

/**
 * Set your special site settings here or use Advanced Custom Fields if you want to do it in WordPress frontend
 */
class ZonemasterSettings {

	private static $instance;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	private function __construct() {
		// code
	}

	/**
	 * 'Activates' the class
	 *
	 * @return class
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new ZonemasterSettings();
		}
		return self::$instance;
	}

	/**
	 * Settings per website in WordPress through  Advanced Custom Fields
	 *
	 * @param  String $field field name in ACF
	 * @return String        value in field or error or ACF is missing
	 */
	public function settings( $field ) {
		if ( class_exists( 'acf' ) ) {
			$setting = get_field( $field, 'apiserver' );

			if ( $setting ) {
				return $setting;
			} else {
				return false;
				_log( 'Setting ' . $field . ' is missing' );
			}
		} else {

			// if you dont use / want to use Advanced Custom Fields Pro you can add your settings here
			switch ( $field ) {

				case 'api_server':
					// base url for your backend server for example http://backend.zonemaster.com
					return 'http://backend.zonemaster.com';
					break;

				case 'api_start_url':
					// if served from subpage otherwize just '/'
					return '/';
					break;

				case 'site_logo':
					// if no logo, just return false
					// ex return 'https://www.zonemaster.com/images/logo.png';
					return get_template_directory_uri() . '/img/zm-logo.svg';
					break;

				case 'polling_interval':
					// How often do you want the javascript to check on the results? (milliseconds)
					// 1500 milliseconds = 1,5 second
					return 1500;
					break;

				case 'debug':
					// IMPORTANT! Should not be used in production, but can be of some value on test/stage/local enviroments
					// 'yes' or 'no, if 'yes' you will get some feedback on what is happening in the browser console
					return 'no';
					break;

				case 'temp_disable_transients':
					// IMPORTANT! Should not be used in production, but can be of some value on test/stage/local enviroments
					// 'yes' or 'no, if 'yes' no transients will be used and you will allways fetch from backend server
					return 'no';
					break;

				case 'transient_parent_zone':
					// How often to look for new parent zone data in seconds
					// 1800 seconds = 30 min
					return 1800;
					break;

				case 'transient_ip_nameserver':
					// How often to look for new ip-adress based on nameserver in seconds
					// 1800 seconds = 30 min
					return 1800;
					break;

				case 'transient_old_test':
					// Set cache for old test in seconds - how often should we fetch the old testdata from backend
					// 3600 seconds = 1 hour
					return 3600;
					break;

				case 'transient_start_test':
					// How often could a specific test be done? Once every.. (seconds)
					// 1800 seconds = 30 min
					return 1800;
					break;

				case 'what_is_pre_delegated_domain_check':
					// set up which page (idnr) should go with which language for explaining what a predelegated test is You would have Polylang plugin installed for this to work
					// If you dont want to use it, return false
					return false;
					// Array example
					// $arr_to_return = array(
					// 		array(
					// 			// use page idnr!
					// 			'page_name' => 44,
					// 			'language'  => 'fr',
					// 			),
					// 		// continue for each language and page
					// 		array(
					// 			// use page idnr!
					// 			'page_name' => 25,
					// 			'language'  => 'en',
					// 			),
					// 		array(
					// 			// use page idnr!
					// 			'page_name' => 17,
					// 			'language'  => 'sv',
					// 			),
					// 	);
					// return $arr_to_return;
					break;

				default:
					_log( 'Check that class-zonemaster-settings.php is set up' );
					_log( 'Setting ' . $field . ' is missing' );
					return false;
					break;
			}
		}

	}
}
