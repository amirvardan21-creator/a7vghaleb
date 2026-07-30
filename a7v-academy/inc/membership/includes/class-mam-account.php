<?php
/**
 * Adds a NEW "عضویت ویژه" tab to the WooCommerce My-Account page.
 *
 * This is purely additive: it registers a new endpoint/menu item and does
 * NOT modify, remove, or replace any existing account tab or template.
 * If WooCommerce is not active, nothing happens.
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_Account {

	private static $instance = null;
	const ENDPOINT = 'vip-membership';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'menu_item' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'endpoint_content' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
	}

	public function add_endpoint() {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	public function query_vars( $vars ) {
		$vars[] = self::ENDPOINT;
		return $vars;
	}

	/** Insert the new tab after "dashboard" without touching the others. */
	public function menu_item( $items ) {
		$new = array();
		foreach ( $items as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'dashboard' === $key ) {
				$new[ self::ENDPOINT ] = 'عضویت ویژه';
			}
		}
		if ( ! isset( $new[ self::ENDPOINT ] ) ) {
			$new[ self::ENDPOINT ] = 'عضویت ویژه';
		}
		return $new;
	}

	/** Render the membership status inside the new tab. */
	public function endpoint_content() {
		echo '<div class="mam-account-tab">';
		echo do_shortcode( '[mafia_status]' );
		echo '<p style="margin-top:18px"><a class="mam-btn mam-btn-ghost" href="' . esc_url( MAM_Restrictions::instance()->plans_url() ) . '">مشاهده پلن‌های عضویت</a></p>';
		echo '</div>';
	}
}
