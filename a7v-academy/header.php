<?php
/**
 * Header template.
 *
 * @package A7V
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if ( get_theme_mod( 'a7v_show_notice', true ) ) : ?>
<div class="notice-bar" id="noticeBar">
	<div class="container notice-inner">
		<span>🔥 پیشنهاد ویژه: <b>۳۰٪ تخفیف</b> اشتراک سالانه — فقط تا <span class="countdown" id="countdown">48:00:00</span></span>
		<button class="notice-close" id="noticeClose" aria-label="بستن">✕</button>
	</div>
</div>
<?php endif; ?>

<header class="site-header" id="siteHeader">
	<div class="container header-inner">
		<?php a7v_logo(); ?>

		<nav class="main-nav" id="mainNav" aria-label="ناوبری اصلی">
		<?php
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'nav-list',
				'depth'          => 1,
			) );
		} else {
			?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="active">خانه</a>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_course' ) ); ?>">دوره‌های آموزشی</a>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_book' ) ); ?>">خلاصه کتاب</a>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_article' ) ); ?>">مقالات ویژه</a>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_mafia' ) ); ?>">مقالات مافیایی</a>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_prompt' ) ); ?>">پرامپت‌نویسی</a>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_income' ) ); ?>">کسب درآمد</a>
			<?php
		}
		?>
		</nav>

		<div class="header-actions">
			<a class="btn btn-primary btn-panel" href="<?php echo esc_url( a7v_account_url() ); ?>">پنل کاربری</a>
			<a class="icon-btn" href="<?php echo esc_url( a7v_account_url() ); ?>" aria-label="حساب کاربری">
				<svg viewBox="0 0 24 24" width="20" height="20"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
			</a>
			<button class="hamburger" id="hamburger" aria-label="منو"><span></span><span></span><span></span></button>
		</div>
	</div>
</header>

<div class="mobile-drawer" id="mobileDrawer">
	<nav>
	<?php
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => '', 'depth' => 1 ) );
	} else {
		?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="active">خانه</a>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_course' ) ); ?>">دوره‌های آموزشی</a>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_book' ) ); ?>">خلاصه کتاب</a>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_article' ) ); ?>">مقالات ویژه</a>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_mafia' ) ); ?>">مقالات مافیایی</a>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_prompt' ) ); ?>">پرامپت‌نویسی</a>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_income' ) ); ?>">کسب درآمد</a>
		<?php
	}
	?>
	</nav>
	<a class="btn btn-primary btn-block" href="<?php echo esc_url( get_theme_mod( 'a7v_vip_link', '#' ) ); ?>">همین حالا عضو شو 👑</a>
</div>
<div class="overlay" id="overlay"></div>

<main id="main">
