<?php
/**
 * Theme Customizer — colors, hero, sections toggle.
 *
 * @package A7V
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function a7v_customize_register( $wp_customize ) {

        /* ---------- Panel ---------- */
        $wp_customize->add_panel( 'a7v_panel', array(
                'title'    => 'تنظیمات A7V',
                'priority' => 1,
        ) );

        /* ---------- Colors ---------- */
        $wp_customize->add_section( 'a7v_colors', array(
                'title' => 'رنگ‌ها',
                'panel' => 'a7v_panel',
        ) );

        $colors = array(
                'a7v_brand' => array( 'قرمز برند', '#e01e2b' ),
                'a7v_glow'  => array( 'قرمز درخشش (Glow)', '#ff3b3b' ),
                'a7v_gold'  => array( 'طلایی VIP', '#c9a24b' ),
                'a7v_bg'    => array( 'پس‌زمینه عمیق', '#070708' ),
        );
        foreach ( $colors as $id => $c ) {
                $wp_customize->add_setting( $id, array(
                        'default'           => $c[1],
                        'sanitize_callback' => 'sanitize_hex_color',
                        'transport'         => 'refresh',
                ) );
                $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, array(
                        'label'   => $c[0],
                        'section' => 'a7v_colors',
                ) ) );
        }

        /* ---------- Hero ---------- */
        $wp_customize->add_section( 'a7v_hero', array(
                'title' => 'بخش قهرمان (Hero)',
                'panel' => 'a7v_panel',
        ) );

        $hero_fields = array(
                'a7v_hero_title' => array( 'عنوان', 'آکادمی مافیا', 'text' ),
                'a7v_hero_tags'  => array( 'برچسب‌ها (با | جدا کن)', 'دانش | قدرت | ثروت', 'text' ),
                'a7v_hero_desc'  => array( 'توضیح', 'به آکادمی مافیا خوش آمدید. جایی که یادگیری تبدیل به قدرت می‌شود.', 'textarea' ),
                'a7v_hero_cta'   => array( 'متن دکمه', 'همین حالا عضو شو 👑', 'text' ),
                'a7v_hero_sub'   => array( 'زیرنویس دکمه', 'دسترسی کامل به تمامی محتواها با اشتراک ویژه', 'text' ),
        );
        foreach ( $hero_fields as $id => $f ) {
                $wp_customize->add_setting( $id, array(
                        'default'           => $f[1],
                        'sanitize_callback' => 'wp_kses_post',
                ) );
                $wp_customize->add_control( $id, array(
                        'label'   => $f[0],
                        'section' => 'a7v_hero',
                        'type'    => $f[2],
                ) );
        }

        $wp_customize->add_setting( 'a7v_hero_img', array( 'sanitize_callback' => 'esc_url_raw' ) );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'a7v_hero_img', array(
                'label'   => 'تصویر قهرمان',
                'section' => 'a7v_hero',
        ) ) );

        /* ---------- Homepage sections toggle ---------- */
        $wp_customize->add_section( 'a7v_sections', array(
                'title' => 'بخش‌های صفحه اصلی',
                'panel' => 'a7v_panel',
        ) );

        $sections = array(
                'a7v_show_cats'    => 'نوار دسته‌ها',
                'a7v_show_vip'     => 'بنر اشتراک VIP',
                'a7v_show_rows'    => 'ردیف‌های محتوا',
                'a7v_show_benefits'=> 'مزایای عضویت',
                'a7v_show_testi'   => 'نظرات کاربران',
                'a7v_show_faq'     => 'سوالات متداول',
                'a7v_show_notice'  => 'نوار اعلان بالا',
        );
        foreach ( $sections as $id => $label ) {
                $wp_customize->add_setting( $id, array(
                        'default'           => true,
                        'sanitize_callback' => 'a7v_sanitize_checkbox',
                ) );
                $wp_customize->add_control( $id, array(
                        'label'   => 'نمایش: ' . $label,
                        'section' => 'a7v_sections',
                        'type'    => 'checkbox',
                ) );
        }

        /* ---------- VIP banner ---------- */
        $wp_customize->add_section( 'a7v_vip', array(
                'title' => 'اشتراک ویژه',
                'panel' => 'a7v_panel',
        ) );
        $wp_customize->add_setting( 'a7v_vip_link', array( 'default' => '#', 'sanitize_callback' => 'esc_url_raw' ) );
        $wp_customize->add_control( 'a7v_vip_link', array( 'label' => 'لینک صفحه پلن‌ها', 'section' => 'a7v_vip', 'type' => 'url' ) );

        /* ---------- Dossier (product info box) ---------- */
        $wp_customize->add_section( 'a7v_dossier_display', array(
                'title' => 'پرونده اطلاعات (نمایش)',
                'panel' => 'a7v_panel',
        ) );
        $wp_customize->add_setting( 'a7v_dossier_mobile_cols', array(
                'default'           => 3,
                'sanitize_callback' => 'a7v_sanitize_dossier_cols',
        ) );
        $wp_customize->add_control( 'a7v_dossier_mobile_cols', array(
                'label'       => 'تعداد آیکون در هر ردیف (موبایل)',
                'description' => 'در گوشی، آیکون‌های باکس «پرونده اطلاعات» در هر ردیف چند تا باشند؟ (۱ تا ۴ — پیش‌فرض ۳)',
                'section'     => 'a7v_dossier_display',
                'type'        => 'select',
                'choices'     => array(
                        1 => '۱ آیکون در هر ردیف',
                        2 => '۲ آیکون در هر ردیف',
                        3 => '۳ آیکون در هر ردیف (پیش‌فرض)',
                        4 => '۴ آیکون در هر ردیف',
                ),
        ) );

        /* ---------- Marquee ---------- */
        $wp_customize->add_section( 'a7v_marquee', array(
                'title' => 'نوار متحرک (Marquee)',
                'panel' => 'a7v_panel',
        ) );

        $wp_customize->add_setting( 'a7v_marquee_loop', array(
                'default'           => true,
                'sanitize_callback' => 'a7v_sanitize_checkbox',
        ) );
        $wp_customize->add_control( 'a7v_marquee_loop', array(
                'label'       => 'حرکت بی‌نهایت (Loop)',
                'description' => 'آیتم‌ها پشت سر هم حرکت کنند',
                'section'     => 'a7v_marquee',
                'type'        => 'checkbox',
        ) );

        $wp_customize->add_setting( 'a7v_marquee_speed', array(
                'default'           => '50',
                'sanitize_callback' => 'a7v_sanitize_speed',
        ) );
        $wp_customize->add_control( 'a7v_marquee_speed', array(
                'label'       => 'سرعت حرکت (ثانیه)',
                'description' => 'مدت زمان یک چرخه کامل. عدد بزرگ‌تر = حرکت کندتر. (۵ تا ۲۰۰)',
                'section'     => 'a7v_marquee',
                'type'        => 'number',
                'input_attrs' => array( 'min' => 5, 'max' => 200, 'step' => 1 ),
        ) );

        $wp_customize->add_setting( 'a7v_marquee_direction', array(
                'default'           => 'left',
                'sanitize_callback' => 'a7v_sanitize_direction',
        ) );
        $wp_customize->add_control( 'a7v_marquee_direction', array(
                'label'       => 'جهت حرکت',
                'section'     => 'a7v_marquee',
                'type'        => 'select',
                'choices'     => array(
                        'left'  => 'حرکت به چپ (پیش‌فرض)',
                        'right' => 'حرکت به راست',
                ),
        ) );

        /* ---------- Footer ---------- */
        $wp_customize->add_section( 'a7v_footer', array(
                'title' => 'فوتر',
                'panel' => 'a7v_panel',
        ) );
        $footer_fields = array(
                'a7v_footer_about'   => array( 'متن درباره', 'آکادمی مافیا، بزرگ‌ترین مرجع آموزش و رشد فردی در زمینه‌های کسب‌وکار، روانشناسی، موفقیت و قدرت.' ),
                'a7v_copyright'      => array( 'متن کپی‌رایت', '© 2025 A7V Academy. All Rights Reserved.' ),
                'a7v_social_tg'      => array( 'لینک تلگرام', '#' ),
                'a7v_social_ig'      => array( 'لینک اینستاگرام', '#' ),
                'a7v_social_yt'      => array( 'لینک یوتیوب', '#' ),
        );
        foreach ( $footer_fields as $id => $f ) {
                $wp_customize->add_setting( $id, array( 'default' => $f[1], 'sanitize_callback' => 'wp_kses_post' ) );
                $wp_customize->add_control( $id, array( 'label' => $f[0], 'section' => 'a7v_footer', 'type' => 'text' ) );
        }
}
add_action( 'customize_register', 'a7v_customize_register' );

