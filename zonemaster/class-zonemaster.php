<?php
/**
 * Main class, a bit large..
 */
class Zonemaster extends ZonemasterSettings {

	/**
	 * The Zonemaster object instance
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	private function __construct() {
		// Hook ajax request on init, avoid slow admin_ajax
		if ( ! is_admin() ) {
			add_action( 'init', [ $this, 'ajax_get_functions' ] );
		}
	}

	/**
	 * "Activates" the class
	 *
	 * @return class
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Zonemaster();
		}
		return self::$instance;
	}


	/**
	 * This function is used to route calls from frontend javascript to backend server
	 * It's a way to be able to restrict in backend which calls are allowed (eg if the case would be that only curl would be allowed )
	 * It also makes it possible to "nonce" and white list calls
	 *
	 * @return void
	 */
	public function ajax_get_functions() {

		// Version, langugage based, used as nonce
		$version = $this->get_zm_version();

		$request_action         = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
		error_log('action: ' . $request_action);
		$request_index          = isset( $_REQUEST['index'] ) ? absint( $_REQUEST['index'] ) : 1;
		$request_ns             = isset( $_REQUEST['ns'] ) ? $this->regexp_check( $_REQUEST['ns'] ) : '';
		$request_ip             = isset( $_REQUEST['ip'] ) ? $this->regexp_check( $_REQUEST['ip'] ) : '';
		$request_zone_tld       = isset( $_REQUEST['zone_tld'] ) ? $this->verify_zone_tld( $_REQUEST['zone_tld'] ) : '';
		$request_id             = isset( $_REQUEST['id'] ) ? $this->regexp_check( $_REQUEST['id'] ) : '';
		$request_oldtest_inline = isset( $_REQUEST['oldtest_inline'] ) ? $this->regexp_check( $_REQUEST['oldtest_inline'] ) : false;
		$request_method         = isset( $_REQUEST['method'] ) ? sanitize_text_field( $_REQUEST['method'] ) : '';
		$request_params         = isset( $_REQUEST['params'] ) ? sanitize_text_field( $_REQUEST['params'] ) : '';

		// Check that backend is not offline
		if ( strpos( $version, 'OFFLINE' ) !== false ) {
			$GLOBALS['OFFLINE'] = $this->offline_text();
		} else {
			$GLOBALS['OFFLINE'] = '';
		}

		// returns html for digest fields
		if ( 'get_digests_html' === $request_action ) {
			// returns '-1' to javascript if nonce not correct
			if ( check_ajax_referer( $version, 'nonce' ) ) {
				$output = $this->digests_html( '0' );

				echo wp_json_encode( $output );
			}

			exit();
		}

		// returns html for nameserver fields
		if ( 'get_nameservers_html' === $request_action ) {
			// returns '-1' to javascript if nonce not correct
			if ( check_ajax_referer( $version, 'nonce' ) ) {
				$key = $request_index;
				$ns  = $request_ns;
				$ip  = $request_ip;

				$output = $this->nameservers_html( $key, $ns, $ip );

				echo wp_json_encode( $output );
			}
			exit();
		}

		// returns html for parent zone
		if ( 'get_data_from_parent_zone' === $request_action ) {
			// returns '-1' to javascript if nonce not correct
			if ( check_ajax_referer( $version, 'nonce' ) ) {
				$zone_tld = $request_zone_tld;
				if ( $zone_tld ) {
					$zone_tld = $this->regexp_check( $zone_tld );

					list( $nameservers_html, $digests_html ) = $this->get_data_from_parent_zone( $zone_tld );

					echo '<div><div id="nameservers_html">' . $nameservers_html . '</div>';
					echo '<div id="digests_html">' . $digests_html . '</div></div>';
				}
			}
			exit();
		}

		// at the end of the test we return html to append to startpage with test result
		// We also use this to fetch old tests inline
		if ( 'get_single_test' === $request_action ) {
			// returns '-1' to javascript if nonce not correct
			if ( check_ajax_referer( $version, 'nonce' ) ) {
				$testid         = $request_id;
				$oldtest_inline = $request_oldtest_inline;

				echo $this->get_single_test( $testid, 'tbr', $oldtest_inline );
			}
			exit();
		}

		// Used for backend api proxy calls through javascript
		if ( 'proxy' === $request_action ) {
			// returns '-1' to javascript if nonce not correct
			if ( check_ajax_referer( $version, 'nonce' ) ) {
				// Allowed backend api call via this function
				$allow = [
					'get_ns_ips',
					'test_progress',
					'get_data_from_parent_zone',
				];

				if ( '' === $request_method || '' === $request_params ) {
					exit;
				}

				$method = $request_method;
				$params = $request_params;

				if ( in_array( $method, $allow, true ) ) {
					$defaults = [
						'method' => trim( $method ),
						'params' => trim( $params ),
					];

					$request_curl = $this->verify_and_curl_request( $defaults );

					if ( $request_curl ) {
						// Generate appropriate content-type header.
						$is_xhr = isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest';
						header( 'Content-type: application/' . ( $is_xhr ? 'json' : 'x-javascript' ) );

						// Generate JSON string
						$json = wp_json_encode( $request_curl );
						print $json;
					}
				}
			}
			exit();
		}
	}

	/**
	 * There should be options to verify the zone inputed, and help to "spell" correct.
	 * This is where thoose methods should be
	 *
	 * @param  String $zone_tld Zone to check
	 * @return string
	 */
	public function verify_zone_tld( $zone_tld ) {

		// check with lowercase
		$zone_tld = mb_strtolower( $zone_tld, 'UTF-8' );

		if ( substr( $zone_tld, 0, 7 ) !== 'http://' && substr( $zone_tld, 0, 8 ) !== 'https://' ) {
			$zone_tld = $zone_tld;
		} elseif ( substr( $zone_tld, 0, 7 ) === 'http://' ) {
			$zone_tld = str_replace( 'http://', '', $zone_tld );
		} elseif ( substr( $zone_tld, 0, 8 ) === 'https://' ) {
			$zone_tld = str_replace( 'https://', '', $zone_tld );
		}
		// No need to return "wrong tld" beacuse of ending "/"
		if ( substr( $zone_tld, -1 ) === '/' ) {
			$zone_tld = rtrim( $zone_tld, '/' );
		}

		// bad zone for example  www.dansk.dk?.se /#.se
		$patterncheck = '/[?#].*/';
		if ( preg_match( $patterncheck, $zone_tld ) ) {
			return false;
		}

		// Convert idna ( räksmörgås.se ==> xn--rksmrgs-5wao1o.se )
		$zone_tld = $this->convert_to_idna( $zone_tld );

		// Check chars
		$valid_url_regex = '/^[a-zA-Z0-9\.-]+$/';
		if ( ! preg_match( $valid_url_regex, $zone_tld ) ) {
			return false;
		}
		return $zone_tld;
	}

