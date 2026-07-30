<?php
/**
 * Template Name: پرداخت تمام‌عرض (مافیا)
 *
 * Full-width template for the VIP checkout page so the mafia payment page
 * uses the entire screen width on desktop/laptop (not the narrow container).
 *
 * @package A7V
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="mam-fullwidth">
		<?php the_content(); ?>
	</div>
	<?php
endwhile;

get_footer();