function a7v_sanitize_checkbox( $v ) {
        return ( isset( $v ) && true == $v ) ? true : false;
}

/** Clamp the dossier mobile columns setting to 1-4. */
function a7v_sanitize_dossier_cols( $v ) {
        $v = (int) $v;
        if ( $v < 1 ) { $v = 1; }
        if ( $v > 4 ) { $v = 4; }
        return $v;
}

/** Clamp marquee speed to 5–200 seconds. */
function a7v_sanitize_speed( $v ) {
        $v = (float) $v;
        if ( $v < 5 )  { $v = 5; }
        if ( $v > 200 ) { $v = 200; }
        return $v;
}

/** Sanitize marquee direction. */
function a7v_sanitize_direction( $v ) {
        return ( $v === 'right' ) ? 'right' : 'left';
}

/**
 * Inline CSS from customizer color settings.
 */
function a7v_dynamic_css() {
        $brand = get_theme_mod( 'a7v_brand', '#e01e2b' );
        $glow  = get_theme_mod( 'a7v_glow', '#ff3b3b' );
        $gold  = get_theme_mod( 'a7v_gold', '#c9a24b' );
        $bg    = get_theme_mod( 'a7v_bg', '#070708' );
        $dcols = (int) get_theme_mod( 'a7v_dossier_mobile_cols', 3 );
        if ( $dcols < 1 ) { $dcols = 1; }
        if ( $dcols > 4 ) { $dcols = 4; }
        return ":root{--blood:{$brand};--blood-glow:{$glow};--gold:{$gold};--bg-abyss:{$bg};--dossier-mcols:{$dcols};}";
}
