<?php
/**
 * A7V Dossier — per content-type product info card ("پرونده اطلاعات").
 *
 * Workflow:
 *   1. Admin defines the FIELDS for each content type (course, book, mafia article…)
 *      once, from:  پیشخوان → صفحه‌ساز A7V → پرونده اطلاعات.
 *      Each field = an icon + a label (e.g. 👤 مدرس, ⏱ مدت آموزش).
 *   2. On each post's edit screen, the admin just fills the VALUES for that
 *      type's fields.
 *   3. The single template renders a luxury "پرونده" card (black / gold / red)
 *      BEFORE the content — matching the classified-dossier design.
 *
 * Field templates are stored in the option `a7v_dossier_settings`.
 * Per-post values are stored in post meta `_a7v_dossier_values`.
 *
 * @package A7V
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* =============================================================
 * 1. Icon set (gold line icons, shown inside the circular badges)
 * ============================================================= */

function a7v_dossier_icons() {
	$a = 'fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"';
	return array(
		'person'   => '<svg viewBox="0 0 24 24" ' . $a . '><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>',
		'clock'    => '<svg viewBox="0 0 24 24" ' . $a . '><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
		'cube'     => '<svg viewBox="0 0 24 24" ' . $a . '><path d="M12 2 3 7v10l9 5 9-5V7z"/><path d="M3 7l9 5 9-5M12 12v10"/></svg>',
		'video'    => '<svg viewBox="0 0 24 24" ' . $a . '><rect x="3" y="6" width="14" height="12" rx="2"/><path d="M17 10l4-2v8l-4-2z"/></svg>',
		'star'     => '<svg viewBox="0 0 24 24" ' . $a . '><path d="M12 3l2.7 5.5 6 .9-4.3 4.2 1 6-5.4-2.8-5.4 2.8 1-6L3.3 9.4l6-.9z"/></svg>',
		'file'     => '<svg viewBox="0 0 24 24" ' . $a . '><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg>',
		'tag'      => '<svg viewBox="0 0 24 24" ' . $a . '><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0l-6.2-6.2A2 2 0 0 1 3.8 12V5a1 1 0 0 1 1-1h7a2 2 0 0 1 1.4.6l6.4 6.4a2 2 0 0 1 0 2.8z"/><circle cx="8" cy="8" r="1.3" fill="currentColor" stroke="none"/></svg>',
		'book'     => '<svg viewBox="0 0 24 24" ' . $a . '><path d="M12 6c-2-1.3-5-1.8-8-1.4v13c3-.4 6 .1 8 1.4 2-1.3 5-1.8 8-1.4v-13C17 4.2 14 4.7 12 6z"/><path d="M12 6v13"/></svg>',
		'users'    => '<svg viewBox="0 0 24 24" ' . $a . '><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.6 3-6 7-6s7 2.4 7 6"/><path d="M16 5.5a3.5 3.5 0 0 1 0 6.5M22 20c0-3-1.6-5-4-5.6"/></svg>',
		'target'   => '<svg viewBox="0 0 24 24" ' . $a . '><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/></svg>',
		'check'    => '<svg viewBox="0 0 24 24" ' . $a . '><path d="M20 6 9 17l-5-5"/></svg>',
		'shield'   => '<svg viewBox="0 0 24 24" ' . $a . '><path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg>',
		'globe'    => '<svg viewBox="0 0 24 24" ' . $a . '><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 2.5 15 0 18M12 3c-2.5 2.5-2.5 15 0 18"/></svg>',
		'calendar' => '<svg viewBox="0 0 24 24" ' . $a . '><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/></svg>',
		'download' => '<svg viewBox="0 0 24 24" ' . $a . '><path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>',
		'bars'     => '<svg viewBox="0 0 24 24" ' . $a . '><path d="M5 20v-6M12 20V5M19 20v-9M3 20h18"/></svg>',
		'gift'     => '<svg viewBox="0 0 24 24" ' . $a . '><rect x="3" y="8" width="18" height="13" rx="1"/><path d="M3 12h18M12 8v13M12 8S9.5 3 7 4.2 12 8 12 8zm0 0s2.5-4.8 5-3.8S12 8 12 8z"/></svg>',
		'crown'    => '<svg viewBox="0 0 24 24" ' . $a . '><path d="M4 8l3.5 4L12 5l4.5 7L20 8l-2 11H6z"/></svg>',
		'play'     => '<svg viewBox="0 0 24 24" ' . $a . '><circle cx="12" cy="12" r="9"/><path d="M10 8l6 4-6 4z" fill="currentColor" stroke="none"/></svg>',
		'list'     => '<svg viewBox="0 0 24 24" ' . $a . '><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>',
		'medal'    => '<svg viewBox="0 0 24 24" ' . $a . '><circle cx="12" cy="15" r="5"/><path d="M8.5 10 6 3h12l-2.5 7"/></svg>',
		'wallet'   => '<svg viewBox="0 0 24 24" ' . $a . '><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M16 14h2"/></svg>',
		'lock'     => '<svg viewBox="0 0 24 24" ' . $a . '><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>',
	);
}

