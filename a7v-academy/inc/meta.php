<?php
/**
 * Meta box for content cards: rating, author, access level, duration, download link.
 * All restrictions are set MANUALLY here in the classic editor sidebar.
 *
 * @package A7V
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function a7v_add_meta_box() {
	$screens = array_keys( a7v_post_types() );
	add_meta_box( 'a7v_meta', '🎩 تنظیمات A7V (دسترسی و دانلود)', 'a7v_meta_box_html', $screens, 'side', 'high' );
}
add_action( 'add_meta_boxes', 'a7v_add_meta_box' );

function a7v_meta_box_html( $post ) {
	wp_nonce_field( 'a7v_meta_save', 'a7v_meta_nonce' );
	$author   = get_post_meta( $post->ID, '_a7v_author', true );
	$rating   = get_post_meta( $post->ID, '_a7v_rating', true );
	$duration = get_post_meta( $post->ID, '_a7v_duration', true );
	$access   = get_post_meta( $post->ID, '_a7v_access', true ) ? get_post_meta( $post->ID, '_a7v_access', true ) : 'vip';
	$download = get_post_meta( $post->ID, '_a7v_download', true );
	?>
	<p><label><strong>سطح دسترسی / محدودیت</strong></label><br>
	<select name="a7v_access" style="width:100%">
		<option value="vip"  <?php selected( $access, 'vip' ); ?>>🔒 ویژه (پولی — فقط اعضا)</option>
		<option value="free" <?php selected( $access, 'free' ); ?>>🔓 رایگان (برای همه)</option>
	</select></p>
	<p class="description" style="margin-bottom:14px">اگر «ویژه» باشد: متن محتوا قفل می‌شود و دکمه دانلود به‌جای فایل، پاپ‌آپ خرید اشتراک را باز می‌کند.</p>

	<p><label><strong>لینک دانلود (اختیاری)</strong></label>
	<input type="url" name="a7v_download" value="<?php echo esc_attr( $download ); ?>" style="width:100%" placeholder="https://..."></p>
	<p class="description" style="margin-bottom:14px">اگر محصول دانلودی است، لینک فایل را اینجا بگذار. در محتوای «ویژه»، دانلود فقط بعد از خرید اشتراک باز می‌شود.</p>

	<hr>
	<p><label><strong>نویسنده / مدرس</strong></label>
	<input type="text" name="a7v_author" value="<?php echo esc_attr( $author ); ?>" style="width:100%" placeholder="مثلاً: مدرس آکادمی مافیا"></p>

	<p><label><strong>امتیاز</strong></label>
	<input type="text" name="a7v_rating" value="<?php echo esc_attr( $rating ); ?>" style="width:100%" placeholder="4.8"></p>

	<p><label><strong>مدت (جلسه / دقیقه)</strong></label>
	<input type="text" name="a7v_duration" value="<?php echo esc_attr( $duration ); ?>" style="width:100%" placeholder="۱۲ جلسه"></p>
	<p class="description">توضیح کوتاه قرمز زیر عنوان کارت از «خلاصه/چکیده» (Excerpt) خوانده می‌شود و بَج دسته از «دسته‌بندی A7V».</p>
	<?php
}

function a7v_meta_save( $post_id ) {
	if ( ! isset( $_POST['a7v_meta_nonce'] ) || ! wp_verify_nonce( $_POST['a7v_meta_nonce'], 'a7v_meta_save' ) ) { return; }
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

	$text_fields = array(
		'a7v_author'   => '_a7v_author',
		'a7v_rating'   => '_a7v_rating',
		'a7v_duration' => '_a7v_duration',
		'a7v_access'   => '_a7v_access',
	);
	foreach ( $text_fields as $field => $key ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
		}
	}
	if ( isset( $_POST['a7v_download'] ) ) {
		update_post_meta( $post_id, '_a7v_download', esc_url_raw( wp_unslash( $_POST['a7v_download'] ) ) );
	}
}
add_action( 'save_post', 'a7v_meta_save' );

/* -------------------------------------------------------------------------
 *  اطلاع‌رسانی (Announcements) meta box
 * ---------------------------------------------------------------------- */

