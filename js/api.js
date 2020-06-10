import $ from 'jquery';

/**
 * A common problem in this file is (or rather was)
 * the use of undeclared variables. No time for refactoring
 * so just declaring myprogress here.
 */
var myprogress; // TODO: improve by storing it inside the zonalizer object

var zonalizer = {

	page: {
		main: function () {

			$(document).on({
				// to check that ip-address is correct (lighter check)
				'blur': function () {
					var this_field   = $(this),
						nameserver    = $(':input:eq(' + ($(':input').index(this_field) - 1) + ')'),
						debug         = 'no';

						debug = zmDefs.debug;
					// remove if previous errors
					$('.js-show-error').remove();

					if ( this_field.val() !== '' ) {
						var re = /^(([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})|([0-9A-Fa-f]{1,4}:[0-9A-Fa-f:]{1,}(:[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})?)|([0-9A-Fa-f]{1,4}::[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))$/;
						var teststring = this_field.val(); // Accepted (but backend will fetch it): '10.1.2.299'; // not accepted (and will generate error in backend): 'ab::10.1.2';

						if ( re.exec(teststring) === null) {
							// Add a error alert
							this_field.closest('.js-ns-container').prepend('<div class="js-show-error column small-12 text-center"><span class="label alert fade-in fast">' + zmDefs.error_ipaddress + ' ' + this_field.val() + ' </span></div>');
							this_field.select();
							if(debug === 'yes') { console.log( 'wrong ip-address : '+ this_field.val()); }

						}
					}

				}
			}, 'input.js-field-ip');

			$(document).on({
				// user requests data from parent zone
				'click': function (e) {
					e.preventDefault();
					var this_zone = $('#zone_tld_pre_delegated').val(),
						data = {
							'action': 'get_data_from_parent_zone',
							'zone_tld': this_zone,
							'nonce': $('#proxynonce').text()
						},
						debug = 'no';

					debug = zmDefs.debug;
					if(debug === 'yes') { console.log( 'Trying to get parent zone data for : '+ this_zone); }

					if ( '' == this_zone ) {
						$('.js-pre-zone-required').prepend(' <span class="label warning fade-in fast">' + zmDefs.required_text + '</span> ');
						$('#zone_tld_pre_delegated').focus();

					} else {

						$('.js-input-nameserver-wrap').prepend('<span class="label info fade-in fast js-parent-fetching">' + zmDefs.fetching_data + '</span>');

						// Post the AJAX query
						$.post('/', data, function(response) {

							$('.js-parent-fetching').remove();

							if ( response != '-1' && response != '' ) {

								var nameservers_html = $(response).find( '#nameservers_html' ).html(),
									digests_html = $(response).find( '#digests_html' ).html();

								// Remove previous fields and ...
								$('.js-input-nameserver-wrap').empty();
								$('.js-input-ds-list').empty();
								// .. add prefilled fields from backend
								$('.js-input-nameserver-wrap').append(nameservers_html);
								$('.js-input-ds-list').append(digests_html);

							} else {
								$('.js-input-nameserver-wrap').prepend('<span class="label alert fade-in fast">' + zmDefs.error_parent + '</span>');
							}

						});
					}

				}
			}, 'button.js-get-data-from-parent-zone');

			// at least one ip version test must be done. Check that not both is unchecked
			$( '.js-check-ipv4' ).click( function () {
				$( '.js-check-ipv6' ).not( ':checked' ).prop( 'checked', true );
			});
			$( '.js-check-ipv6' ).click( function () {
				$( '.js-check-ipv4' ).not( ':checked' ).prop( 'checked', true );
			});


			// add or remove fields for nameservers
			var max_fields      = 30, // maximum nameservers allowed
				wrapper         = $('.js-input-nameserver-wrap'), // Nameservers field wrapper
				add_button      = $('.js-add-nameserver-button'), // Add Nameservers button
				copy_ns_html    = $('#nameservers_html').html(), // html for duplication
				x               = 1; // initlal text box count
			add_button.click(function(e){ //on add input button click
				e.preventDefault();
				if ( x < max_fields ) {
					x++; //text box increment
					$(wrapper).append(copy_ns_html);//add input box

				} else {
					// no more fields
					$(add_button).hide();
				}
			});

			wrapper.on('click', '.js-remove-nameservers', function(e){ //user click on remove button
				e.preventDefault();

				$(this).closest('.js-ns-container').remove(); x--;
				$(add_button).show();

			});


			// add or remove fields for digests
			var ds_wrapper      = $('.js-input-ds-list'),
				ds_add_button   = $('.js-digest-button'),
				copy_ds_html    = $('#digests_html').html();

			ds_add_button.click(function(e){
				e.preventDefault();
				$(ds_wrapper).append(copy_ds_html);
			});

			ds_wrapper.on('click', '.js-remove-digests', function(e){ //user click on remove button
				e.preventDefault();
				$(this).closest('.js-digest-container').remove();
			});


			// Submit the simple domain check form
			$(document).on('submit', 'form.js-domain-check', function(e) {
				e.preventDefault();
				// empty the address field
				history.pushState('', document.title, location.pathname);
				// modify lang url
				var langurl = $('.lang-item a').attr('href');

				if ( langurl !== undefined ) {
					langurl = langurl.split("?")[0];
					$('.lang-item a').attr('href', langurl);
				}


				var $form = $( '.js-domain-check' ),
					term = $('#zone_tld_domain_check').val(),
					ipv4 = 0,
					ipv6 = 0;
				if ( $('#check_ip_v4_domain_check').is(':checked') ) {
					ipv4 = 1;
				}
				if ( $('#check_ip_v6_domain_check').is(':checked') ) {
					ipv6 = 1;
				}
				post_form(term,ipv4,ipv6,'js-domain-check');

			});

			// prevents pre-delegated form from removing ns / ip-fields
			$(document).on('keypress', 'form.js-pre-delegated-check input[type="text"], form.js-pre-delegated-check input[type="checkbox"]', function(e) {
				if (e.which == 13) {
					$(e.target);

					var $targ = $(e.target),
						debug = 'no';

					debug = zmDefs.debug;
					if(debug === 'yes') { console.log( 'Enter detected for target: '+ $targ); }

					if ( !$targ.is(':button,:submit') ) {

						var focusNext = false;
						$(document).find(':input:visible').each(function(){

							if (this === e.target) {
								focusNext = true;
							}
							else if (focusNext){
								$(this).focus();
								return false;
							}
						});

						return false;
					}
				}
			});

			// Submit the predelegated form
			$(document).on('submit', 'form.js-pre-delegated-check', function(e) {
				e.preventDefault();

				// empty the address field
				history.pushState('', document.title, location.pathname);

				// modify lang url
				var langurl = $('.lang-item a').attr('href');
				if ( langurl !== undefined ) {
					langurl = langurl.split("?")[0];
					$('.lang-item a').attr('href', langurl);
				}

				var $form = $( '.js-pre-delegated-check' ),
					term = $('#zone_tld_pre_delegated').val(),
					ipv4 = 0,
					ipv6 = 0,
					nameservers  = [],
					ip_addresses = [],
					keytag    = [],
					digtype   = [],
					digest    = [],
					algorithm = [];

				if ( $('#check_ip_v4_predelegated').is(':checked') ) {
					ipv4 = 1;
				}
				if ( $('#check_ip_v6_predelegated').is(':checked') ) {
					ipv6 = 1;
				}
				// nameserver fields
				$('.js-field-blur').each(function( index ) {
					nameservers.push($( this ).val());
				});
				$('.js-field-ip').each(function( index ) {
					ip_addresses.push($( this ).val());
				});

				// digests fields
				$('.js-key-tag').each(function( index ) {
					keytag.push($( this ).val());
				});
				$('.js-digest-type').each(function( index ) {
					digtype.push($( this ).val());
				});
				$('.js-digest').each(function( index ) {
					digest.push($( this ).val());
				});
				$('.js-algorithm').each(function( index ) {
					algorithm.push($( this ).val());
				});

				post_form(term,ipv4,ipv6,'js-pre-delegated-check',nameservers,ip_addresses,keytag,digtype,digest,algorithm);

			});

			// Links disabled during test
			$(document).on('click', 'a.js-disabled', function(e) {
				e.preventDefault();
			});

			// function used to post form data to backend
			function post_form (term, ipv4, ipv6, form_class, nameservers, ip_addresses, keytag, digtype, digest, algorithm ) {

					$( 'html,body' ).animate({ scrollTop: $( '#domain_check-label' ).offset().top}, 1000);
					$( '.fade-in.fast.warning, .fade-in.fast.alert' ).hide();

					$( '#singletest, #resultsarea, #progressarea, #errorurl' ).empty();
					$('.js-domain-check .text-center.lead, .js-pre-zone-required').append('<span class="label info fade-in fast start-test">' + zmDefs.start_test + '</span>');


					$('input, button, option').prop('disabled', true);
					$( 'a' ).not('a.js-cancel').addClass('js-disabled');

					// Send the data using post to page-start.php - yes simple :)
					var posting = $.post( '/', {
						zone_tld: term,
						check_ip_v4: ipv4,
						check_ip_v6: ipv6,
						field_ns: nameservers,
						field_ip: ip_addresses,
						field_key_tag: keytag,
						field_algorithm: algorithm,
						field_digest_type: digtype,
						field_digest: digest,
						// used for checking form nonce
						tld_zonemaster: $('#tld_zonemaster').val(),
						cache: true
					});



					// The result is displayed on page-start.php, as if we would have no-js
					posting.done(function( data ) {

						var apianswer = $( data ).find( "#apianswer" ).text(),
							proxynonce = $( data ).find( "#proxynonce" ).text(),
							errorurl = $( data ).find( '#errorurl' ),
							progressarea = $( data ).find( '#js-div-report-status' ).html();

							if(debug === 'yes') { console.log( 'errorurl: '+ errorurl); }

						// Simple error message if the test is not working
						if ( errorurl.length > 0 ) {
							$('.test-form').prepend(errorurl);
							$( '.start-test' ).remove();
							$('input, button, option').prop('disabled', false);
							$( 'a' ).removeClass('js-disabled');
							$( '#test-result-tab' ).addClass( 'hide' );
						}

						if ( apianswer ) {

							var interval,
								id               = apianswer,
								polling_interval = zmDefs.polling_interval,
								waitingOnResult  = zmDefs.waitingOnResult,
								current_language = zmDefs.current_language,
								debug            = 'no';

							debug = zmDefs.debug;
							$( '.js-hide' ).removeClass( 'js-hide' );
							$( '#singletest, #resultsarea, #progressarea, #errorurl' ).empty();
							$( '#test-result-tab, #test_progress' ).removeClass( 'hide' );
							$( '#test-result-tab' ).trigger( "click" );
							$( '#test-result-tab a' ).text( zmDefs.start_test );
							$( '#progressarea' ).html(progressarea);
							$( '.start-test' ).remove();

							var temp,
								starttest = true;
							//check status
							var urltocheck = '/?action=proxy&method=test_progress&test_id=' + id + '&nonce=' + proxynonce;
							function checkstatus(urltocheck,callback){
								starttest = false;
								temp = $.getJSON(urltocheck, function (data) {
									callback(data,urltocheck);
								}).done(function(data){
									// set the last request to null then done so we can start a new
									temp = null;
								});
							}
							//get our JSON
							var interval = null;
							interval = setInterval(function() {
										waitandgetresults(urltocheck);
									},polling_interval);


							function waitandgetresults(urltocheck) {
								// if slow server, don't overload with requests about test progress.
								if ( temp && starttest === false ) {
									return;
								}

								checkstatus(urltocheck,function (data) {

									if ( data != '-1' ) {
										// when we get our data, evaluate
										var dataResult = parseInt(data.result);

										if ( dataResult <= 99 ) {

											if ( dataResult > 10 ) {
												$( '#test-result-tab a' ).text( zmDefs.running_test );

											}

											if(debug === 'yes') { console.log( 'progress: '+ dataResult); }

											$( '.progress' ).attr( 'aria-valuenow', dataResult);
											$( '.progress' ).attr( 'aria-valuetext', dataResult + 'percent' );

											$( '.progress-meter' ).css( 'width',dataResult + '%' );
											$( '.progress-meter-text' ).text(dataResult + '%' );

											// If we get stuck on the same progress, indicate to user
											if ((typeof myprogress != 'undefined' ) && myprogress === dataResult) {
												$( '.progress' ).removeClass( 'progress-waiting' );
												myprogress = 1 + dataResult;


											} else if ((typeof myprogress != 'undefined' ) && myprogress > dataResult) {
												$( '.progress' ).addClass( 'progress-waiting' );


											} else {
												$( '.progress' ).removeClass( 'progress-waiting' );
												myprogress = dataResult;

											}

										} else if (dataResult == 100 ) {

											$( '.progress' ).addClass( 'progress-waiting' );
											$( '.progress-meter' ).attr( 'aria-valuenow','100' );
											$( '.progress-meter' ).css( 'width','100%' );
											$( '.progress-meter-text' ).text( waitingOnResult );

											clearInterval(interval); // stop the interval
											// avoid quing if response is slow
											if ( temp ) {
												temp.abort();
											}

											// Get finished results and hide progress
											window.location.href = '/?resultid=' + id;

										}

									} else {
										// We have got an nonce error (-1)
										clearInterval(interval); // stop the interval
										$( '.js-report-status' ).stop().hide(function() {
											$( '.start-test' ).remove();
											$('input, button, option').prop('disabled', false);
											$( 'a' ).removeClass('js-disabled');
											$( '#test-result-tab' ).addClass( 'hide' );

											$( '#resultsarea' ).append('<div class="div-results" id="singletest"><div class="div-tab-area alert"><div class="row"><div class="column"><br><p class="result-text-two">' + zmDefs.error_text + '</p><br></div></div></div></div>');
										});
									}
								});
							}
						}

					});
			};

			$(document).on({
				// retrive old test result inline
				'click': function (e) {
					e.preventDefault();
					var thislink = $(this),
						testid   = thislink.attr('data-testid'),
						$reveal  = $('#reveal_' + testid),
						debug    = 'no',
						data = {
							'action': 'get_single_test',
							'id': testid,
							'oldtest_inline': true,
							'nonce': $('#proxynonce').text()
						};

						// reveal-modal allready is fetched, don't do ajax-call
						if ( $reveal.text() !== '' ) {
							$reveal.foundation('open');

						} else {

							thislink.addClass('hide');
							thislink.closest('td').prepend('<span class="label info fade-in fast js-test-fetching-' + testid + '">' + zmDefs.fetching_data + '</span>');

							// Post the AJAX query to Zonemaster class
							var expanding = $.post('/', data, function(response) {

								$( '.js-test-fetching-' + testid ).remove();

								// activate foundations js on selected, newly added elements
								var elem_rev = new Foundation.Reveal( $('#reveal_' + testid), {
									hideDelay : 20,
									resetOnClose: true
								});
								$reveal.html(response).foundation('open');

								var elem_tab = new Foundation.Tabs( $('#result-tabs' + testid) );

								// activate clipboard function on new element
								var clipboard = new Clipboard('.copy-link');
								clipboard.destroy();

								thislink.removeClass('hide');
								thislink.prepend('* ');
							});

						}


					debug = zmDefs.debug;
					if(debug === 'yes') { console.log( 'expanding test result for : '+ $(this).attr('data-testid')); }

				}
			}, '.js-expand-testresult');



			$(document).on({
				// scroll to testresult sections
				'click': function (e) {
					e.preventDefault();
					var thislink = $(this),
						testid = thislink.attr('href'),
						debug = 'no';

					if ( thislink.hasClass('in-reveal') ) {
						$( '.reveal' ).animate({ scrollTop: $( testid ).offset().top - 100}, 1000);
					} else {
						$( 'html,body' ).animate({ scrollTop: $( testid ).offset().top - 36}, 1000);
					}

					debug = zmDefs.debug;
					if(debug === 'yes') { console.log( 'click btn : '+ testid ); }

				}
			}, '.js-scroll');

			$(document).on({
				// expand predelegated test params
				'click': function (e) {
					e.preventDefault();
					var thislink = $(this),
						debug = 'no';

					if ( thislink.hasClass('reverse') ) {
						thislink.removeClass('reverse');
						$('.js-expand-params').hide('fast');
					} else {
						thislink.addClass('reverse');
						$('.js-expand-params').removeClass('show-if-no-js');
						$('.js-expand-params').show('fast');
					}

					debug = zmDefs.debug;
					if(debug === 'yes') { console.log( 'click expand test params'); }

				}
			}, '.expand-link');



		}

	}
};

$.fn.zonalizer = function (page) {
	zonalizer.page[page]();
	return this;
};