/** Human labels for icons (used in the admin picker). */
function a7v_dossier_icon_labels() {
	return array(
		'person' => 'مدرس / شخص', 'clock' => 'زمان', 'cube' => 'حجم', 'video' => 'ویدیو / جلسه',
		'star' => 'ستاره / سطح', 'file' => 'فایل / فرمت', 'tag' => 'قیمت / برچسب', 'book' => 'کتاب',
		'users' => 'کاربران', 'target' => 'هدف / مخاطب', 'check' => 'تیک', 'shield' => 'گارانتی',
		'globe' => 'زبان', 'calendar' => 'تاریخ / بروزرسانی', 'download' => 'دانلود', 'bars' => 'سطح',
		'gift' => 'هدیه', 'crown' => 'ویژه / تاج', 'play' => 'پخش', 'list' => 'لیست',
		'medal' => 'دستاورد', 'wallet' => 'کیف پول', 'lock' => 'دسترسی',
	);
}

function a7v_dossier_icon_svg( $key ) {
	$icons = a7v_dossier_icons();
	return isset( $icons[ $key ] ) ? $icons[ $key ] : $icons['check'];
}

/* =============================================================
 * 2. Default field templates per content type
 * ============================================================= */

function a7v_dossier_default_settings() {
	$course = array(
		array( 'key' => 'instructor', 'icon' => 'person', 'label' => 'مدرس' ),
		array( 'key' => 'duration',   'icon' => 'clock',  'label' => 'مدت آموزش' ),
		array( 'key' => 'filesize',   'icon' => 'cube',   'label' => 'حجم فایل' ),
		array( 'key' => 'sessions',   'icon' => 'video',  'label' => 'تعداد جلسات' ),
		array( 'key' => 'level',      'icon' => 'star',   'label' => 'سطح دوره' ),
		array( 'key' => 'format',     'icon' => 'file',   'label' => 'نوع فایل' ),
		array( 'key' => 'price',      'icon' => 'tag',    'label' => 'قیمت' ),
	);
	$book = array(
		array( 'key' => 'author',   'icon' => 'person', 'label' => 'نویسنده' ),
		array( 'key' => 'pages',    'icon' => 'file',   'label' => 'تعداد صفحات' ),
		array( 'key' => 'readtime', 'icon' => 'clock',  'label' => 'زمان مطالعه' ),
		array( 'key' => 'level',    'icon' => 'star',   'label' => 'سطح' ),
		array( 'key' => 'price',    'icon' => 'tag',    'label' => 'قیمت' ),
	);
	$article = array(
		array( 'key' => 'author',   'icon' => 'person', 'label' => 'نویسنده' ),
		array( 'key' => 'readtime', 'icon' => 'clock',  'label' => 'زمان مطالعه' ),
		array( 'key' => 'level',    'icon' => 'star',   'label' => 'سطح' ),
		array( 'key' => 'access',   'icon' => 'lock',   'label' => 'نوع دسترسی' ),
	);

	$titles = array(
		'a7v_course'  => 'اطلاعات پرونده آموزش',
		'a7v_book'    => 'اطلاعات پرونده کتاب',
		'a7v_article' => 'اطلاعات پرونده مقاله',
		'a7v_mafia'   => 'اطلاعات پرونده مافیایی',
		'a7v_prompt'  => 'اطلاعات پرونده پرامپت',
		'a7v_tool'    => 'اطلاعات پرونده ابزار',
		'a7v_income'  => 'اطلاعات پرونده کسب درآمد',
	);
	$field_map = array(
		'a7v_course'  => $course,
		'a7v_book'    => $book,
		'a7v_article' => $article,
		'a7v_mafia'   => $article,
		'a7v_prompt'  => $article,
		'a7v_tool'    => $article,
		'a7v_income'  => $article,
	);

	$out = array();
	foreach ( array_keys( a7v_post_types() ) as $pt ) {
		$out[ $pt ] = array(
			'enabled' => true,
			'title'   => isset( $titles[ $pt ] ) ? $titles[ $pt ] : 'اطلاعات پرونده',
			'fields'  => isset( $field_map[ $pt ] ) ? $field_map[ $pt ] : $article,
		);
	}
	return $out;
}

