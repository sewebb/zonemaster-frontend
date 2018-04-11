import $ from 'jquery';

import {
    Foundation,
    MediaQuery,
    Box,
    Keyboard,
    Motion,
    Nest,
    TimerAndImageLoader,
    Touch,
    Triggers,
    AccordionMenu,
    Drilldown,
    Dropdown,
    ResponsiveMenu,
    ResponsiveToggle,
    Reveal,
    Sticky,
    Tabs,
    Toggler
} from 'foundation-sites';

import './api';

if ( typeof $ === 'function' ) {
	$( 'body' ).removeClass( 'no-js' );

	$( document ).ready( function () {
		// Fix problem with IE
		$.ajaxSetup({ cache: false });
		// Start API
		$( document ).zonalizer( 'main' );

		// Set up clipboard
		var clipboard = new Clipboard('.copy-link');

		clipboard.on('success', function(e) {
		    var span = $(e.trigger);
		    span.addClass('fade-in fast reverse');
		    span.text(zmDefs.copy_done);

		    e.clearSelection();
		});

		clipboard.on('error', function(e) {
		    var span = $(e.trigger);
		    span.addClass('fade-in fast reverse');
		    span.text(fallbackMessage(e.action));
		});

		function fallbackMessage(action) {
			var actionMsg = '',
				actionKey = (action === 'cut'?'X':'C');

			if (/iPhone|iPad/i.test(navigator.userAgent)) {
				actionMsg='No support :(';
			} else if (/Mac/i.test(navigator.userAgent)) {
				actionMsg = zmDefs.copy_press + ' ⌘-' + actionKey + ' ' + zmDefs.copy_to + ' ' + zmDefs.copy;
			} else {
				actionMsg = zmDefs.copy_press + ' Ctrl-' + actionKey + ' ' + zmDefs.copy_to + ' ' + zmDefs.copy;
			}
			return actionMsg;
		}


		var topLink = $('.top_link');
		var showTopLink = 500;
		topLink.hide();

		$(window).scroll( function(){
			var y = $(window).scrollTop();
			if( y > showTopLink  ) {
				topLink.fadeIn('slow');
			} else {
				topLink.fadeOut('slow');
			}
		});

		topLink.click( function(e) {
			e.preventDefault();
			$('html,body').animate( {scrollTop : 0}, 'slow' );
		});

		// Correct colors on "normal" tabs
		$('#test-input-tabs').on('change.zf.tabs', function() {
			$( '.tabs-panel.full-width ').removeClass( 'success ');
			$( '.tabs-panel.full-width ').removeClass( 'alert ');
			$( '.tabs-panel.full-width ').removeClass( 'warning ');
			if ( $( '#test-result-tab' ).hasClass( 'is-active' ) ) {
				$( '.start-page-wp-content' ).addClass('hide');
			} else {
				$( '.start-page-wp-content' ).removeClass('hide');
			}
		});



		// Set input focus
		$('#zone_tld_domain_check').focus();


		// Get faq-text from no-js area in page-start.php
		var pre_faq        = $('.js-append-to-what-is-predelegated').html(),
			header_pre_faq = $('#header-pre-faq').text();

		$('.js-header-pre-faq').text(header_pre_faq);
		$('#what-is-predelegated').append(pre_faq);


		// set langlink in meny if possible
		function getParameterByName(name, url) {
		    if (!url) url = window.location.href;
		    name = name.replace(/[\[\]]/g, "\\$&");
		    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
		        results = regex.exec(url);
		    if (!results) return null;
		    if (!results[2]) return '';
		    return decodeURIComponent(results[2].replace(/\+/g, " "));
		}

		// help resultpage to translate
		$( '.lang-item a' ).each(function() {

			var langurl   = $( this ).attr('href'),
				id        = getParameterByName('resultid'),
				tab       = getParameterByName('tab');

				if ( langurl !== undefined && null !== id && null !== tab ) {
					$( this ).attr('href', langurl + '?resultid=' + id + '&tab=' + tab);
				} else if ( langurl !== undefined && null !== tab ) {
					$( this ).attr('href', langurl + '?tab=' + tab);
				} else if ( langurl !== undefined && null !== id ) {
					$( this ).attr('href', langurl + '?resultid=' + id);
				}

		});

		/**
		 * Re-calculate sticky elements on tab change.
		 *
		 * If a sticky elements is inside a hidden container on load
		 * it will not have correct dimensions when it's displayed.
		 * This fixes that.
		 */
		$(document).on('change.zf.tabs', function () {
			if ($('[data-sticky]').length) {
				$('[data-sticky]').foundation('_calc', true);
			}
		});

	});
}

$( document ).foundation();

// Animera hamburgar-menyn
var menuButton = document.getElementById( 'title-bar-mobile-menu' );
menuButton.addEventListener( 'click', function ( e ) {
	menuButton.classList.toggle( 'is-active' );
	e.preventDefault();
});
