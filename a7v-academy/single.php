<?php
/**
 * Single content template (download button + VIP paywall popup).
 *
 * @package A7V
 */

get_header();

while ( have_posts() ) :
	the_post();
	$id       = get_the_ID();
	$locked   = function_exists( 'a7v_is_locked' ) ? a7v_is_locked( $id ) : false;
	$terms    = get_the_terms( $id, 'a7v_category' );
	$cat      = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
	$author   = get_post_meta( $id, '_a7v_author', true );
	$rating   = get_post_meta( $id, '_a7v_rating', true );
	$dur      = get_post_meta( $id, '_a7v_duration', true );
	$download = get_post_meta( $id, '_a7v_download', true );
	?>
	<article class="entry container">
		<header class="entry-header">
			<?php if ( $cat ) : ?><span class="badge cat" style="position:static;display:inline-block;margin-bottom:12px"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<div class="entry-meta">
				<?php if ( $author ) : ?><span>👤 <?php echo esc_html( $author ); ?></span><?php endif; ?>
				<?php if ( $rating ) : ?><span style="color:var(--gold)">★ <?php echo esc_html( $rating ); ?></span><?php endif; ?>
				<?php if ( $dur ) : ?><span>⏱ <?php echo esc_html( $dur ); ?></span><?php endif; ?>
				<span><?php echo $locked ? '🔒 ویژه (اشتراک)' : '🔓 رایگان'; ?></span>
			</div>
		</header>

		<?php if ( function_exists( 'a7v_render_product_info' ) ) { a7v_render_product_info( $id ); } ?>

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="entry-thumb"><?php the_post_thumbnail( 'large' ); ?></div>
		<?php endif; ?>

		<?php if ( $download ) : ?>
			<div class="entry-actions">
				<?php if ( $locked ) : ?>
					<button type="button" class="btn btn-primary btn-lg js-locked">⬇️ دانلود</button>
					<span class="lock-note">🔒 دانلود فقط برای اعضای ویژه</span>
				<?php else : ?>
					<a class="btn btn-primary btn-lg" href="<?php echo esc_url( $download ); ?>" target="_blank" rel="noopener nofollow" download>⬇️ دانلود</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $locked ) : ?>
			<div class="paywall">
				<div class="entry-content" style="max-height:260px;overflow:hidden"><?php echo wp_kses_post( wpautop( wp_trim_words( get_the_content(), 60, '…' ) ) ); ?></div>
				<div class="paywall-fade"></div>
			</div>
			<div class="paywall-box">
				<h3>🔒 این محتوا ویژه اعضای آکادمی مافیا است</h3>
				<p>با تهیه اشتراک ویژه، به این محتوا و تمام دوره‌ها، کتاب‌ها و مقالات دسترسی نامحدود پیدا کن.</p>
				<a class="btn btn-primary btn-lg js-locked" href="#">👑 عضویت ویژه</a>
			</div>
		<?php else : ?>
			<div class="entry-content"><?php the_content(); ?></div>
		<?php endif; ?>
	</article>

	<?php
	// Star rating + comments — available for ALL content (free & VIP).
	if ( function_exists( 'a7v_render_engagement' ) ) {
		a7v_render_engagement( $id );
	}
	?>
	<?php
endwhile;

get_footer();
