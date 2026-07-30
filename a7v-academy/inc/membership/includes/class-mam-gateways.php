<?php
/**
 * Payment gateways: ZarinPal, Zibal (online) + card-to-card (manual).
 *
 * Online flow:
 *   1. start_online() creates a pending record + requests a payment token,
 *      then redirects the user to the gateway.
 *   2. The gateway returns to  ?mam_verify=<method>  where we verify and,
 *      on success, activate the membership.
 *
 * Amounts: plan prices are treated as Toman and converted to Rial (×10)
 * for the gateways (standard for Iranian PSPs).
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_Gateways {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'maybe_verify' ) );
	}

	/** List enabled gateways => label. */
	public function available() {
		$out = array();
		if ( mam_get_setting( 'gw_zarinpal_on', 0 ) && mam_get_setting( 'gw_zarinpal_merchant', '' ) ) {
			$out['zarinpal'] = 'درگاه پرداخت زرین‌پال';
		}
		if ( mam_get_setting( 'gw_zibal_on', 0 ) && mam_get_setting( 'gw_zibal_merchant', '' ) ) {
			$out['zibal'] = 'درگاه پرداخت زیبال';
		}
		if ( mam_get_setting( 'gw_card_on', 0 ) ) {
			$out['card'] = 'کارت به کارت';
		}
		return $out;
	}

	/** Callback URL the gateway returns to. */
	private function callback_url( $method ) {
		return add_query_arg( 'mam_verify', $method, home_url( '/' ) );
	}

	/**
	 * Begin an online payment. Creates a record, requests a token, redirects.
	 * On failure returns a WP_Error.
	 *
	 * @param string $method zarinpal|zibal
	 * @param string $plan_key
	 * @param array  $form_data
	 * @return WP_Error|void  (redirects on success)
	 */
	public function start_online( $method, $plan_key, $form_data, $amount_override = 0 ) {
		$plan = MAM_Settings::plan( $plan_key );
		if ( ! $plan ) {
			return new WP_Error( 'mam_plan', 'پلن انتخابی نامعتبر است.' );
		}
		$amount_toman = $amount_override > 0 ? (int) $amount_override : (int) $plan['price'];
		$amount_rial  = $amount_toman * 10;
		$user_id      = get_current_user_id();

		$record_id = MAM_Payments::create( array(
			'user_id'       => $user_id,
			'plan_key'      => $plan_key,
			'plan_label'    => $plan['label'],
			'amount'        => $amount_toman,
			'method'        => $method,
			'status'        => 'pending',
			'duration_days' => (int) $plan['days'],
			'form_data'     => $form_data,
		) );

		$desc = sprintf( 'خرید اشتراک %s — %s', $plan['label'], mam_get_setting( 'brand_name', 'آکادمی مافیا' ) );
		$email = isset( $form_data['email'] ) ? $form_data['email'] : '';
		$mobile= isset( $form_data['mobile'] ) ? $form_data['mobile'] : '';

		if ( 'zarinpal' === $method ) {
			$res = $this->zarinpal_request( $amount_rial, $desc, $email, $mobile, $record_id );
		} elseif ( 'zibal' === $method ) {
			$res = $this->zibal_request( $amount_rial, $desc, $mobile, $record_id );
		} else {
			return new WP_Error( 'mam_gw', 'درگاه نامعتبر است.' );
		}

		if ( is_wp_error( $res ) ) {
			MAM_Payments::update( $record_id, array( 'status' => 'failed', 'note' => $res->get_error_message() ) );
			return $res;
		}

		MAM_Payments::update( $record_id, array( 'authority' => $res['authority'] ) );
		wp_redirect( $res['redirect'] ); // phpcs:ignore WordPress.Security.SafeRedirect
		exit;
	}

	/* ------------------------------ ZarinPal ------------------------------ */

	private function zarinpal_endpoints() {
		$sandbox = mam_get_setting( 'gw_zarinpal_sandbox', 0 );
		$base    = $sandbox ? 'https://sandbox.zarinpal.com' : 'https://api.zarinpal.com';
		$pay     = $sandbox ? 'https://sandbox.zarinpal.com' : 'https://www.zarinpal.com';
		return array(
			'request' => $base . '/pg/v4/payment/request.json',
			'verify'  => $base . '/pg/v4/payment/verify.json',
			'startpay'=> $pay . '/pg/StartPay/',
		);
	}

	private function zarinpal_request( $amount_rial, $desc, $email, $mobile, $record_id ) {
		$ep   = $this->zarinpal_endpoints();
		$body = array(
			'merchant_id'  => mam_get_setting( 'gw_zarinpal_merchant', '' ),
			'amount'       => $amount_rial,
			'description'  => $desc,
			'callback_url' => $this->callback_url( 'zarinpal' ),
			'metadata'     => array_filter( array( 'email' => $email, 'mobile' => $mobile ) ),
		);
		$resp = wp_remote_post( $ep['request'], array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/json', 'Accept' => 'application/json' ),
			'body'    => wp_json_encode( $body ),
		) );
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( isset( $data['data']['authority'] ) && ! empty( $data['data']['authority'] ) && (int) $data['data']['code'] === 100 ) {
			$authority = $data['data']['authority'];
			return array( 'authority' => $authority, 'redirect' => $ep['startpay'] . $authority );
		}
		$msg = isset( $data['errors']['message'] ) ? $data['errors']['message'] : 'خطا در ایجاد تراکنش زرین‌پال.';
		return new WP_Error( 'zarinpal', $msg );
	}

	private function zarinpal_verify( $record ) {
		$ep   = $this->zarinpal_endpoints();
		$body = array(
			'merchant_id' => mam_get_setting( 'gw_zarinpal_merchant', '' ),
			'amount'      => (int) $record->amount * 10,
			'authority'   => $record->authority,
		);
		$resp = wp_remote_post( $ep['verify'], array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/json', 'Accept' => 'application/json' ),
			'body'    => wp_json_encode( $body ),
		) );
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		$code = isset( $data['data']['code'] ) ? (int) $data['data']['code'] : 0;
		if ( 100 === $code || 101 === $code ) {
			return array( 'ref' => isset( $data['data']['ref_id'] ) ? $data['data']['ref_id'] : '' );
		}
		return new WP_Error( 'zarinpal', 'تأیید پرداخت زرین‌پال ناموفق بود.' );
	}

	/* ------------------------------- Zibal -------------------------------- */

	private function zibal_request( $amount_rial, $desc, $mobile, $record_id ) {
		$body = array(
			'merchant'    => mam_get_setting( 'gw_zibal_merchant', '' ),
			'amount'      => $amount_rial,
			'callbackUrl' => $this->callback_url( 'zibal' ),
			'description' => $desc,
			'mobile'      => $mobile,
			'orderId'     => 'mam-' . $record_id,
		);
		$resp = wp_remote_post( 'https://gateway.zibal.ir/v1/request', array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $body ),
		) );
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( isset( $data['result'] ) && 100 === (int) $data['result'] && ! empty( $data['trackId'] ) ) {
			$track = (string) $data['trackId'];
			return array( 'authority' => $track, 'redirect' => 'https://gateway.zibal.ir/start/' . $track );
		}
		$msg = isset( $data['message'] ) ? $data['message'] : 'خطا در ایجاد تراکنش زیبال.';
		return new WP_Error( 'zibal', $msg );
	}

	private function zibal_verify( $record ) {
		$body = array(
			'merchant' => mam_get_setting( 'gw_zibal_merchant', '' ),
			'trackId'  => $record->authority,
		);
		$resp = wp_remote_post( 'https://gateway.zibal.ir/v1/verify', array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $body ),
		) );
		if ( is_wp_error( $resp ) ) {
			return $resp;
		}
		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		$result = isset( $data['result'] ) ? (int) $data['result'] : 0;
		if ( 100 === $result || 201 === $result ) {
			return array( 'ref' => isset( $data['refNumber'] ) ? $data['refNumber'] : $record->authority );
		}
		return new WP_Error( 'zibal', 'تأیید پرداخت زیبال ناموفق بود.' );
	}

	/* ---------------------------- Verify handler --------------------------- */

	public function maybe_verify() {
		if ( empty( $_GET['mam_verify'] ) ) {
			return;
		}
		$method = sanitize_key( wp_unslash( $_GET['mam_verify'] ) );

		if ( 'zarinpal' === $method ) {
			$authority = isset( $_GET['Authority'] ) ? sanitize_text_field( wp_unslash( $_GET['Authority'] ) ) : '';
			$status    = isset( $_GET['Status'] ) ? sanitize_text_field( wp_unslash( $_GET['Status'] ) ) : '';
			$record    = $authority ? MAM_Payments::get_by_authority( $authority ) : null;
			if ( ! $record ) {
				$this->finish_redirect( false, 'تراکنش یافت نشد.' );
			}
			if ( 'OK' !== $status ) {
				MAM_Payments::update( $record->id, array( 'status' => 'canceled' ) );
				$this->finish_redirect( false, 'پرداخت توسط کاربر لغو شد.' );
			}
			$v = $this->zarinpal_verify( $record );
		} elseif ( 'zibal' === $method ) {
			$track   = isset( $_GET['trackId'] ) ? sanitize_text_field( wp_unslash( $_GET['trackId'] ) ) : '';
			$success = isset( $_GET['success'] ) ? sanitize_text_field( wp_unslash( $_GET['success'] ) ) : '';
			$record  = $track ? MAM_Payments::get_by_authority( $track ) : null;
			if ( ! $record ) {
				$this->finish_redirect( false, 'تراکنش یافت نشد.' );
			}
			if ( '1' !== $success ) {
				MAM_Payments::update( $record->id, array( 'status' => 'canceled' ) );
				$this->finish_redirect( false, 'پرداخت ناموفق بود.' );
			}
			$v = $this->zibal_verify( $record );
		} else {
			return;
		}

		if ( is_wp_error( $v ) ) {
			MAM_Payments::update( $record->id, array( 'status' => 'failed', 'note' => $v->get_error_message() ) );
			$this->finish_redirect( false, $v->get_error_message() );
		}

		// Success — activate.
		if ( 'completed' !== $record->status ) {
			$status = MAM_Membership::instance()->activate( $record->user_id, $record->plan_key, $record->duration_days );
			MAM_Payments::update( $record->id, array(
				'status'     => 'completed',
				'txn_ref'    => isset( $v['ref'] ) ? $v['ref'] : '',
				'start_date' => $status['start'],
				'end_date'   => $status['end'],
			) );
		}
		$this->finish_redirect( true, 'پرداخت با موفقیت انجام شد. اشتراک شما فعال شد ✅' );
	}

	private function finish_redirect( $ok, $msg ) {
		$url = add_query_arg( array(
			'mam'     => $ok ? 'success' : 'failed',
			'mam_msg' => rawurlencode( $msg ),
		), MAM_Restrictions::instance()->plans_url() );
		wp_safe_redirect( $url );
		exit;
	}
}
