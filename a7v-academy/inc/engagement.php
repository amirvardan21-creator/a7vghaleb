<?php
/**
 * A7V Engagement — user star ratings + comments for ALL content
 * (both free and VIP/paid).
 *
 * Ratings are stored per post and recorded (one vote per user / per guest).
 *   _a7v_rate_voters : array( voter_key => stars )   (source of truth)
 *   _a7v_rate_count  : int   number of votes         (derived cache)
 *   _a7v_rate_avg    : float average 1-5             (derived cache)
 *
 * Comments use WordPress' native system (CPTs already declare 'comments'
 * support). We force the comment form open on A7V content so members can
 * always leave a review.
 *
 * @package A7V
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* =============================================================
 * 1. Rating storage helpers
 * ============================================================= */

/** Unique key for the current visitor (user id, or hashed IP for guests). */
function a7v_rating_voter_key() {
	if ( is_user_logged_in() ) {
		return 'u' . get_current_user_id();
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'anon';
	return 'g' . substr( md5( $ip . wp_salt() ), 0, 16 );
}

/** Get raw voters array for a post. */
function a7v_rating_voters( $post_id ) {
	$v = get_post_meta( $post_id, '_a7v_rate_voters', true );
	return is_array( $v ) ? $v : array();
}

/** Average (float, 1 decimal) and count for a post's user ratings. */
function a7v_rating_stats( $post_id ) {
	$voters = a7v_rating_voters( $post_id );
	$count  = count( $voters );
	$avg    = $count ? round( array_sum( $voters ) / $count, 1 ) : 0;
	return array( 'avg' => $avg, 'count' => $count );
}

/** Star value already given by the current visitor (0 if none). */
function a7v_rating_user_value( $post_id ) {
	$voters = a7v_rating_voters( $post_id );
	$key    = a7v_rating_voter_key();
	return isset( $voters[ $key ] ) ? (int) $voters[ $key ] : 0;
}

/**
 * Best rating to display on cards: computed average if any votes exist,
 * otherwise the editorial rating from the meta box.
 */
function a7v_display_rating( $post_id ) {
	$stats = a7v_rating_stats( $post_id );
	if ( $stats['count'] > 0 ) {
		return number_format( $stats['avg'], 1 );
	}
	$editorial = get_post_meta( $post_id, '_a7v_rating', true );
	return $editorial ? $editorial : '4.8';
}

/* =============================================================
 * 2. AJAX — record a vote
 * ============================================================= */

function a7v_ajax_rate() {
	check_ajax_referer( 'a7v_rate', 'nonce' );

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$stars   = isset( $_POST['stars'] ) ? absint( $_POST['stars'] ) : 0;

	if ( ! $post_id || get_post_status( $post_id ) !== 'publish' ) {
		wp_send_json_error( array( 'msg' => 'محتوا یافت نشد.' ), 400 );
	}
	if ( $stars < 1 || $stars > 5 ) {
		wp_send_json_error( array( 'msg' => 'امتیاز نامعتبر است.' ), 400 );
	}

	$voters = a7v_rating_voters( $post_id );
	$key    = a7v_rating_voter_key();
	$voters[ $key ] = $stars;

	$count = count( $voters );
	$avg   = $count ? round( array_sum( $voters ) / $count, 1 ) : 0;

	update_post_meta( $post_id, '_a7v_rate_voters', $voters );
	update_post_meta( $post_id, '_a7v_rate_count', $count );
	update_post_meta( $post_id, '_a7v_rate_avg', $avg );

	wp_send_json_success( array(
		'avg'   => number_format( $avg, 1 ),
		'count' => $count,
		'you'   => $stars,
	) );
}
add_action( 'wp_ajax_a7v_rate', 'a7v_ajax_rate' );
add_action( 'wp_ajax_nopriv_a7v_rate', 'a7v_ajax_rate' );

/* =============================================================
 * 3. Front-end assets (localize ajax data + inline handler)
 * ============================================================= */

function a7v_engagement_assets() {
	if ( ! is_singular( array_keys( a7v_post_types() ) ) ) { return; }

	wp_localize_script( 'a7v-theme', 'A7VRate', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'a7v_rate' ),
	) );

	$js = <<<'JS'
