<?php
/**
 * Admin — plans editor (simplified: just create subscriptions).
 * Buying happens on the site owner's own landing page via shortcodes.
 *
 * @package MafiaAcademyMembership
 * @var array $plans
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap mam-wrap">
	<div class="mam-head"><h1><span class="mam-logo">M</span> پلن‌های اشتراک</h1></div>

	<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore ?>
		<div class="mam-notice ok">اشتراک‌ها ذخیره شدند ✅</div>
	<?php endif; ?>

	<div class="mam-card" style="max-width:760px">
		<div class="mam-card-title">راهنما</div>
		<p style="color:#c8ccd6;line-height:2;margin:0">
			اینجا فقط اشتراک بساز (نام، قیمت، مدت). بعد از ذخیره، برای هر اشتراک یک
			<b style="color:#c9a24b">لینک مستقیم پرداخت</b> ساخته می‌شود. کافی است آن لینک را کپی کنی
			و روی هر دکمه یا عکس در سایت بگذاری؛ کاربر با کلیک، بدون هیچ شورت‌کدی مستقیم به
			صفحهٔ پرداخت همان اشتراک می‌رود.
		</p>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="mam_save_plans">
		<?php wp_nonce_field( 'mam_save_plans', 'mam_plans_nonce' ); ?>

		<div id="mam-plans-list">
			<?php foreach ( $plans as $p ) : ?>
				<div class="mam-plan-row mam-card">
					<div class="mam-plan-fields mam-plan-simple">
						<label>نام اشتراک<input type="text" name="plan[label][]" value="<?php echo esc_attr( $p['label'] ); ?>" placeholder="مثلاً: اشتراک سالانه"></label>
						<label>قیمت (تومان)<input type="text" name="plan[price][]" value="<?php echo esc_attr( $p['price'] ); ?>" placeholder="1990000"></label>
						<label>مدت (روز)<input type="number" name="plan[days][]" value="<?php echo esc_attr( $p['days'] ); ?>" placeholder="365"></label>
						<label>کلید (خودکار)<input type="text" name="plan[key][]" value="<?php echo esc_attr( $p['key'] ); ?>" dir="ltr" placeholder="خالی = خودکار"></label>
					</div>
					<?php $buy_link = MAM_Restrictions::instance()->checkout_url( $p['key'] ); ?>
					<div class="mam-plan-link">
						<label>لینک مستقیم پرداخت این اشتراک</label>
						<div class="mam-plan-link-row">
							<input type="text" readonly value="<?php echo esc_url( $buy_link ); ?>" onfocus="this.select()" dir="ltr">
							<button type="button" class="button button-primary mam-copy-link" data-link="<?php echo esc_attr( $buy_link ); ?>">کپی لینک</button>
							<a class="button" href="<?php echo esc_url( $buy_link ); ?>" target="_blank" rel="noopener">باز کردن</a>
						</div>
					</div>
					<button type="button" class="button mam-danger mam-remove-plan">حذف اشتراک</button>
				</div>
			<?php endforeach; ?>
		</div>

		<template id="mam-plan-template">
			<div class="mam-plan-row mam-card">
				<div class="mam-plan-fields mam-plan-simple">
					<label>نام اشتراک<input type="text" name="plan[label][]" value="" placeholder="مثلاً: اشتراک سالانه"></label>
					<label>قیمت (تومان)<input type="text" name="plan[price][]" value="" placeholder="1990000"></label>
					<label>مدت (روز)<input type="number" name="plan[days][]" value="30" placeholder="30"></label>
					<label>کلید (خودکار)<input type="text" name="plan[key][]" value="" dir="ltr" placeholder="خالی = خودکار"></label>
				</div>
				<button type="button" class="button mam-danger mam-remove-plan">حذف اشتراک</button>
			</div>
		</template>

		<p>
			<button type="button" class="button" id="mam-add-plan">+ افزودن اشتراک</button>
			<button type="submit" class="button button-primary">ذخیره اشتراک‌ها</button>
		</p>
	</form>
</div>
