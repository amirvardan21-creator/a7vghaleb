<?php
/**
 * A7V Authentication — username + password register / login + profile.
 *
 * Registration collects: username, phone (optional), password.
 * Login: username + password (validated via wp_signon).
 *
 * @package A7V
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$GLOBALS['a7v_auth_msg']  = '';
$GLOBALS['a7v_auth_type'] = 'success';

/* ---------- URL helpers ---------- */
function a7v_register_url() {
	$pid = get_option( 'a7v_register_pid' );
	return ( $pid && 'publish' === get_post_status( $pid ) ) ? get_permalink( $pid ) : wp_registration_url();
}
function a7v_dashboard_url() {
	$pid = get_option( 'a7v_dashboard_pid' );
	return ( $pid && 'publish' === get_post_status( $pid ) ) ? get_permalink( $pid ) : home_url( '/' );
}
function a7v_account_url() {
	return is_user_logged_in() ? a7v_dashboard_url() : a7v_register_url();
}

/* ---------- Phone normalizer (Persian/Arabic digits -> EN) ---------- */
function a7v_normalize_phone( $raw ) {
	$raw = wp_unslash( (string) $raw );
	$fa  = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
	$ar  = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
	$en  = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$raw = str_replace( $fa, $en, $raw );
	$raw = str_replace( $ar, $en, $raw );
	return preg_replace( '/\D/', '', $raw );
}

/* ---------- Process register / login / profile ---------- */
function a7v_process_auth() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['a7v_action'] ) ) {
		return;
	}
	if ( ! isset( $_POST['a7v_nonce'] ) || ! wp_verify_nonce( $_POST['a7v_nonce'], 'a7v_auth' ) ) {
		$GLOBALS['a7v_auth_msg']  = 'خطای امنیتی. دوباره تلاش کنید.';
		$GLOBALS['a7v_auth_type'] = 'error';
		return;
	}

	$action = sanitize_text_field( wp_unslash( $_POST['a7v_action'] ) );

	/* ----- REGISTER ----- */
	if ( 'register' === $action ) {
		$username = sanitize_user( wp_unslash( $_POST['a7v_user'] ?? '' ), true );
		$phone    = a7v_normalize_phone( $_POST['a7v_phone'] ?? '' );
		$pass     = trim( (string) wp_unslash( $_POST['a7v_pass'] ?? '' ) );

		if ( '' === $username ) {
			$GLOBALS['a7v_auth_msg']  = 'نام کاربری معتبر وارد کنید (حروف انگلیسی یا عدد).';
			$GLOBALS['a7v_auth_type'] = 'error';
			return;
		}
		if ( strlen( $pass ) < 4 ) {
			$GLOBALS['a7v_auth_msg']  = 'رمز عبور باید حداقل ۴ کاراکتر باشد.';
			$GLOBALS['a7v_auth_type'] = 'error';
			return;
		}
		if ( username_exists( $username ) ) {
			$GLOBALS['a7v_auth_msg']  = 'این نام کاربری قبلاً ثبت شده است. لطفاً وارد شوید.';
			$GLOBALS['a7v_auth_type'] = 'error';
			return;
		}

		$email = ( strlen( $phone ) >= 10 ) ? $phone . '@a7v.local' : 'user' . time() . wp_rand( 100, 999 ) . '@a7v.local';

		$uid = wp_insert_user( array(
			'user_login'   => $username,
			'user_pass'    => $pass,
			'user_email'   => $email,
			'display_name' => $username,
			'nickname'     => $username,
			'role'         => 'subscriber',
		) );

		if ( is_wp_error( $uid ) ) {
			$GLOBALS['a7v_auth_msg']  = $uid->get_error_message();
			$GLOBALS['a7v_auth_type'] = 'error';
			return;
		}

		if ( strlen( $phone ) >= 10 ) {
			update_user_meta( $uid, 'a7v_phone', $phone );
		}
		update_user_meta( $uid, 'a7v_member_since', current_time( 'mysql' ) );

		wp_set_current_user( $uid );
		wp_set_auth_cookie( $uid, true );
		wp_safe_redirect( a7v_dashboard_url() );
		exit;
	}

	/* ----- LOGIN (username + password) ----- */
	if ( 'login' === $action ) {
		$creds = array(
			'user_login'    => sanitize_user( wp_unslash( $_POST['a7v_user'] ?? '' ) ),
			'user_password' => trim( (string) wp_unslash( $_POST['a7v_pass'] ?? '' ) ),
			'remember'      => true,
		);
		$user = wp_signon( $creds, is_ssl() );
		if ( is_wp_error( $user ) ) {
			$GLOBALS['a7v_auth_msg']  = 'نام کاربری یا رمز عبور اشتباه است.';
			$GLOBALS['a7v_auth_type'] = 'error';
			return;
		}
		wp_safe_redirect( a7v_dashboard_url() );
		exit;
	}

	/* ----- UPDATE PROFILE ----- */
	if ( 'update_profile' === $action && is_user_logged_in() ) {
		$uid     = get_current_user_id();
		$display = sanitize_text_field( wp_unslash( $_POST['a7v_display'] ?? '' ) );
		$phone   = a7v_normalize_phone( $_POST['a7v_phone'] ?? '' );
		$newpass = trim( (string) wp_unslash( $_POST['a7v_newpass'] ?? '' ) );

		if ( '' !== $display ) {
			wp_update_user( array( 'ID' => $uid, 'display_name' => $display ) );
		}
		if ( strlen( $phone ) >= 10 ) {
			update_user_meta( $uid, 'a7v_phone', $phone );
		}
		if ( '' !== $newpass ) {
			if ( strlen( $newpass ) < 4 ) {
				$GLOBALS['a7v_auth_msg']  = 'رمز جدید باید حداقل ۴ کاراکتر باشد.';
				$GLOBALS['a7v_auth_type'] = 'error';
				return;
			}
			wp_set_password( $newpass, $uid );
			wp_set_current_user( $uid );
			wp_set_auth_cookie( $uid, true );
		}
		$GLOBALS['a7v_auth_msg']  = 'اطلاعات حساب با موفقیت به‌روزرسانی شد.';
		$GLOBALS['a7v_auth_type'] = 'success';
	}
}
add_action( 'template_redirect', 'a7v_process_auth' );