/** Get merged settings (stored ∪ defaults for any missing type). */
function a7v_get_dossier_settings() {
	$stored = get_option( 'a7v_dossier_settings', array() );
	$def    = a7v_dossier_default_settings();
	if ( ! is_array( $stored ) ) { $stored = array(); }
	$out = array();
	foreach ( array_keys( a7v_post_types() ) as $pt ) {
		if ( isset( $stored[ $pt ] ) && is_array( $stored[ $pt ] ) ) {
			$out[ $pt ] = wp_parse_args( $stored[ $pt ], array( 'enabled' => true, 'title' => 'اطلاعات پرونده', 'fields' => array() ) );
		} else {
			$out[ $pt ] = $def[ $pt ];
		}
	}
	return $out;
}

function a7v_get_type_fields( $post_type ) {
	$settings = a7v_get_dossier_settings();
	return isset( $settings[ $post_type ]['fields'] ) ? $settings[ $post_type ]['fields'] : array();
}

/* =============================================================
 * 3. Sanitizers
 * ============================================================= */

function a7v_sanitize_dossier_settings( $raw ) {
	$icons = a7v_dossier_icons();
	$out   = array();
	if ( ! is_array( $raw ) ) { return $out; }
	foreach ( array_keys( a7v_post_types() ) as $pt ) {
		if ( ! isset( $raw[ $pt ] ) || ! is_array( $raw[ $pt ] ) ) { continue; }
		$conf   = $raw[ $pt ];
		$fields = array();
		if ( isset( $conf['fields'] ) && is_array( $conf['fields'] ) ) {
			foreach ( $conf['fields'] as $f ) {
				if ( ! is_array( $f ) ) { continue; }
				$key = isset( $f['key'] ) && $f['key'] !== '' ? sanitize_key( $f['key'] ) : ( 'f' . wp_rand( 1000, 999999 ) );
				$fields[] = array(
					'key'   => $key,
					'icon'  => ( isset( $f['icon'] ) && isset( $icons[ $f['icon'] ] ) ) ? $f['icon'] : 'check',
					'label' => sanitize_text_field( isset( $f['label'] ) ? $f['label'] : '' ),
				);
			}
		}
		$out[ $pt ] = array(
			'enabled' => ! empty( $conf['enabled'] ),
			'title'   => sanitize_text_field( isset( $conf['title'] ) ? $conf['title'] : 'اطلاعات پرونده' ),
			'fields'  => $fields,
		);
	}
	return $out;
}

/* =============================================================
 * 4. Settings page (define fields per content type)
 * ============================================================= */

