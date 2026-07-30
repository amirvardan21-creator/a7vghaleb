<?php
/**
 * Custom Post Types + shared taxonomy.
 *
 * @package A7V
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Map of post types: slug => [singular, plural, dashicon, rewrite].
 */
function a7v_post_types() {
	return array(
		'a7v_course'  => array( 'دوره آموزشی', 'دوره‌های آموزشی', 'dashicons-welcome-learn-more', 'course' ),
		'a7v_book'    => array( 'خلاصه کتاب', 'خلاصه کتاب‌ها', 'dashicons-book-alt', 'book-summary' ),
		'a7v_article' => array( 'مقاله ویژه', 'مقالات ویژه', 'dashicons-star-filled', 'featured-article' ),
		'a7v_mafia'   => array( 'مقاله مافیایی', 'مقالات مافیایی', 'dashicons-businessman', 'mafia-article' ),
		'a7v_prompt'  => array( 'پرامپت', 'پرامپت‌ها', 'dashicons-editor-code', 'prompt' ),
		'a7v_tool'    => array( 'ابزار', 'ابزارها', 'dashicons-admin-tools', 'tool' ),
		'a7v_income'  => array( 'کسب درآمد', 'بخش کسب درآمد', 'dashicons-money-alt', 'income' ),
	);
}

/**
 * Register all CPTs + the shared category taxonomy.
 */
function a7v_register_cpts() {
	foreach ( a7v_post_types() as $slug => $d ) {
		register_post_type( $slug, array(
			'labels'        => array(
				'name'               => $d[1],
				'singular_name'      => $d[0],
				'add_new'            => 'افزودن جدید',
				'add_new_item'       => 'افزودن ' . $d[0],
				'edit_item'          => 'ویرایش ' . $d[0],
				'new_item'           => $d[0] . ' جدید',
				'view_item'          => 'مشاهده ' . $d[0],
				'search_items'       => 'جستجوی ' . $d[1],
				'not_found'          => 'موردی یافت نشد',
				'menu_name'          => $d[1],
			),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => $d[2],
			'show_in_menu'  => 'a7v_paid_content',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
			'rewrite'       => array( 'slug' => $d[3] ),
			'show_in_rest'  => true,
		) );
	}

	a7v_register_notice_cpt();

	register_taxonomy( 'a7v_category', array_keys( a7v_post_types() ), array(
		'labels'       => array(
			'name'          => 'دسته‌بندی‌ها',
			'singular_name' => 'دسته‌بندی',
			'add_new_item'  => 'افزودن دسته‌بندی',
			'menu_name'     => 'دسته‌بندی‌ها',
		),
		'hierarchical' => true,
		'public'       => true,
		'show_in_rest' => true,
		'show_admin_column' => true,
		'rewrite'      => array( 'slug' => 'a7v-cat' ),
	) );
}
add_action( 'init', 'a7v_register_cpts' );

/**
 * Announcements / news CPT (اطلاع‌رسانی).
 * Admin adds notices from wp-admin; they are shown to users in the dashboard.
 */
function a7v_register_notice_cpt() {
	register_post_type( 'a7v_notice', array(
		'labels' => array(
			'name'               => 'اطلاع‌رسانی‌ها',
			'singular_name'      => 'اطلاع‌رسانی',
			'add_new'            => 'افزودن اطلاعیه',
			'add_new_item'       => 'افزودن اطلاعیه جدید',
			'edit_item'          => 'ویرایش اطلاعیه',
			'new_item'           => 'اطلاعیه جدید',
			'view_item'          => 'مشاهده اطلاعیه',
			'search_items'       => 'جستجوی اطلاعیه‌ها',
			'not_found'          => 'اطلاعیه‌ای یافت نشد',
			'all_items'          => 'همه اطلاعیه‌ها',
			'menu_name'          => 'اطلاع‌رسانی',
		),
		'public'        => true,
		'has_archive'   => false,
		'menu_icon'     => 'dashicons-megaphone',
		'menu_position' => 4,
		'supports'      => array( 'title', 'editor', 'thumbnail' ),
		'rewrite'       => array( 'slug' => 'notice' ),
		'show_in_rest'  => true,
	) );
}

