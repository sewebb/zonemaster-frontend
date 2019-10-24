<?php
/**
 * Template Name: Start page with search
 *
 * @package zonemaster-iis-frontend
 *
 */

get_header(); ?>
<?php
// Set defaults for ip-check
$is_ipv4_checked            = 'checked';
$is_ipv6_checked            = 'checked';
$nameservers_html           = '';
$digests_html               = '';
$nameservers_array          = array();
$ds_digest_pairs_array      = array();
$pre_delegated_domain_check = false;
$do_test_progress           = false;
$tab_test_progress_aria     = '';
$tab_pre_delegated_aria     = '';
$tab_pre_delegated_class    = '';
$tab_domain_check_aria      = '';
$tab_domain_check_class     = '';
$requested_test_id          = '';
$get_single_test_tab        = '';
$zone_tld                   = '';

$request_tab = isset( $_REQUEST['tab'] ) ? sanitize_text_field( $_REQUEST['tab'] ) : '';
// no-js params
if ( '' !== $request_tab ) {
	switch ( $request_tab ) {
		case 'tpd':
			$pre_delegated_domain_check = true;
			break;

		case 'ttp':
			$do_test_progress = true;
			break;

		case 'tar':
			$get_single_test_tab = 'tar';
			break;

		case 'tet':
			$get_single_test_tab = 'tet';
			break;

		default:
			$get_single_test_tab = 'tbr';
			break;
	}
}

// if we should show an old test
$requested_test_id   = isset( $_REQUEST['resultid'] ) ? $zm->regexp_check( $_REQUEST['resultid'] ) : '';
$post_zone_tld       = isset( $_POST['zone_tld'] ) ? sanitize_text_field( $_POST['zone_tld'] ) : '';
$post_tld_zonemaster = isset( $_POST['tld_zonemaster'] ) ? sanitize_text_field( $_POST['tld_zonemaster'] ) : '';

