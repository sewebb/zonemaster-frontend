<?php
// Adjust the breakpoint of the title-bar by adjusting this variable
$breakpoint = "large"; ?>

<div class="title-bar hide-for-<?php echo $breakpoint ?>" data-responsive-toggle="top-bar-menu" data-hide-for="<?php echo $breakpoint ?>">
	<a href="#" class="float-right" data-toggle id="title-bar-mobile-menu"><span class="burger-icon"></span></a>
</div>

<div class="top-bar" id="top-bar-menu">
	<div class="top-bar-right">
		<?php joints_top_nav(); ?>
	</div>
</div>

