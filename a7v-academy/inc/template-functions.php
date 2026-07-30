<?php
/**
 * Template helper functions: icons, cards, rows, categories.
 *
 * @package A7V
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Inline SVG icon set.
 */
function a7v_icon( $key ) {
	$icons = array(
		'income'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
		'produce' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M10 9l5 3-5 3z" fill="currentColor" stroke="none"/></svg>',
		'prompt'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9l3 3-3 3M13 15h4"/></svg>',
		'article' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l2.5 6.5L21 9l-5 4.5L17.5 21 12 17l-5.5 4L8 13.5 3 9l6.5-.5z"/></svg>',
		'mafia'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 11c2-5 14-5 16 0M2 12h20M6 12c0 2 2 3 6 3s6-1 6-3"/></svg>',
		'book'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 6c-2-1.5-5-2-8-1.5v13C7 17 10 17.5 12 19c2-1.5 5-2 8-1.5v-13C17 4 14 4.5 12 6zM12 6v13"/></svg>',
		'all'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
		'course'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 9L12 4 2 9l10 5 10-5zM6 11v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/></svg>',
		'tool'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.3 2.3-2-2 2.3-2.3z"/></svg>',
		// Dashboard navigation icons.
		'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>',
		'saved'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1z"/></svg>',
		'badge'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><path d="M9 14.5 7 22l5-3 5 3-2-7.5"/></svg>',
		'download'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>',
		'bell'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>',
		'crown'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M3 7l4 5 5-7 5 7 4-5-2 13H5L3 7z"/></svg>',
		'settings'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><circle cx="12" cy="12" r="3.2"/><path d="M19.5 12a7.5 7.5 0 0 0-.1-1.3l2-1.6-2-3.4-2.4 1a7.6 7.6 0 0 0-2.2-1.3L14.4 2h-4l-.4 2.4a7.6 7.6 0 0 0-2.2 1.3l-2.4-1-2 3.4 2 1.6a7.6 7.6 0 0 0 0 2.6l-2 1.6 2 3.4 2.4-1a7.6 7.6 0 0 0 2.2 1.3l.4 2.4h4l.4-2.4a7.6 7.6 0 0 0 2.2-1.3l2.4 1 2-3.4-2-1.6c.07-.43.1-.86.1-1.3z"/></svg>',
		'logout'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17l5-5-5-5M20 12H9M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/></svg>',
		'announce'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11v2a1 1 0 0 0 1 1h2l4 4V6L6 10H4a1 1 0 0 0-1 1zM14 8a4 4 0 0 1 0 8M17 5a8 8 0 0 1 0 14"/></svg>',
	);
	return isset( $icons[ $key ] ) ? $icons[ $key ] : $icons['all'];
}

/**
 * Logo markup (uses custom-logo if set, otherwise text mark).
 */
function a7v_logo() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	?>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo">
		<span class="logo-mark" aria-hidden="true">
			<svg viewBox="0 0 48 48" width="38" height="38"><path d="M24 4c-7 0-13 4-13 10 0 2 1 4 3 5-3 1-5 4-5 8 0 7 7 12 15 12s15-5 15-12c0-4-2-7-5-8 2-1 3-3 3-5 0-6-6-10-13-10z" fill="none" stroke="currentColor" stroke-width="2.2"/><circle cx="18" cy="22" r="2.4" fill="currentColor"/><circle cx="30" cy="22" r="2.4" fill="currentColor"/></svg>
		</span>
		<span class="logo-text"><b>A7V</b><small>ACADEMY</small></span>
	</a>
	<?php
}

/**
 * Configuration for homepage content rows.
 */
function a7v_rows_config() {
	return array(
		array( 'pt' => 'a7v_course',  'id' => 'courses',  'ic' => 'course',  'title' => 'دوره‌های آموزشی', 'type' => 'دوره' ),
		array( 'pt' => 'a7v_book',    'id' => 'books',    'ic' => 'book',    'title' => 'خلاصه کتاب',      'type' => 'کتاب' ),
		array( 'pt' => 'a7v_article', 'id' => 'articles', 'ic' => 'article', 'title' => 'مقالات ویژه',     'type' => 'مقاله' ),
		array( 'pt' => 'a7v_mafia',   'id' => 'mafia',    'ic' => 'mafia',   'title' => 'مقالات مافیایی',  'type' => 'مافیایی' ),
	);
}

/**
 * Quick category navigation (links to CPT archives).
 */
function a7v_render_cats() {
	$cats = array(
		array( 'income',  'کسب درآمد',       'a7v_income' ),
		array( 'produce', 'تولید محتوا',     '' ),
		array( 'prompt',  'پرامپت‌نویسی',     'a7v_prompt' ),
		array( 'article', 'مقالات ویژه',     'a7v_article' ),
		array( 'book',    'خلاصه کتاب',      'a7v_book' ),
		array( 'all',     'همه محصولات',     '' ),
		array( 'course',  'دوره‌های آموزشی', 'a7v_course' ),
	);
	echo '<div class="cats-grid">';
	foreach ( $cats as $c ) {
		$link = $c[2] ? get_post_type_archive_link( $c[2] ) : '#';
		printf(
			'<a class="cat" href="%s"><div class="cat-ic">%s</div><span>%s</span></a>',
			esc_url( $link ? $link : '#' ),
			a7v_icon( $c[0] ),
			esc_html( $c[1] )
		);
	}
	echo '</div>';
}

