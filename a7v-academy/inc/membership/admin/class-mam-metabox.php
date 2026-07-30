<?php
/**
 * Classic-editor metabox: "تنظیمات عضویت مافیا".
 * Lets the admin restrict a post/page/product to VIP members.
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_Metabox {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	/** Post types that can be restricted (filterable). */
	private function screens() {
		$screens = array( 'post', 'page', 'product' );
		// Include the A7V academy content types if present.
		if ( function_exists( 'a7v_post_types' ) ) {
			$screens = array_merge( $screens, array_keys( a7v_post_types() ) );
		}
		return apply_filters( 'mam_restrict_post_types', array_unique( $screens ) );
	}

	public function add() {
		foreach ( $this->screens() as $screen ) {
			add_meta_box( 'mam_restrict', '🔒 تنظیمات عضویت مافیا', array( $this, 'html' ), $screen, 'side', 'high' );
		}
	}

	public function html( $post ) {
		wp_nonce_field( 'mam_restrict_save', 'mam_restrict_nonce' );
		$on   = get_post_meta( $post->ID, '_mam_restrict', true );
		$mode = get_post_meta( $post->ID, '_mam_restrict_mode', true );
		$mode = in_array( $mode, array( 'full', 'partial' ), true ) ? $mode : 'full';
		?>
		<p>
			<label>
				<input type="checkbox" name="mam_restrict" value="1" <?php checked( $on, '1' ); ?>>
				<strong>این محتوا فقط برای اعضای ویژه باشد</strong>
			</label>
		</p>

		<p><strong>سطح محدودیت:</strong></p>
		<p>
			<label style="display:block;margin-bottom:6px">
				<input type="radio" name="mam_restrict_mode" value="full" <?php checked( $mode, 'full' ); ?>>
				کل محتوا قفل شود
			</label>
			<label style="display:block">
				<input type="radio" name="mam_restrict_mode" value="partial" <?php checked( $mode, 'partial' ); ?>>
				از یک بخش مشخص تا انتهای مطلب
			</label>
		</p>

		<div style="background:#f6f7f7;border:1px solid #dcdcde;border-radius:6px;padding:10px;font-size:12px;line-height:1.8;margin-top:8px">
			برای حالت «از یک بخش مشخص»، متن محرمانه را داخل شورت‌کد زیر بگذارید؛ متن بالای آن آزاد می‌ماند:
			<br>
			<code>[mafia_vip]</code> متن محرمانه… <code>[/mafia_vip]</code>
			<br><br>
			برای فایل دانلودی محدود:
			<br>
			<code>[mafia_download url="لینک فایل" label="دانلود فایل"]</code>
		</div>
		<?php
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST['mam_restrict_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mam_restrict_nonce'] ) ), 'mam_restrict_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_mam_restrict', ! empty( $_POST['mam_restrict'] ) ? '1' : '' );
		$mode = isset( $_POST['mam_restrict_mode'] ) ? sanitize_key( wp_unslash( $_POST['mam_restrict_mode'] ) ) : 'full';
		update_post_meta( $post_id, '_mam_restrict_mode', in_array( $mode, array( 'full', 'partial' ), true ) ? $mode : 'full' );
	}
}
