<?php
/**
 * Admin panel: mafia-styled dashboard, subscriptions, payments, plans, settings.
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_Admin {

	private static $instance = null;
	const CAP = 'manage_options';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_mam_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_mam_save_plans', array( $this, 'save_plans' ) );
		add_action( 'admin_post_mam_edit_member', array( $this, 'edit_member' ) );
	}

	/* ------------------------------- Menu --------------------------------- */

	public function menu() {
		$icon = 'dashicons-shield-alt';
		add_menu_page( 'آکادمی مافیا', 'آکادمی مافیا', self::CAP, 'mam-dashboard', array( $this, 'page_dashboard' ), $icon, 3 );
		add_submenu_page( 'mam-dashboard', 'پیشخوان', 'پیشخوان', self::CAP, 'mam-dashboard', array( $this, 'page_dashboard' ) );
		add_submenu_page( 'mam-dashboard', 'اعضا و اشتراک‌ها', 'اعضا', self::CAP, 'mam-subscriptions', array( $this, 'page_subscriptions' ) );
		add_submenu_page( 'mam-dashboard', 'پرداخت‌ها', 'پرداخت‌ها', self::CAP, 'mam-payments', array( $this, 'page_payments' ) );
		add_submenu_page( 'mam-dashboard', 'پلن‌های اشتراک', 'پلن‌های اشتراک', self::CAP, 'mam-plans', array( $this, 'page_plans' ) );
		add_submenu_page( 'mam-dashboard', 'تنظیمات', 'تنظیمات', self::CAP, 'mam-settings', array( $this, 'page_settings' ) );
	}

	/** Only load our assets on our pages. */
	public function assets( $hook ) {
		if ( false === strpos( $hook, 'mam-' ) ) {
			return;
		}
		$css = MAM_DIR . 'assets/css/mafia-admin.css';
		$js  = MAM_DIR . 'assets/js/mafia-admin.js';
		wp_enqueue_media();
		wp_enqueue_style( 'mam-admin', MAM_URL . 'assets/css/mafia-admin.css', array(), file_exists( $css ) ? filemtime( $css ) : MAM_VERSION );
		wp_enqueue_script( 'chartjs', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js', array(), '4.4.1', true );
		wp_enqueue_script( 'mam-admin', MAM_URL . 'assets/js/mafia-admin.js', array( 'chartjs' ), file_exists( $js ) ? filemtime( $js ) : MAM_VERSION, true );
		wp_localize_script( 'mam-admin', 'MAMAdmin', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'mam_admin' ),
		) );
	}

	private function fmt( $n ) {
		return number_format( (float) $n );
	}

	/* ----------------------------- Dashboard ------------------------------ */

	public function page_dashboard() {
		if ( ! current_user_can( self::CAP ) ) { return; }

		$now       = current_time( 'timestamp' );
		$today0    = gmdate( 'Y-m-d 00:00:00', $now );
		$today1    = gmdate( 'Y-m-d 23:59:59', $now );
		$month0    = gmdate( 'Y-m-01 00:00:00', $now );

		$members   = MAM_Membership::instance()->count_by_status( 'active' );
		$new_users = MAM_Membership::instance()->new_users_count( 30 );
		$sales_today = MAM_Payments::revenue_between( $today0, $today1 );
		$sales_month = MAM_Payments::revenue_between( $month0, $today1 );
		$revenue_all = MAM_Payments::revenue_total();

		$rev_days  = MAM_Payments::revenue_by_day( 14 );
		$sub_days  = MAM_Payments::subs_by_day( 14 );
		$breakdown = MAM_Payments::status_breakdown();
		$latest    = MAM_Payments::query( array( 'limit' => 8 ) );

		// Build chart series for last 14 days.
		$labels = array();
		$rev_series = array();
		$sub_series = array();
		for ( $i = 13; $i >= 0; $i-- ) {
			$d = gmdate( 'Y-m-d', strtotime( "-{$i} days", $now ) );
			$labels[]     = gmdate( 'm/d', strtotime( $d ) );
			$rev_series[] = isset( $rev_days[ $d ] ) ? $rev_days[ $d ] : 0;
			$sub_series[] = isset( $sub_days[ $d ] ) ? $sub_days[ $d ] : 0;
		}

		include MAM_DIR . 'admin/views/dashboard.php';
	}

	/* --------------------------- Subscriptions ---------------------------- */

	public function page_subscriptions() {
		if ( ! current_user_can( self::CAP ) ) { return; }

		$paged   = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
		$per      = 20;
		$q = new WP_User_Query( array(
			'meta_key'    => '_mam_expiry',
			'meta_compare'=> 'EXISTS',
			'number'      => $per,
			'offset'      => ( $paged - 1 ) * $per,
			'orderby'     => 'meta_value',
			'meta_type'   => 'DATETIME',
			'order'       => 'DESC',
			'count_total' => true,
		) );
		$members = $q->get_results();
		$total   = $q->get_total();
		$pages   = (int) ceil( $total / $per );

		include MAM_DIR . 'admin/views/subscriptions.php';
	}

	/* ----------------------------- Payments ------------------------------- */

	public function page_payments() {
		if ( ! current_user_can( self::CAP ) ) { return; }

		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore
		$method = isset( $_GET['method'] ) ? sanitize_key( wp_unslash( $_GET['method'] ) ) : ''; // phpcs:ignore
		$paged  = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore
		$per    = 20;

		$records = MAM_Payments::query( array(
			'status' => $status,
			'method' => $method,
			'limit'  => $per,
			'offset' => ( $paged - 1 ) * $per,
		) );
		$total = MAM_Payments::count( array( 'status' => $status, 'method' => $method ) );
		$pages = (int) ceil( $total / $per );

		include MAM_DIR . 'admin/views/payments.php';
	}

	/* ------------------------------- Plans -------------------------------- */

	public function page_plans() {
		if ( ! current_user_can( self::CAP ) ) { return; }
		$plans = MAM_Settings::plans();
		include MAM_DIR . 'admin/views/plans.php';
	}

	public function save_plans() {
		if ( ! current_user_can( self::CAP ) || ! isset( $_POST['mam_plans_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mam_plans_nonce'] ) ), 'mam_save_plans' ) ) {
			wp_die( 'دسترسی نامعتبر' );
		}
		$raw   = isset( $_POST['plan'] ) && is_array( $_POST['plan'] ) ? wp_unslash( $_POST['plan'] ) : array(); // phpcs:ignore
		$clean = array();
		$labels_in = isset( $raw['label'] ) && is_array( $raw['label'] ) ? $raw['label'] : array();
		foreach ( $labels_in as $i => $label ) {
			$label = sanitize_text_field( $label );
			if ( '' === $label ) { continue; }
			$key = isset( $raw['key'][ $i ] ) && '' !== trim( $raw['key'][ $i ] ) ? sanitize_key( $raw['key'][ $i ] ) : '';
			if ( '' === $key ) {
				$key = sanitize_key( 'plan_' . ( $i + 1 ) . '_' . wp_rand( 100, 999 ) );
			}
			$clean[] = array(
				'key'      => $key,
				'label'    => $label,
				'price'    => (int) preg_replace( '/[^0-9]/', '', isset( $raw['price'][ $i ] ) ? $raw['price'][ $i ] : '0' ),
				'days'     => max( 1, (int) ( isset( $raw['days'][ $i ] ) ? $raw['days'][ $i ] : 30 ) ),
				'featured' => 0,
				'features' => array(),
			);
		}
		update_option( 'mam_plans', $clean );
		wp_safe_redirect( add_query_arg( array( 'page' => 'mam-plans', 'updated' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/* ----------------------------- Settings ------------------------------- */

	public function page_settings() {
		if ( ! current_user_can( self::CAP ) ) { return; }
		$s = MAM_Settings::instance()->all();
		include MAM_DIR . 'admin/views/settings.php';
	}

	public function save_settings() {
		if ( ! current_user_can( self::CAP ) || ! isset( $_POST['mam_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mam_settings_nonce'] ) ), 'mam_save_settings' ) ) {
			wp_die( 'دسترسی نامعتبر' );
		}
		$in  = wp_unslash( $_POST ); // phpcs:ignore
		$cur = MAM_Settings::instance()->all();

		$hex = function( $v, $fallback ) {
			$c = sanitize_hex_color( $v );
			return $c ? $c : $fallback;
		};

		$out = $cur;
		// Appearance.
		$out['color_bg']      = $hex( isset( $in['color_bg'] ) ? $in['color_bg'] : '', $cur['color_bg'] );
		$out['color_surface'] = $hex( isset( $in['color_surface'] ) ? $in['color_surface'] : '', $cur['color_surface'] );
		$out['color_brand']   = $hex( isset( $in['color_brand'] ) ? $in['color_brand'] : '', $cur['color_brand'] );
		$out['color_gold']    = $hex( isset( $in['color_gold'] ) ? $in['color_gold'] : '', $cur['color_gold'] );
		$out['color_text']    = $hex( isset( $in['color_text'] ) ? $in['color_text'] : '', $cur['color_text'] );
		$out['brand_name']    = sanitize_text_field( isset( $in['brand_name'] ) ? $in['brand_name'] : $cur['brand_name'] );

		// Messages.
		foreach ( array( 'msg_locked_title','msg_locked_body','msg_locked_btn','msg_dl_title','msg_dl_body','msg_dl_btn','msg_expired' ) as $k ) {
			$out[ $k ] = sanitize_text_field( isset( $in[ $k ] ) ? $in[ $k ] : $cur[ $k ] );
		}

		// Gateways.
		$out['gw_zarinpal_on']       = ! empty( $in['gw_zarinpal_on'] ) ? 1 : 0;
		$out['gw_zarinpal_merchant'] = sanitize_text_field( isset( $in['gw_zarinpal_merchant'] ) ? $in['gw_zarinpal_merchant'] : '' );
		$out['gw_zarinpal_sandbox']  = ! empty( $in['gw_zarinpal_sandbox'] ) ? 1 : 0;
		$out['gw_zibal_on']          = ! empty( $in['gw_zibal_on'] ) ? 1 : 0;
		$out['gw_zibal_merchant']    = sanitize_text_field( isset( $in['gw_zibal_merchant'] ) ? $in['gw_zibal_merchant'] : '' );
		$out['gw_card_on']           = ! empty( $in['gw_card_on'] ) ? 1 : 0;
		$out['gw_card_number']       = sanitize_text_field( isset( $in['gw_card_number'] ) ? $in['gw_card_number'] : '' );
		$out['gw_card_holder']       = sanitize_text_field( isset( $in['gw_card_holder'] ) ? $in['gw_card_holder'] : '' );
		$out['gw_card_desc']         = sanitize_textarea_field( isset( $in['gw_card_desc'] ) ? $in['gw_card_desc'] : '' );

		// Gateway logo images (optional — shown instead of the default icon).
		$out['gw_zarinpal_logo'] = esc_url_raw( isset( $in['gw_zarinpal_logo'] ) ? $in['gw_zarinpal_logo'] : '' );
		$out['gw_zibal_logo']    = esc_url_raw( isset( $in['gw_zibal_logo'] ) ? $in['gw_zibal_logo'] : '' );
		$out['gw_card_logo']     = esc_url_raw( isset( $in['gw_card_logo'] ) ? $in['gw_card_logo'] : '' );

		// Behaviour.
		$out['restrict_products'] = ! empty( $in['restrict_products'] ) ? 1 : 0;
		$out['auto_expire']       = ! empty( $in['auto_expire'] ) ? 1 : 0;
		$out['plans_page_id']     = (int) ( isset( $in['plans_page_id'] ) ? $in['plans_page_id'] : $cur['plans_page_id'] );
		$out['auth_page_id']      = (int) ( isset( $in['auth_page_id'] ) ? $in['auth_page_id'] : ( isset( $cur['auth_page_id'] ) ? $cur['auth_page_id'] : 0 ) );

		// Purchase-form fields (ordered).
		$fields = array();
		if ( isset( $in['field_key'] ) && is_array( $in['field_key'] ) ) {
			foreach ( $in['field_key'] as $i => $key ) {
				$key = sanitize_key( $key );
				if ( '' === $key ) { continue; }
				$fields[] = array(
					'key'      => $key,
					'label'    => sanitize_text_field( isset( $in['field_label'][ $i ] ) ? $in['field_label'][ $i ] : $key ),
					'type'     => in_array( ( isset( $in['field_type'][ $i ] ) ? $in['field_type'][ $i ] : 'text' ), array( 'text','email','tel','number','password' ), true ) ? $in['field_type'][ $i ] : 'text',
					'width'    => ( isset( $in['field_width'][ $i ] ) && 'full' === $in['field_width'][ $i ] ) ? 'full' : 'half',
					'enabled'  => ! empty( $in['field_enabled'][ $i ] ) ? 1 : 0,
					'required' => ! empty( $in['field_required'][ $i ] ) ? 1 : 0,
				);
			}
		}
		if ( ! empty( $fields ) ) {
			$out['form_fields'] = $fields;
		}

		// Checkout page copy.
		foreach ( array( 'checkout_title', 'checkout_subtitle', 'checkout_secure' ) as $k ) {
			if ( isset( $in[ $k ] ) ) {
				$out[ $k ] = sanitize_text_field( $in[ $k ] );
			}
		}

		// Discount codes.
		$codes = array();
		if ( isset( $in['disc_code'] ) && is_array( $in['disc_code'] ) ) {
			foreach ( $in['disc_code'] as $i => $code ) {
				$code = sanitize_text_field( $code );
				if ( '' === trim( $code ) ) { continue; }
				$type = ( isset( $in['disc_type'][ $i ] ) && 'fixed' === $in['disc_type'][ $i ] ) ? 'fixed' : 'percent';
				$val  = (int) preg_replace( '/[^0-9]/', '', isset( $in['disc_value'][ $i ] ) ? $in['disc_value'][ $i ] : '0' );
				if ( 'percent' === $type ) {
					$val = min( 100, max( 0, $val ) );
				}
				$codes[] = array( 'code' => $code, 'type' => $type, 'value' => $val );
			}
		}
		$out['discount_codes'] = $codes;

		MAM_Settings::instance()->save( $out );
		wp_safe_redirect( add_query_arg( array( 'page' => 'mam-settings', 'updated' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/* -------------------------- Edit a member ----------------------------- */

	public function edit_member() {
		if ( ! current_user_can( self::CAP ) || ! isset( $_POST['mam_member_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mam_member_nonce'] ) ), 'mam_edit_member' ) ) {
			wp_die( 'دسترسی نامعتبر' );
		}
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$action  = isset( $_POST['member_action'] ) ? sanitize_key( wp_unslash( $_POST['member_action'] ) ) : '';

		if ( $user_id && 'cancel' === $action ) {
			MAM_Membership::instance()->cancel( $user_id );
		} elseif ( $user_id && 'extend' === $action ) {
			$plan = isset( $_POST['plan_key'] ) ? sanitize_key( wp_unslash( $_POST['plan_key'] ) ) : '';
			$days = isset( $_POST['days'] ) ? (int) $_POST['days'] : 0;
			MAM_Membership::instance()->activate( $user_id, $plan, $days );
		} elseif ( $user_id && 'set_date' === $action ) {
			$date = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';
			if ( $date ) {
				MAM_Membership::instance()->set_expiry( $user_id, gmdate( 'Y-m-d H:i:s', strtotime( $date ) ) );
			}
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'mam-subscriptions', 'updated' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}
}

/* -------------------------------------------------------------------------
 *  Shared admin helpers (labels / badges)
 * ---------------------------------------------------------------------- */
function mam_method_label( $method ) {
	$map = array(
		'zarinpal'    => 'زرین‌پال',
		'zibal'       => 'زیبال',
		'card'        => 'کارت به کارت',
		'woocommerce' => 'ووکامرس',
	);
	return isset( $map[ $method ] ) ? $map[ $method ] : $method;
}

function mam_status_badge( $status ) {
	$map = array(
		'completed' => array( 'تکمیل شده', 'ok' ),
		'pending'   => array( 'در انتظار', 'warn' ),
		'failed'    => array( 'ناموفق', 'bad' ),
		'canceled'  => array( 'لغو شده', 'bad' ),
	);
	$d = isset( $map[ $status ] ) ? $map[ $status ] : array( $status, 'muted' );
	return '<span class="mam-badge mam-badge-' . esc_attr( $d[1] ) . '">' . esc_html( $d[0] ) . '</span>';
}
