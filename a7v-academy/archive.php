<?php
/**
 * Archive template (CPT + categories).
 *
 * @package A7V
 */

get_header();

// Determine icon + type label from current post type.
$pt   = get_post_type() ? get_post_type() : get_query_var( 'post_type' );
$map  = array(
	'a7v_course'  => array( 'course', 'دوره' ),
	'a7v_book'    => array( 'book', 'کتاب' ),
	'a7v_article' => array( 'article', 'مقاله' ),
	'a7v_mafia'   => array( 'mafia', 'مافیایی' ),
	'a7v_prompt'  => array( 'prompt', 'پرامپت' ),
	'a7v_tool'    => array( 'tool', 'ابزار' ),
	'a7v_income'  => array( 'income', 'کسب درآمد' ),
);
$row  = isset( $map[ $pt ] ) ? array( 'ic' => $map[ $pt ][0], 'type' => $map[ $pt ][1] ) : array( 'ic' => 'all', 'type' => '' );
?>

<section class="archive-head">
	<div class="container">
		<h1><?php echo esc_html( post_type_archive_title( '', false ) ? post_type_archive_title( '', false ) : ( is_tax() ? single_term_title( '', false ) : get_the_archive_title() ) ); ?></h1>
		<div class="crumb"><?php echo esc_html( get_the_archive_description() ); ?></div>
	</div>
</section>

<section class="container">
	<?php if ( have_posts() ) : ?>
		<div class="archive-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				$id    = get_the_ID();
				$terms = get_the_terms( $id, 'a7v_category' );
				$item  = array(
					'link'  => get_permalink(),
					't'     => get_the_title(),
					'cat'   => ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '',
					'desc'  => wp_trim_words( get_the_excerpt(), 14, '…' ),
					'a'     => get_post_meta( $id, '_a7v_author', true ),
					'r'     => get_post_meta( $id, '_a7v_rating', true ) ? get_post_meta( $id, '_a7v_rating', true ) : '4.8',
					'thumb' => get_the_post_thumbnail_url( $id, 'a7v-square' ),
				);
				echo a7v_card_html( $item, $row );
			endwhile;
			?>
		</div>
		<div class="container" style="padding-bottom:50px;text-align:center"><?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => 'قبلی', 'next_text' => 'بعدی' ) ); ?></div>
	<?php else : ?>
		<div class="no-content">هنوز محتوایی در این بخش منتشر نشده است.</div>
	<?php endif; ?>
</section>

<?php get_footer(); ?>