function a7v_dossier_menu() {
	$hook = add_submenu_page(
		'a7v-home-builder',
		'پرونده اطلاعات محتوا',
		'پرونده اطلاعات',
		'edit_theme_options',
		'a7v-dossier',
		'a7v_dossier_settings_page'
	);
	if ( $hook ) {
		add_action( 'load-' . $hook, function () {
			add_action( 'admin_enqueue_scripts', 'a7v_dossier_settings_assets' );
		} );
	}
}
add_action( 'admin_menu', 'a7v_dossier_menu', 20 );

function a7v_dossier_settings_assets() {
	$css = A7V_DIR . '/assets/css/product-builder.css';
	$js  = A7V_DIR . '/assets/js/product-builder.js';
	wp_enqueue_style( 'a7v-ds', A7V_URI . '/assets/css/product-builder.css', array(), file_exists( $css ) ? filemtime( $css ) : A7V_VER );
	wp_enqueue_script( 'a7v-ds', A7V_URI . '/assets/js/product-builder.js', array(), file_exists( $js ) ? filemtime( $js ) : A7V_VER, true );

	$pt_labels = array();
	foreach ( a7v_post_types() as $slug => $d ) { $pt_labels[ $slug ] = $d[1]; }

	$icons = array();
	$labels = a7v_dossier_icon_labels();
	foreach ( a7v_dossier_icons() as $k => $svg ) {
		$icons[ $k ] = array( 'label' => isset( $labels[ $k ] ) ? $labels[ $k ] : $k, 'svg' => $svg );
	}

	wp_localize_script( 'a7v-ds', 'A7VDossier', array(
		'mode'      => 'settings',
		'ajax'      => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'a7v_dossier' ),
		'settings'  => a7v_get_dossier_settings(),
		'postTypes' => $pt_labels,
		'icons'     => $icons,
		'i18n'      => array( 'saved' => 'ذخیره شد ✅', 'saving' => 'در حال ذخیره…' ),
	) );
}

function a7v_dossier_settings_page() {
	?>
	<div class="wrap a7v-ds-wrap">
		<h1>🏛️ پرونده اطلاعات محتوا</h1>
		<p class="a7v-ds-desc">برای هر نوع محتوای پولی، فیلدهای پرونده را تعریف کن (آیکون + عنوان). سپس در صفحه‌ی ویرایش هر محتوا فقط مقدار همین فیلدها را پر می‌کنی و کارت لوکس «پرونده» به‌صورت خودکار بالای صفحه نمایش داده می‌شود.</p>

		<div class="a7v-ds-app" id="a7vDossierSettings">
			<div class="a7v-ds-tabs" id="a7vDsTabs"></div>
			<div class="a7v-ds-editor" id="a7vDsEditor"></div>
			<div class="a7v-ds-bar">
				<button type="button" class="button button-primary" id="a7vDsSave">ذخیره تنظیمات</button>
				<span id="a7vDsStatus" class="a7v-ds-status"></span>
			</div>
		</div>
	</div>
	<?php
}

