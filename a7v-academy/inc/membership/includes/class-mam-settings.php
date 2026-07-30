<?php
/**
 * Settings store — everything configurable, nothing hard-coded.
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_Settings {

	private static $instance = null;
	private $settings = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->settings = wp_parse_args( get_option( 'mam_settings', array() ), self::defaults() );
	}

	/** Read a single setting (dot-free, top-level or nested via array access in callers). */
	public function get( $key, $default = null ) {
		if ( isset( $this->settings[ $key ] ) ) {
			return $this->settings[ $key ];
		}
		return null === $default ? ( isset( self::defaults()[ $key ] ) ? self::defaults()[ $key ] : null ) : $default;
	}

	/** All settings. */
	public function all() {
		return $this->settings;
	}

	/** Persist settings (already sanitized by caller). */
	public function save( $settings ) {
		$this->settings = wp_parse_args( $settings, self::defaults() );
		update_option( 'mam_settings', $this->settings );
	}

	/* ------------------------------------------------------------------ */

	/** Default settings. */
	public static function defaults() {
		return array(
			// Appearance.
			'color_bg'        => '#0a0a0b',
			'color_surface'   => '#141416',
			'color_brand'     => '#FF0000',
			'color_gold'      => '#c9a24b',
			'color_text'      => '#f4f4f5',
			'brand_name'      => 'آکادمی مافیا',

			// Pages.
			'plans_page_id'    => 0,
			'checkout_page_id' => 0,
			'auth_page_id'     => 0,

			// Discount / coupon codes (array of { code, type: percent|fixed, value }).
			'discount_codes'  => array(),

			// Checkout page copy.
			'checkout_title'   => 'اطلاعات پرداخت',
			'checkout_subtitle'=> 'لطفاً اطلاعات خود را وارد کنید',
			'checkout_secure'  => 'اطلاعات شما امن و محفوظ است',

			// Messages.
			'msg_locked_title'   => 'این پرونده محرمانه است 🔒',
			'msg_locked_body'    => 'برای دسترسی به این محتوا باید اشتراک ویژهٔ آکادمی مافیا را فعال کنید.',
			'msg_locked_btn'     => 'مشاهده پلن‌های عضویت',
			'msg_dl_title'       => 'دسترسی شما به این فایل محدود است 🔒',
			'msg_dl_body'        => 'برای دریافت فایل، عضویت ویژه خریداری کنید.',
			'msg_dl_btn'         => 'خرید اشتراک',
			'msg_expired'        => 'اشتراک شما منقضی شده است. برای ادامهٔ دسترسی، تمدید کنید.',

			// Gateways.
			'gw_zarinpal_on'    => 0,
			'gw_zarinpal_merchant' => '',
			'gw_zarinpal_sandbox'  => 0,
			'gw_zarinpal_logo'  => '',
			'gw_zibal_on'       => 0,
			'gw_zibal_merchant' => '',
			'gw_zibal_logo'     => '',
			'gw_card_on'        => 1,
			'gw_card_number'    => '',
			'gw_card_holder'    => '',
			'gw_card_logo'      => '',
			'gw_card_desc'      => 'مبلغ پلن انتخابی را به شمارهٔ کارت زیر واریز کنید و سپس فرم زیر را تکمیل نمایید.',

			// Purchase form fields (order + enabled + required).
			'form_fields'       => self::default_form_fields(),

			// Behaviour.
			'restrict_products' => 1, // integrate WooCommerce product restriction.
			'auto_expire'       => 1,
		);
	}

	/** Default purchase-form fields (ordered). */
	public static function default_form_fields() {
		return array(
			array( 'key' => 'full_name', 'label' => 'نام و نام خانوادگی', 'type' => 'text',     'enabled' => 1, 'required' => 1, 'width' => 'full' ),
			array( 'key' => 'email',     'label' => 'ایمیل',               'type' => 'email',    'enabled' => 1, 'required' => 1, 'width' => 'full' ),
			array( 'key' => 'username',  'label' => 'نام کاربری',          'type' => 'text',     'enabled' => 1, 'required' => 1, 'width' => 'half' ),
			array( 'key' => 'password',  'label' => 'رمز عبور',            'type' => 'password', 'enabled' => 1, 'required' => 1, 'width' => 'half' ),
			array( 'key' => 'phone',     'label' => 'شماره تماس',          'type' => 'tel',      'enabled' => 1, 'required' => 1, 'width' => 'full' ),
		);
	}

	/** Default membership plans. */
	public static function default_plans() {
		return array(
			array(
				'key'      => 'monthly',
				'label'    => 'ماهانه',
				'price'    => 390000,
				'days'     => 30,
				'featured' => 0,
				'features' => array( 'دسترسی کامل به محتوای ویژه', 'پشتیبانی استاندارد', 'به‌روزرسانی محتوا' ),
			),
			array(
				'key'      => 'yearly',
				'label'    => 'سالانه',
				'price'    => 1990000,
				'days'     => 365,
				'featured' => 1,
				'features' => array( 'دسترسی کامل به تمام محتواها', 'پشتیبانی ویژه VIP', 'تخفیف روی دوره‌ها', 'محتوای اختصاصی مافیا' ),
			),
			array(
				'key'      => 'sixmonth',
				'label'    => '۶ ماهه',
				'price'    => 1090000,
				'days'     => 180,
				'featured' => 0,
				'features' => array( 'دسترسی کامل به محتوای ویژه', 'پشتیبانی ویژه', 'به‌روزرسانی محتوا' ),
			),
		);
	}

	/* --------------------------- Discount codes --------------------------- */

	/** All configured discount codes (normalized). */
	public static function discount_codes() {
		$codes = get_option( 'mam_settings' );
		$codes = is_array( $codes ) && isset( $codes['discount_codes'] ) ? $codes['discount_codes'] : array();
		return is_array( $codes ) ? $codes : array();
	}

	/**
	 * Find a discount code (case-insensitive).
	 *
	 * @return array|null { code, type: percent|fixed, value }
	 */
	public static function find_discount( $code ) {
		$code = trim( (string) $code );
		if ( '' === $code ) {
			return null;
		}
		foreach ( self::discount_codes() as $d ) {
			if ( isset( $d['code'] ) && 0 === strcasecmp( trim( $d['code'] ), $code ) ) {
				return array(
					'code'  => $d['code'],
					'type'  => ( isset( $d['type'] ) && 'fixed' === $d['type'] ) ? 'fixed' : 'percent',
					'value' => max( 0, (int) ( isset( $d['value'] ) ? $d['value'] : 0 ) ),
				);
			}
		}
		return null;
	}

	/**
	 * Compute the price after applying a discount code.
	 *
	 * @param int    $price Base price (Toman).
	 * @param string $code  Discount code entered by the user.
	 * @return array { valid, discount, final, label }
	 */
	public static function compute_discount( $price, $code ) {
		$price = max( 0, (int) $price );
		$out   = array( 'valid' => false, 'discount' => 0, 'final' => $price, 'label' => '' );

		$d = self::find_discount( $code );
		if ( ! $d ) {
			return $out;
		}

		if ( 'percent' === $d['type'] ) {
			$pct              = min( 100, max( 0, (int) $d['value'] ) );
			$out['discount']  = (int) round( $price * $pct / 100 );
			$out['label']     = $pct . '٪ تخفیف';
		} else {
			$out['discount']  = min( $price, (int) $d['value'] );
			$out['label']     = number_format( (int) $d['value'] ) . ' تومان تخفیف';
		}

		$out['final']  = max( 0, $price - $out['discount'] );
		$out['valid']  = true;
		return $out;
	}

	/** Get plans (option). */
	public static function plans() {
		$plans = get_option( 'mam_plans', array() );
		return is_array( $plans ) && ! empty( $plans ) ? $plans : self::default_plans();
	}

	/** Find a plan by key. */
	public static function plan( $key ) {
		foreach ( self::plans() as $p ) {
			if ( isset( $p['key'] ) && $p['key'] === $key ) {
				return $p;
			}
		}
		return null;
	}
}