(function(){
	document.addEventListener('click', function(e){
		var star = e.target.closest('.a7v-stars [data-star]');
		if(!star){ return; }
		var wrap = star.closest('.a7v-rating');
		if(!wrap || wrap.classList.contains('is-busy')){ return; }
		var val = parseInt(star.getAttribute('data-star'), 10);
		var pid = wrap.getAttribute('data-post');
		wrap.classList.add('is-busy');
		var body = new URLSearchParams();
		body.append('action','a7v_rate');
		body.append('nonce', (window.A7VRate||{}).nonce||'');
		body.append('post_id', pid);
		body.append('stars', val);
		fetch((window.A7VRate||{}).ajax, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString()})
			.then(function(r){ return r.json(); })
			.then(function(res){
				wrap.classList.remove('is-busy');
				if(!res || !res.success){
					var m = wrap.querySelector('.a7v-rating-msg');
					if(m){ m.textContent = (res && res.data && res.data.msg) ? res.data.msg : 'خطا در ثبت امتیاز.'; }
					return;
				}
				paint(wrap, res.data.you);
				wrap.setAttribute('data-you', res.data.you);
				var avgEl = wrap.querySelector('.a7v-rating-avg');
				var cntEl = wrap.querySelector('.a7v-rating-count');
				if(avgEl){ avgEl.textContent = res.data.avg; }
				if(cntEl){ cntEl.textContent = res.data.count; }
				var msg = wrap.querySelector('.a7v-rating-msg');
				if(msg){ msg.textContent = 'امتیاز شما ثبت شد ✅'; }
			})
			.catch(function(){
				wrap.classList.remove('is-busy');
				var m = wrap.querySelector('.a7v-rating-msg');
				if(m){ m.textContent = 'خطا در ارتباط با سرور.'; }
			});
	});
	// hover preview
	document.addEventListener('mouseover', function(e){
		var star = e.target.closest('.a7v-stars [data-star]');
		if(!star){ return; }
		var wrap = star.closest('.a7v-rating');
		paint(wrap, parseInt(star.getAttribute('data-star'),10), true);
	});
	document.addEventListener('mouseout', function(e){
		var stars = e.target.closest('.a7v-stars');
		if(!stars){ return; }
		var wrap = stars.closest('.a7v-rating');
		paint(wrap, parseInt(wrap.getAttribute('data-you')||'0',10));
	});
	function paint(wrap, val, hover){
		if(!wrap){ return; }
		wrap.querySelectorAll('.a7v-stars [data-star]').forEach(function(s){
			var sv = parseInt(s.getAttribute('data-star'),10);
			s.classList.toggle('on', sv <= val);
			s.classList.toggle('hover', !!hover && sv <= val);
		});
	}
})();
JS;
	wp_add_inline_script( 'a7v-theme', $js );
}
add_action( 'wp_enqueue_scripts', 'a7v_engagement_assets', 20 );

/* =============================================================
 * 4. Rating widget renderer
 * ============================================================= */

function a7v_render_rating( $post_id ) {
	$stats = a7v_rating_stats( $post_id );
	$you   = a7v_rating_user_value( $post_id );
	$avg   = $stats['avg'] ? number_format( $stats['avg'], 1 ) : '—';
	?>
	<div class="a7v-rating" data-post="<?php echo (int) $post_id; ?>" data-you="<?php echo (int) $you; ?>">
		<div class="a7v-rating-head">
			<span class="a7v-rating-score">
				<span class="a7v-rating-avg"><?php echo esc_html( $avg ); ?></span>
				<span class="a7v-rating-of">از ۵</span>
			</span>
			<span class="a7v-rating-meta">(<span class="a7v-rating-count"><?php echo (int) $stats['count']; ?></span> رأی)</span>
		</div>
		<div class="a7v-rating-action">
			<span class="a7v-rating-label">امتیاز شما:</span>
			<div class="a7v-stars" role="radiogroup" aria-label="امتیاز به این محتوا">
				<?php for ( $s = 1; $s <= 5; $s++ ) : ?>
					<button type="button" class="a7v-star<?php echo ( $s <= $you ) ? ' on' : ''; ?>" data-star="<?php echo (int) $s; ?>" aria-label="<?php echo (int) $s; ?> ستاره">★</button>
				<?php endfor; ?>
			</div>
		</div>
		<p class="a7v-rating-msg" aria-live="polite"></p>
	</div>
	<?php
}

/* =============================================================
 * 5. Comments — always allow on A7V content
 * ============================================================= */

/** Force the comment form open on A7V single content (front-end only). */
function a7v_force_comments_open( $open, $post_id ) {
	if ( is_admin() ) { return $open; }
	$post = get_post( $post_id );
	if ( $post && in_array( $post->post_type, array_keys( a7v_post_types() ), true ) ) {
		return true;
	}
	return $open;
}
add_filter( 'comments_open', 'a7v_force_comments_open', 10, 2 );

/**
 * Combined engagement block (rating + comments) for single templates.
 */
function a7v_render_engagement( $post_id ) {
	echo '<section class="a7v-engage container">';
	echo '<h2 class="a7v-engage-title">امتیاز و نظرات</h2>';
	a7v_render_rating( $post_id );
	comments_template();
	echo '</section>';
}
