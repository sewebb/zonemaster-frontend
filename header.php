<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="utf-8">

		<title><?php wp_title( '|', true, 'right' ); ?> <?php bloginfo( 'name' ); ?></title>

		<meta name="description" content="<?php bloginfo( 'description' ); ?>">
		<meta name="author" content="">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">

		<?php wp_head();?>

	</head>
	<body <?php body_class( 'no-js zm' ); ?>>

		<div class="hero">

			<div class="row align-middle hero-inner">
				<div>
					<div class="row align-middle">
						<a href="/"><h1 class="hero-title"><?php _e( 'Zonemaster', 'zm_text' ); ?></h1></a>
					</div>
				</div>

				<?php
				// Show extra messages only on frontpage
				if ( is_front_page() ) {
				?>
				<div class="hero-status">
					<?php
					if ( '' !== $GLOBALS['OFFLINE'] ) {
						echo $GLOBALS['OFFLINE'];
					}

					// Notice if Polylang is not installed
					if ( ! function_exists( 'pll_current_language' ) ) {
						_log( __( 'Config error! Polylang plugin is not active OR you havent choosen a default language', 'zm_text' ) . __( '. Ignore this if you dont use another language besides english', 'zm_text' ) );
					}
					?>
				</div>
				<?php
				}
				?>
			</div>

			<?php // WP-menu
				// Not on frontpage, menu is among tabs there
			if ( ! is_front_page() ) {
				?>
				<div class="row align-center align-middle">
				<div class="columns">
				<ul class="tabs" id="test-input-tabs">
					<li class="tabs-title">
						<a href="/?tab=tdc"><?php _e( 'Domain check', 'zm_text' ); ?></a>
					</li>
					<li class="tabs-title">
						<a href="/?tab=tpd"><?php _e( 'Pre-delegated domain check', 'zm_text' ); ?></a>
					</li>
				</ul>
				</div>
				<?php // WP-menu ?>
				<div class="columns shrink">
				<?php get_template_part( 'menuparts/nav', 'title-bar' ); ?>
				</div>
				</div>

				<?php
			}
				?>
		</div>

		<div class="site-wrapper">
