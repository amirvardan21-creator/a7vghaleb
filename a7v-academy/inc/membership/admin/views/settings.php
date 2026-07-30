<?php
/**
 * Admin — settings view (tabbed): appearance, gateways, messages, form.
 *
 * @package MafiaAcademyMembership
 * @var array $s  current settings
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
$fields = isset( $s['form_fields'] ) ? $s['form_fields'] : MAM_Settings::default_form_fields();
?>
<div class="wrap mam-wrap">
	<div class="mam-head"><h1><span class="mam-logo">M</span> تنظیمات آکادمی مافیا</h1></div>

	<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore ?>
		<div class="mam-notice ok">تنظیمات ذخیره شد ✅</div>
	<?php endif; ?>

	<div class="mam-tabs" id="mam-tabs">
		<button class="mam-tab on" data-tab="appearance">ظاهر و رنگ‌ها</button>
		<button class="mam-tab" data-tab="gateways">درگاه‌های پرداخت</button>
		<button class="mam-tab" data-tab="messages">متن پیام‌ها</button>
		<button class="mam-tab" data-tab="form">فرم خرید</button>
		<button class="mam-tab" data-tab="checkout">صفحهٔ پرداخت و کد تخفیف</button>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="mam_save_settings">
		<?php wp_nonce_field( 'mam_save_settings', 'mam_settings_nonce' ); ?>

		<!-- Appearance -->
		<div class="mam-panel on" data-panel="appearance">
			<div class="mam-card">
				<div class="mam-card-title">ظاهر پنل و رنگ‌ها</div>
				<div class="mam-fld"><label>نام برند</label><input type="text" name="brand_name" value="<?php echo esc_attr( $s['brand_name'] ); ?>"></div>
				<div class="mam-color-grid">
					<div class="mam-fld"><label>پس‌زمینه</label><input type="color" name="color_bg" value="<?php echo esc_attr( $s['color_bg'] ); ?>"></div>
					<div class="mam-fld"><label>سطح/کارت</label><input type="color" name="color_surface" value="<?php echo esc_attr( $s['color_surface'] ); ?>"></div>
					<div class="mam-fld"><label>قرمز مافیا</label><input type="color" name="color_brand" value="<?php echo esc_attr( $s['color_brand'] ); ?>"></div>
					<div class="mam-fld"><label>طلایی VIP</label><input type="color" name="color_gold" value="<?php echo esc_attr( $s['color_gold'] ); ?>"></div>
					<div class="mam-fld"><label>متن</label><input type="color" name="color_text" value="<?php echo esc_attr( $s['color_text'] ); ?>"></div>
				</div>
				<div class="mam-fld"><label>صفحهٔ پلن‌ها</label>
					<?php wp_dropdown_pages( array( 'name' => 'plans_page_id', 'selected' => (int) $s['plans_page_id'], 'show_option_none' => '— انتخاب صفحه —', 'option_none_value' => 0 ) ); ?>
				</div>
				<div class="mam-fld"><label>صفحهٔ ورود / ثبت‌نام</label>
					<?php wp_dropdown_pages( array( 'name' => 'auth_page_id', 'selected' => (int) ( isset( $s['auth_page_id'] ) ? $s['auth_page_id'] : 0 ), 'show_option_none' => '— تشخیص خودکار —', 'option_none_value' => 0 ) ); ?>
					<p style="margin:6px 2px 0;font-size:12px;color:#8a8f99">صفحهٔ ورود/ثبت‌نام سایت. اگر خالی بماند، به‌صورت خودکار پیدا می‌شود.</p>
				</div>
				<div class="mam-fld mam-check"><label><input type="checkbox" name="restrict_products" value="1" <?php checked( ! empty( $s['restrict_products'] ) ); ?>> محدودسازی محصولات ووکامرس نشان‌دار</label></div>
				<div class="mam-fld mam-check"><label><input type="checkbox" name="auto_expire" value="1" <?php checked( ! empty( $s['auto_expire'] ) ); ?>> بررسی خودکار انقضای اشتراک (روزانه)</label></div>
			</div>
		</div>

		<!-- Gateways -->
		<div class="mam-panel" data-panel="gateways">
			<div class="mam-card">
				<div class="mam-card-title">زرین‌پال</div>
				<div class="mam-fld mam-check"><label><input type="checkbox" name="gw_zarinpal_on" value="1" <?php checked( ! empty( $s['gw_zarinpal_on'] ) ); ?>> فعال‌سازی درگاه زرین‌پال</label></div>
				<div class="mam-fld"><label>مرچنت کد (Merchant ID)</label><input type="text" name="gw_zarinpal_merchant" value="<?php echo esc_attr( $s['gw_zarinpal_merchant'] ); ?>" dir="ltr"></div>
				<div class="mam-fld mam-check"><label><input type="checkbox" name="gw_zarinpal_sandbox" value="1" <?php checked( ! empty( $s['gw_zarinpal_sandbox'] ) ); ?>> حالت تست (Sandbox)</label></div>
				<div class="mam-fld mam-logo-fld">
					<label>لوگوی درگاه (اختیاری — جای آیکون نمایش داده می‌شود)</label>
					<div class="mam-logo-row">
						<input type="text" name="gw_zarinpal_logo" value="<?php echo esc_attr( isset( $s['gw_zarinpal_logo'] ) ? $s['gw_zarinpal_logo'] : '' ); ?>" dir="ltr" placeholder="آدرس تصویر یا انتخاب از رسانه">
						<button type="button" class="button mam-media-pick" data-target="gw_zarinpal_logo">انتخاب تصویر</button>
					</div>
					<div class="mam-logo-preview"><?php if ( ! empty( $s['gw_zarinpal_logo'] ) ) : ?><img src="<?php echo esc_url( $s['gw_zarinpal_logo'] ); ?>" alt=""><?php endif; ?></div>
				</div>
			</div>
			<div class="mam-card">
				<div class="mam-card-title">زیبال</div>
				<div class="mam-fld mam-check"><label><input type="checkbox" name="gw_zibal_on" value="1" <?php checked( ! empty( $s['gw_zibal_on'] ) ); ?>> فعال‌سازی درگاه زیبال</label></div>
				<div class="mam-fld"><label>مرچنت (Merchant)</label><input type="text" name="gw_zibal_merchant" value="<?php echo esc_attr( $s['gw_zibal_merchant'] ); ?>" dir="ltr"></div>
				<div class="mam-fld mam-logo-fld">
					<label>لوگوی درگاه (اختیاری)</label>
					<div class="mam-logo-row">
						<input type="text" name="gw_zibal_logo" value="<?php echo esc_attr( isset( $s['gw_zibal_logo'] ) ? $s['gw_zibal_logo'] : '' ); ?>" dir="ltr" placeholder="آدرس تصویر یا انتخاب از رسانه">
						<button type="button" class="button mam-media-pick" data-target="gw_zibal_logo">انتخاب تصویر</button>
					</div>
					<div class="mam-logo-preview"><?php if ( ! empty( $s['gw_zibal_logo'] ) ) : ?><img src="<?php echo esc_url( $s['gw_zibal_logo'] ); ?>" alt=""><?php endif; ?></div>
				</div>
			</div>
			<div class="mam-card">
				<div class="mam-card-title">کارت به کارت</div>
				<div class="mam-fld mam-check"><label><input type="checkbox" name="gw_card_on" value="1" <?php checked( ! empty( $s['gw_card_on'] ) ); ?>> فعال‌سازی پرداخت کارت به کارت</label></div>
				<div class="mam-fld"><label>شماره کارت</label><input type="text" name="gw_card_number" value="<?php echo esc_attr( $s['gw_card_number'] ); ?>" dir="ltr"></div>
				<div class="mam-fld"><label>نام صاحب کارت</label><input type="text" name="gw_card_holder" value="<?php echo esc_attr( $s['gw_card_holder'] ); ?>"></div>
				<div class="mam-fld"><label>توضیحات پرداخت</label><textarea name="gw_card_desc" rows="3"><?php echo esc_textarea( $s['gw_card_desc'] ); ?></textarea></div>
				<div class="mam-fld mam-logo-fld">
					<label>لوگوی درگاه (اختیاری)</label>
					<div class="mam-logo-row">
						<input type="text" name="gw_card_logo" value="<?php echo esc_attr( isset( $s['gw_card_logo'] ) ? $s['gw_card_logo'] : '' ); ?>" dir="ltr" placeholder="آدرس تصویر یا انتخاب از رسانه">
						<button type="button" class="button mam-media-pick" data-target="gw_card_logo">انتخاب تصویر</button>
					</div>
					<div class="mam-logo-preview"><?php if ( ! empty( $s['gw_card_logo'] ) ) : ?><img src="<?php echo esc_url( $s['gw_card_logo'] ); ?>" alt=""><?php endif; ?></div>
				</div>
			</div>
		</div>

		<!-- Messages -->
		<div class="mam-panel" data-panel="messages">
			<div class="mam-card">
				<div class="mam-card-title">پیام محدودیت محتوا</div>
				<div class="mam-fld"><label>عنوان</label><input type="text" name="msg_locked_title" value="<?php echo esc_attr( $s['msg_locked_title'] ); ?>"></div>
				<div class="mam-fld"><label>متن</label><textarea name="msg_locked_body" rows="2"><?php echo esc_textarea( $s['msg_locked_body'] ); ?></textarea></div>
				<div class="mam-fld"><label>متن دکمه</label><input type="text" name="msg_locked_btn" value="<?php echo esc_attr( $s['msg_locked_btn'] ); ?>"></div>
			</div>
			<div class="mam-card">
				<div class="mam-card-title">پیام محدودیت دانلود</div>
				<div class="mam-fld"><label>عنوان</label><input type="text" name="msg_dl_title" value="<?php echo esc_attr( $s['msg_dl_title'] ); ?>"></div>
				<div class="mam-fld"><label>متن</label><textarea name="msg_dl_body" rows="2"><?php echo esc_textarea( $s['msg_dl_body'] ); ?></textarea></div>
				<div class="mam-fld"><label>متن دکمه</label><input type="text" name="msg_dl_btn" value="<?php echo esc_attr( $s['msg_dl_btn'] ); ?>"></div>
			</div>
			<div class="mam-card">
				<div class="mam-card-title">پیام انقضا</div>
				<div class="mam-fld"><label>متن</label><input type="text" name="msg_expired" value="<?php echo esc_attr( $s['msg_expired'] ); ?>"></div>
			</div>
		</div>

		<!-- Form fields -->
		<div class="mam-panel" data-panel="form">
			<div class="mam-card">
				<div class="mam-card-title">فیلدهای فرم خرید (کشیدن برای تغییر ترتیب)</div>
				<div id="mam-fields-list">
					<?php foreach ( $fields as $f ) : ?>
						<div class="mam-field-row" draggable="true">
							<span class="mam-drag" title="جابجایی">⋮⋮</span>
							<input type="text" name="field_label[]" value="<?php echo esc_attr( $f['label'] ); ?>" placeholder="عنوان">
							<input type="text" name="field_key[]" value="<?php echo esc_attr( $f['key'] ); ?>" placeholder="کلید" dir="ltr">
							<select name="field_type[]">
								<?php foreach ( array( 'text' => 'متن', 'email' => 'ایمیل', 'tel' => 'موبایل', 'number' => 'عدد', 'password' => 'رمز عبور' ) as $tv => $tl ) : ?>
									<option value="<?php echo esc_attr( $tv ); ?>" <?php selected( $f['type'], $tv ); ?>><?php echo esc_html( $tl ); ?></option>
								<?php endforeach; ?>
							</select>
							<select name="field_width[]" title="عرض فیلد">
								<option value="half" <?php selected( ( isset( $f['width'] ) ? $f['width'] : 'half' ), 'half' ); ?>>نصف‌عرض</option>
								<option value="full" <?php selected( ( isset( $f['width'] ) ? $f['width'] : 'half' ), 'full' ); ?>>تمام‌عرض</option>
							</select>
							<label class="mam-check">فعال<input type="checkbox" name="field_enabled[]" value="1" <?php checked( ! empty( $f['enabled'] ) ); ?>></label>
							<label class="mam-check">اجباری<input type="checkbox" name="field_required[]" value="1" <?php checked( ! empty( $f['required'] ) ); ?>></label>
							<button type="button" class="button mam-danger mam-remove-field">×</button>
						</div>
					<?php endforeach; ?>
				</div>
				<template id="mam-field-template">
					<div class="mam-field-row" draggable="true">
						<span class="mam-drag">⋮⋮</span>
						<input type="text" name="field_label[]" value="" placeholder="عنوان">
						<input type="text" name="field_key[]" value="" placeholder="کلید" dir="ltr">
						<select name="field_type[]">
							<option value="text">متن</option><option value="email">ایمیل</option><option value="tel">موبایل</option><option value="number">عدد</option><option value="password">رمز عبور</option>
						</select>
						<select name="field_width[]" title="عرض فیلد">
							<option value="half">نصف‌عرض</option>
							<option value="full">تمام‌عرض</option>
						</select>
						<label class="mam-check">فعال<input type="checkbox" name="field_enabled[]" value="1" checked></label>
						<label class="mam-check">اجباری<input type="checkbox" name="field_required[]" value="1"></label>
						<button type="button" class="button mam-danger mam-remove-field">×</button>
					</div>
				</template>
				<p><button type="button" class="button" id="mam-add-field">+ افزودن فیلد سفارشی</button></p>
			</div>
		</div>

		<!-- Checkout page + discount codes -->
		<?php $discounts = isset( $s['discount_codes'] ) && is_array( $s['discount_codes'] ) ? $s['discount_codes'] : array(); ?>
		<div class="mam-panel" data-panel="checkout">
			<div class="mam-card">
				<div class="mam-card-title">متن صفحهٔ پرداخت</div>
				<div class="mam-fld"><label>عنوان بالای فرم</label><input type="text" name="checkout_title" value="<?php echo esc_attr( isset( $s['checkout_title'] ) ? $s['checkout_title'] : 'اطلاعات پرداخت' ); ?>"></div>
				<div class="mam-fld"><label>زیرعنوان</label><input type="text" name="checkout_subtitle" value="<?php echo esc_attr( isset( $s['checkout_subtitle'] ) ? $s['checkout_subtitle'] : 'لطفاً اطلاعات خود را وارد کنید' ); ?>"></div>
				<div class="mam-fld"><label>متن امنیت (پایین کارت سفارش)</label><input type="text" name="checkout_secure" value="<?php echo esc_attr( isset( $s['checkout_secure'] ) ? $s['checkout_secure'] : 'اطلاعات شما امن و محفوظ است' ); ?>"></div>
			</div>

			<div class="mam-card">
				<div class="mam-card-title">کدهای تخفیف</div>
				<p style="color:#c8ccd6;line-height:2;margin:0 0 12px">
					برای هر کد، نوع تخفیف (درصدی یا مبلغ ثابت به تومان) و مقدار آن را وارد کنید. کاربر می‌تواند این کد را در صفحهٔ پرداخت وارد کند.
				</p>
				<div id="mam-disc-list">
					<?php foreach ( $discounts as $d ) : ?>
						<div class="mam-disc-row">
							<input type="text" name="disc_code[]" value="<?php echo esc_attr( isset( $d['code'] ) ? $d['code'] : '' ); ?>" placeholder="کد (مثلاً MAFIA20)" dir="ltr">
							<select name="disc_type[]">
								<option value="percent" <?php selected( ( isset( $d['type'] ) ? $d['type'] : 'percent' ), 'percent' ); ?>>درصدی (٪)</option>
								<option value="fixed" <?php selected( ( isset( $d['type'] ) ? $d['type'] : '' ), 'fixed' ); ?>>مبلغ ثابت (تومان)</option>
							</select>
							<input type="text" name="disc_value[]" value="<?php echo esc_attr( isset( $d['value'] ) ? $d['value'] : '' ); ?>" placeholder="مقدار" dir="ltr">
							<button type="button" class="button mam-danger mam-remove-disc">×</button>
						</div>
					<?php endforeach; ?>
				</div>
				<template id="mam-disc-template">
					<div class="mam-disc-row">
						<input type="text" name="disc_code[]" value="" placeholder="کد (مثلاً MAFIA20)" dir="ltr">
						<select name="disc_type[]">
							<option value="percent">درصدی (٪)</option>
							<option value="fixed">مبلغ ثابت (تومان)</option>
						</select>
						<input type="text" name="disc_value[]" value="" placeholder="مقدار" dir="ltr">
						<button type="button" class="button mam-danger mam-remove-disc">×</button>
					</div>
				</template>
				<p><button type="button" class="button" id="mam-add-disc">+ افزودن کد تخفیف</button></p>
			</div>
		</div>

		<p class="mam-save-bar"><button type="submit" class="button button-primary button-hero">ذخیره تنظیمات</button></p>
	</form>
</div>
