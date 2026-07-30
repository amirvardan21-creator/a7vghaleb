<?php
/**
 * Fallback template (blog / search / generic loop).
 *
 * @package A7V
 */

get_header();
?>
<section class="archive-head">
	<div class="container"><h1><?php echo is_search() ? esc_html( 'نتایج جستجو' ) : esc_html( get_the_archive_title() ? get_the_archive_title() : 'آخرین مطالب' ); ?></h1></div>
</section>

<section class="container">
	<?php if ( have_posts() ) : ?>
		<div class="archive-grid">
			<?php
			$row = array( 'ic' => 'all', 'type' => '' );
			while ( have_posts() ) :
				the_post();
				$id   = get_the_ID();
				$item = array(
					'link'  => get_permalink(),
					't'     => get_the_title(),
					'cat'   => '',
					'desc'  => wp_trim_words( get_the_excerpt(), 14, '…' ),
					'a'     => '',
					'r'     => '4.8',
					'thumb' => get_the_post_thumbnail_url( $id, 'a7v-square' ),
				);
				echo a7v_card_html( $item, $row );
			endwhile;
			?>
		</div>
		<div style="padding-bottom:50px;text-align:center"><?php the_posts_pagination(); ?></div>
	<?php else : ?>
		<div class="no-content">موردی یافت نشد.</div>
	<?php endif; ?>
</section>

<?php get_footer(); ?>
