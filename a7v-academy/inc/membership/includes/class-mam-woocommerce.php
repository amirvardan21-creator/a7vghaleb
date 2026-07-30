<?php
/**
 * WooCommerce integration (fully optional — guarded when WC is inactive).
 *
 * SUBSCRIPTION MODEL (single-membership site):
 * - Plans are NOT separate WooCommerce products. They are pure membership
 *   settings (name / price / duration) stored in the `mam_plans` option.
 * - Each plan gets a DIRECT "buy" link ( ?mam_buy=<plan_key> ) that:
 *     1. empties the cart,
 *     2. adds ONE hidden internal product priced dynamically to the plan price,
 *     3. redirects the user STRAIGHT to the WooCommerce checkout page
 *        (no cart page, no per-plan product).
 * - When the WooCommerce order is paid/completed, the membership for the
 *   matching plan is activated/extended and ALL restricted content unlocks.
 *
 * Backward compatible: if a shop owner still maps a real product to a plan via
 * the "_mam_plan_key" product field, that keeps working too.
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_WooCommerce {

	private static $instance = null;

	/** Option key holding the internal membership product ID. */
	const PRODUCT_OPTION = 'mam_membership_product_id';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Legacy product-mapping field (still supported).
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'product_field' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_field' ) );

		// Direct-to-checkout buy flow (no cart, no per-plan product).
		add_action( 'template_redirect', array( $this, 'handle_buy' ) );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_cart_price' ), 20 );
		add_filter( 'woocommerce_cart_item_name', array( $this, 'cart_item_name' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_line_item_meta' ), 10, 4 );

		// Keep the internal product out of the shop + search results.
		add_action( 'pre_get_posts', array( $this, 'hide_internal_product' ) );

		// Activate membership on order completion / payment.
		add_action( 'woocommerce_order_status_completed', array( $this, 'process_order' ) );
		add_action( 'woocommerce_payment_complete', array( $this, 'process_order' ) );
	}

	/** True if WooCommerce is active. */
	public function wc_active() {
		return class_exists( 'WooCommerce' );
	}

	/* ===================================================================
	 *  DIRECT BUY  →  WOOCOMMERCE CHECKOUT (no cart, no per-plan product)
	 * =================================================================== */

	/**
	 * Build the direct buy URL for a plan.
	 * Clicking it sends the user straight to the WooCommerce checkout page.
	 */
	public function buy_url( $plan_key ) {
		return add_query_arg( 'mam_buy', rawurlencode( $plan_key ), home_url( '/' ) );
	}

	/**
	 * Get (or lazily create) the single hidden internal product used purely as
	 * a WooCommerce payment vehicle for every subscription plan.
	 *
	 * @return int Product ID (0 if WooCommerce is inactive).
	 */
	public function membership_product_id() {
		$pid = (int) get_option( self::PRODUCT_OPTION, 0 );
		if ( $pid && 'product' === get_post_type( $pid ) && 'trash' !== get_post_status( $pid ) ) {
			return $pid;
		}
		if ( ! $this->wc_active() ) {
			return 0;
		}

		$product = new WC_Product_Simple();
		$product->set_name( 'اشتراک ویژه آکادمی مافیا' );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_virtual( true );
		$product->set_sold_individually( true );
		$product->set_regular_price( '1000' );
		$product->set_price( '1000' );
		$product->update_meta_data( '_mam_internal', '1' );
		$pid = (int) $product->save();

		update_option( self::PRODUCT_OPTION, $pid );
		return $pid;
	}

	/**
	 * Handle a ?mam_buy=<plan_key> click: reset cart, add the internal product
	 * carrying the plan key, then redirect straight to checkout.
	 */
	public function handle_buy() {
		if ( empty( $_GET['mam_buy'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$plan_key = sanitize_key( wp_unslash( $_GET['mam_buy'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$plan     = MAM_Settings::plan( $plan_key );
		$plans_url = MAM_Restrictions::instance()->plans_url();

		if ( ! $plan ) {
			wp_safe_redirect( $plans_url );
			exit;
		}

		// If WooCommerce is inactive, gracefully fall back to the built-in form.
		if ( ! $this->wc_active() ) {
			wp_safe_redirect( add_query_arg( 'plan', $plan_key, $plans_url ) . '#mam-form' );
			exit;
		}

		// A subscription must be attached to a real user account.
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( mam_auth_url( $this->buy_url( $plan_key ) ) );
			exit;
		}

		$pid = $this->membership_product_id();
		if ( ! $pid ) {
			wp_safe_redirect( $plans_url );
			exit;
		}

		// Make sure the cart/session is loaded on this front-end request.
		if ( is_null( WC()->cart ) ) {
			if ( function_exists( 'wc_load_cart' ) ) {
				wc_load_cart();
			}
		}
		if ( is_null( WC()->cart ) ) {
			wp_safe_redirect( $plans_url );
			exit;
		}

		// Reset cart so ONLY the chosen subscription is being paid for.
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $pid, 1, 0, array(), array( 'mam_plan_key' => $plan_key ) );

		// Straight to the payment/checkout page — no cart page.
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}

	/** Dynamically price the internal product to the chosen plan's price. */
	public function apply_cart_price( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			// still fine to run again; loop is idempotent.
		}
		foreach ( $cart->get_cart() as $item ) {
			if ( empty( $item['mam_plan_key'] ) ) {
				continue;
			}
			$plan = MAM_Settings::plan( $item['mam_plan_key'] );
			if ( $plan && isset( $item['data'] ) && is_object( $item['data'] ) ) {
				$item['data']->set_price( (float) $plan['price'] );
			}
		}
	}

	/** Show the plan label as the cart line title. */
	public function cart_item_name( $name, $item, $key ) {
		if ( ! empty( $item['mam_plan_key'] ) ) {
			$plan = MAM_Settings::plan( $item['mam_plan_key'] );
			if ( $plan ) {
				return esc_html( 'اشتراک ' . $plan['label'] );
			}
		}
		return $name;
	}

	/** Show the plan duration under the checkout line item. */
	public function cart_item_data( $data, $item ) {
		if ( ! empty( $item['mam_plan_key'] ) ) {
			$plan = MAM_Settings::plan( $item['mam_plan_key'] );
			if ( $plan ) {
				$data[] = array(
					'key'   => 'مدت اعتبار',
					'value' => (int) $plan['days'] . ' روز',
				);
			}
		}
		return $data;
	}

	/** Persist the plan key (and a friendly name) onto the order line item. */
	public function add_line_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['mam_plan_key'] ) ) {
			return;
		}
		$plan_key = sanitize_key( $values['mam_plan_key'] );
		$item->add_meta_data( '_mam_plan_key', $plan_key, true );

		$plan = MAM_Settings::plan( $plan_key );
		if ( $plan ) {
			$item->set_name( 'اشتراک ' . $plan['label'] );
		}
	}

	/** Keep the internal payment product out of shop/search listings. */
	public function hide_internal_product( $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}
		$pid = (int) get_option( self::PRODUCT_OPTION, 0 );
		if ( ! $pid ) {
			return;
		}
		$ex   = (array) $q->get( 'post__not_in' );
		$ex[] = $pid;
		$q->set( 'post__not_in', array_unique( array_filter( $ex ) ) );
	}

	/* ===================================================================
	 *  LEGACY: map a real WooCommerce product to a plan (still supported)
	 * =================================================================== */

	/** Add a "membership plan" selector to the product edit screen. */
	public function product_field() {
		if ( ! function_exists( 'woocommerce_wp_select' ) ) {
			return;
		}
		$options = array( '' => '— بدون اشتراک —' );
		foreach ( MAM_Settings::plans() as $p ) {
			$options[ $p['key'] ] = $p['label'] . ' (' . number_format( (int) $p['price'] ) . ' تومان)';
		}
		echo '<div class="options_group">';
		woocommerce_wp_select( array(
			'id'          => '_mam_plan_key',
			'label'       => 'اشتراک آکادمی مافیا',
			'description' => 'با تکمیل سفارش این محصول، این پلن اشتراک برای کاربر فعال می‌شود.',
			'desc_tip'    => true,
			'options'     => $options,
		) );
		echo '</div>';
	}

	/** Save the product plan mapping. */
	public function save_product_field( $post_id ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$val = isset( $_POST['_mam_plan_key'] ) ? sanitize_key( wp_unslash( $_POST['_mam_plan_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		update_post_meta( $post_id, '_mam_plan_key', $val );
	}

	/* ===================================================================
	 *  ORDER → MEMBERSHIP ACTIVATION
	 * =================================================================== */

	/**
	 * When an order completes/pays, activate membership for:
	 *   - the direct-buy internal product (plan key stored on the line item), OR
	 *   - any legacy product mapped to a plan via _mam_plan_key.
	 */
	public function process_order( $order_id ) {
		if ( ! $this->wc_active() ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		if ( $order->get_meta( '_mam_processed' ) ) {
			return;
		}
		$user_id = $order->get_user_id();
		if ( ! $user_id ) {
			return;
		}

		$activated = false;
		foreach ( $order->get_items() as $item ) {
			// 1) Plan key stored directly on the order line (direct-buy flow).
			$plan_key = $item->get_meta( '_mam_plan_key' );

			// 2) Fallback: legacy product → plan mapping.
			if ( ! $plan_key ) {
				$product = $item->get_product();
				if ( $product ) {
					$plan_key = get_post_meta( $product->get_id(), '_mam_plan_key', true );
				}
			}
			if ( ! $plan_key ) {
				continue;
			}

			$plan = MAM_Settings::plan( $plan_key );
			if ( ! $plan ) {
				continue;
			}

			$qty  = max( 1, (int) $item->get_quantity() );
			$days = (int) $plan['days'] * $qty;

			$status = MAM_Membership::instance()->activate( $user_id, $plan_key, $days );

			MAM_Payments::create( array(
				'user_id'       => $user_id,
				'plan_key'      => $plan_key,
				'plan_label'    => $plan['label'],
				'amount'        => (float) $item->get_total(),
				'method'        => 'woocommerce',
				'status'        => 'completed',
				'txn_ref'       => (string) $order_id,
				'duration_days' => $days,
				'start_date'    => $status['start'],
				'end_date'      => $status['end'],
				'note'          => 'WooCommerce Order #' . $order_id,
			) );
			$activated = true;
		}

		if ( $activated ) {
			$order->update_meta_data( '_mam_processed', 1 );
			$order->save();
		}
	}
}
