<?php
/**
 * Content + download restriction engine.
 *
 * Per-post meta (set from the classic-editor metabox):
 *   _mam_restrict       '1' to restrict this content
 *   _mam_restrict_mode  'full' | 'partial'
 *
 * Partial restriction uses the shortcode:
 *   [mafia_vip] ...محتوای محرمانه... [/mafia_vip]
 * Everything above/around the shortcode stays public; the wrapped part is
 * VIP-only ("از یک بخش مشخص تا انتهای مطلب").
 *
 * Gated downloads use:
 *   [mafia_download url="https://..." label="دانلود فایل"]
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_Restrictions {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'the_content', array( $this, 'filter_content' ), 20 );
		add_shortcode( 'mafia_vip', array( $this, 'sc_partial' ) );
		add_shortcode( 'mafia_download', array( $this, 'sc_download' ) );

		// WooCommerce product restriction.
		add_filter( 'woocommerce_is_purchasable', array( $this, 'wc_purchasable' ), 10, 2 );
	}

	/** Is a post flagged VIP-only? */
	public function is_restricted( $post_id ) {
		return '1' === get_post_meta( $post_id, '_mam_restrict', true );
	}

	/** Restriction mode: full|partial. */
	public function restrict_mode( $post_id ) {
		$m = get_post_meta( $post_id, '_mam_restrict_mode', true );
		return in_array( $m, array( 'full', 'partial' ), true ) ? $m : 'full';
	}

	/**
	 * Main content filter — handles FULL restriction.
	 * Partial restriction is handled by the [mafia_vip] shortcode.
	 */
	public function filter_content( $content ) {
		if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$post_id = get_the_ID();
		if ( ! $post_id || ! $this->is_restricted( $post_id ) ) {
			return $content;
		}
		if ( MAM_Membership::instance()->is_active() ) {
			return $content;
		}
		if ( 'partial' === $this->restrict_mode( $post_id ) ) {
			// Let the shortcode do its job; non-wrapped content stays visible.
			return $content;
		}
		// Full restriction: replace everything with the locked box.
		return $this->locked_box( 'content' );
	}

	/**
	 * [mafia_vip]...[/mafia_vip] — show inner content only to active members.
	 */
	public function sc_partial( $atts, $content = '' ) {
		if ( MAM_Membership::instance()->is_active() ) {
			return do_shortcode( $content );
		}
		return $this->locked_box( 'content' );
	}

	/**
	 * [mafia_download url="" label=""] — gated download button.
	 */
	public function sc_download( $atts ) {
		$atts = shortcode_atts( array(
			'url'   => '',
			'label' => 'دانلود فایل',
		), $atts, 'mafia_download' );

		if ( MAM_Membership::instance()->is_active() ) {
			if ( empty( $atts['url'] ) ) {
				return '';
			}
			return sprintf(
				'<a class="mam-btn mam-btn-download" href="%s" target="_blank" rel="noopener nofollow" download>⬇️ %s</a>',
				esc_url( $atts['url'] ),
				esc_html( $atts['label'] )
			);
		}
		return $this->locked_box( 'download' );
	}

	/**
	 * WooCommerce: block purchase of restricted products for non-members.
	 */
	public function wc_purchasable( $purchasable, $product ) {
		if ( ! mam_get_setting( 'restrict_products', 1 ) ) {
			return $purchasable;
		}
		$pid = $product ? $product->get_id() : 0;
		if ( $pid && $this->is_restricted( $pid ) && ! MAM_Membership::instance()->is_active() ) {
			return false;
		}
		return $purchasable;
	}

	/**
	 * Render the "محرمانه" locked box.
	 *
	 * @param string $type content|download
	 */
	public function locked_box( $type = 'content' ) {
		$s        = MAM_Settings::instance();
		$logged   = is_user_logged_in();
		$expired  = $logged && 'expired' === MAM_Membership::instance()->get_status()['state'];
		$plans_url= $this->plans_url();

		if ( 'download' === $type ) {
			$title = $s->get( 'msg_dl_title' );
			$body  = $s->get( 'msg_dl_body' );
			$btn   = $s->get( 'msg_dl_btn' );
		} else {
			$title = $s->get( 'msg_locked_title' );
			$body  = $s->get( 'msg_locked_body' );
			$btn   = $s->get( 'msg_locked_btn' );
		}

		ob_start();
		?>
		<div class="mam-locked" role="region" aria-label="محتوای محرمانه">
			<div class="mam-locked-seal" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
			</div>
			<h3 class="mam-locked-title"><?php echo esc_html( $title ); ?></h3>
			<p class="mam-locked-body"><?php echo esc_html( $body ); ?></p>
			<?php if ( $expired ) : ?>
				<p class="mam-locked-note"><?php echo esc_html( $s->get( 'msg_expired' ) ); ?></p>
			<?php endif; ?>
			<a class="mam-btn mam-btn-primary" href="<?php echo esc_url( $plans_url ); ?>"><?php echo esc_html( $btn ); ?></a>
			<?php if ( ! $logged ) : ?>
				<a class="mam-locked-login" href="<?php echo esc_url( mam_auth_url( get_permalink() ) ); ?>">قبلاً عضو شدی؟ ورود</a>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/** URL of the plans page. */
	public function plans_url() {
		$pid = (int) mam_get_setting( 'plans_page_id', 0 );
		if ( $pid && 'publish' === get_post_status( $pid ) ) {
			return get_permalink( $pid );
		}
		return home_url( '/vip-plans/' );
	}

	/**
	 * URL of the dedicated checkout (payment) page, optionally preselecting a plan.
	 * This is the direct link each plan uses for its buy button/image.
	 */
	public function checkout_url( $plan_key = '' ) {
		$pid = (int) mam_get_setting( 'checkout_page_id', 0 );
		$url = ( $pid && 'publish' === get_post_status( $pid ) ) ? get_permalink( $pid ) : home_url( '/vip-checkout/' );
		if ( $plan_key ) {
			$url = add_query_arg( 'plan', rawurlencode( $plan_key ), $url );
		}
		return $url;
	}
}