function a7v_ajax_save_dossier() {
	check_ajax_referer( 'a7v_dossier', 'nonce' );
	if ( ! current_user_can( 'edit_theme_options' ) ) { wp_send_json_error( 'forbidden', 403 ); }
	$raw   = isset( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : array(); // phpcs:ignore
	$clean = a7v_sanitize_dossier_settings( $raw );
	update_option( 'a7v_dossier_settings', $clean );
	wp_send_json_success();
}
add_action( 'wp_ajax_a7v_save_dossier', 'a7v_ajax_save_dossier' );

/* =============================================================
 * 5. Per-post metabox — just the VALUES
 * ============================================================= */

function a7v_dossier_metabox() {
	$screens = array_keys( a7v_post_types() );
	add_meta_box( 'a7v_dossier', '🏛️ پرونده اطلاعات (مقادیر این محتوا)', 'a7v_dossier_metabox_html', $screens, 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'a7v_dossier_metabox' );

function a7v_dossier_metabox_html( $post ) {
	wp_nonce_field( 'a7v_dossier_values_save', 'a7v_dossier_values_nonce' );
	$fields = a7v_get_type_fields( $post->post_type );
	$values = get_post_meta( $post->ID, '_a7v_dossier_values', true );
	if ( ! is_array( $values ) ) { $values = array(); }
	$fileno = get_post_meta( $post->ID, '_a7v_dossier_fileno', true );

	if ( empty( $fields ) ) {
		echo '<div class="a7v-dv-empty">برای این نوع محتوا هنوز فیلدی تعریف نشده. ابتدا از <a href="' . esc_url( admin_url( 'admin.php?page=a7v-dossier' ) ) . '">صفحه‌ساز A7V → پرونده اطلاعات</a> فیلدها را تعریف کن.</div>';
		return;
	}
	echo '<div class="a7v-dv">';
	echo '<p class="a7v-dv-hint">مقدار هر فیلد را وارد کن. فیلدهای خالی در کارت نمایش داده نمی‌شوند. فیلدها را از صفحه‌ی «پرونده اطلاعات» می‌توانی تغییر دهی.</p>';
	echo '<div class="a7v-dv-grid">';
	foreach ( $fields as $f ) {
		$key = $f['key'];
		$val = isset( $values[ $key ] ) ? $values[ $key ] : '';
		printf(
			'<div class="a7v-dv-field"><label><span class="a7v-dv-ic">%s</span> %s</label><input type="text" name="a7v_dossier_values[%s]" value="%s"></div>',
			a7v_dossier_icon_svg( $f['icon'] ), // phpcs:ignore
			esc_html( $f['label'] ),
			esc_attr( $key ),
			esc_attr( $val )
		);
	}
	echo '</div>';
	printf(
		'<div class="a7v-dv-field a7v-dv-fileno"><label>شماره پرونده (اختیاری — پیش‌فرض: کد نوشته)</label><input type="text" name="a7v_dossier_fileno" value="%s" placeholder="%s"></div>',
		esc_attr( $fileno ),
		esc_attr( $post->ID )
	);
	echo '</div>';
}

function a7v_dossier_values_save( $post_id ) {
	if ( ! isset( $_POST['a7v_dossier_values_nonce'] ) || ! wp_verify_nonce( $_POST['a7v_dossier_values_nonce'], 'a7v_dossier_values_save' ) ) { return; }
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

	$clean = array();
	if ( isset( $_POST['a7v_dossier_values'] ) && is_array( $_POST['a7v_dossier_values'] ) ) {
		foreach ( wp_unslash( $_POST['a7v_dossier_values'] ) as $k => $v ) { // phpcs:ignore
			$clean[ sanitize_key( $k ) ] = sanitize_text_field( $v );
		}
	}
	update_post_meta( $post_id, '_a7v_dossier_values', $clean );

	if ( isset( $_POST['a7v_dossier_fileno'] ) ) {
		update_post_meta( $post_id, '_a7v_dossier_fileno', sanitize_text_field( wp_unslash( $_POST['a7v_dossier_fileno'] ) ) );
	}
}
add_action( 'save_post', 'a7v_dossier_values_save' );

/* Enqueue metabox styles on A7V edit screens. */
function a7v_dossier_edit_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) { return; }
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array_keys( a7v_post_types() ), true ) ) { return; }
	$css = A7V_DIR . '/assets/css/product-builder.css';
	wp_enqueue_style( 'a7v-ds', A7V_URI . '/assets/css/product-builder.css', array(), file_exists( $css ) ? filemtime( $css ) : A7V_VER );
}
add_action( 'admin_enqueue_scripts', 'a7v_dossier_edit_assets' );

/* =============================================================
 * 6. Front-end render — the luxury dossier card
 * ============================================================= */

/** Convert Latin digits to Persian for the file number. */
function a7v_fa_digits( $str ) {
	$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
	return str_replace( $en, $fa, (string) $str );
}