	/**
	 * Convert to idna
	 *
	 * @param string $zone_tld Checked zone
	 */
	public function convert_to_idna( $zone_tld ) {

		if ( empty( $zone_tld ) ) {
			return false;
		}

		if ( ! mb_detect_encoding( $zone_tld, 'UTF-8', true ) ) {
			// String should be UTF-8
			$zone_tld = utf8_encode( $zone_tld );
		}

		$convert = new idna_convert();

		// Translate IDN-domain to punycode
		$zone_tld_converted = $convert->encode( $zone_tld );
		$zone_tld           = $zone_tld_converted;

		return $zone_tld;
	}

	/**
	 * Label an idna domain
	 *
	 * @param string $tested_zone_tld Checked zone
	 */
	public function label_idna_test( $tested_zone_tld ) {

		// If it's not a idn-domain
		if ( strpos( $tested_zone_tld, 'xn--' ) === false || empty( $tested_zone_tld ) ) {
			return $tested_zone_tld;
		}

		$idna_convert = new idna_convert();
		// Encode zone to its punycode presentation
		$zone_tld_converted = $idna_convert->decode( $tested_zone_tld );

		return $tested_zone_tld . ' (' . $zone_tld_converted . ')';

	}


	/**
	 * Verify incoming data that will be sent to backend
	 *
	 * @param  array $options  method = using it talk to backend / $create_transient = if we should pass this along / params post data to backend
	 * @return bool
	 */
	public function verify_and_curl_request( $options = [] ) {

		$defaults = [
			'method'            => '',
			'create_transient'  => false,
			'transient_seconds' => 0,
			'params'            => [],
		];
		$options  = array_merge( $defaults, $options );
		extract( $options );

		// sanitize data from text field and params
		$params            = $this->sanitize_array( $params );
		$method            = $this->regexp_check( $method );
		$transient_seconds = absint( $transient_seconds );

		if ( ! $params ) {
			_log( 'Params is empty' );
			return false;
		}

		$sanitized_options = [
			'method'            => $method,
			'create_transient'  => $create_transient,
			'params'            => $params,
			'transient_seconds' => $transient_seconds,
		];

		// validate_syntax on method "start_domain_test"
		if ( false && 'start_domain_test' === $method ) { // validate_syntax is deprecated, so we block the call
			$validate_syntax = [
				'method'            => 'validate_syntax',
				'create_transient'  => true,
				'params'            => $params,
				'transient_seconds' => 60,
			];
			$syntax          = $this->analyze_zone_tld( $validate_syntax );

			if ( 'ok' === sanitize_text_field( $syntax['result']['status'] ) ) {
				// if check params ok, do call
				return $this->analyze_zone_tld( $sanitized_options );
			} elseif ( 'nok' === sanitize_text_field( $syntax['result']['status'] ) ) {
				return $syntax;
			}
		} else {
			// other tests can't be verified via 'validate_syntax', only start_domain_test
			return $this->analyze_zone_tld( $sanitized_options );
		}

		return false;
	}

	/**
	 * Only certain characters should be posted to backend
	 *
	 * @param  string $value Posted value from frontend
	 *
	 * @return string or integer
	 */
	public function regexp_check( $value = '' ) {
		$check_against = '/^[a-zA-Z0-9\-\.\_\:[:blank:]]+$/';
		if ( ! preg_match( $check_against, $value ) ) {
			$value = '';
		}

		return $value;
	}

	/**
	 * Sanitize the input when it's an array
	 *
	 * @param  array $array [un-sanitized]
	 * @return array         [sanitized]
	 */
	public function sanitize_array( &$array ) {

		if ( is_array( $array ) ) {
			foreach ( $array as &$value ) {
				if ( ! is_array( $value ) ) {
					$value = $this->regexp_check( $value );

				} else {
					// go inside this function again
					$this->sanitize_array( $value );
				}
			}
		}

		return $array;
	}


