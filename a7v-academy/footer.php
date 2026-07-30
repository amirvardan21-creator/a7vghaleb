<?php
/**
 * Footer template.
 *
 * @package A7V
 */
?>
</main>

<footer class="site-footer">
	<div class="container footer-grid">
		<div class="footer-brand">
			<?php a7v_logo(); ?>
			<p><?php echo esc_html( get_theme_mod( 'a7v_footer_about', 'آکادمی مافیا، بزرگ‌ترین مرجع آموزش و رشد فردی در زمینه‌های کسب‌وکار، روانشناسی، موفقیت و قدرت.' ) ); ?></p>
		</div>

		<div class="footer-col">
			<h4>دسترسی سریع</h4>
			<?php
			if ( has_nav_menu( 'footer_quick' ) ) {
				wp_nav_menu( array( 'theme_location' => 'footer_quick', 'container' => false, 'depth' => 1 ) );
			} else {
				?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_course' ) ); ?>">دوره‌های آموزشی</a>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_book' ) ); ?>">خلاصه کتاب</a>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_article' ) ); ?>">مقالات ویژه</a>
				<?php
			}
			?>
		</div>

		<div class="footer-col">
			<h4>بخش‌های ویژه</h4>
			<?php
			if ( has_nav_menu( 'footer_special' ) ) {
				wp_nav_menu( array( 'theme_location' => 'footer_special', 'container' => false, 'depth' => 1 ) );
			} else {
				?>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_prompt' ) ); ?>">پرامپت‌نویسی</a>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_income' ) ); ?>">کسب درآمد</a>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_tool' ) ); ?>">ابزارهای کاربردی</a>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'a7v_mafia' ) ); ?>">مقالات مافیایی</a>
				<?php
			}
			?>
		</div>

		<div class="footer-col">
			<h4>اطلاعات</h4>
			<?php
			if ( has_nav_menu( 'footer_info' ) ) {
				wp_nav_menu( array( 'theme_location' => 'footer_info', 'container' => false, 'depth' => 1 ) );
			} else {
				?>
				<a href="#">درباره ما</a>
				<a href="#">تماس با ما</a>
				<a href="#">سوالات متداول</a>
				<a href="#">قوانین و مقررات</a>
				<?php
			}
			?>
		</div>

		<div class="footer-col">
			<h4>همراه ما باشید</h4>
			<div class="socials">
				<a href="<?php echo esc_url( get_theme_mod( 'a7v_social_tg', '#' ) ); ?>" aria-label="تلگرام" class="soc">✈</a>
				<a href="<?php echo esc_url( get_theme_mod( 'a7v_social_ig', '#' ) ); ?>" aria-label="اینستاگرام" class="soc">◎</a>
				<a href="<?php echo esc_url( get_theme_mod( 'a7v_social_yt', '#' ) ); ?>" aria-label="یوتیوب" class="soc">▶</a>
			</div>
		</div>
	</div>
	<div class="footer-bottom"><div class="container"><?php echo esc_html( get_theme_mod( 'a7v_copyright', '© 2025 A7V Academy. All Rights Reserved.' ) ); ?></div></div>
</footer>

<?php if ( function_exists( 'a7v_render_bottom_tab' ) ) { a7v_render_bottom_tab(); } ?>

<div class="a7v-modal" id="a7vPaywall" aria-hidden="true">
	<div class="a7v-modal-overlay" data-close></div>
	<div class="a7v-modal-card glass">
		<button class="a7v-modal-x" data-close aria-label="بستن">✕</button>
		<div class="crown">🔒</div>
		<h3>محتوای ویژه اعضای آکادمی مافیا</h3>
		<p>این محتوا فقط برای اعضای دارای اشتراک ویژه در دسترس است. با تهیه اشتراک، به این محتوا و تمام دوره‌ها، کتاب‌ها و مقالات دسترسی نامحدود پیدا کن.</p>
		<a class="btn btn-primary btn-lg" href="<?php echo esc_url( get_theme_mod( 'a7v_vip_link', '#' ) ); ?>">👑 خرید اشتراک ویژه</a>
		<a class="a7v-modal-login" href="<?php echo esc_url( a7v_account_url() ); ?>">قبلاً عضو شدی؟ ورود / حساب کاربری</a>
	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
