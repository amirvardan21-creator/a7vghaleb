<?php
/**
 * Installation: database tables, default options, capabilities.
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_Install {

	/** Table name (without prefix). */
	const TABLE = 'mam_payments';

	/** Fully-qualified payments table name. */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Run on activation.
	 */
	public static function activate() {
		self::create_tables();
		self::seed_options();
		self::maybe_create_plans_page();

		// Schedule the daily expiry check.
		if ( ! wp_next_scheduled( 'mam_daily_expiry_check' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mam_daily_expiry_check' );
		}

		update_option( 'mam_db_version', MAM_VERSION );
		flush_rewrite_rules();
	}

	/**
	 * Run on deactivation.
	 */
	public static function deactivate() {
		$ts = wp_next_scheduled( 'mam_daily_expiry_check' );
		if ( $ts ) {
			wp_unschedule_event( $ts, 'mam_daily_expiry_check' );
		}
		flush_rewrite_rules();
	}

	/**
	 * Create the payments / subscriptions table.
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			plan_key VARCHAR(64) NOT NULL DEFAULT '',
			plan_label VARCHAR(191) NOT NULL DEFAULT '',
			amount DECIMAL(18,2) NOT NULL DEFAULT 0,
			method VARCHAR(32) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			txn_ref VARCHAR(191) NOT NULL DEFAULT '',
			authority VARCHAR(191) NOT NULL DEFAULT '',
			duration_days INT(11) NOT NULL DEFAULT 0,
			start_date DATETIME NULL,
			end_date DATETIME NULL,
			receipt_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			form_data LONGTEXT NULL,
			note TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY status (status),
			KEY method (method),
			KEY created_at (created_at)
		) {$collate};";

		dbDelta( $sql );
	}

	/**
	 * Seed default options if not present.
	 */
	public static function seed_options() {
		if ( false === get_option( 'mam_settings', false ) ) {
			add_option( 'mam_settings', MAM_Settings::defaults() );
		}
		if ( false === get_option( 'mam_plans', false ) ) {
			add_option( 'mam_plans', MAM_Settings::default_plans() );
		}
	}

	/**
	 * Create a "پلن‌های عضویت" page containing the plans shortcode.
	 */
	public static function maybe_create_plans_page() {
		$settings = get_option( 'mam_settings', array() );
		$existing = isset( $settings['plans_page_id'] ) ? (int) $settings['plans_page_id'] : 0;

		if ( $existing && 'publish' === get_post_status( $existing ) ) {
			return;
		}

		$page_id = wp_insert_post( array(
			'post_title'   => 'پلن‌های عضویت ویژه',
			'post_name'    => 'vip-plans',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '[mafia_plans]',
		) );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			$settings = get_option( 'mam_settings', array() );
			$settings['plans_page_id'] = $page_id;
			update_option( 'mam_settings', $settings );
		}
	}

	/**
	 * Create a dedicated "پرداخت اشتراک" checkout page holding the purchase form.
	 * Each plan links directly to this page ( ?plan=<key> ) — no shortcode needed.
	 */
	public static function maybe_create_checkout_page() {
		$tpl      = 'page-templates/template-checkout.php';
		$settings = get_option( 'mam_settings', array() );
		$existing = isset( $settings['checkout_page_id'] ) ? (int) $settings['checkout_page_id'] : 0;

		if ( $existing && 'publish' === get_post_status( $existing ) ) {
			// Ensure the existing checkout page uses the full-width template.
			if ( get_page_template_slug( $existing ) !== $tpl ) {
				update_post_meta( $existing, '_wp_page_template', $tpl );
			}
			return;
		}

		$page_id = wp_insert_post( array(
			'post_title'   => 'پرداخت اشتراک',
			'post_name'    => 'vip-checkout',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '[mafia_purchase_form]',
			'page_template'=> $tpl,
		) );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_post_meta( $page_id, '_wp_page_template', $tpl );
			$settings = get_option( 'mam_settings', array() );
			$settings['checkout_page_id'] = $page_id;
			update_option( 'mam_settings', $settings );
		}
	}
}