/**
 * Public entry point (called from single.php).
 */
function a7v_render_product_info( $post_id ) {
	a7v_render_dossier( $post_id );
}

function a7v_render_dossier( $post_id ) {
	$type     = get_post_type( $post_id );
	$settings = a7v_get_dossier_settings();
	if ( ! isset( $settings[ $type ] ) || empty( $settings[ $type ]['enabled'] ) ) { return; }

	$conf   = $settings[ $type ];
	$fields = isset( $conf['fields'] ) ? $conf['fields'] : array();
	$values = get_post_meta( $post_id, '_a7v_dossier_values', true );
	if ( ! is_array( $values ) ) { $values = array(); }

	// Only fields with a non-empty value are shown.
	$cards = array();
	foreach ( $fields as $f ) {
		$val = isset( $values[ $f['key'] ] ) ? trim( $values[ $f['key'] ] ) : '';
		if ( '' === $val ) { continue; }
		$cards[] = array( 'icon' => $f['icon'], 'label' => $f['label'], 'value' => $val );
	}

	$fileno = get_post_meta( $post_id, '_a7v_dossier_fileno', true );
	if ( '' === $fileno ) { $fileno = $post_id; }
	$title = isset( $conf['title'] ) && '' !== $conf['title'] ? $conf['title'] : 'اطلاعات پرونده';
	?>
	<section class="dossier">
		<div class="dossier-frame">
			<span class="dossier-glow" aria-hidden="true"></span>
			<span class="dossier-grain" aria-hidden="true"></span>
			<span class="dossier-seal" aria-hidden="true">
				<svg viewBox="0 0 100 100" fill="none">
					<circle cx="50" cy="50" r="47" stroke="currentColor" stroke-width="1"/>
					<circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width=".6" stroke-dasharray="2 3"/>
					<path d="M34 40l7 7 9-12 9 12 7-7-4 26H38z" stroke="currentColor" stroke-width="1" stroke-linejoin="round"/>
					<text x="50" y="60" text-anchor="middle" font-size="22" font-weight="700" fill="currentColor" font-family="Georgia,serif">M</text>
				</svg>
			</span>

			<div class="dossier-head">
				<div class="dossier-title">
					<span class="dossier-emblem" aria-hidden="true">
						<svg viewBox="0 0 40 44" fill="none">
							<path d="M20 2 36 8v14c0 10-7 16.5-16 20C11 38.5 4 32 4 22V8z" stroke="var(--gold,#c9a24b)" stroke-width="1.6"/>
							<path d="M12 17l3 3 5-6 5 6 3-3-1.6 11H13.6z" stroke="var(--gold,#c9a24b)" stroke-width="1.4" stroke-linejoin="round"/>
							<text x="20" y="30" text-anchor="middle" font-size="10" font-weight="700" fill="var(--gold,#c9a24b)" font-family="Georgia,serif">M</text>
						</svg>
					</span>
					<h3><?php echo esc_html( $title ); ?></h3>
				</div>
				<div class="dossier-file">پرونده شماره <?php echo esc_html( a7v_fa_digits( $fileno ) ); ?></div>
			</div>

			<div class="dossier-divider"><span></span><i>◆</i><span></span></div>

			<?php if ( ! empty( $cards ) ) : ?>
			<div class="dossier-grid">
				<?php foreach ( $cards as $c ) : ?>
					<div class="dossier-card">
						<span class="dossier-ic"><?php echo a7v_dossier_icon_svg( $c['icon'] ); // phpcs:ignore ?></span>
						<span class="dossier-label"><?php echo esc_html( $c['label'] ); ?>:</span>
						<span class="dossier-value"><?php echo esc_html( $c['value'] ); ?></span>
						<span class="dossier-uline" aria-hidden="true"></span>
					</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<div class="dossier-foot"><i>◆</i><span>PRIVATE ACADEMY</span><i>◆</i></div>
		</div>
	</section>
	<?php
}
