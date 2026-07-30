<?php
/**
 * Front-end asset loader + dynamic theme colors from settings.
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_Assets {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'front' ) );
	}

	public function front() {
		$css = MAM_DIR . 'assets/css/mafia-front.css';
		$js  = MAM_DIR . 'assets/js/mafia-front.js';

		wp_enqueue_style( 'mam-front', MAM_URL . 'assets/css/mafia-front.css', array(), file_exists( $css ) ? filemtime( $css ) : MAM_VERSION );
		wp_add_inline_style( 'mam-front', $this->dynamic_css() );

		wp_enqueue_script( 'mam-front', MAM_URL . 'assets/js/mafia-front.js', array(), file_exists( $js ) ? filemtime( $js ) : MAM_VERSION, true );
	}

	/** CSS variables derived from the settings. */
	public function dynamic_css() {
		$s = MAM_Settings::instance();
		return sprintf(
			':root{--mam-bg:%s;--mam-surface:%s;--mam-brand:%s;--mam-gold:%s;--mam-text:%s;}',
			sanitize_hex_color( $s->get( 'color_bg' ) ) ?: '#0a0a0b',
			sanitize_hex_color( $s->get( 'color_surface' ) ) ?: '#141416',
			sanitize_hex_color( $s->get( 'color_brand' ) ) ?: '#FF0000',
			sanitize_hex_color( $s->get( 'color_gold' ) ) ?: '#c9a24b',
			sanitize_hex_color( $s->get( 'color_text' ) ) ?: '#f4f4f5'
		);
	}
}
