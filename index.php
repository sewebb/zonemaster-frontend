<?php get_header();
// Start the loop.
while ( have_posts() ) : the_post();
?>
<div class="full-width standard-page expanded">

		<div class="row align-bottom">
			<div class="small-12 medium-7 column">
				<?php the_content(); ?>
			</div>
			<div class="column hide-for-small-only medium-5 ">
				<?php
				$orange_fill = 'ff7900';
				$red_fill    = 'e00034';
				$green_fill  = '34b233';

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
// End of the loop.
endwhile;
get_footer(); ?>
