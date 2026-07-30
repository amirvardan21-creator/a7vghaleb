<?php
/**
 * Shared auth form markup (register page template + [a7v_register]).
 *
 * @package A7V
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$msg  = $GLOBALS['a7v_auth_msg'] ?? '';
$type = $GLOBALS['a7v_auth_type'] ?? 'error';
?>
<div class="auth-wrap">
	<?php if ( is_user_logged_in() ) : ?>
		<div class="auth-card glass" style="text-align:center">
			<div class="crown" style="font-size:30px">👑</div>
			<h1 style="font-size:24px;margin:10px 0">شما وارد شده‌اید</h1>
			<p style="color:var(--text-mid);margin-bottom:20px">به باشگاه خوش آمدی، <?php echo esc_html( wp_get_current_user()->display_name ); ?></p>
			<a class="btn btn-primary btn-lg" href="<?php echo esc_url( a7v_dashboard_url() ); ?>">ورود به حساب کاربری</a>
		</div>
	<?php else : ?>
		<div class="auth-card glass">
			<div class="auth-head">
				<div class="crown">👑</div>
				<h1>ورود به باشگاه مافیا</h1>
				<p>با نام کاربری و رمز عبور</p>
			</div>

			<?php if ( $msg ) : ?>
				<div class="auth-msg<?php echo 'success' === $type ? ' ok' : ''; ?>"><?php echo esc_html( $msg ); ?></div>
			<?php endif; ?>

			<div class="auth-tabs">
				<button data-auth-tab="register" class="active">ثبت‌نام</button>
				<button data-auth-tab="login">ورود</button>
			</div>

			<!-- REGISTER -->
			<form class="auth-form active" data-form="register" method="post">
				<?php wp_nonce_field( 'a7v_auth', 'a7v_nonce' ); ?>
				<input type="hidden" name="a7v_action" value="register">
				<div class="field"><label>نام کاربری</label><input type="text" name="a7v_user" placeholder="نام کاربری (انگلیسی یا عدد)" required></div>
				<div class="field"><label>شماره تماس</label><input type="tel" name="a7v_phone" placeholder="۰۹۱۲۳۴۵۶۷۸۹" inputmode="numeric"></div>
				<div class="field"><label>رمز عبور</label><input type="password" name="a7v_pass" placeholder="حداقل ۴ کاراکتر" required></div>
				<button class="btn btn-primary btn-block btn-lg" type="submit">ثبت‌نام و ورود 👑</button>
				<p class="auth-foot">با ثبت‌نام، قوانین آکادمی مافیا را می‌پذیرید.</p>
			</form>

			<!-- LOGIN -->
			<form class="auth-form" data-form="login" method="post">
				<?php wp_nonce_field( 'a7v_auth', 'a7v_nonce' ); ?>
				<input type="hidden" name="a7v_action" value="login">
				<div class="field"><label>نام کاربری</label><input type="text" name="a7v_user" placeholder="نام کاربری" required></div>
				<div class="field"><label>رمز عبور</label><input type="password" name="a7v_pass" placeholder="رمز عبور" required></div>
				<button class="btn btn-primary btn-block btn-lg" type="submit">ورود به حساب</button>
				<p class="auth-foot">حساب نداری؟ از تب «ثبت‌نام» عضو شو.</p>
			</form>
		</div>
	<?php endif; ?>
</div>
