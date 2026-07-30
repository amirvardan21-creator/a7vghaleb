<?php
/**
 * A7V Mobile Bottom Bar (نوار پایین موبایل / bottom-tab).
 *
 * Fully editable from:  پیشخوان → سفارشی‌سازی → تنظیمات A7V → نوار پایین موبایل.
 * Each of the 5 slots has: نمایش (on/off) + آیکون + نوشته + لینک.
 * Slot 3 is the raised center button (اشتراک/VIP) with the special hexagon style.
 *
 * @package A7V
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* =============================================================
 * Icon set for the bottom bar (stroke line icons, 24x24)
 * ============================================================= */
function a7v_tab_icons() {
	return array(
		'home'     => '<path d="M3 11l9-7 9 7"/><path d="M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9"/><path d="M10 20v-5h4v5"/>',
		'folder'   => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
		'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
		'book'     => '<path d="M4 5a2 2 0 0 1 2-2h12v18H6a2 2 0 0 1-2-2z"/><path d="M18 3v18"/>',
		'crown'    => '<path d="M4 8l3.5 4L12 5l4.5 7L20 8l-1.6 10H5.6z"/><path d="M5.6 18h12.8"/>',
		'gem'      => '<path d="M6 3h12l3 6-9 12L3 9z"/><path d="M3 9h18M9 3l3 18M15 3l-3 18"/>',
		'star'     => '<path d="M12 3l2.7 5.5 6 .9-4.3 4.2 1 6-5.4-2.8-5.4 2.8 1-6L3.3 9.4l6-.9z"/>',
		'bookmark' => '<path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1z"/>',
		'heart'    => '<path d="M12 21C5 15.5 3 12 3 8.5A4.5 4.5 0 0 1 12 6a4.5 4.5 0 0 1 9 2.5C21 12 19 15.5 12 21z"/>',
		'user'     => '<circle cx="12" cy="13.5" r="3"/><path d="M5.5 12.5c.5-3.6 2-5.5 6.5-5.5s6 1.9 6.5 5.5"/><path d="M4 12.6c2-1 4.6-1.6 8-1.6s6 .6 8 1.6"/>',
		'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-2.7 1.1V21a2 2 0 0 1-4 0v-.1A1.6 1.6 0 0 0 6.6 19l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.6 1.6 0 0 0 3 13.4H3a2 2 0 0 1 0-4h.1A1.6 1.6 0 0 0 4.6 6.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.6 1.6 0 0 0 10 3.6V3a2 2 0 0 1 4 0v.1A1.6 1.6 0 0 0 17.4 4.6l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0 1.1 2.7H21a2 2 0 0 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1z"/>',
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
		'phone'    => '<path d="M6 3h5l2 5-3 2a12 12 0 0 0 6 6l2-3 5 2v5a2 2 0 0 1-2 2A18 18 0 0 1 4 5a2 2 0 0 1 2-2z"/>',
		'chat'     => '<path d="M4 5h16a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H9l-5 4V6a1 1 0 0 1 1-1z"/>',
		'bell'     => '<path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
		'fire'     => '<path d="M12 2c1 3-2 4-2 7a2 2 0 0 0 4 0c2 2 3 4 3 6a5 5 0 0 1-10 0c0-4 3-6 5-13z"/>',
		'graduate' => '<path d="M12 3 2 8l10 5 10-5z"/><path d="M6 10.5V15c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.5"/>',
		'money'    => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
	);
}

/** Human labels for the icon picker. */
function a7v_tab_icon_labels() {
	return array(
		'home' => 'خانه', 'folder' => 'پوشه / محتوا', 'grid' => 'شبکه', 'book' => 'کتاب',
		'crown' => 'تاج / VIP', 'gem' => 'الماس / اشتراک', 'star' => 'ستاره', 'bookmark' => 'نشان / ذخیره',
		'heart' => 'قلب', 'user' => 'کاربر / پروفایل', 'settings' => 'تنظیمات', 'search' => 'جستجو',
		'phone' => 'تماس', 'chat' => 'گفتگو', 'bell' => 'اعلان', 'fire' => 'داغ',
		'graduate' => 'آموزش', 'money' => 'کسب درآمد',
	);
}

/** Full <svg> string for a tab icon key. */
function a7v_tab_icon_svg( $key ) {
	$icons = a7v_tab_icons();
	$path  = isset( $icons[ $key ] ) ? $icons[ $key ] : $icons['home'];
	return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $path . '</svg>';
}

/* =============================================================
 * Default slots (match the original hard-coded bar)
 * ============================================================= */
function a7v_bottom_tab_defaults() {
	return array(
		1 => array( 'on' => true, 'icon' => 'home',     'label' => 'خانه',    'url' => '' ),
		2 => array( 'on' => true, 'icon' => 'folder',   'label' => 'محتوا',   'url' => '' ),
		3 => array( 'on' => true, 'icon' => 'gem',      'label' => 'اشتراک',  'url' => '' ), // center (VIP)
		4 => array( 'on' => true, 'icon' => 'bookmark', 'label' => 'ذخیره',   'url' => '' ),
		5 => array( 'on' => true, 'icon' => 'user',     'label' => 'پروفایل', 'url' => '' ),
	);
}

/** URL fallback per slot when the admin left the link empty. */
function a7v_bottom_tab_fallback_url( $slot ) {
	switch ( $slot ) {
		case 1: return home_url( '/' );
		case 2: return get_post_type_archive_link( 'a7v_course' );
		case 3: return get_theme_mod( 'a7v_vip_link', '#' );
		case 4:
		case 5: return function_exists( 'a7v_account_url' ) ? a7v_account_url() : home_url( '/' );
	}
	return home_url( '/' );
}