/* ---------- Auto-create auth pages ---------- */
function a7v_create_pages() {
	$defs = array(
		'a7v_register_pid'  => array( 'ثبت‌نام / ورود', 'page-templates/template-register.php' ),
		'a7v_dashboard_pid' => array( 'حساب کاربری', 'page-templates/template-dashboard.php' ),
	);
	foreach ( $defs as $opt => $d ) {
		$existing = get_option( $opt );
		if ( $existing && 'publish' === get_post_status( $existing ) ) { continue; }
		$pid = wp_insert_post( array(
			'post_title'   => $d[0],
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		) );
		if ( $pid && ! is_wp_error( $pid ) ) {
			update_post_meta( $pid, '_wp_page_template', $d[1] );
			update_option( $opt, $pid );
		}
	}
}
add_action( 'admin_init', 'a7v_create_pages' );

/* ---------- Subscription helper (demo) ---------- */
function a7v_subscription_days( $uid = 0 ) {
	$uid   = $uid ? $uid : get_current_user_id();
	$since = get_user_meta( $uid, 'a7v_member_since', true );
	$exp   = $since ? strtotime( $since . ' +1 year' ) : strtotime( '+1 year' );
	return max( 0, (int) ceil( ( $exp - time() ) / DAY_IN_SECONDS ) );
}

/* ---------- Shortcode (alternative to page template) ---------- */
function a7v_sc_register() {
	ob_start();
	include locate_template( 'page-templates/parts-auth.php' );
	return ob_get_clean();
}
add_shortcode( 'a7v_register', 'a7v_sc_register' );