function a7v_notice_meta_box() {
	add_meta_box( 'a7v_notice_meta', '📣 تنظیمات اطلاعیه', 'a7v_notice_meta_html', 'a7v_notice', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'a7v_notice_meta_box' );

function a7v_notice_meta_html( $post ) {
	wp_nonce_field( 'a7v_notice_save', 'a7v_notice_nonce' );
	$type = get_post_meta( $post->ID, '_a7v_notice_type', true );
	$type = $type ? $type : 'news';
	$pin  = get_post_meta( $post->ID, '_a7v_notice_pin', true );
	$link = get_post_meta( $post->ID, '_a7v_notice_link', true );
	$types = array(
		'news'   => '📢 خبر',
		'update' => '🔄 آپدیت سایت',
		'event'  => '🎉 رویداد',
		'gift'   => '🎁 پیشنهاد ویژه',
		'warn'   => '⚠️ مهم / هشدار',
	);
	?>
	<p><label><strong>نوع اطلاعیه</strong></label><br>
	<select name="a7v_notice_type" style="width:100%">
		<?php foreach ( $types as $val => $lbl ) : ?>
			<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $type, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
		<?php endforeach; ?>
	</select></p>

	<p style="margin-top:14px"><label><input type="checkbox" name="a7v_notice_pin" value="1" <?php checked( $pin, '1' ); ?>> <strong>سنجاق به بالا (مهم)</strong></label></p>
	<p class="description" style="margin-bottom:14px">اطلاعیه‌های سنجاق‌شده بالاتر از بقیه و با برجستگی بیشتر نمایش داده می‌شوند.</p>

	<p><label><strong>لینک (اختیاری)</strong></label>
	<input type="url" name="a7v_notice_link" value="<?php echo esc_attr( $link ); ?>" style="width:100%" placeholder="https://..."></p>
	<p class="description">اگر پر شود، روی اطلاعیه دکمه «مشاهده» نمایش داده می‌شود.</p>
	<?php
}

function a7v_notice_save( $post_id ) {
	if ( ! isset( $_POST['a7v_notice_nonce'] ) || ! wp_verify_nonce( $_POST['a7v_notice_nonce'], 'a7v_notice_save' ) ) { return; }
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

	if ( isset( $_POST['a7v_notice_type'] ) ) {
		update_post_meta( $post_id, '_a7v_notice_type', sanitize_text_field( wp_unslash( $_POST['a7v_notice_type'] ) ) );
	}
	update_post_meta( $post_id, '_a7v_notice_pin', isset( $_POST['a7v_notice_pin'] ) ? '1' : '' );
	if ( isset( $_POST['a7v_notice_link'] ) ) {
		update_post_meta( $post_id, '_a7v_notice_link', esc_url_raw( wp_unslash( $_POST['a7v_notice_link'] ) ) );
	}
}
add_action( 'save_post', 'a7v_notice_save' );

/**
 * Fetch announcements for display on the dashboard.
 *
 * @param int $limit Max notices to return.
 * @return array List of [type, label, title, body, date, link, pinned].
 */
function a7v_get_notices( $limit = 6 ) {
	$labels = array(
		'news'   => 'خبر',
		'update' => 'آپدیت',
		'event'  => 'رویداد',
		'gift'   => 'پیشنهاد',
		'warn'   => 'مهم',
	);
	$q = new WP_Query( array(
		'post_type'      => 'a7v_notice',
		'posts_per_page' => (int) $limit,
		'post_status'    => 'publish',
		'no_found_rows'  => true,
		'meta_key'       => '_a7v_notice_pin',
		'orderby'        => array( 'meta_value' => 'DESC', 'date' => 'DESC' ),
	) );

	$out = array();
	if ( $q->have_posts() ) {
		while ( $q->have_posts() ) {
			$q->the_post();
			$id   = get_the_ID();
			$type = get_post_meta( $id, '_a7v_notice_type', true );
			$type = $type ? $type : 'news';
			$out[] = array(
				'type'   => $type,
				'label'  => isset( $labels[ $type ] ) ? $labels[ $type ] : 'خبر',
				'title'  => get_the_title(),
				'body'   => wp_trim_words( wp_strip_all_tags( get_the_content() ), 28, '…' ),
				'date'   => get_the_date( 'j F Y' ),
				'link'   => get_post_meta( $id, '_a7v_notice_link', true ),
				'pinned' => '1' === get_post_meta( $id, '_a7v_notice_pin', true ),
			);
		}
		wp_reset_postdata();
	}
	return $out;
}