/**
 * Resolve the final list of bottom-tab items from customizer settings.
 */
function a7v_get_bottom_tab_items() {
	$defaults = a7v_bottom_tab_defaults();
	$items    = array();
	foreach ( $defaults as $slot => $d ) {
		$on = get_theme_mod( "a7v_tab{$slot}_on", $d['on'] );
		if ( ! $on ) { continue; }
		$url = trim( (string) get_theme_mod( "a7v_tab{$slot}_url", $d['url'] ) );
		if ( '' === $url ) { $url = a7v_bottom_tab_fallback_url( $slot ); }
		$items[] = array(
			'slot'   => $slot,
			'icon'   => get_theme_mod( "a7v_tab{$slot}_icon", $d['icon'] ),
			'label'  => get_theme_mod( "a7v_tab{$slot}_label", $d['label'] ),
			'url'    => $url,
			'center' => ( 3 === $slot ), // middle slot = raised VIP button
		);
	}
	return $items;
}

/**
 * Render the mobile bottom navigation bar.
 * Called from footer.php.
 */
function a7v_render_bottom_tab() {
	if ( ! get_theme_mod( 'a7v_tabbar_enable', true ) ) { return; }
	$items = a7v_get_bottom_tab_items();
	if ( empty( $items ) ) { return; }
	?>
	<nav class="bottom-tab" aria-label="ناوبری موبایل">
		<?php foreach ( $items as $it ) :
			$is_home   = ( 1 === $it['slot'] );
			$active    = ( $is_home && ( is_front_page() || is_home() ) ) ? ' active' : '';
			$class     = $it['center'] ? 'tab-vip' : trim( $active );
			?>
			<?php if ( $it['center'] ) : ?>
				<a href="<?php echo esc_url( $it['url'] ); ?>" class="tab-vip">
					<span class="tab-hex"><?php echo a7v_tab_icon_svg( $it['icon'] ); // phpcs:ignore ?></span>
					<span class="tab-label"><?php echo esc_html( $it['label'] ); ?></span>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $it['url'] ); ?>"<?php echo $active ? ' class="active"' : ''; ?>>
					<?php echo a7v_tab_icon_svg( $it['icon'] ); // phpcs:ignore ?>
					<span><?php echo esc_html( $it['label'] ); ?></span>
				</a>
			<?php endif; ?>
		<?php endforeach; ?>
	</nav>
	<?php
}

/* =============================================================
 * Customizer section
 * ============================================================= */
function a7v_bottom_tab_customize( $wp_customize ) {
	$wp_customize->add_section( 'a7v_tabbar', array(
		'title' => 'نوار پایین موبایل',
		'panel' => 'a7v_panel',
	) );

	// Master toggle.
	$wp_customize->add_setting( 'a7v_tabbar_enable', array(
		'default'           => true,
		'sanitize_callback' => 'a7v_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'a7v_tabbar_enable', array(
		'label'   => 'نمایش نوار پایین در موبایل',
		'section' => 'a7v_tabbar',
		'type'    => 'checkbox',
	) );

	// Icon dropdown choices.
	$icon_choices = array();
	$labels       = a7v_tab_icon_labels();
	foreach ( array_keys( a7v_tab_icons() ) as $k ) {
		$icon_choices[ $k ] = isset( $labels[ $k ] ) ? $labels[ $k ] : $k;
	}

	$defaults = a7v_bottom_tab_defaults();
	foreach ( $defaults as $slot => $d ) {
		$center_note = ( 3 === $slot ) ? ' (دکمه وسط / برجسته)' : '';

		// Show/hide.
		$wp_customize->add_setting( "a7v_tab{$slot}_on", array(
			'default'           => $d['on'],
			'sanitize_callback' => 'a7v_sanitize_checkbox',
		) );
		$wp_customize->add_control( "a7v_tab{$slot}_on", array(
			'label'   => "نمایش آیکون {$slot}{$center_note}",
			'section' => 'a7v_tabbar',
			'type'    => 'checkbox',
		) );

		// Icon.
		$wp_customize->add_setting( "a7v_tab{$slot}_icon", array(
			'default'           => $d['icon'],
			'sanitize_callback' => 'a7v_sanitize_tab_icon',
		) );
		$wp_customize->add_control( "a7v_tab{$slot}_icon", array(
			'label'   => "آیکون {$slot}",
			'section' => 'a7v_tabbar',
			'type'    => 'select',
			'choices' => $icon_choices,
		) );

		// Label.
		$wp_customize->add_setting( "a7v_tab{$slot}_label", array(
			'default'           => $d['label'],
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( "a7v_tab{$slot}_label", array(
			'label'   => "نوشته {$slot}",
			'section' => 'a7v_tabbar',
			'type'    => 'text',
		) );

		// Link.
		$wp_customize->add_setting( "a7v_tab{$slot}_url", array(
			'default'           => $d['url'],
			'sanitize_callback' => 'esc_url_raw',
		) );
		$wp_customize->add_control( "a7v_tab{$slot}_url", array(
			'label'       => "لینک {$slot}",
			'description' => 'خالی بگذارید تا لینک پیش‌فرض استفاده شود.',
			'section'     => 'a7v_tabbar',
			'type'        => 'url',
		) );
	}
}
add_action( 'customize_register', 'a7v_bottom_tab_customize', 20 );

/** Sanitize an icon key against the allowed set. */
function a7v_sanitize_tab_icon( $val ) {
	$icons = a7v_tab_icons();
	return isset( $icons[ $val ] ) ? $val : 'home';
}