// Check that post data is comming from the form before moving on
if ( '' !== $post_zone_tld && ( ( '' !== $post_tld_zonemaster && wp_verify_nonce( $post_tld_zonemaster, 'check_tld_zone' ) ) ) ) {
	$zone_tld = trim( $post_zone_tld );

	// We only want to start if there is a zone_tld
	if ( '' !== $zone_tld ) {
		$zm = Zonemaster::get_instance();

		$verify_zone_tld = $zm->verify_zone_tld( $zone_tld );

		// Return error message if we don't like the posted data - verify_zone_tld will be false
		if ( ! $verify_zone_tld ) {
			echo '<div class="row"><div class="small-12 medium-8 columns" id="errorurl"><p class="callout warning fade-in fast">' . __( "Sorry, we can't test this.", 'zm_text' ) . '</p></div></div>';
		} else {
			$check_ip_v4 = $zm->regexp_check( $_POST['check_ip_v4'] );
			$check_ip_v6 = $zm->regexp_check( $_POST['check_ip_v6'] );

			if ( '1' === $check_ip_v4 ) {
				$is_ipv4_checked = 'checked';
				$check_ip_v4     = true;
			} else {
				$is_ipv4_checked = '';
				$check_ip_v4     = false;
			}

			if ( '1' === $check_ip_v6 ) {
				$is_ipv6_checked = 'checked';
				$check_ip_v6     = true;
			} else {
				$is_ipv6_checked = '';
				$check_ip_v6     = false;
			}
			// Make sure we test at least one ip-version if both checkboxes has been unchecked (no-js )
			if ( 0 === $check_ip_v4 + $check_ip_v6 ) {
				$check_ip_v4     = true;
				$check_ip_v6     = false;
				$is_ipv4_checked = 'checked';
			}

			// If no-js is active in browser, check the different submit-buttons available
			$get_data_from_parent_zone = isset( $_POST['get_data_from_parent_zone'] ) ? true : false;
			$add_no_js_ns_field        = isset( $_POST['add_ns_field'] ) ? true : false;
			$del_no_js_ns_field        = isset( $_POST['del_ns_field'] ) ? true : false;
			$check_ip_address          = isset( $_POST['check_ip_address'] ) ? true : false;
			$add_no_js_digest_field    = isset( $_POST['add_digest_field'] ) ? true : false;
			$del_no_js_digest_field    = isset( $_POST['del_digest_field'] ) ? true : false;

			$post_field_ns     = isset( $_POST['field_ns'] ) ? $zm->sanitize_array( $_POST['field_ns'] ) : array();
			$post_del_ns_field = isset( $_POST['del_ns_field'] ) ? sanitize_text_field( $_POST['del_ns_field'] ) : '';

			$post_field_key_tag    = isset( $_POST['field_key_tag'] ) ? $zm->sanitize_array( $_POST['field_key_tag'] ) : array();
			$post_del_digest_field = isset( $_POST['del_digest_field'] ) ? sanitize_text_field( $_POST['del_digest_field'] ) : '';

			$key = -1;
			if ( ! empty( $post_field_ns ) ) {
				foreach ( $post_field_ns as $key => $ns_field ) {
					$ns_field = $zm->regexp_check( $ns_field );

					if ( ! ( $del_no_js_ns_field && $key == $post_del_ns_field ) && ( '' !== $ns_field ) ) {
						$ip_field = $zm->regexp_check( $_POST['field_ip'][ $key ] );
						// Frontend, lighter check if valid IP-address
						if ( '' !== trim( $ip_field ) ) {
							$re = '/^(([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})|([0-9A-Fa-f]{1,4}:[0-9A-Fa-f:]{1,}(:[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})?)|([0-9A-Fa-f]{1,4}::[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}))$/';

							// example "bad" ip-address:  "ab::10.1.2";
							if ( ! preg_match( $re, $ip_field, $matches ) ) {
								$ip_field = 'Not valid: ' . $ip_field;
							}
						}

						// do html for fields
						$nameservers_html .= $zm->nameservers_html( $key, $ns_field, $ip_field );
						// prepare array for test engine
						$nameservers_array[] = [
							'ns' => $ns_field,
							'ip' => $ip_field,
						];

						// Correct tab, no-js
						$pre_delegated_domain_check = true;
					}
				}
			}

			$ds_key = -1;
			if ( ! empty( $post_field_key_tag ) ) {
				foreach ( $post_field_key_tag as $ds_key => $key_tag_field ) {
					$key_tag_field = $zm->regexp_check( $key_tag_field );

					if ( ! ( $del_no_js_digest_field && $ds_key == $post_del_digest_field ) && ( '' !== $key_tag_field ) ) {
						$key_tag_field     = $zm->regexp_check( $_POST['field_key_tag'][ $ds_key ] );
						$algorithm_field   = $zm->regexp_check( $_POST['field_algorithm'][ $ds_key ] );
						$digest_type_field = $zm->regexp_check( $_POST['field_digest_type'][ $ds_key ] );
						$digest_field      = $zm->regexp_check( $_POST['field_digest'][ $ds_key ] );

						// do html for fields
						$digests_html .= $zm->digests_html( $ds_key, $key_tag_field, $algorithm_field, $digest_type_field, $digest_field );
						// prepare array for test engine
						$ds_digest_pairs_array[] = [
							'keytag'    => intval( $key_tag_field ),
							'algorithm' => intval( $algorithm_field ),
							'digtype'   => intval( $digest_type_field ),
							'digest'    => $digest_field,
						];

						// Correct tab
						$pre_delegated_domain_check = true;
					}
				}
			}

			// User (no-js ) request a new empty set of nameserver fields
			if ( $add_no_js_ns_field ) {
				$key++;
				$nameservers_html .= $zm->nameservers_html( $key );
			}

			// User (no-js ) request a new empty set of digest fields
			if ( $add_no_js_digest_field ) {
				$ds_key++;
				$digests_html .= $zm->digests_html( $ds_key );
			}

			// User (no-js ) has requested to get domain parent nameservers
			// This removes previously entered data
			if ( $get_data_from_parent_zone ) {
				list( $nameservers_html, $digests_html ) = $zm->get_data_from_parent_zone( $verify_zone_tld );
			}

			// If NOt one of the submitbuttons NOT intended for starting test (no-js )
			if ( ! $get_data_from_parent_zone && ! $add_no_js_ns_field && ! $del_no_js_ns_field && ! $check_ip_address && ! $add_no_js_digest_field && ! $del_no_js_digest_field ) {
				$params = [
					'client_id'      => 'IIS Zonemaster frontend',
					'domain'         => $verify_zone_tld,
					'profile'        => 'default',
					'client_version' => '1',
					'ipv6'           => $check_ip_v6,
					'ipv4'           => $check_ip_v4,
					'nameservers'    => $nameservers_array,
					'ds_info'        => $ds_digest_pairs_array,
				];
				$params = $zm->sanitize_array( $params );

				if ( empty( $params['nameservers'] ) ) {
					unset( $params['nameservers'] );
				}
				if ( empty( $params['ds_info'] ) ) {
					unset( $params['ds_info'] );
				}
				if ( empty( $params['ipv6'] ) ) {
					unset( $params['ipv6'] );
				}
				if ( empty( $params['ipv4'] ) ) {
					unset( $params['ipv4'] );
				}
				$response = $zm->verify_and_curl_request(
					[
						'method'            => 'start_domain_test',
						'params'            => $params,
						'create_transient'  => true,
						'transient_seconds' => sanitize_text_field( $zm->settings( 'transient_start_test' ) ),
					]
				);

				$error_in_response = false;
				// If something went wrong
				if ( ! $response ) {
					echo '<div class="row"><div class="small-12 medium-6 columns" id="errorurl"><p class="callout warning fade-in">' . __( 'Sorry, we can´t test right now, API seems to be offline.', 'zm_text' ) . '</p></div></div>';
				} else {
					// Backend answers with 200 ok even if there is an error. Check for it
					if ( isset( $response['error'] ) || ( isset( $response['result']['status'] ) && 'nok' === $response['result']['status'] ) ) {
						$err_message       = isset( $response['result']['message'] ) ? $response['result']['message'] : '';
						$error_in_response = true;

						echo '<div class="row"><div class="small-12 columns" id="errorurl"><p class="callout warning fade-in">' . __( 'Checking is not possible.', 'zm_text' ) . ' ' . __( 'API backend reports an error.', 'zm_text' ) . '<br>' . esc_html( $err_message ) . '</p></div></div>';
					} else {
						// Hurray, we probably got an id back to continue on with checking test progress
						$testid           = sanitize_text_field( $response['result'] );
						$do_test_progress = true;
					}
				}

				// If we actually got an answer (duh, see above )
				if ( $response && ! $error_in_response ) {
					// Return basic form stuff to use if client has javascript acitivated
					echo '<div class="hide"><div id="apianswer">' . esc_html( $testid ) . '</div><div id="checkedurl">' . esc_html( $zone_tld ) . '</div><div id="usedipv6">' . esc_html( $check_ip_v6 ) . '</div><div id="usedipv4">' . esc_html( $check_ip_v4 ) . '</div></div>';
				}
			} // end if we have used no-js to add or remove stuff
		} // end verify zone
	} // end if there is zone_tld input data
} // end if zone_tld exits and is nouncad