/* =========================================================================
 *  «محتویات پولی» — a single grouped admin menu holding every content type
 *  that requires a VIP subscription. All CPTs above use show_in_menu =>
 *  'a7v_paid_content', so they appear as submenus here. Fully customizable:
 *  add categories, add new items, and manage WooCommerce products in one place.
 * ========================================================================= */
function a7v_paid_content_menu() {
	add_menu_page(
		'محتویات پولی',
		'محتویات پولی',
		'edit_posts',
		'a7v_paid_content',
		'a7v_paid_content_overview',
		'dashicons-lock',
		4
	);

	// Rename the auto-generated first submenu (mirrors the parent) to "نمای کلی".
	add_submenu_page( 'a7v_paid_content', 'نمای کلی محتویات پولی', 'نمای کلی', 'edit_posts', 'a7v_paid_content', 'a7v_paid_content_overview' );

	// Categories (shared taxonomy) — so the admin can add/manage دسته‌بندی‌ها.
	add_submenu_page(
		'a7v_paid_content',
		'دسته‌بندی‌ها',
		'➕ دسته‌بندی‌ها',
		'manage_categories',
		'edit-tags.php?taxonomy=a7v_category&post_type=a7v_course'
	);

	// WooCommerce products (if active) — «همه محصولات».
	if ( post_type_exists( 'product' ) ) {
		add_submenu_page(
			'a7v_paid_content',
			'همه محصولات',
			'همه محصولات',
			'edit_products',
			'edit.php?post_type=product'
		);
	}
}
add_action( 'admin_menu', 'a7v_paid_content_menu' );

/**
 * Overview page: quick access to every paid content type.
 */
function a7v_paid_content_overview() {
	if ( ! current_user_can( 'edit_posts' ) ) { return; }
	$types = a7v_post_types();
	echo '<div class="wrap"><h1 style="display:flex;align-items:center;gap:10px">🔒 محتویات پولی</h1>';
	echo '<p style="max-width:760px;color:#50575e">تمام محتواهایی که برای دسترسی به آن‌ها کاربر باید اشتراک ویژه بخرد، اینجا مدیریت می‌شوند. برای قفل‌کردن هر محتوا، هنگام ویرایش آن، باکس «🔒 تنظیمات عضویت مافیا» را فعال کنید. می‌توانید از «دسته‌بندی‌ها» دسته‌های دلخواه خود را اضافه کنید.</p>';

	echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px;margin-top:20px">';
	foreach ( $types as $slug => $d ) {
		$count    = wp_count_posts( $slug );
		$published= isset( $count->publish ) ? (int) $count->publish : 0;
		$list_url = admin_url( 'edit.php?post_type=' . $slug );
		$add_url  = admin_url( 'post-new.php?post_type=' . $slug );
		echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px">';
		echo '<div style="font-weight:700;font-size:15px;margin-bottom:6px">' . esc_html( $d[1] ) . '</div>';
		echo '<div style="color:#787c82;font-size:13px;margin-bottom:12px">' . esc_html( number_format_i18n( $published ) ) . ' مورد منتشرشده</div>';
		echo '<a class="button button-primary" href="' . esc_url( $add_url ) . '">افزودن</a> ';
		echo '<a class="button" href="' . esc_url( $list_url ) . '">مشاهده همه</a>';
		echo '</div>';
	}
	echo '</div>';

	echo '<p style="margin-top:22px"><a class="button" href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=a7v_category&post_type=a7v_course' ) ) . '">➕ مدیریت دسته‌بندی‌ها</a></p>';
	echo '</div>';
}