	/**
	 * Create a call to backend
	 *
	 * @param array $options Merges with default settings
	 * @return string
	 */
	private function analyze_zone_tld( $options = [] ) {
		$defaults = [
			'method'            => '',
			'api_call'          => sanitize_text_field( $this->settings( 'api_server' ) ) . sanitize_text_field( $this->settings( 'api_start_url' ) ),
			'field_policy'      => '',
			'field_ns'          => [],
			'field_ds'          => [],
			'field_meta_data'   => [],
			'params'            => [],
			'timeout'           => 10,
			'create_transient'  => false,
			'transient_seconds' => 10,
		];
		$options  = array_merge( $defaults, $options );
		extract( $options );

		$locale_payload = 'dummy_payload_transient_name';
		$params         = $this->sanitize_array( $params );
		$method         = sanitize_text_field( $method );

		$id   = date( 'YmdHis' ) . floor( microtime( true ) ); // something random
		$send = [
			'jsonrpc' => '2.0',
			'method'  => $method,
			'id'      => $id,
		];
		if ( ! empty( $params ) ) {
			$send['params'] = $params;
		}

		$payload = wp_json_encode( $send );

		// Set transient name
		if ( $create_transient ) {
			$currentlang = get_locale();

			// Current lang so not to risk mixup (eg version info / proxy nonce )
			$locale_payload = $currentlang . wp_json_encode( $params ) . $method;

			// Max 40 chars in transient name
			if ( strlen( $locale_payload ) > 40 ) {
				// Shorten with md5 but add method to be easier to spot in db
				$locale_payload = $method . '-' . md5( $locale_payload );
			}
		}

		// Remove transients based on GUI options page ( Should only be used for debugging)
		if ( 'yes' === sanitize_text_field( $this->settings( 'temp_disable_transients' ) ) ) {
			delete_transient( $locale_payload );
			$create_transient = false;
		}

		// If there is no transient cache available  - go fetch
		if ( false === ( $json = get_transient( $locale_payload ) ) ) {
			$response = wp_remote_post( $api_call, [
				'method'      => 'POST',
				'timeout'     => $timeout,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => [],
				'body'        => $payload,
				'cookies'     => [],
			] );
_log($payload);
_log($response);
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();

				// Empty transient if an error
				if ( $create_transient ) {
					delete_transient( $locale_payload );
				}
				if ( stripos( $error_message, 'Failed to connect to' ) ) {
					return 'API backend server is offline';
				}
				return false;
			} else {
				if ( 200 === $response['response']['code'] ) {
					$json = json_decode( $response['body'], true );

					// if call requests a transient
					if ( $create_transient ) {
						$transient_seconds = absint( $transient_seconds );
						set_transient( $locale_payload, $json, $transient_seconds );
					}
					return $json;
				} else {
					// Empty transient if an error
					if ( $create_transient ) {
						delete_transient( $locale_payload );
					}
				}
			}
		}
		// we can get the cached version
		return $json;
	}


	/**
	 * Create html for rows of nameserver fields. Used also via Ajax-call from api.js
	 *
	 * @param  integer $key makes it easier to remove field then no-js
	 * @param  string  $ns  if any, nameserver to print as value
	 * @param  string  $ip  if any, ip-server for prefill
	 * @return string
	 */
	public function nameservers_html( $key = '0', $ns = '', $ip = '' ) {
		$html = '<div class="row js-ns-container">
						<div class="small-6 columns">
							<input type="text" name="field_ns[]" value="' . esc_attr( $ns ) . '" placeholder="NS" class="js-field-blur">
						</div>
						<div class="small-6 columns">
							<div class="input-group">
								<input type="text" class="input-group-field js-field-ip" name="field_ip[]" value="' . esc_attr( $ip ) . '" placeholder="IP">
								<div class="input-group-button">
									<button type="submit" name="del_ns_field" value="' . esc_attr( $key ) . '" class="alert button js-remove-nameservers">X</button>
								</div>
							</div>
						</div>
					</div>';
		return $html;
	}


	/**
	 * Creat html for digest fields. Also used then user has javascript
	 *
	 * @param  int    $key         makes it easier to remove filed then no-js
	 * @param  string $key_tag     [description]
	 * @param  int    $algorithm   [description]
	 * @param  int    $digest_type [description]
	 * @param  string $digest      [description]
	 * @return string              [description]
	 */
	public function digests_html( $key = '0', $key_tag = '', $algorithm = '', $digest_type = '', $digest = '' ) {

		$three   = '';
		$five    = '';
		$six     = '';
		$seven   = '';
		$eight   = '';
		$nine    = '';
		$sha_1   = '';
		$sha_256 = '';

		switch ( esc_attr( $algorithm ) ) {
			case '3':
				$three = 'selected';
				break;

			case '5':
				$five = 'selected';
				break;

			case '6':
				$six = 'selected';
				break;

			case '7':
				$seven = 'selected';
				break;

			case '8':
				$eight = 'selected';
				break;

			case '9':
				$nine = 'selected';
				break;
		}

		switch ( $digest_type ) {
			case '1':
				$sha_1 = 'selected';
				break;

			case '2':
				$sha_256 = 'selected';
				break;
		}

		$html = '<div class="row js-digest-container">
						<div class="small-6 medium-3 columns">
							<input type="text" class="js-key-tag" name="field_key_tag[]" value="' . esc_attr( $key_tag ) . '" placeholder="Key tag">
						</div>
						<div class="small-6 medium-3 columns">
							<select name="field_algorithm[]" class="js-algorithm">
								<option value=""></option>
								<option value="3" ' . $three . '>' . esc_html( $this->option_name( '3' ) ) . '</option>
								<option value="5" ' . $five . '>' . esc_html( $this->option_name( '5' ) ) . '</option>
								<option value="6" ' . $six . '>' . esc_html( $this->option_name( '6' ) ) . '</option>
								<option value="7" ' . $seven . '>' . esc_html( $this->option_name( '7' ) ) . '</option>
								<option value="8" ' . $eight . '>' . esc_html( $this->option_name( '8' ) ) . '</option>
								<option value="9" ' . $nine . '>' . esc_html( $this->option_name( '9' ) ) . '</option>
							</select>
						</div>
						<div class="small-6 medium-2 columns">
							<select name="field_digest_type[]" class="js-digest-type">
								<option value=""></option>
								<option value="1" ' . $sha_1 . '>' . esc_html( $this->option_name( '1', 'field_digest_type' ) ) . '</option>
								<option value="2" ' . $sha_256 . '>' . esc_html( $this->option_name( '2', 'field_digest_type' ) ) . '</option>
							</select>
						</div>
						<div class="small-6 medium-4 columns">
							<div class="input-group">
								<input type="text" class="input-group-field js-digest" name="field_digest[]" value="' . esc_attr( $digest ) . '" placeholder="Digest">
								<div class="input-group-button">
									<button type="submit" name="del_digest_field" value="' . esc_attr( $key ) . '" class="alert button js-remove-digests">X</button>
								</div>
							</div>
						</div>
					</div>';
		return $html;
	}

	/**
	 * We have use for the option names in at least two places
	 *
	 * @param  string $option_number What name to return, based on number as a string
	 * @param  string $field         Which field to return option name for
	 * @return string
	 */
	private function option_name( $option_number, $field = 'field_algorithm' ) {

		if ( 'field_digest_type' === $field ) {
			switch ( $option_number ) {
				case '1':
					$name = 'SHA-1';
					break;

				case '2':
					$name = 'SHA-256';
					break;
			}
		} elseif ( 'field_algorithm' === $field ) {
			switch ( $option_number ) {
				case '3':
					$name = 'DSA/SHA1';
					break;

				case '5':
					$name = 'RSA/SHA1';
					break;

				case '6':
					$name = 'DSA-NSEC3-SHA1';
					break;

				case '7':
					$name = 'RSASHA1-NSEC3-SHA1';
					break;

				case '8':
					$name = 'RSA/SHA-256';
					break;

				case '9':
					$name = 'RSA/SHA-512';
					break;
			}
		}
		return $name;
	}

	/**
	 * Request fields to be prefilled based on domain namne
	 *
	 * @param  string $zone_tld domain name
	 * @return array            We use the array as a list to print html
	 */
	public function get_data_from_parent_zone( $zone_tld ) {

		$nameservers_html = '';
		$digests_html     = '';
		$key              = -1;
		// API-call to get parent nameservers - ad ds data if any is available
		$parent_addresses = $this->verify_and_curl_request(
			[
				'method'            => 'get_data_from_parent_zone',
				'params'            => $zone_tld,
				'create_transient'  => true,
				'transient_seconds' => $this->settings( 'transient_parent_zone' ),
			]
		);

		$parent_nameservers = $this->sanitize_array( $parent_addresses['result']['ns_list'] );
		$parent_digests     = $this->sanitize_array( $parent_addresses['result']['ds_list'] );

		// create filled nameserver fields
		foreach ( $parent_nameservers as $parent_nameserver ) {
			$key++;
			$nameservers_html .= $this->nameservers_html( $key, $parent_nameserver['ns'], $parent_nameserver['ip'] );
		}

		// create filled digest fields
		foreach ( $parent_digests as $parent_digest ) {
			$key++;
			$digests_html .= $this->digests_html( $key, $parent_digest['keytag'], $parent_digest['algorithm'], $parent_digest['digtype'], $parent_digest['digest'] );
		}
		return [ $nameservers_html, $digests_html ];
	}

	/**
	 * Prints html for ip-version checkboxes
	 *
	 * @param  int    $id for checkbox
	 * @param  string $is_ipv4_checked indicates if value selected
	 * @param  string $is_ipv6_checked indicates if value selected
	 * @return string                  Simple html
	 */
	public function ip_checkboxes( $id = '1', $is_ipv4_checked = 'checked', $is_ipv6_checked = 'checked' ) {
		$html = '<span>' . __( 'Option - check domain with ', 'zm_text' ) . '</span>:&nbsp;<input class="js-check-ipv4" id="check_ip_v4_' . $id . '" name="check_ip_v4" type="checkbox" ' . esc_attr( $is_ipv4_checked ) . ' value="1"><label for="check_ip_v4_' . esc_attr( $id ) . '">IPv4 </label>
				<input class="js-check-ipv6" id="check_ip_v6_' . esc_attr( $id ) . '" name="check_ip_v6" type="checkbox" ' . esc_attr( $is_ipv6_checked ) . ' value="1"><label for="check_ip_v6_' . esc_attr( $id ) . '">IPv6 </label>';
		return $html;
	}


	/**
	 * Version info for printing in footer and individual tests
	 *
	 * @return string
	 */
	public function get_zm_version() {

		$api_version = $this->analyze_zone_tld(
			[
				'method'            => 'version_info',
				'params'            => [],
				'create_transient'  => true,
				'transient_seconds' => 60,
			]
		);
		$zm_backend  = sanitize_text_field( $api_version['result']['zonemaster_backend'] );
		$zm_engine   = sanitize_text_field( $api_version['result']['zonemaster_engine'] );

		// if we want the footer text and api is online
		if ( ! empty( $zm_engine ) ) {
			// We think it´s not nescessary to show user ip
			// $text    = __( 'IIS presents', 'zm_text' ) . ' ' . __( 'Zonemaster', 'zm_text' ) . ' ' .' backend v' . $zm_backend . ' ' . __( 'with', 'zm_text' ) . ' ' . __( 'Zonemaster engine', 'zm_text' ) . ' ' . $zm_engine . ' ' . __( 'to IP ', 'zm_text' ) . $_SERVER['REMOTE_ADDR'];
			$text = __( 'IIS presents', 'zm_text' ) . ' ' . __( 'Zonemaster', 'zm_text' ) . '  backend v' . esc_html( $zm_backend ) . ' ' . __( 'with', 'zm_text' ) . ' ' . __( 'Zonemaster engine', 'zm_text' ) . ' ' . esc_html( $zm_engine );
		} else {
			$text = $this->offline_text();
		}

		return $text;
	}

	/**
	 * Text then backend API is offline
	 *
	 * @return string
	 */
	private function offline_text() {

		$html = '<span class="label alert"><strong>OFFLINE</strong></span> ' . __( 'Sorry, API is currently offline. Try again in a while.', 'zm_text' );
		return $html;
	}

	/**
	 * [get_ns_ips description]
	 *
	 * @param  [type] $ns [description]
	 * @return [type]     [description]
	 */
	public function get_ns_ips( $ns ) {

		if ( '' !== $ns ) {
			$addresses = $this->analyze_zone_tld(
				[
					'method'            => 'get_ns_ips',
					'params'            => esc_attr( $ns ),
					'create_transient'  => true,
					'transient_seconds' => sanitize_text_field( $this->settings( 'transient_ip_nameserver' ) ),
				]
			);
			return $addresses['result'];
		}
		return false;
	}

	/**
	 * [test_progress description]
	 *
	 * @param  [type] $testid [description]
	 * @return [type]         [description]
	 */
	public function test_progress( $testid ) {
		$progress = $this->analyze_zone_tld(
			[
				'method' => 'test_progress',
				'params' => [ 'test_id' => esc_attr( $testid ) ],
			]
		);
		return $progress['result'];
	}

	/**
	 * Template for then we show results on start page
	 *
	 * @param  string $testid [description]
	 * @param  string $current_progress [description]
	 * @param  string $zone_tld [description]
	 * @param  string $check_ip_v4 [description]
	 * @param  string $check_ip_v6 [description]
	 * @return void
	 */
	public function polling_test_template( $testid, $current_progress = 1, $zone_tld = '', $check_ip_v4 = 1, $check_ip_v6 = 1 ) {

		$ipv4_label = 'alert';
		$ipv6_label = 'alert';
		if ( $check_ip_v6 ) {
			$ipv6_label = 'success';
		}
		if ( $check_ip_v4 ) {
			$ipv4_label = 'success';
		}
		// If zone tested is idna add the original raw text to label
		$label_output = $this->label_idna_test( $zone_tld );
		$server_name  = sanitize_text_field( $_SERVER['SERVER_NAME'] );

		?>
		<div id="js-div-report-status">
			<div class="row align-center" >
				<div class="small-12 medium-11 large-10 xlarge-8 xxlarge-7 column columns">

					<code class="show-if-no-js fade-in"><?php _e( 'In a short while your test will be ready at ', 'zm_text' ); ?>
						<a class="stat" href="/?resultid=<?php echo $testid; ?>">
							<?php echo $server_name; ?>/?resultid=<?php echo esc_attr( $testid ); ?>
						</a>
						<br>
						<?php _e( 'Click the link to refresh the status of your test.', 'zm_text' ); ?>
					</code>

					<div class="js-report-status polling-area fade-in fast" >
						<?php
						//rechecking no-js based on testid - simplify
						if ( '' !== $zone_tld ) {
						?>
							<h3>
								<?php _e( 'Currently checking', 'zm_text' ); ?>: <span id="js-fqdn"><?php echo esc_html( $label_output ); ?></span>
							</h3>
							<span class="<?php echo esc_attr( $ipv4_label ); ?> label js-ipv4">ipv4</span>
							<span class="<?php echo esc_attr( $ipv6_label ); ?> label js-ipv6">ipv6</span>
						<?php
						}
						?>

						<span class="js-policy"></span>
						<div class="js-message-area"></div>
						<br>
						<a href="/" class="expanded primary button js-cancel hide-if-no-js"><?php _e( 'Cancel test mode', 'zm_text' ); ?></a>
						<br>
						<div class="secondary progress progress-waiting" role="progressbar" tabindex="0" aria-valuenow="<?php echo esc_attr( $current_progress ); ?>" aria-valuemin="0" aria-valuetext="<?php echo $current_progress; ?> percent" aria-valuemax="100">
							<div class="progress-meter" style="width: <?php echo esc_attr( $current_progress ); ?>%">
								<p class="progress-meter-text"><?php echo esc_attr( $current_progress ); ?>%</p>
							</div>
						</div>

					</div>

				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Get specific test
	 *
	 * @param  string $testid Uniq id
	 * @param  string $tab What tab we want to refer
	 * @param  bool   $oldtest_inline True if we are going to use it in a modal /reveal
	 * @return void
	 */
	public function get_single_test( $testid, $tab = 'tbr', $oldtest_inline = false ) {

		if ( function_exists( 'pll_current_language' ) ) {
			$selected_lang = pll_current_language();
		} else {
			$selected_lang = 'en';
		}

		$params = [
			'language' => $selected_lang,
			'id'       => esc_attr( $testid ),
		];

		if ( $oldtest_inline ) {
			$get_test_results = [
				'method'            => 'get_test_results',
				'create_transient'  => true,
				'transient_seconds' => sanitize_text_field( $this->settings( 'transient_old_test' ) ),
				'params'            => $params,
			];
		} else {
			$get_test_results = [
				'method'           => 'get_test_results',
				'create_transient' => false,
				'params'           => $params,
			];
		}

		$testresults = $this->verify_and_curl_request( $get_test_results );

		// Very simple error checking
		if ( isset( $testresults['error'] ) ) {
			_log( '$testresults[error] - could be a problem with many test at the same time?' );
		} else {
			$testresult      = $testresults['result']['results'];
			$frontend_params = $this->sanitize_array( $testresults['result']['params'] );
			$fqdn            = sanitize_text_field( $frontend_params['domain'] );

			// Mask for frontend_params (define which keys to include)
			$frontend_intersect = [
				'domain'    => '',
				'client_id' => '',
				'profile'   => '',
			];
			$get_history_params = [
				'offset'          => 0,
				'limit'           => 200,
				'frontend_params' => array_intersect_key( $frontend_params, $frontend_intersect ),
			];

			// Next: Check to see if the site has been checked recently,
			$status = $this->verify_and_curl_request(
				[
					'method'           => 'get_test_history',
					'params'           => $get_history_params,
					'create_transient' => false,
				]
			);

			// colors for traffic light, gray if nothing to show
			$red_fill    = '575756';
			$orange_fill = '575756';
			$green_fill  = '575756';
			error_log('$status result');
			_log($status['result']);

			foreach ( $status['result'] as $key => $result ) {
				_log('testid='.$testid . ' result id = ' . $result['id']);
				if ( $result['id'] === $testid ) {
					$creation_time  = sanitize_text_field( $result['creation_time'] );
					$overall_result = sanitize_text_field( $result['overall_result'] );
				}

				switch ( $overall_result ) {
					case 'warning':
						$result_header_text = __( 'Contains warnings!', 'zm_text' );
						$div_tab_area_color = 'warning';
						$orange_fill        = 'ff7900';
						break;

					case 'error':
						$result_header_text = __( 'Contains errors!', 'zm_text' );
						$div_tab_area_color = 'alert';
						$red_fill           = 'e00034';
						break;

					case 'critical':
						$result_header_text = __( 'Contains critical errors!', 'zm_text' );
						$div_tab_area_color = 'alert';
						$red_fill           = 'e00034';
						break;

					case 'INFO':
						$result_header_text = __( 'All is well!', 'zm_text' );
						$div_tab_area_color = 'success';
						$green_fill         = '34b233';
						break;

					case 'notice':
						$result_header_text = __( 'All is well!', 'zm_text' );
						$div_tab_area_color = 'success';
						$green_fill         = '34b233';
						break;

					default:
						$result_header_text = __( 'Sorry, we could not determ test results', 'zm_text' );
						$div_tab_area_color = 'primary';
						break;
				}
			}

			if ( '' !== $fqdn && null !== $fqdn ) {

				// If zone tested is idna add the original raw text to label
				$label_output = $this->label_idna_test( $fqdn );

				// Button is only used for then single tests are shown in modal / reveal?>
				<button class="close-button old-test-reveal" data-close aria-label="Close reveal" type="button">
					<span aria-hidden="true">&times;</span>
				</button>

				<div class="div-results" id="singletest">
					<div class="div-tab-area <?php echo $div_tab_area_color; ?>">
						<div class="row align-bottom">
							<div class="column small-10 medium-7">
								<p class="result-text-one"><?php echo esc_html( $label_output ); ?></p>
								<p class="result-text-two"><?php echo esc_html( $result_header_text ); ?></p>
								<p class="result-text-three"><?php echo esc_html( $creation_time ); ?></p>


								<div class="result-text-three">
									<?php
									// check what kind of test this is and label it
									$is_test_undelegated = isset( $frontend_params['nameservers'] ) && is_array( $frontend_params['nameservers'] );
									$has_test_digests    = isset( $frontend_params['nameservers'] ) && is_array( $frontend_params['ds_info'] );

									if ( $is_test_undelegated ) {
										$ns_ip = '<div class="row small-font"><div class="column small-6 medium-12 large-6"><label class="label ' . $div_tab_area_color . '">Nameserver</label></div><div class="column small-6 medium-12 large-6"><label class="label ' . $div_tab_area_color . '">IP-address</label></div>';
										foreach ( $frontend_params['nameservers'] as $value ) {
											$ns     = sanitize_text_field( $value['ns'] );
											$ip     = sanitize_text_field( $value['ip'] );
											$ns_ip .= '<div class="column small-6 medium-12 large-6">' . esc_html( $ns ) . '</div>
														<div class="column small-6 medium-12 large-6">' . esc_html( $ip ) . '</div>';
										}
										$ns_ip .= '</div>';
									}

									if ( $has_test_digests ) {
										foreach ( $frontend_params['ds_info'] as $value ) {
											$keytag    = sanitize_text_field( $value['keytag'] );
											$digtype   = sanitize_text_field( $value['digtype'] );
											$algorithm = sanitize_text_field( $value['algorithm'] );
											$digest    = sanitize_text_field( $value['digest'] );

											$digests .= '<div class="row small-font"><div class="column shrink"><label class="label ' . $div_tab_area_color . '">Keytag</label>' . esc_html( $keytag ) . '</div>
														<div class="column shrink"><label class="label ' . $div_tab_area_color . '">Digtype</label>' . esc_html( $this->option_name( $digtype, 'field_digest_type' ) ) . '</div>
														<div class="column shrink"><label class="label ' . $div_tab_area_color . '">Algorithm</label>' . esc_html( $this->option_name( $algorithm, 'field_algorithm' ) ) . '</div>
														<div class="column shrink"><label class="label ' . $div_tab_area_color . '">Digest</label>' . esc_html( $digest ) . '</div></div>';
										}
									}

									$ipv4 = isset( $frontend_params['ipv4'] ) && true == $frontend_params['ipv4'] ? 'IPv4' : '';
									$ipv6 = isset( $frontend_params['ipv6'] ) && true == $frontend_params['ipv6'] ? 'IPv6' : '';
									$and  = '';

									if ( ! empty( $ipv4 ) && ! empty( $ipv6 ) ) {
										$and = ' ' . __( 'and', 'zm_text' ) . ' ';
									}
									echo '<ul><li>' . __( 'Checked with:', 'zm_text' ) . ' ' . $ipv4 . $and . $ipv6 . '</li>';

									$un_delegated = empty( $is_test_undelegated ) ? '' : __( 'Pre-delegated domain check', 'zm_text' ) . ' <span class="hide-if-no-js expand-link">' . __( 'with', 'zm_text' ) . '...</span>';
									if ( '' !== $un_delegated ) {
										echo '<li>' . $un_delegated . '<span class="show-if-no-js js-expand-params">' . $ns_ip . $digests . '</span></li></ul>';
									} else {
										echo '</ul>';
									}
									?>
								</div>
								<p class="result-text-three">
									<?php _e( 'Link to this result is:', 'zm_text' ); ?>
									<br>
									<a href="/?resultid=<?php echo $testid; ?>">
										<?php echo $_SERVER['SERVER_NAME']; ?>/?resultid=<?php echo esc_attr( $testid ); ?>
									</a>

									<?php
									// display a link that holds a copyable url if user has javascript
									$protocol = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] || 443 == $_SERVER['SERVER_PORT'] ) ? 'https://' : 'http://';?>
									<span class="hide-if-no-js">
										&nbsp;|&nbsp;
										<span class="copy-link" data-clipboard-text="<?php echo esc_attr( $protocol ); ?><?php echo esc_attr( $_SERVER['SERVER_NAME'] ); ?>/?resultid=<?php echo esc_attr( $testid ); ?>">
											<?php _e( 'Copy', 'zm_text' ); ?>
										</span>
									</span>
								</p>

							</div>
							<div class="column hide-for-small-only medium-5">
								<?php
								$img = '<svg id="streetlights" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 540.5 360">
										<defs><style>.cls-1{opacity:0.1;}.cls-2-' . esc_attr( $testid ) . '{fill:#575756;} .red-' . esc_attr( $testid ) . '{fill:#' . $red_fill . ';} .orange-' . esc_attr( $testid ) . '{fill:#' . $orange_fill . ';} .green-' . esc_attr( $testid ) . '{fill:#' . $green_fill . ';}</style></defs>
										<title>Zonemaster lights</title><polygon class="cls-1" points="310 143.33 166.67 0 0 307.68 52.32 360 540.5 360 310 143.33"/><rect width="166.67" height="307.68"/><path class="cls-2-' . esc_attr( $testid ) . ' red-' . esc_attr( $testid ) . '" d="M83.33,103.18a40.5,40.5,0,1,0-40.5-40.5,40.5,40.5,0,0,0,40.5,40.5"/><path class="cls-2-' . esc_attr( $testid ) . ' orange-' . esc_attr( $testid ) . '" d="M83.33,194.34a40.5,40.5,0,1,0-40.5-40.5,40.5,40.5,0,0,0,40.5,40.5"/><path class="cls-2-' . esc_attr( $testid ) . ' green-' . esc_attr( $testid ) . '" d="M83.33,285.5A40.5,40.5,0,1,0,42.83,245a40.5,40.5,0,0,0,40.5,40.5"/></svg>';
								?>
									<div class="stoplights-img">
										<?php echo $img;
										?>

									</div>

							</div>
						</div>

						<?php
						// Tab active helper then no-js
						switch ( $tab ) {
							case 'tet':
								$tab_earlier_tests_class = ' is-active';
								$tab_earlier_tests_aria  = ' aria-selected="true"';
								break;

							default:
								$tab_basic_result_class  = ' is-active';
								$tab_basic_result_aria   = ' aria-selected="true"';
								$tab_earlier_tests_class = ' ';
								$tab_earlier_tests_aria  = ' ';
								break;
						}

						// Help foundation identify the tabs with requested testid
						// Used then fetching single tests inline on tab "Earlier tests"
						if ( $oldtest_inline ) {
							$oldtest_id = $testid;
						} else {
							$oldtest_id = '';
						}
						?>
						<div class="row">

							<ul class="tabs white column" data-tabs id="result-tabs<?php echo $oldtest_id; ?>">

								<li class="tabs-title white<?php echo $tab_basic_result_class; ?>">
									<a href="/?resultid=<?php echo $testid; ?>&tab=tbr#basic_result<?php echo $oldtest_id; ?>"<?php echo $tab_basic_result_aria; ?>>
										<?php _e( 'Result', 'zm_text' ); ?>
									</a>
								</li>

								<?php
								// if the test is not shown inline in tab "Earlier tests" (with javascript click)
								if ( ! $oldtest_inline ) {
								?>
									<li class="tabs-title white<?php echo $tab_earlier_tests_class; ?>">
										<a href="/?resultid=<?php echo $testid; ?>&tab=tet#earlier_tests"<?php echo $tab_earlier_tests_aria; ?>>
											<?php _e( 'Earlier tests of ', 'zm_text' ); ?> <?php echo $label_output; ?>
										</a>
									</li>
								<?php
								}
								?>
							</ul>
						</div>
					</div>
					<div class="row">
						<?php // identify tabs content so what foundation can ( try ) activate them correctly in js ?>
						<div class="tabs-content white column" data-tabs-content="result-tabs<?php echo $oldtest_id; ?>">

							<div class="tabs-panel<?php echo $tab_basic_result_class; ?>" id="basic_result<?php echo $oldtest_id; ?>" >

						<?php
						$module_group = '';
						$tablerow     = '';

						foreach ( $testresult as $result ) {
							$level   = sanitize_text_field( $result['level'] );
							$message = sanitize_text_field( $result['message'] );

							// Add space after commas
							$comma   = '/[,]/';
							$message = preg_replace( $comma, ', ', $message );
							// Space in MX-records
							$mx      = '/(\/MX=)/';
							$message = preg_replace( $mx, '/ MX=', $message );

							// Group results that arrive in order
							if ( $module_group !== $result['module'] ) {
								$module_group = sanitize_text_field( $result['module'] );
								$tablerow    .= '<tr><th colspan="3" class="text-left" id="' . esc_attr( $oldtest_id ) . '_' . esc_attr( $module_group ) . '">' . esc_html( $module_group ) . '</th></tr>';

								// Start fresh for new module-group
								unset( $module_group_array );
							}

							switch ( $level ) {
								case 'CRITICAL':
									$levelclass = 'alert callout';
									$tablerow  .= '<tr class="' . esc_attr( $levelclass ) . '">
																			<td class="break-word">' . esc_html( $message ) . '</td>
																			<td><span class="alert badge">&nbsp;</span></td>
																			<td>' . __( 'Critical error!', 'zm_text' ) . '</td>
																		</tr>';

									$module_group_array[]          = esc_attr( $level );
									$button_array[ $module_group ] = $module_group_array;

									break;

								case 'ERROR':
									$levelclass = 'alert callout';
									$tablerow  .= '<tr class="' . esc_attr( $levelclass ) . '">
																			<td class="break-word">' . esc_html( $message ) . '</td>
																			<td><span class="alert badge">&nbsp;</span></td>
																			<td>' . __( 'Error!', 'zm_text' ) . '</td>
																		</tr>';

									$module_group_array[]          = esc_attr( $level );
									$module_group_array[]          = [ 'id' => esc_attr( $oldtest_id ) . '_' . esc_attr( $module_group ) ];
									$button_array[ $module_group ] = $module_group_array;

									break;

								case 'WARNING':
									$levelclass                    = 'warning callout';
									$tablerow                     .= '<tr class="' . esc_attr( $levelclass ) . '">
																			<td class="break-word">' . esc_html( $message ) . '</td>
																			<td><span class="warning badge">&nbsp;</span></td>
																			<td>' . __( 'Warning!', 'zm_text' ) . '</td>
																		</tr>';
									$module_group_array[]          = esc_attr( $level );
									$module_group_array[]          = [ 'id' => esc_attr( $oldtest_id ) . '_' . esc_attr( $module_group ) ];
									$button_array[ $module_group ] = $module_group_array;

									break;

								case 'NOTICE':
									$levelclass                    = 'primary callout';
									$tablerow                     .= '<tr class="' . esc_attr( $levelclass ) . '">
																			<td class="break-word">' . esc_html( $message ) . '</td>
																			<td><span class="success badge">&nbsp;</span></td>
																			<td>' . __( 'OK', 'zm_text' ) . '</td>
																		</tr>';
									$module_group_array[]          = esc_attr( $level );
									$module_group_array[]          = [ 'id' => esc_attr( $oldtest_id ) . '_' . esc_attr( $module_group ) ];
									$button_array[ $module_group ] = $module_group_array;

									break;

								case 'INFO':
									$tablerow                     .= '<tr>
																			<td class="break-word">' . esc_html( $message ) . '</td>
																			<td><span class="success badge">&nbsp;</span></td>
																			<td>' . __( 'OK', 'zm_text' ) . '</td>
																		</tr>';
									$module_group_array[]          = esc_attr( $level );
									$module_group_array[]          = [ 'id' => esc_attr( $oldtest_id ) . '_' . esc_attr( $module_group ) ];
									$button_array[ $module_group ] = $module_group_array;

									break;

								default:
									$tablerow .= '<tr>
													<td class="break-word">' . esc_html( $message ) . '</td>
													<td></td>
													<td></td>
												</tr>';
									break;
							}
						}

						?>
								<?php
								// Print the tests different part as navigational buttons with correct coloring
								// Make the poor buttons stick then scrolling
								// Foundation don't play nicely with dynamically added sticky containers at the moment,
								// we only add sticky button nav at the current test
								if ( ! $oldtest_inline ) {
									$js_scroll        = 'js-scroll';
									$sticky_class     = 'sticky';
									$sticky_container = 'data-sticky-container';
									$sticky_data      = 'data-sticky';
									$up_button        = '<a class="button small primary top_link" href="#">' . __( 'Back to top', 'zm_text' ) . '</a>';
								} else {
									$js_scroll        = 'js-scroll in-reveal';
									$sticky_class     = '';
									$sticky_container = '';
									$sticky_data      = '';
								}

								?>
								<div class="js-stick" <?php echo esc_attr( $sticky_container ); ?> >
									<div data-options="stickyOn: small" data-top-anchor="basic_result" class="<?php echo esc_attr( $sticky_class ); ?>" <?php echo esc_attr( $sticky_data ); ?>>
										<?php
										foreach ( $button_array as $button_text => $btn_array ) {
											$badge_counts = [
												'CRITICAL' => 0,
												'ERROR'    => 0,
												'WARNING'  => 0,
												'NOTICE'   => 0,
												'INFO'     => 0,
											];
											foreach ( $btn_array as $btn_index => $btn_value ) {
												if ( ! is_array( $btn_value ) ) {
													$badge_counts[ $btn_value ]++;
												}
											}
											if ( in_array( 'CRITICAL', $btn_array, true ) || in_array( 'ERROR', $btn_array, true ) ) {
												$alert_counts = absint( $badge_counts['CRITICAL'] + $badge_counts['ERROR'] + $badge_counts['WARNING'] );

												echo '<span class="button-with-badge"><a class="button small alert ' . esc_attr( $js_scroll ) . '" href="#' . $btn_array[1]['id'] . '">' . esc_html( $button_text ) . '</a><span class="badge alert top-right">' . esc_html( $alert_counts ) . '</span></span>';
											} elseif ( in_array( 'WARNING', $btn_array, true ) ) {
												$warning_counts = absint( $badge_counts['WARNING'] );

												echo '<span class="button-with-badge"><a class="button small warning ' . esc_attr( $js_scroll ) . '" href="#' . $btn_array[1]['id'] . '">' . esc_html( $button_text ) . '</a><span class="badge warning top-right">' . esc_html( $warning_counts ) . '</span></span>';
											} else {
												$notice_counts = absint( $badge_counts['NOTICE'] );

												if ( $notice_counts > 0 ) {
													$notice_badge = '<span class="badge primary top-right">' . esc_html( $notice_counts ) . '</span>';
												} else {
													$notice_badge = '';
												}

												echo '<span class="button-with-badge"><a class="button small success ' . esc_attr( $js_scroll ) . '" href="#' . esc_attr( $btn_array[1]['id'] ) . '">' . esc_html( $button_text ) . '</a>' . $notice_badge . '</span>';
											}
										}
										echo $up_button;
										?>
									</div>
								</div>

								<table id="sticky-table">
									<tbody>
										<?php echo $tablerow; ?>
									</tbody>
								</table>



							</div>
							<?php // end basic tab panel

							// if the test is not shown inline in tab "Earlier tests" (with javascript click)
							if ( ! $oldtest_inline ) {
								$reveal_modal = ''; ?>
								<div class="tabs-panel<?php echo $tab_earlier_tests_class; ?>" id="earlier_tests">

									<table>
										<thead>
											<tr>
												<th><?php _e( 'Creation time', 'zm_text' ); ?></th>
												<th></th>
												<th><?php _e( 'Overall result', 'zm_text' ); ?></th>
											</tr>
										</thead>
										<tbody>
								<?php
								foreach ( $status['result'] as $result ) {

									$id             = sanitize_text_field( $result['id'] );
									$creation_time  = sanitize_text_field( $result['creation_time'] );
									$overall_result = sanitize_text_field( $result['overall_result'] );

									if ( $id === $testid ) {
										$testlink = esc_html( $creation_time ) . ' <em>' . __( '( This is current test.)', 'zm_text' ) . '</em>';
									} else {
										$testlink = '<a href="/?resultid=' . esc_attr( $id ) . '" class="js-expand-testresult" data-testid="' . esc_attr( $id ) . '">' . esc_html( $creation_time ) . '</a>';
									}

									$reveal_modal .= '<div id="reveal_' . esc_attr( $id ) . '" data-v-offset="50" class="large reveal ';

									switch ( $overall_result ) {
										case 'warning':
											$reveal_modal .= 'warning"></div>';
											echo '<tr>
														<td>' . $testlink . '</td>
														<td><span class="warning badge">&nbsp;</span></td>
														<td>' . __( 'Contains warnings!', 'zm_text' ) . '</td>
													</tr>';
											break;

										case 'critical':
											$reveal_modal .= 'alert"></div>';
											echo '<tr>
														<td>' . $testlink . '</td>
														<td><span class="alert badge">&nbsp;</span></td>
														<td>' . __( 'Contains critical errors!', 'zm_text' ) . '</td>
													</tr>';
											break;

										case 'error':
											$reveal_modal .= 'alert"></div>';
											echo '<tr>
														<td>' . $testlink . '</td>
														<td><span class="alert badge">&nbsp;</span></td>
														<td>' . __( 'Contains errors!', 'zm_text' ) . '</td>
													</tr>';
											break;

										case 'INFO':
											$reveal_modal .= 'success"></div>';
											echo '<tr>
															<td>' . $testlink . '</td>
															<td><span class="success badge">&nbsp;</span></td>
															<td>' . __( 'OK', 'zm_text' ) . '</td>
														</tr>';
											break;

										case 'notice':
											$reveal_modal .= 'success"></div>';
											echo '<tr>
														<td>' . $testlink . '</td>
														<td><span class="success badge">&nbsp;</span></td>
														<td>' . __( 'OK', 'zm_text' ) . '</td>
													</tr>';
											break;

										default:
											$reveal_modal .= 'primary"></div>';
											echo '<tr>
														<td>' . $testlink . '</td>
														<td></td>
														<td>' . __( 'Sorry, we could not determ test results', 'zm_text' ) . '</td>
													</tr>';
											break;
									} // end switch
								} // end foreeach
									?>
										</tbody>
									</table>
									<?php echo $reveal_modal; ?>
								</div>
								<?php
							} // end oldtest_inline
							?>
						</div><?php // tabs-content ?>
					</div>
					<div class="div-tab-area footer <?php echo $div_tab_area_color; ?>">
						<div class="row">
							<div class="column">
								<?php
								// this should be engine version with any luck
								echo esc_html( $testresult[0]['message'] ); ?>
							</div>
							<div class="column align-self-center">

							</div>
						</div>
					</div>
				</div>

			<?php
			// if fqdn ( a test is available )
			} else {
				// no test could be identified
				echo '<p class="callout warning">' . __( 'The test you´re looking for is either not yet ready or has been deleted.', 'zm_text' ) . ' ' . __( '(Or Backend API might be offline )', 'zm_text' ) . '</p>';
			}
		} // else print results
	} // end function get_single_test

} // Class Zonemaster

// class name outside class
$zm = Zonemaster::get_instance();