// *** Tab active helper, mostly for then no-js
// we are commensing test or looking at an old test
$hide_startpage_content = '';
if ( $do_test_progress || '' !== $requested_test_id ) {
	$tab_test_progress_class = ' is-active';
	$tab_test_progress_aria  = ' aria-selected="true"';
	$hide_startpage_content  = ' hide';
} elseif ( $pre_delegated_domain_check ) {
	$tab_pre_delegated_class = ' is-active';
	$tab_pre_delegated_aria  = ' aria-selected="true"';

	$tab_test_progress_class = ' hide';
} else {
	// just "normal"/first tab
	$tab_domain_check_class = ' is-active';
	$tab_domain_check_aria  = ' aria-selected="true"';

	$tab_test_progress_class = ' hide';
}

// All tabs get a "double" link, q-string for no-js and hash for foundation tabs to work
?>
<?php // Top most tabs and navigation meny from WP ?>
<div class="row align-center align-middle">
	<div class="columns">
		<ul class="tabs white" data-tabs id="test-input-tabs">
			<li class="tabs-title<?php echo $tab_domain_check_class; ?>">
				<a href="/?tab=tdc#domain_check"<?php echo esc_attr( $tab_domain_check_aria ); ?>><?php _e( 'Domain check', 'zm_text' ); ?></a>
			</li>
			<li class="tabs-title<?php echo $tab_pre_delegated_class; ?>">
				<a href="/?tab=tpd#pre_delegated"<?php echo esc_attr( $tab_pre_delegated_aria ); ?>><?php _e( 'Pre-delegated domain check', 'zm_text' ); ?></a>
			</li>
			<li class="tabs-title<?php echo $tab_test_progress_class; ?>" id="test-result-tab">
				<a href="/?resultid=<?php echo $requested_test_id; ?>&tab=ttp#test_progress" class="js-tab-test-progress"<?php echo esc_attr( $tab_test_progress_aria ); ?>><?php _e( 'Test result', 'zm_text' ); ?></a>
			</li>
		</ul>
	</div>
	<?php // WP-menu ?>
	<div class="columns shrink">
		<?php get_template_part( 'menuparts/nav', 'title-bar' ); ?>
	</div>
