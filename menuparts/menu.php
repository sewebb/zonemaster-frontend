<?php
// Register menus
register_nav_menus(
	array(
		'main-nav' => __( 'The Main Menu', 'zm_text' ),   // Main nav in header
	)
);

// The Top Menu
function joints_top_nav() {
	$polylang_args     = array (
						'echo' => 0,
						'hide_if_no_translation' => 1,
						'hide_current' => 1,
		);
	// check if polylang is installed
	if ( function_exists( 'pll_current_language' ) ) {
		$polylang_switcher = pll_the_languages( $polylang_args );
	} else {
		$polylang_switcher = '';
	}
	wp_nav_menu( array(
					'container'      => false,                           // Remove nav container
					'menu_class'     => 'vertical medium-horizontal menu',       // Adding custom nav class
					'items_wrap'     => '<ul id="%1$s" class="%2$s" data-responsive-menu="accordion large-dropdown">%3$s' . $polylang_switcher . '</ul>',
					'theme_location' => 'main-nav',        			// Where it's located in the theme
					'fallback_cb'    => false,                         // No fallback
				)
	);
}




