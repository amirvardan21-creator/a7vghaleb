<?php
/**
 * Front-end shortcodes:
 *   [mafia_plans]          plan cards
 *   [mafia_purchase_form]  customizable purchase form (fields + gateways + card)
 *   [mafia_status]         membership status + circular countdown timer
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_Shortcodes {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'mafia_plans', array( $this, 'sc_plans' ) );
		add_shortcode( 'mafia_purchase_form', array( $this, 'sc_form' ) );
		add_shortcode( 'mafia_buy', array( $this, 'sc_buy' ) );
		add_shortcode( 'mafia_status', array( $this, 'sc_status' ) );
	}

	/** Format a Toman price. */
	private function price( $t ) {
		return number_format( (int) $t ) . ' تومان';
	}

	/** Human-friendly validity duration label. */
	private function duration_label( $days ) {
		$days = (int) $days;
		if ( $days >= 365 && $days % 365 === 0 ) {
			return ( $days / 365 ) . ' سال';
		}
		if ( $days >= 30 && $days % 30 === 0 ) {
			return ( $days / 30 ) . ' ماه';
		}
		return $days . ' روز';
	}

	/**
	 * Direct buy link for a plan → the custom mafia-styled checkout page with
	 * the plan preselected. This is the link used on plan cards, buttons/images.
	 */
	private function buy_href( $plan_key ) {
		return MAM_Restrictions::instance()->checkout_url( $plan_key );
	}

	/* ------------------------------- Plans -------------------------------- */

	public function sc_plans( $atts ) {
		$plans = MAM_Settings::plans();
		ob_start();
		echo '<div class="mam-plans">';
		foreach ( $plans as $p ) {
			$featured = ! empty( $p['featured'] ) ? ' mam-plan-featured' : '';
			?>
			<div class="mam-plan<?php echo esc_attr( $featured ); ?>">
				<?php if ( ! empty( $p['featured'] ) ) : ?><span class="mam-plan-badge">پرطرفدار</span><?php endif; ?>
				<div class="mam-plan-name"><?php echo esc_html( $p['label'] ); ?></div>
				<div class="mam-plan-price"><?php echo esc_html( number_format( (int) $p['price'] ) ); ?><span>تومان</span></div>
				<div class="mam-plan-duration">مدت اعتبار: <?php echo esc_html( $this->duration_label( $p['days'] ) ); ?></div>
				<p class="mam-plan-access">با فعال‌سازی این اشتراک، به تمام دوره‌ها، محصولات، دانلودها و محتوای ویژه سایت دسترسی کامل خواهید داشت.</p>
				<a class="mam-btn mam-btn-primary" href="<?php echo esc_url( $this->buy_href( $p['key'] ) ); ?>">خرید اشتراک</a>
			</div>
			<?php
		}
		echo '</div>';
		return ob_get_clean();
	}

	/* --------------------------- Purchase form ---------------------------- */

	public function sc_form( $atts ) {
		$atts     = shortcode_atts( array( 'plan' => '' ), $atts, 'mafia_purchase_form' );
		$plans    = MAM_Settings::plans();
		$gateways = MAM_Gateways::instance()->available();
		$fields   = mam_get_setting( 'form_fields', MAM_Settings::default_form_fields() );

		// Resolve the selected plan: shortcode attr → ?plan= → first plan.
		$sel_key  = $atts['plan'] ? sanitize_key( $atts['plan'] ) : ( isset( $_GET['plan'] ) ? sanitize_key( wp_unslash( $_GET['plan'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$selected = $sel_key ? MAM_Settings::plan( $sel_key ) : null;
		if ( ! $selected && ! empty( $plans ) ) {
			$selected = $plans[0];
		}

		// Result banner after redirect.
		$banner = '';
		if ( isset( $_GET['mam'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$ok     = 'success' === $_GET['mam']; // phpcs:ignore
			$msg    = isset( $_GET['mam_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['mam_msg'] ) ) : ''; // phpcs:ignore
			$banner = '<div class="mam-banner ' . ( $ok ? 'ok' : 'err' ) . '">' . esc_html( $msg ) . '</div>';
		}

		if ( empty( $plans ) || ! $selected ) {
			return $banner . '<div class="mam-banner err">هنوز هیچ اشتراکی تعریف نشده است.</div>';
		}
		if ( empty( $gateways ) ) {
			return $banner . '<div class="mam-banner err">هیچ درگاه پرداختی فعال نیست. لطفاً از پیشخوان ← آکادمی مافیا ← تنظیمات ← درگاه‌های پرداخت، یک درگاه را فعال کنید.</div>';
		}

		$logged_in = is_user_logged_in();
		$cur_user  = $logged_in ? wp_get_current_user() : null;
		$cur_name  = '';
		$cur_phone = '';
		if ( $logged_in ) {
			$cur_name = trim( $cur_user->first_name . ' ' . $cur_user->last_name );
			if ( '' === $cur_name ) { $cur_name = $cur_user->display_name; }
			$cur_phone = (string) get_user_meta( $cur_user->ID, 'mam_phone', true );
		}
		$mam_login_url = mam_auth_url( get_permalink() );

		$brand      = mam_get_setting( 'brand_name', 'آکادمی مافیا' );
		$c_title    = mam_get_setting( 'checkout_title', 'اطلاعات پرداخت' );
		$c_sub      = mam_get_setting( 'checkout_subtitle', 'لطفاً اطلاعات خود را وارد کنید' );
		$c_secure   = mam_get_setting( 'checkout_secure', 'اطلاعات شما امن و محفوظ است' );
		$base_price = (int) $selected['price'];

		ob_start();
		?>
		<div class="mam-checkout" id="mam-form"
			data-plan="<?php echo esc_attr( $selected['key'] ); ?>"
			data-price="<?php echo esc_attr( $base_price ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'mam_discount' ) ); ?>"
			data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">

			<?php echo $banner; // phpcs:ignore ?>

			<div class="mam-checkout-grid">

				<!-- ============ Order summary ============ -->
				<aside class="mam-summary">
					<div class="mam-summary-brand">
						<span class="mam-summary-seal" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M12 3 4 6v5c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6l-8-3Z"/></svg>
						</span>
						<span class="mam-summary-brandname"><?php echo esc_html( $brand ); ?></span>
					</div>

					<div class="mam-summary-title">سفارش شما</div>
					<div class="mam-summary-plan">اشتراک <?php echo esc_html( $selected['label'] ); ?></div>

					<div class="mam-summary-meta">
						<div><span>مدت اعتبار</span><b><?php echo esc_html( $this->duration_label( $selected['days'] ) ); ?></b></div>
						<div><span>کد</span><b dir="ltr"><?php echo esc_html( $selected['key'] ); ?></b></div>
					</div>

					<div class="mam-summary-line"><span>مبلغ</span><b class="mam-sum-price"><?php echo esc_html( $this->price( $base_price ) ); ?></b></div>
					<div class="mam-summary-line mam-summary-off" hidden><span>تخفیف</span><b class="mam-sum-off">۰</b></div>
					<div class="mam-summary-total"><span>جمع کل</span><b class="mam-sum-total"><?php echo esc_html( $this->price( $base_price ) ); ?></b></div>

					<div class="mam-summary-note">🔒 <?php echo esc_html( $c_secure ); ?></div>
				</aside>

				<!-- ============ Payment info form ============ -->
				<div class="mam-checkout-main">
					<h2 class="mam-checkout-h"><?php echo esc_html( $c_title ); ?></h2>
					<p class="mam-checkout-sub"><?php echo esc_html( $c_sub ); ?></p>

					<form class="mam-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="mam_purchase">
						<?php wp_nonce_field( 'mam_purchase', 'mam_nonce' ); ?>
						<input type="hidden" name="mam_plan" value="<?php echo esc_attr( $selected['key'] ); ?>">
						<input type="hidden" name="mam_discount" class="mam-discount-applied" value="">

												<?php if ( $logged_in ) : ?>
							<div class="mam-loggedin-note">وارد شده به عنوان <b><?php echo esc_html( $cur_user->display_name ); ?></b> — اطلاعات حساب کاربری شما استفاده می‌شود.</div>
							<input type="hidden" name="mam_field[full_name]" value="<?php echo esc_attr( $cur_name ); ?>">
							<div class="mam-form-grid">
								<div class="mam-field is-full">
									<label for="mam-phone">شماره تماس *</label>
									<input type="tel" id="mam-phone" name="mam_field[phone]" value="<?php echo esc_attr( $cur_phone ); ?>" dir="ltr" autocomplete="tel" required>
								</div>
							</div>
						<?php else : ?>
							<div class="mam-form-grid">
								<div class="mam-field is-full">
									<label for="mam-full_name">نام و نام خانوادگی *</label>
									<input type="text" id="mam-full_name" name="mam_field[full_name]" autocomplete="name" required>
								</div>
								<div class="mam-field is-full">
									<label for="mam-email">ایمیل *</label>
									<input type="email" id="mam-email" name="mam_field[email]" dir="ltr" autocomplete="email" required>
								</div>
								<div class="mam-field">
									<label for="mam-username">نام کاربری *</label>
									<input type="text" id="mam-username" name="mam_field[username]" dir="ltr" autocomplete="username" required>
								</div>
								<div class="mam-field">
									<label for="mam-password">رمز عبور *</label>
									<input type="password" id="mam-password" name="mam_field[password]" dir="ltr" autocomplete="new-password" required>
								</div>
								<div class="mam-field is-full">
									<label for="mam-phone">شماره تماس *</label>
									<input type="tel" id="mam-phone" name="mam_field[phone]" dir="ltr" autocomplete="tel" required>
								</div>
							</div>
							<p class="mam-checkout-login">قبلاً حساب کاربری دارید؟ <a href="<?php echo esc_url( $mam_login_url ); ?>">وارد شوید</a></p>
						<?php endif; ?>

						<div class="mam-pay-title">انتخاب درگاه پرداخت</div>
						<div class="mam-gw-grid mam-gateways">
							<?php $first = true; foreach ( $gateways as $gk => $gl ) : ?>
								<label class="mam-gw" data-gw="<?php echo esc_attr( $gk ); ?>">
									<input type="radio" name="mam_method" value="<?php echo esc_attr( $gk ); ?>" <?php checked( $first, true ); ?> required>
									<span class="mam-gw-ic" aria-hidden="true"><?php echo $this->gw_icon( $gk ); // phpcs:ignore ?></span>
									<span class="mam-gw-name"><?php echo esc_html( $gl ); ?></span>
								</label>
							<?php $first = false; endforeach; ?>
						</div>

						<?php if ( isset( $gateways['card'] ) ) : ?>
							<div class="mam-card-box" data-card-box hidden>
								<h4>اطلاعات کارت به کارت</h4>
								<p class="mam-card-desc"><?php echo esc_html( mam_get_setting( 'gw_card_desc', '' ) ); ?></p>
								<div class="mam-card-info">
									<div><span>شماره کارت</span><b dir="ltr"><?php echo esc_html( mam_get_setting( 'gw_card_number', '—' ) ); ?></b></div>
									<div><span>به نام</span><b><?php echo esc_html( mam_get_setting( 'gw_card_holder', '—' ) ); ?></b></div>
								</div>
								<div class="mam-form-grid">
									<div class="mam-field">
										<label for="mam-txn">شماره پیگیری پرداخت *</label>
										<input type="text" id="mam-txn" name="mam_txn">
									</div>
									<div class="mam-field">
										<label for="mam-receipt">تصویر رسید *</label>
										<input type="file" id="mam-receipt" name="mam_receipt" accept="image/*">
									</div>
								</div>
							</div>
						<?php endif; ?>

						<div class="mam-discount">
							<label class="mam-discount-label">کد تخفیف دارید؟</label>
							<div class="mam-discount-row">
								<input type="text" class="mam-discount-code" placeholder="کد تخفیف را وارد کنید" autocomplete="off">
								<button type="button" class="mam-btn mam-btn-ghost mam-discount-apply">اعمال</button>
							</div>
							<div class="mam-discount-msg" role="status"></div>
						</div>

						<button type="submit" class="mam-btn mam-btn-primary mam-btn-lg">🔒 پرداخت و تکمیل سفارش</button>
					</form>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Icon (or custom logo image from settings) per payment gateway. */
	private function gw_icon( $key ) {
		// A custom logo uploaded in Settings → Gateways overrides the default icon.
		$logo = mam_get_setting( 'gw_' . $key . '_logo', '' );
		if ( $logo ) {
			return '<img class="mam-gw-img" src="' . esc_url( $logo ) . '" alt="" loading="lazy">';
		}

		$card = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 9.5h19"/></svg>';
		$icons = array(
			'zarinpal' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M8 13c1.5 2 6.5 2 8 0"/><path d="M9 9h.01M15 9h.01"/></svg>',
			'zibal'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 7h16l-9 10h9"/></svg>',
			'card'     => $card,
		);
		return isset( $icons[ $key ] ) ? $icons[ $key ] : $card;
	}

	/* ------------------------------ Buy button ---------------------------- */

	/**
	 * [mafia_buy plan="yearly" label="خرید اشتراک" url=""]
	 * A direct buy button for one subscription — perfect for a custom landing page.
	 * If "url" is empty it points to the plans page; otherwise to your landing URL.
	 */
	public function sc_buy( $atts ) {
		$atts = shortcode_atts( array(
			'plan'  => '',
			'label' => '',
			'url'   => '',
			'class' => '',
		), $atts, 'mafia_buy' );

		$plan = $atts['plan'] ? MAM_Settings::plan( sanitize_key( $atts['plan'] ) ) : null;
		if ( ! $plan ) {
			return '';
		}
		$label = $atts['label'] ? $atts['label'] : ( 'خرید اشتراک ' . $plan['label'] . ' — ' . $this->price( $plan['price'] ) );
		// Direct to WooCommerce checkout (no cart). A custom "url" still overrides.
		if ( $atts['url'] ) {
			$href = add_query_arg( 'plan', $plan['key'], $atts['url'] ) . '#mam-form';
		} else {
			$href = $this->buy_href( $plan['key'] );
		}

		return sprintf(
			'<a class="mam-btn mam-btn-primary mam-buy %s" href="%s">%s</a>',
			esc_attr( $atts['class'] ),
			esc_url( $href ),
			esc_html( $label )
		);
	}

	/* ------------------------------ Status -------------------------------- */

	public function sc_status( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<div class="mam-status mam-status-guest"><p>برای مشاهدهٔ وضعیت اشتراک وارد شوید.</p><a class="mam-btn mam-btn-primary" href="' . esc_url( mam_auth_url() ) . '">ورود</a></div>';
		}
		$s = mam_membership();

		$r      = 52;
		$circ   = 2 * M_PI * $r;
		$offset = $circ - ( ( $s['percent'] / 100 ) * $circ );

		ob_start();
		?>
		<div class="mam-status mam-state-<?php echo esc_attr( $s['state'] ); ?>">
			<div class="mam-timer">
				<svg viewBox="0 0 120 120" class="mam-ring" aria-hidden="true">
					<circle class="mam-ring-bg" cx="60" cy="60" r="<?php echo (int) $r; ?>"/>
					<circle class="mam-ring-fg" cx="60" cy="60" r="<?php echo (int) $r; ?>"
						stroke-dasharray="<?php echo esc_attr( $circ ); ?>"
						stroke-dashoffset="<?php echo esc_attr( $offset ); ?>"/>
				</svg>
				<div class="mam-timer-num">
					<strong><?php echo esc_html( number_format_i18n( $s['days_left'] ) ); ?></strong>
					<span><?php echo 'active' === $s['state'] ? 'روز مانده' : ( 'expired' === $s['state'] ? 'منقضی' : '—' ); ?></span>
				</div>
			</div>
			<div class="mam-status-info">
				<div class="mam-status-badge mam-badge-<?php echo esc_attr( $s['state'] ); ?>"><?php echo esc_html( $s['state_label'] ); ?></div>
				<?php if ( $s['plan_label'] ) : ?><div class="mam-status-row"><span>پلن:</span> <b><?php echo esc_html( $s['plan_label'] ); ?></b></div><?php endif; ?>
				<?php if ( $s['start'] ) : ?><div class="mam-status-row"><span>شروع:</span> <b><?php echo esc_html( mysql2date( 'Y/m/d', $s['start'] ) ); ?></b></div><?php endif; ?>
				<?php if ( $s['end'] ) : ?><div class="mam-status-row"><span>پایان:</span> <b><?php echo esc_html( mysql2date( 'Y/m/d', $s['end'] ) ); ?></b></div><?php endif; ?>
				<?php if ( 'active' !== $s['state'] ) : ?>
					<a class="mam-btn mam-btn-primary" href="<?php echo esc_url( MAM_Restrictions::instance()->plans_url() ); ?>"><?php echo 'expired' === $s['state'] ? 'تمدید اشتراک' : 'خرید اشتراک'; ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