/**
 * Single card markup.
 *
 * @param array $item   card data (t, cat, desc, a, r, link, thumb).
 * @param array $row    row config (ic, type).
 */
function a7v_card_html( $item, $row ) {
	$link  = ! empty( $item['link'] ) ? esc_url( $item['link'] ) : '#';
	$media = '';
	if ( ! empty( $item['thumb'] ) ) {
		$media = '<img src="' . esc_url( $item['thumb'] ) . '" alt="' . esc_attr( $item['t'] ) . '" loading="lazy">';
	} else {
		$media = '<div class="ph"></div><div class="silhouette">' . a7v_icon( $row['ic'] ) . '</div>';
	}
	ob_start(); ?>
	<div class="card">
		<a class="card-link" href="<?php echo $link; ?>">
			<div class="card-media">
				<?php echo $media; // phpcs:ignore ?>
				<div class="card-grad"></div>
				<span class="badge type"><?php echo a7v_icon( $row['ic'] ); ?><span><?php echo esc_html( $row['type'] ); ?></span></span>
				<?php if ( ! empty( $item['cat'] ) ) : ?>
					<span class="badge cat"><?php echo esc_html( $item['cat'] ); ?></span>
				<?php endif; ?>
			</div>
			<div class="card-body">
				<div class="card-title"><?php echo esc_html( $item['t'] ); ?></div>
				<?php if ( ! empty( $item['desc'] ) ) : ?>
					<div class="card-desc"><?php echo esc_html( $item['desc'] ); ?></div>
				<?php endif; ?>
				<?php if ( ! empty( $item['a'] ) ) : ?>
					<div class="card-author"><?php echo esc_html( $item['a'] ); ?></div>
				<?php endif; ?>
				<div class="card-foot">
					<span class="card-rate"><span class="star">★</span> <?php echo esc_html( $item['r'] ); ?></span>
					<span class="card-cta">رایگان برای اعضای آکادمی مافیا</span>
				</div>
			</div>
		</a>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Query a CPT into card-ready items (with demo fallback).
 */
function a7v_get_items( $row ) {
	$q = new WP_Query( array(
		'post_type'      => $row['pt'],
		'posts_per_page' => 8,
		'no_found_rows'  => true,
	) );

	$items = array();
	if ( $q->have_posts() ) {
		while ( $q->have_posts() ) {
			$q->the_post();
			$id    = get_the_ID();
			$terms = get_the_terms( $id, 'a7v_category' );
			$cat   = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
			$items[] = array(
				'link'  => get_permalink(),
				't'     => get_the_title(),
				'cat'   => $cat,
				'desc'  => wp_trim_words( get_the_excerpt(), 14, '…' ),
				'a'     => get_post_meta( $id, '_a7v_author', true ),
				'r'     => function_exists( 'a7v_display_rating' ) ? a7v_display_rating( $id ) : ( get_post_meta( $id, '_a7v_rating', true ) ? get_post_meta( $id, '_a7v_rating', true ) : '4.8' ),
				'thumb' => get_the_post_thumbnail_url( $id, 'a7v-square' ),
			);
		}
		wp_reset_postdata();
	} else {
		$items = a7v_demo_items( $row['id'] );
	}
	return $items;
}

/**
 * Render all homepage content rows.
 */
function a7v_render_rows() {
	$i = 0;
	foreach ( a7v_rows_config() as $row ) {
		$items   = a7v_get_items( $row );
		$archive = get_post_type_archive_link( $row['pt'] );
		?>
		<div class="content-row container" id="<?php echo esc_attr( $row['id'] ); ?>">
			<div class="row-head">
				<div class="row-title"><span class="rt-ic"><?php echo a7v_icon( $row['ic'] ); ?></span><?php echo esc_html( $row['title'] ); ?></div>
				<a class="row-more" href="<?php echo esc_url( $archive ? $archive : '#' ); ?>">مشاهده همه ◄</a>
			</div>
			<div class="row-track-wrap">
				<button class="row-nav prev" data-row="<?php echo (int) $i; ?>" aria-label="قبلی">›</button>
				<button class="row-nav next" data-row="<?php echo (int) $i; ?>" aria-label="بعدی">‹</button>
				<div class="row-track" id="track-<?php echo (int) $i; ?>">
					<?php foreach ( $items as $item ) { echo a7v_card_html( $item, $row ); } ?>
				</div>
			</div>
		</div>
		<?php
		$i++;
	}
}

/**
 * Access control: is this post locked for the current visitor?
 */
function a7v_is_locked( $post_id ) {
	$access = get_post_meta( $post_id, '_a7v_access', true );
	if ( 'free' === $access ) {
		return false;
	}
	// Simplified membership check — replace with real subscription logic.
	return ! is_user_logged_in();
}
