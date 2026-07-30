<?php
/**
 * A7V Academy — Theme bootstrap
 *
 * @package A7V
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'A7V_VER', '1.7.0' );
define( 'A7V_DIR', get_template_directory() );
define( 'A7V_URI', get_template_directory_uri() );

require_once A7V_DIR . '/inc/cpt.php';
require_once A7V_DIR . '/inc/meta.php';
require_once A7V_DIR . '/inc/customizer.php';
require_once A7V_DIR . '/inc/demo-data.php';
require_once A7V_DIR . '/inc/template-functions.php';
require_once A7V_DIR . '/inc/auth.php';
require_once A7V_DIR . '/inc/home-builder.php';
require_once A7V_DIR . '/inc/product-builder.php';
require_once A7V_DIR . '/inc/bottom-bar.php';
require_once A7V_DIR . '/inc/engagement.php';
require_once A7V_DIR . '/inc/membership/loader.php';

/**
 * Theme setup
 */
function a7v_setup() {
	load_theme_textdomain( 'a7v', A7V_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );

	add_image_size( 'a7v-square', 640, 640, true );

	register_nav_menus( array(
		'primary'        => __( 'منوی اصلی', 'a7v' ),
		'footer_quick'   => __( 'فوتر: دسترسی سریع', 'a7v' ),
		'footer_special' => __( 'فوتر: بخش‌های ویژه', 'a7v' ),
		'footer_info'    => __( 'فوتر: اطلاعات', 'a7v' ),
	) );
}
add_action( 'after_setup_theme', 'a7v_setup' );

/**
 * Enqueue assets
 */
function a7v_assets() {
	$css     = A7V_DIR . '/assets/css/main.css';
	$js      = A7V_DIR . '/assets/js/theme.js';
	$css_ver = file_exists( $css ) ? filemtime( $css ) : A7V_VER;
	$js_ver  = file_exists( $js ) ? filemtime( $js ) : A7V_VER;

	wp_enqueue_style( 'a7v-main', A7V_URI . '/assets/css/main.css', array(), $css_ver );
	if ( function_exists( 'a7v_dynamic_css' ) ) {
		wp_add_inline_style( 'a7v-main', a7v_dynamic_css() );
	}

	wp_enqueue_script( 'a7v-theme', A7V_URI . '/assets/js/theme.js', array(), $js_ver, true );
}
add_action( 'wp_enqueue_scripts', 'a7v_assets' );

/**
 * Content width
 */
function a7v_content_width() {
	$GLOBALS['content_width'] = 1200;
}
add_action( 'after_setup_theme', 'a7v_content_width', 0 );

/**
 * Register footer widget area (optional)
 */
function a7v_widgets() {
	register_sidebar( array(
		'name'          => __( 'فوتر — ستون اطلاعات', 'a7v' ),
		'id'            => 'footer-widgets',
		'before_widget' => '<div class="footer-widget">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4>',
		'after_title'   => '</h4>',
	) );
}
add_action( 'widgets_init', 'a7v_widgets' );

/**
 * Flush rewrite rules on theme activation so CPT permalinks work.
 */
function a7v_activate() {
	a7v_register_cpts();
	if ( function_exists( 'a7v_create_pages' ) ) {
		a7v_create_pages();
	}
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'a7v_activate' );