</div>

<?php // Contents of tab divided in panels ?>
<div class="tabs-content" data-tabs-content="test-input-tabs">

	<div class="tabs-panel<?php echo $tab_domain_check_class; ?> full-width primary" id="domain_check">
		<div class="row align-center">

			<form id="form_domain_check" method="post" class="test-form js-domain-check small-12 medium-11 large-10 xlarge-8 xxlarge-7 column">
				<p class="text-center lead">
					<?php _e( 'Test your DNS server today. ', 'zm_text' ); ?>
				</p>

				<div class="text-center">
					<?php echo $zm->ip_checkboxes( 'domain_check', $is_ipv4_checked, $is_ipv6_checked ); ?>
				</div>

				<div class="input-group large">
					<input type="text" class="input-group-field" name="zone_tld" id="zone_tld_domain_check" placeholder="<?php _e( 'Input zone.tld', 'zm_text' ); ?>" required value="<?php echo esc_attr( $zone_tld ); ?>">
					<div class="input-group-button">
						<input type="submit" class="button submit-zone" id="submit_zone_domain_check" value="<?php _e( 'Test Now', 'zm_text' ); ?>">
					</div>
					<?php
					// check that form is posted from here
					wp_nonce_field( 'check_tld_zone', 'tld_zonemaster' ); ?>
				</div>

			</form>
		</div>

	</div>

	<div class="tabs-panel<?php echo esc_attr( $tab_pre_delegated_class ); ?> full-width primary" id="pre_delegated">

		<div class="row align-center">

			<form id="form_pre_delegated" method="post" class="test-form js-pre-delegated-check small-12 medium-11 large-10 xlarge-8 xxlarge-7 column">

				<p class="text-center lead">
					<?php _e( 'Test your DNS server today. ', 'zm_text' ); ?>
					<span class="hide-if-no-js"><a data-open="what-is-predelegated">
						<span class="js-header-pre-faq"></span>
					</a></span>
				</p>

				<p class="text-center js-pre-zone-required"><?php _e( 'Input zone.tld', 'zm_text' ); ?></p>

				<div class="input-group large">
					<input type="text" name="zone_tld" id="zone_tld_pre_delegated" placeholder="zone.tld" required value="<?php echo esc_attr( $zone_tld ); ?>">
				</div>

				<?php
				// check that form is posted from here
				wp_nonce_field( 'check_tld_zone', 'tld_zonemaster' );
				?>

				<div class="text-center">
					<?php // default submit button for enter in zone-field - start test ?>
					<button type="submit" class="hide"></button>
					<button type="submit" name="get_data_from_parent_zone" class="success button js-get-data-from-parent-zone">
						<?php _e( 'Fetch data from parent zone', 'zm_text' ); ?>
					</button>
				</div>

				<div class="js-input-nameserver-wrap">

						<p class="text-center">
							<?php _e( 'Nameservers (up to 30)', 'zm_text' ); ?>
						</p>

					<?php
						// Where could have been a submit done (no-js ), then we will print thoose filled nameserver fields
					if ( '' !== $nameservers_html ) {
						echo $nameservers_html;
					} else {
						echo $zm->nameservers_html( '0' );
					}
					?>
				</div>

				<div>
					<button type="submit" name="add_ns_field" class="expanded button js-add-nameserver-button">
						<?php _e( '+ Add more nameservers', 'zm_text' ); ?>
					</button>
				</div>

				<p class="text-center">
					<?php _e( 'Digests', 'zm_text' ); ?>
				</p>
				<div class="js-input-ds-list">
			<?php
				// Where could have been a submit done (no-js ), then we will print thoose filled digest fields
				// if we would want a row to start with (right now it starts empty ) - add else { echo $zm->digests_html( '0' ); }
			if ( '' !== $digests_html ) {
				echo $digests_html;
			}
				?>
				</div>

				<div>
					<button type="submit" name="add_digest_field" class="expanded button js-digest-button">
						<?php _e( '+ Add row for digest', 'zm_text' ); ?>
					</button>
				</div>

				<div class="text-center">
					<?php
					// prints checkboxes
					echo $zm->ip_checkboxes( 'predelegated', $is_ipv4_checked, $is_ipv6_checked ); ?>
				</div>

				<div class="large row align-center">
					<div class="columns medium-6">
						<input type="submit" class="button expanded submit-zone" id="submit_zone_pre_delegated" value="<?php _e( 'Test Now', 'zm_text' ); ?>">
					</div>
				</div>

				<?php
				// This adds explantion for tab predlegated if text has been added in API-settings page
				// Get ACF repeater field or array from class-zonemaster-settings.php
				$what_is_pre_delegated_domain_check = $zm->settings( 'what_is_pre_delegated_domain_check' );

				if ( $what_is_pre_delegated_domain_check ) {
					// Don't produce PHP Fatal error if Polylang hasn't been installed
					if ( function_exists( 'pll_current_language' ) ) {
						$selected_lang = pll_current_language();
					}
					if ( ! $selected_lang ) {
						$selected_lang = 'en';
					}

					echo '<div class="callout show-if-no-js js-append-to-what-is-predelegated">';

					foreach ( $what_is_pre_delegated_domain_check as $faq_text ) {
						$lang = sanitize_text_field( $faq_text['language'] );
						if ( $selected_lang === $lang ) {
							$faq_page = absint( $faq_text['page_name'] );
						}
					}
					// This header will also be used as link if it exists
					echo '<h1 id="header-pre-faq">' . __( 'What is pre-delegated domain check?', 'zm_text' ) . '</h1>';
					$include = get_pages(
						[
							'include'   => $faq_page,
							'post_type' => 'page',
						]
					);
					// Content will also be copied to javascript modal
					$content = apply_filters( 'the_content', $include[0]->post_content );
					echo $content . '</div>';
				}
				?>
			</form>
		</div>

		<?php // "reveal" is a modal ?>
		<div class="reveal" id="what-is-predelegated" data-reveal data-reveal data-v-offset="50">
			<?php // javascript will fill this div from above div "js-append-to-what-is-predelegated" ?>
			<button class="close-button" data-close aria-label="Close modal" type="button">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	</div>

	<div class="tabs-panel full-width primary<?php echo esc_attr( $tab_test_progress_class ); ?>" id="test_progress">
		<?php
		// first submit to test engine
		if ( $do_test_progress && '' == $requested_test_id ) {
			$zm->polling_test_template( $testid, '', $verify_zone_tld, $check_ip_v4, $check_ip_v6 );
			// could be an old test och a request to poll ongoing test (then no-js )
		} elseif ( '' !== $requested_test_id ) {
			$check_progress = $zm->test_progress( $requested_test_id );

			if ( '100' === $check_progress ) {
				// test is finished
				$zm->get_single_test( $requested_test_id, $get_single_test_tab );
			} elseif ( ! $check_progress ) {
				echo '<p class="callout alert text-center">' . __( 'The test you´re trying to get  is missing.', 'zm_text' ) . ' ' . __( '(Or Backend API might be offline )', 'zm_text' ) . '</p>';
			} else {
				// test is not yet ready
				$zm->polling_test_template( $requested_test_id, $check_progress );
			}
		}
		?>
		<div class="progress-area" id="progressarea">
			<?php // progress is loaded here if js is present ?>
		</div>
		<div class="js-hide results-area" id="resultsarea">
		<?php // html (results ) from /browse/[id] are shown here if javascript is active ?>
		</div>
	</div>

