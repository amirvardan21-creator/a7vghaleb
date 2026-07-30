<?php
/**
 * Default page template.
 *
 * @package A7V
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article class="entry container">
		<header class="entry-header"><h1><?php the_title(); ?></h1></header>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="entry-thumb"><?php the_post_thumbnail( 'large' ); ?></div>
		<?php endif; ?>
		<div class="entry-content"><?php the_content(); ?></div>
	</article>
	<?php
endwhile;

get_footer();
