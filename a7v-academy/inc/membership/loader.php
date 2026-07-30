<?php
/**
 * Membership module loader — integrates the VIP membership system INTO the
 * A7V Academy theme (no separate plugin required).
 *
 * Loaded from the theme's functions.php. Because a theme's functions.php runs
 * AFTER the `plugins_loaded` hook has already fired, we boot on
 * `after_setup_theme` and run the install/upgrade routine on `admin_init`
 * (themes have no activation hook).
 *
 * @package A7V
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---- Constants the membership classes expect (namespaced to the theme) ---- */
if ( ! defined( 'MAM_VERSION' ) ) {
	define( 'MAM_VERSION', ( defined( 'A7V_VER' ) ? A7V_VER : '1.0.0' ) . '-theme' );
}
define( 'MAM_FILE', __FILE__ );
define( 'MAM_DIR', trailingslashit( get_template_directory() . '/inc/membership' ) );
define( 'MAM_URL', trailingslashit( get_template_directory_uri() . '/inc/membership' ) );
define( 'MAM_BASENAME', 'a7v-academy/inc/membership/loader.php' );

/* ------------------------------- Includes -------------------------------- */
require_once MAM_DIR . 'includes/class-mam-install.php';
require_once MAM_DIR . 'includes/class-mam-settings.php';
require_once MAM_DIR . 'includes/class-mam-payments.php';
require_once MAM_DIR . 'includes/class-mam-membership.php';
require_once MAM_DIR . 'includes/class-mam-restrictions.php';
require_once MAM_DIR . 'includes/class-mam-gateways.php';
require_once MAM_DIR . 'includes/class-mam-shortcodes.php';
require_once MAM_DIR . 'includes/class-mam-ajax.php';
require_once MAM_DIR . 'includes/class-mam-woocommerce.php';
require_once MAM_DIR . 'includes/class-mam-account.php';
require_once MAM_DIR . 'includes/class-mam-assets.php';

if ( is_admin() ) {
	require_once MAM_DIR . 'admin/class-mam-admin.php';
	require_once MAM_DIR . 'admin/class-mam-metabox.php';
}

/* ------------------------------- Boot ------------------------------------ */
function mam_boot() {
	MAM_Settings::instance();
	MAM_Membership::instance();
	MAM_Restrictions::instance();
	MAM_Gateways::instance();
	MAM_Shortcodes::instance();
	MAM_Ajax::instance();
	MAM_WooCommerce::instance();
	MAM_Account::instance();
	MAM_Assets::instance();

	if ( is_admin() ) {
		MAM_Admin::instance();
		MAM_Metabox::instance();
	}
}
add_action( 'after_setup_theme', 'mam_boot', 9 );

/* --------------------------- Install / upgrade --------------------------- */
/**
 * Runs the DB/table + default options setup when the module version changes.
 * Fires on admin_init so it self-heals even if the theme was already active.
 */
function mam_theme_install_check() {
	if ( get_option( 'mam_db_version' ) === MAM_VERSION ) {
		return;
	}
	MAM_Install::create_tables();
	MAM_Install::seed_options();
	MAM_Install::maybe_create_plans_page();
	MAM_Install::maybe_create_checkout_page();

	if ( ! wp_next_scheduled( 'mam_daily_expiry_check' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mam_daily_expiry_check' );
	}

	// Register the My-Account endpoint then flush so the new VIP tab works.
	if ( class_exists( 'MAM_Account' ) ) {
		MAM_Account::instance()->add_endpoint();
	}
	flush_rewrite_rules();

	update_option( 'mam_db_version', MAM_VERSION );
}
add_action( 'admin_init', 'mam_theme_install_check' );

/* Clean up the cron event if the theme is switched away. */
function mam_theme_deactivate() {
	$ts = wp_next_scheduled( 'mam_daily_expiry_check' );
	if ( $ts ) {
		wp_unschedule_event( $ts, 'mam_daily_expiry_check' );
	}
}
add_action( 'switch_theme', 'mam_theme_deactivate' );

/* ------------------------------- Helpers --------------------------------- */
if ( ! function_exists( 'mam_membership' ) ) {
	function mam_membership( $user_id = 0 ) {
		return MAM_Membership::instance()->get_status( $user_id );
	}
}
if ( ! function_exists( 'mam_get_setting' ) ) {
	function mam_get_setting( $key, $default = null ) {
		return MAM_Settings::instance()->get( $key, $default );
	}
}

if ( ! function_exists( 'mam_auth_url' ) ) {
	/**
	 * URL of the site's login / register page.
	 * Priority: an admin-selected page → an auto-detected login/register page
	 * → the default WordPress login screen (wp-login.php).
	 *
	 * @param string $redirect Optional URL to return to after login.
	 * @return string
	 */
	function mam_auth_url( $redirect = '' ) {
		static $base = null;

		if ( null === $base ) {
			$base = '';

			// 1) Admin-selected page (Settings → صفحه ورود/ثبت‌نام).
			$pid = (int) mam_get_setting( 'auth_page_id', 0 );
			if ( $pid && 'publish' === get_post_status( $pid ) ) {
				$base = get_permalink( $pid );
			}

			// 2) Auto-detect a login / register page by slug or title.
			if ( ! $base ) {
				$needles = array( 'ورود', 'ثبت', 'login', 'log-in', 'sign', 'signin', 'account', 'حساب', 'عضویت' );
				$pages   = get_posts( array(
					'post_type'   => 'page',
					'post_status' => 'publish',
					'numberposts' => 100,
					'fields'      => 'ids',
				) );
				foreach ( $pages as $p ) {
					$hay = rawurldecode( (string) get_post_field( 'post_name', $p ) ) . ' ' . get_the_title( $p );
					foreach ( $needles as $n ) {
						if ( false !== strpos( $hay, $n ) ) { $base = get_permalink( $p ); break 2; }
					}
				}
			}
		}

		// 3) Fallback to the WordPress login screen.
		if ( ! $base ) {
			return $redirect ? wp_login_url( $redirect ) : wp_login_url();
		}

		return $redirect ? add_query_arg( 'redirect_to', rawurlencode( $redirect ), $base ) : $base;
	}
}
