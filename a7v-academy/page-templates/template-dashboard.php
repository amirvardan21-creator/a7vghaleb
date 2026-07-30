<?php
/**
 * Template Name: A7V — حساب کاربری
 *
 * Mafia-styled user dashboard (banner + tidy tab bar + panels).
 *
 * @package A7V
 */

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( a7v_register_url() );
	exit;
}

get_header();

$user     = wp_get_current_user();
$username = $user->user_login;
$name     = $user->display_name ? $user->display_name : $username;
$initial  = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 );
$phone    = get_user_meta( $user->ID, 'a7v_phone', true );
$since    = get_user_meta( $user->ID, 'a7v_member_since', true );
$days     = a7v_subscription_days( $user->ID );
$msg      = $GLOBALS['a7v_auth_msg'] ?? '';
$mtype    = $GLOBALS['a7v_auth_type'] ?? 'success';

$tabs = array(
	'overview'  => array( 'dashboard', 'داشبورد' ),
	'courses'   => array( 'course',    'دوره‌های من' ),
	'saved'     => array( 'saved',     'ذخیره‌شده‌ها' ),
	'badges'    => array( 'badge',     'نشان‌ها' ),
	'downloads' => array( 'download',  'دانلودها' ),
	'notif'     => array( 'bell',      'اعلان‌ها' ),
	'sub'       => array( 'crown',     'اشتراک' ),
	'settings'  => array( 'settings',  'تنظیمات' ),
);

$notices = function_exists( 'a7v_get_notices' ) ? a7v_get_notices( 6 ) : array();
?>

