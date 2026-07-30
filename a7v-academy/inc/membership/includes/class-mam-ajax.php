<?php
/**
 * Form + AJAX handlers.
 *   admin_post_mam_purchase        purchase form submit (online + card-to-card)
 *   wp_ajax_mam_admin_approve      admin approve/reject a card payment
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_Ajax {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_post_mam_purchase', array( $this, 'handle_purchase' ) );
		add_action( 'admin_post_nopriv_mam_purchase', array( $this, 'handle_purchase' ) );
		add_action( 'wp_ajax_mam_admin_action', array( $this, 'admin_action' ) );

		// Live discount-code check on the checkout page.
		add_action( 'wp_ajax_mam_apply_discount', array( $this, 'apply_discount' ) );
		add_action( 'wp_ajax_nopriv_mam_apply_discount', array( $this, 'apply_discount' ) );
	}

	/** AJAX: validate a discount code against a plan and return the new totals. */
	public function apply_discount() {
		check_ajax_referer( 'mam_discount', 'nonce' );

		$plan_key = isset( $_POST['plan'] ) ? sanitize_key( wp_unslash( $_POST['plan'] ) ) : '';
		$code     = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		$plan     = MAM_Settings::plan( $plan_key );

		if ( ! $plan ) {
			wp_send_json_error( array( 'message' => 'پلن نامعتبر است.' ) );
		}

		$price = (int) $plan['price'];
		$disc  = MAM_Settings::compute_discount( $price, $code );

		if ( ! $disc['valid'] ) {
			wp_send_json_error( array( 'message' => 'کد تخفیف نامعتبر است.' ) );
		}

		wp_send_json_success( array(
			'code'      => $code,
			'label'     => $disc['label'],
			'discount'  => (int) $disc['discount'],
			'final'     => (int) $disc['final'],
			'off_html'  => number_format( (int) $disc['discount'] ) . ' تومان',
			'final_html'=> number_format( (int) $disc['final'] ) . ' تومان',
			'message'   => 'کد تخفیف اعمال شد ✅ (' . $disc['label'] . ')',
		) );
	}

	/**
	 * Redirect back to the checkout page (where the form was submitted) with a
	 * status message. Staying on the checkout page means a validation error never
	 * bounces the user to the plans list.
	 */
	private function back( $ok, $msg ) {
		$plan_key = isset( $_POST['mam_plan'] ) ? sanitize_key( wp_unslash( $_POST['mam_plan'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		// Prefer the exact page the form came from (keeps ?plan= and anchor).
		$target = wp_get_referer();
		if ( ! $target ) {
			$target = MAM_Restrictions::instance()->checkout_url( $plan_key );
		}

		$url = add_query_arg( array(
			'mam'     => $ok ? 'success' : 'failed',
			'mam_msg' => rawurlencode( $msg ),
		), remove_query_arg( array( 'mam', 'mam_msg' ), $target ) );
		wp_safe_redirect( $url . '#mam-form' );
		exit;
	}

	/**
	 * Handle the purchase form.
	 */
	public function handle_purchase() {
		if ( ! isset( $_POST['mam_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mam_nonce'] ) ), 'mam_purchase' ) ) {
			$this->back( false, 'درخواست نامعتبر است.' );
		}

		$plan_key = isset( $_POST['mam_plan'] ) ? sanitize_key( wp_unslash( $_POST['mam_plan'] ) ) : '';
		$method   = isset( $_POST['mam_method'] ) ? sanitize_key( wp_unslash( $_POST['mam_method'] ) ) : '';
		$plan     = MAM_Settings::plan( $plan_key );

		if ( ! $plan ) {
			$this->back( false, 'پلن انتخابی نامعتبر است.' );
		}

		// Resolve the buyer account: use the logged-in user, or create one from
		// the checkout form so a guest never has to register separately. The
		// password is only used to create the account and is never stored.
		$raw_fields = isset( $_POST['mam_field'] ) && is_array( $_POST['mam_field'] ) ? wp_unslash( $_POST['mam_field'] ) : array(); // phpcs:ignore

		if ( is_user_logged_in() ) {
			$buyer_id = get_current_user_id();
			if ( ! empty( $raw_fields['phone'] ) ) {
				$phone = sanitize_text_field( $raw_fields['phone'] );
				update_user_meta( $buyer_id, 'mam_phone', $phone );
				update_user_meta( $buyer_id, 'billing_phone', $phone );
			}
		} else {
			$buyer_id = $this->register_buyer( $raw_fields );
			if ( is_wp_error( $buyer_id ) ) {
				$this->back( false, $buyer_id->get_error_message() );
			}
		}

		$buyer       = get_userdata( $buyer_id );
		$buyer_name  = $buyer ? trim( $buyer->first_name . ' ' . $buyer->last_name ) : '';
		if ( '' === $buyer_name && $buyer ) { $buyer_name = $buyer->display_name; }
		$buyer_phone = (string) get_user_meta( $buyer_id, 'mam_phone', true );
		if ( '' === $buyer_phone && ! empty( $raw_fields['phone'] ) ) {
			$buyer_phone = sanitize_text_field( $raw_fields['phone'] );
		}
		if ( '' === $buyer_phone ) {
			$this->back( false, 'لطفاً شماره تماس را وارد کنید.' );
		}

		// Payment metadata (never contains the password).
		$form_data = array(
			'full_name' => $buyer_name,
			'phone'     => $buyer_phone,
			'mobile'    => $buyer_phone,
			'email'     => $buyer ? $buyer->user_email : '',
			'username'  => $buyer ? $buyer->user_login : '',
		);

		// Discount code (optional) — re-validated on the server, never trusted from the client.
		$discount_code = isset( $_POST['mam_discount'] ) ? sanitize_text_field( wp_unslash( $_POST['mam_discount'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$disc          = MAM_Settings::compute_discount( (int) $plan['price'], $discount_code );
		$final_amount  = $disc['valid'] ? (int) $disc['final'] : (int) $plan['price'];
		if ( $disc['valid'] && '' !== $discount_code ) {
			$form_data['_discount_code']   = $discount_code;
			$form_data['_discount_amount'] = (int) $disc['discount'];
		}

		$gateways = MAM_Gateways::instance()->available();
		if ( ! isset( $gateways[ $method ] ) ) {
			$this->back( false, 'روش پرداخت نامعتبر است.' );
		}

		/* ---------------- Online gateways ---------------- */
		if ( 'zarinpal' === $method || 'zibal' === $method ) {
			$res = MAM_Gateways::instance()->start_online( $method, $plan_key, $form_data, $final_amount );
			if ( is_wp_error( $res ) ) {
				$this->back( false, $res->get_error_message() );
			}
			// start_online() redirects on success; nothing else to do.
			exit;
		}

		/* ---------------- Card-to-card ---------------- */
		if ( 'card' === $method ) {
			$txn = isset( $_POST['mam_txn'] ) ? sanitize_text_field( wp_unslash( $_POST['mam_txn'] ) ) : '';
			if ( '' === $txn ) {
				$this->back( false, 'شماره پیگیری پرداخت را وارد کنید.' );
			}

			$receipt_id = 0;
			if ( ! empty( $_FILES['mam_receipt']['name'] ) ) {
				$receipt_id = $this->handle_receipt_upload();
				if ( is_wp_error( $receipt_id ) ) {
					$this->back( false, $receipt_id->get_error_message() );
				}
			} else {
				$this->back( false, 'تصویر رسید را بارگذاری کنید.' );
			}

			$record_id = MAM_Payments::create( array(
				'user_id'       => get_current_user_id(),
				'plan_key'      => $plan_key,
				'plan_label'    => $plan['label'],
				'amount'        => $final_amount,
				'method'        => 'card',
				'status'        => 'pending',
				'txn_ref'       => $txn,
				'duration_days' => (int) $plan['days'],
				'receipt_id'    => $receipt_id,
				'form_data'     => $form_data,
			) );

			do_action( 'mam_card_payment_submitted', $record_id );
			$this->notify_admin_new_card( $record_id );

			$this->back( true, 'اطلاعات پرداخت شما ثبت شد و در انتظار تأیید مدیر است. پس از تأیید، اشتراک فعال می‌شود.' );
		}

		$this->back( false, 'روش پرداخت پشتیبانی نمی‌شود.' );
	}

	/**
	 * Create a WordPress account from the checkout form for a guest buyer and
	 * log them in, so every subscription is attached to a real account.
	 *
	 * @param array $raw Raw (unslashed) mam_field values.
	 * @return int|WP_Error New user ID on success.
	 */
	private function register_buyer( $raw ) {
		$full     = isset( $raw['full_name'] ) ? sanitize_text_field( $raw['full_name'] ) : '';
		$email    = isset( $raw['email'] ) ? sanitize_email( $raw['email'] ) : '';
		$username = isset( $raw['username'] ) ? trim( sanitize_user( $raw['username'], false ) ) : '';
		$password = isset( $raw['password'] ) ? (string) $raw['password'] : '';
		$phone    = isset( $raw['phone'] ) ? sanitize_text_field( $raw['phone'] ) : '';

		if ( '' === $full || '' === $email || '' === $username || '' === $password || '' === $phone ) {
			return new WP_Error( 'mam_reg', 'لطفاً نام و نام خانوادگی، ایمیل، نام کاربری، رمز عبور و شماره تماس را وارد کنید.' );
		}
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'mam_reg', 'لطفاً یک ایمیل معتبر وارد کنید.' );
		}
		if ( mb_strlen( $password ) < 6 ) {
			return new WP_Error( 'mam_reg', 'رمز عبور باید حداقل ۶ کاراکتر باشد.' );
		}
		if ( username_exists( $username ) ) {
			return new WP_Error( 'mam_reg', 'این نام کاربری قبلاً ثبت شده است. لطفاً نام دیگری انتخاب کنید یا وارد شوید.' );
		}
		if ( email_exists( $email ) ) {
			return new WP_Error( 'mam_reg', 'این ایمیل قبلاً ثبت شده است. لطفاً وارد شوید یا ایمیل دیگری وارد کنید.' );
		}

		// Split the full name into first / last for the WP profile.
		$parts      = preg_split( '/\s+/', $full, 2 );
		$first_name = isset( $parts[0] ) ? $parts[0] : $full;
		$last_name  = isset( $parts[1] ) ? $parts[1] : '';

		$user_id = wp_insert_user( array(
			'user_login'   => $username,
			'user_pass'    => $password,
			'user_email'   => $email,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'display_name' => $full,
			'role'         => 'subscriber',
		) );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		update_user_meta( $user_id, 'mam_phone', $phone );
		update_user_meta( $user_id, 'billing_phone', $phone );

		// Log the new user in for the rest of the checkout request.
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		return (int) $user_id;
	}

	/** Securely handle the receipt image upload. */
	private function handle_receipt_upload() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file = $_FILES['mam_receipt']; // phpcs:ignore
		$check = wp_check_filetype( $file['name'] );
		$allowed = array( 'jpg', 'jpeg', 'png', 'webp', 'gif' );
		if ( ! in_array( strtolower( $check['ext'] ), $allowed, true ) ) {
			return new WP_Error( 'mam_upload', 'فرمت تصویر مجاز نیست (فقط JPG/PNG/WEBP).' );
		}

		$id = media_handle_upload( 'mam_receipt', 0 );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		return (int) $id;
	}

	/** Email the site admin about a new card-to-card submission. */
	private function notify_admin_new_card( $record_id ) {
		$admin = get_option( 'admin_email' );
		$rec   = MAM_Payments::get( $record_id );
		if ( ! $admin || ! $rec ) { return; }
		$link  = admin_url( 'admin.php?page=mam-payments' );
		$subj  = '[' . get_bloginfo( 'name' ) . '] پرداخت کارت‌به‌کارت جدید';
		$body  = "یک پرداخت کارت‌به‌کارت جدید ثبت شد.\n";
		$body .= 'پلن: ' . $rec->plan_label . "\n";
		$body .= 'مبلغ: ' . number_format( (float) $rec->amount ) . " تومان\n";
		$body .= 'شماره پیگیری: ' . $rec->txn_ref . "\n";
		$body .= 'بررسی و تأیید: ' . $link . "\n";
		wp_mail( $admin, $subj, $body );
	}

	/**
	 * Admin AJAX: approve / reject / cancel a payment or membership.
	 */
	public function admin_action() {
		check_ajax_referer( 'mam_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'msg' => 'دسترسی ندارید.' ), 403 );
		}
		$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$rec = $id ? MAM_Payments::get( $id ) : null;

		if ( ! $rec ) {
			wp_send_json_error( array( 'msg' => 'رکورد یافت نشد.' ), 404 );
		}

		if ( 'approve' === $do ) {
			$status = MAM_Membership::instance()->activate( $rec->user_id, $rec->plan_key, $rec->duration_days );
			MAM_Payments::update( $id, array(
				'status'     => 'completed',
				'start_date' => $status['start'],
				'end_date'   => $status['end'],
			) );
			wp_send_json_success( array( 'msg' => 'اشتراک فعال شد.', 'status' => 'completed' ) );
		}

		if ( 'reject' === $do ) {
			MAM_Payments::update( $id, array( 'status' => 'failed' ) );
			wp_send_json_success( array( 'msg' => 'پرداخت رد شد.', 'status' => 'failed' ) );
		}

		wp_send_json_error( array( 'msg' => 'عملیات نامعتبر.' ), 400 );
	}
}