</div>
<?php // used in conjuction with javascript then adding more rows of fields ?>
<div class="hide" id="nameservers_html"><?php echo $zm->nameservers_html(); ?></div><div class="hide" id="digests_html"><?php echo $zm->digests_html(); ?></div>
<?php
// This is what we want to write / show from the actual WP-page
if ( have_posts() ) {
	while ( have_posts() ) :
		the_post(); ?>
		<div class="page-wrapper">
			<div class="row start-page-wp-content align-bottom<?php echo $hide_startpage_content; ?>">
				<div class="small-12 medium-7 column">
					<?php the_content(); ?>
				</div>
				<div class="column hide-for-small-only medium-5 ">
					<?php
					$orange_fill = 'f99963';
					$red_fill    = 'ff4069';
					$green_fill  = '55c7b4';

					$img_all_lights = '<svg id="all_streetlights" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 540.5 360">
							<defs><style>.cls-1{opacity:0.1;}.cls-2-all{fill:#575756;} .red-all{fill:#' . esc_attr( $red_fill ) . ';} .orange-all{fill:#' . esc_attr( $orange_fill ) . ';} .green-all{fill:#' . esc_attr( $green_fill ) . ';}</style></defs>
							<title>Zonemaster all lights</title><polygon class="cls-1" points="310 143.33 166.67 0 0 307.68 52.32 360 540.5 360 310 143.33"/><rect width="166.67" height="307.68"/><path class="cls-2-all red-all" d="M83.33,103.18a40.5,40.5,0,1,0-40.5-40.5,40.5,40.5,0,0,0,40.5,40.5"/><path class="cls-2-all orange-all" d="M83.33,194.34a40.5,40.5,0,1,0-40.5-40.5,40.5,40.5,0,0,0,40.5,40.5"/><path class="cls-2-all green-all" d="M83.33,285.5A40.5,40.5,0,1,0,42.83,245a40.5,40.5,0,0,0,40.5,40.5"/></svg>';
					?>
					<figure class="all-stoplights-img">
						<?php echo $img_all_lights; ?>
					</figure>
				</div>
			</div>
		</div>
	<?php
	endwhile;
}
get_footer(); ?>