<section class="dash">
	<div class="container">

		<!-- BANNER -->
		<div class="dash-banner glass">
			<div class="db-user">
				<div class="dash-avatar"><?php echo esc_html( $initial ); ?></div>
				<div>
					<div class="dash-name"><?php echo esc_html( $name ); ?></div>
					<div class="dash-vip">👑 عضو ویژه VIP</div>
				</div>
			</div>
			<div class="db-sub">
				<div class="db-days"><b><?php echo esc_html( $days ); ?></b> روز تا پایان اشتراک</div>
				<a class="btn btn-primary" href="<?php echo esc_url( get_theme_mod( 'a7v_vip_link', '#' ) ); ?>">تمدید اشتراک</a>
			</div>
		</div>

		<!-- NAV (red glass menu) -->
		<nav class="dash-menu glass" aria-label="منوی حساب کاربری">
			<div class="dash-menu-grid">
				<?php $first = true; foreach ( $tabs as $key => $t ) : ?>
					<button type="button" data-tab="<?php echo esc_attr( $key ); ?>" class="dash-menu-btn<?php echo $first ? ' active' : ''; ?>">
						<span class="dm-ic"><?php echo a7v_icon( $t[0] ); // phpcs:ignore ?></span>
						<span class="dm-lbl"><?php echo esc_html( $t[1] ); ?></span>
					</button>
				<?php $first = false; endforeach; ?>
				<a class="dash-menu-btn dash-logout" href="<?php echo esc_url( wp_logout_url( a7v_register_url() ) ); ?>">
					<span class="dm-ic"><?php echo a7v_icon( 'logout' ); // phpcs:ignore ?></span>
					<span class="dm-lbl">خروج</span>
				</a>
			</div>
		</nav>

		<!-- PANELS -->
		<div class="dash-main">

			<!-- OVERVIEW -->
			<div class="dash-panel active" data-panel="overview">
				<div class="stat-grid">
					<div class="stat glass"><div class="num">۱۲</div><div class="lbl">دوره فعال</div></div>
					<div class="stat glass"><div class="num">۸</div><div class="lbl">کتاب ذخیره‌شده</div></div>
					<div class="stat glass"><div class="num">۲۴</div><div class="lbl">مقاله خوانده‌شده</div></div>
					<div class="stat glass"><div class="num">۵</div><div class="lbl">نشان افتخار</div></div>
				</div>

				<!-- ANNOUNCEMENTS / NEWS -->
				<div class="panel-title"><span class="pt-ic"><?php echo a7v_icon( 'announce' ); // phpcs:ignore ?></span> اطلاع‌رسانی و اخبار</div>
				<div class="notice-list">
					<?php if ( ! empty( $notices ) ) : ?>
						<?php foreach ( $notices as $n ) : ?>
							<div class="notice-card glass type-<?php echo esc_attr( $n['type'] ); ?><?php echo $n['pinned'] ? ' pinned' : ''; ?>">
								<div class="nc-ic"><?php echo a7v_icon( 'announce' ); // phpcs:ignore ?></div>
								<div class="nc-body">
									<div class="nc-head">
										<span class="nc-tag"><?php echo esc_html( $n['label'] ); ?></span>
										<?php if ( $n['pinned'] ) : ?><span class="nc-pin">📌 مهم</span><?php endif; ?>
										<span class="nc-date"><?php echo esc_html( $n['date'] ); ?></span>
									</div>
									<h4><?php echo esc_html( $n['title'] ); ?></h4>
									<?php if ( $n['body'] ) : ?><p><?php echo esc_html( $n['body'] ); ?></p><?php endif; ?>
									<?php if ( ! empty( $n['link'] ) ) : ?>
										<a class="nc-link" href="<?php echo esc_url( $n['link'] ); ?>">مشاهده ◄</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<div class="notice-empty glass">
							<div class="ne-ic"><?php echo a7v_icon( 'announce' ); // phpcs:ignore ?></div>
							<div>
								<h4>هنوز اطلاعیه‌ای منتشر نشده</h4>
								<p>اطلاعیه‌ها و اخبار (مثل آپدیت سایت) از پیشخوان مدیریت ← «اطلاع‌رسانی» اضافه می‌شوند و اینجا برای کاربران نمایش داده می‌شوند.</p>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<div class="panel-title"><span class="pt-ic"><?php echo a7v_icon( 'dashboard' ); // phpcs:ignore ?></span> ادامه یادگیری</div>
				<div class="dash-list">
					<?php foreach ( array( array( 'هوش مالی و سرمایه‌گذاری', 65 ), array( 'روانشناسی دارک', 30 ), array( 'مهارت مذاکره‌سازی', 80 ) ) as $c ) : ?>
						<div class="dash-item glass">
							<div class="di-ic"><?php echo a7v_icon( 'course' ); ?></div>
							<div class="di-body">
								<h4><?php echo esc_html( $c[0] ); ?></h4>
								<div class="progress"><span style="width:<?php echo (int) $c[1]; ?>%"></span></div>
								<div class="di-meta">پیشرفت <?php echo (int) $c[1]; ?>٪ — ادامه از جلسه بعدی</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- MY COURSES -->
			<div class="dash-panel" data-panel="courses">
				<div class="panel-title"><span class="pt-ic"><?php echo a7v_icon( 'course' ); ?></span> دوره‌های من</div>
				<div class="dash-list">
					<?php foreach ( array( array( 'هوش مالی و سرمایه‌گذاری', 65 ), array( 'روانشناسی دارک', 30 ), array( 'مهارت مذاکره‌سازی', 80 ), array( 'برندسازی شخصی', 10 ) ) as $c ) : ?>
						<div class="dash-item glass">
							<div class="di-ic"><?php echo a7v_icon( 'course' ); ?></div>
							<div class="di-body">
								<h4><?php echo esc_html( $c[0] ); ?></h4>
								<div class="progress"><span style="width:<?php echo (int) $c[1]; ?>%"></span></div>
								<div class="di-meta">پیشرفت <?php echo (int) $c[1]; ?>٪</div>
							</div>
							<a class="btn btn-primary" href="#" style="padding:9px 16px;font-size:13px">ادامه</a>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- SAVED -->
			<div class="dash-panel" data-panel="saved">
				<div class="panel-title"><span class="pt-ic"><?php echo a7v_icon( 'saved' ); ?></span> ذخیره‌شده‌ها</div>
				<div class="dash-list">
					<?php foreach ( array( array( 'book', '۴۸ قانون قدرت' ), array( 'mafia', 'قوانین مخفی بازی قدرت' ), array( 'article', 'رازهای اعتماد به نفس بالا' ) ) as $s ) : ?>
						<div class="dash-item glass">
							<div class="di-ic"><?php echo a7v_icon( $s[0] ); ?></div>
							<div class="di-body"><h4 style="margin:0"><?php echo esc_html( $s[1] ); ?></h4></div>
							<a class="btn btn-primary" href="#" style="padding:9px 16px;font-size:13px">مشاهده</a>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- BADGES -->
			<div class="dash-panel" data-panel="badges">
				<div class="panel-title"><span class="pt-ic"><?php echo a7v_icon( 'badge' ); ?></span> نشان‌های افتخار</div>
				<div class="badges-grid">
					<?php
					$badges = array(
						array( '🎯', 'اولین قدم', 'عضویت در باشگاه', false ),
						array( '🔥', '۵ دوره', '۵ دوره تکمیل شد', false ),
						array( '📚', 'کتاب‌خوان', '۱۰ خلاصه مطالعه', false ),
						array( '👑', 'دُن مافیا', 'سطح نهایی قدرت', true ),
						array( '💎', 'عضو ۱ ساله', 'یک سال وفاداری', true ),
						array( '⚡', 'فعال هفته', '۷ روز پیاپی', true ),
					);
					foreach ( $badges as $b ) : ?>
						<div class="badge-card glass<?php echo $b[3] ? ' locked' : ''; ?>">
							<div class="bc-ic"><?php echo $b[0]; ?></div>
							<h5><?php echo esc_html( $b[1] ); ?></h5>
							<small><?php echo esc_html( $b[2] ); ?></small>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- DOWNLOADS -->
			<div class="dash-panel" data-panel="downloads">
				<div class="panel-title"><span class="pt-ic"><?php echo a7v_icon( 'download' ); ?></span> دانلودها</div>
				<div class="dash-list">
					<?php foreach ( array( 'جزوه دوره هوش مالی (PDF)', 'چک‌لیست ۴۸ قانون قدرت (PDF)', 'قالب برنامه‌ریزی هفتگی (PDF)' ) as $d ) : ?>
						<div class="dash-item glass">
							<div class="di-ic">📄</div>
							<div class="di-body"><h4 style="margin:0"><?php echo esc_html( $d ); ?></h4></div>
							<a class="btn btn-primary" href="#" style="padding:9px 16px;font-size:13px">دانلود</a>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- NOTIFICATIONS -->
			<div class="dash-panel" data-panel="notif">
				<div class="panel-title"><span class="pt-ic"><?php echo a7v_icon( 'bell' ); ?></span> اعلان‌ها</div>
				<div class="dash-list">
					<?php foreach ( array( array( '🎬', 'دوره جدید «فن بیان و نفوذ کلام» اضافه شد', 'امروز' ), array( '👑', 'اشتراک ویژه شما فعال است', '۲ روز پیش' ), array( '🏅', 'نشان «اولین قدم» را دریافت کردی', 'هفته پیش' ) ) as $n ) : ?>
						<div class="dash-item glass">
							<div class="di-ic"><?php echo $n[0]; ?></div>
							<div class="di-body"><h4 style="margin:0 0 4px"><?php echo esc_html( $n[1] ); ?></h4><div class="di-meta"><?php echo esc_html( $n[2] ); ?></div></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- SUBSCRIPTION -->
			<div class="dash-panel" data-panel="sub">
				<div class="panel-title"><span class="pt-ic"><?php echo a7v_icon( 'crown' ); ?></span> اشتراک ویژه</div>
				<div class="sub-card glass">
					<div class="sub-ring">
						<div class="dash-vip" style="margin-bottom:14px">وضعیت: فعال ✅</div>
						<div class="sub-days"><?php echo esc_html( $days ); ?></div>
						<div style="color:var(--text-mid);margin-top:6px">روز تا پایان اشتراک</div>
						<?php if ( $since ) : ?><div class="di-meta" style="margin-top:10px">عضو از: <?php echo esc_html( date_i18n( 'Y/m/d', strtotime( $since ) ) ); ?></div><?php endif; ?>
					</div>
				</div>
				<div style="text-align:center"><a class="btn btn-primary btn-lg" href="<?php echo esc_url( get_theme_mod( 'a7v_vip_link', '#' ) ); ?>">تمدید / ارتقای اشتراک</a></div>
			</div>

			<!-- SETTINGS -->
			<div class="dash-panel" data-panel="settings">
				<div class="panel-title"><span class="pt-ic"><?php echo a7v_icon( 'settings' ); ?></span> تنظیمات حساب</div>
				<?php if ( $msg ) : ?><div class="auth-msg<?php echo 'success' === $mtype ? ' ok' : ''; ?>" style="max-width:480px;margin-bottom:16px"><?php echo esc_html( $msg ); ?></div><?php endif; ?>
				<form class="dash-form" method="post">
					<?php wp_nonce_field( 'a7v_auth', 'a7v_nonce' ); ?>
					<input type="hidden" name="a7v_action" value="update_profile">
					<div class="field"><label>نام کاربری (غیرقابل تغییر)</label><input type="text" value="<?php echo esc_attr( $username ); ?>" disabled></div>
					<div class="field"><label>نام نمایشی</label><input type="text" name="a7v_display" value="<?php echo esc_attr( $name ); ?>"></div>
					<div class="field"><label>شماره تماس</label><input type="tel" name="a7v_phone" value="<?php echo esc_attr( $phone ); ?>" inputmode="numeric"></div>
					<div class="field"><label>رمز عبور جدید (اختیاری)</label><input type="password" name="a7v_newpass" placeholder="برای تغییر رمز پر کنید"></div>
					<button class="btn btn-primary btn-lg" type="submit">ذخیره تغییرات</button>
				</form>
			</div>

		</div>
	</div>
</section>

<?php get_footer(); ?>
