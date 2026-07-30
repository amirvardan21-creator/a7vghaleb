<?php
/**
 * A7V Home Builder — a lightweight Elementor-style visual builder for the homepage.
 *
 * Stores the homepage as an ordered list of blocks (JSON in the `a7v_home_layout`
 * option) and renders front-page.php from that layout. Ships an admin page with a
 * live preview iframe, drag-to-reorder sections, per-block field editing and AJAX save.
 *
 * @package A7V
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* =============================================================
 * 1. Block registry (schema shared with the JS builder)
 * ============================================================= */

/**
 * Icon keys available for icon <select> fields (from a7v_icon()).
 */
function a7v_builder_icon_keys() {
        return array( 'income', 'produce', 'prompt', 'article', 'mafia', 'book', 'all', 'course', 'tool', 'crown', 'badge', 'download', 'bell', 'settings', 'announce', 'saved' );
}

/**
 * The full block registry. Each entry defines the label, icon and editable field
 * schema. Field types: text, textarea, url, image, color, toggle, select, richtext,
 * repeater (with `fields` sub-schema). This same structure is localized to JS so the
 * builder can render forms automatically.
 */
function a7v_block_registry() {
        $icon_options = array();
        foreach ( a7v_builder_icon_keys() as $k ) {
                $icon_options[ $k ] = $k;
        }

        return array(

                'hero' => array(
                        'label'  => 'بخش قهرمان (Hero)',
                        'dashicon' => 'dashicons-cover-image',
                        'section'  => true,
                        'fields' => array(
                                array( 'key' => 'image',     'label' => 'تصویر پس‌زمینه', 'type' => 'image' ),
                                array( 'key' => 'title',     'label' => 'عنوان',          'type' => 'text' ),
                                array( 'key' => 'accent',    'label' => 'کلمه‌ی برجسته (قرمز)', 'type' => 'text' ),
                                array( 'key' => 'tags',      'label' => 'برچسب‌ها',       'type' => 'repeater', 'fields' => array(
                                        array( 'key' => 'text', 'label' => 'برچسب', 'type' => 'text' ),
                                ) ),
                                array( 'key' => 'desc',      'label' => 'توضیح',          'type' => 'textarea' ),
                                array( 'key' => 'cta_text',  'label' => 'متن دکمه',        'type' => 'text' ),
                                array( 'key' => 'cta_link',  'label' => 'لینک دکمه',       'type' => 'url' ),
                                array( 'key' => 'sub',       'label' => 'زیرنویس دکمه',    'type' => 'text' ),
                        ),
                ),

                'marquee' => array(
                        'label'  => 'نوار متحرک (Marquee)',
                        'dashicon' => 'dashicons-controls-repeat',
                        'section'  => true,
                        'fields' => array(
                                array( 'key' => 'marquee_items',  'label' => 'آیتم‌های نوار متحرک',     'type' => 'repeater', 'fields' => array(
                                        array( 'key' => 'icon',       'label' => 'آیکون',     'type' => 'select', 'options' => $icon_options ),
                                        array( 'key' => 'text',       'label' => 'متن',       'type' => 'text' ),
                                        array( 'key' => 'icon_color', 'label' => 'رنگ آیکون', 'type' => 'color' ),
                                        array( 'key' => 'text_color', 'label' => 'رنگ متن',   'type' => 'color' ),
                                        array( 'key' => 'text_size',  'label' => 'اندازه متن (px)',  'type' => 'text' ),
                                        array( 'key' => 'icon_size',  'label' => 'اندازه آیکون (px)','type' => 'text' ),
                                ) ),
                                array( 'key' => 'mq_speed',        'label' => 'سرعت حرکت (ثانیه — بزرگ‌تر = کندتر)', 'type' => 'text' ),
                                array( 'key' => 'mq_direction',    'label' => 'جهت حرکت',            'type' => 'select', 'options' => array( 'left' => 'چپ به راست (پیش‌فرض)', 'right' => 'راست به چپ' ) ),
                                array( 'key' => 'mq_bg_color',     'label' => 'رنگ پس‌زمینه آیتم‌ها',      'type' => 'color' ),
                                array( 'key' => 'mq_bg_opacity',   'label' => 'شفافیت پس‌زمینه (۰–۱۰۰)',    'type' => 'text' ),
                                array( 'key' => 'mq_border_color', 'label' => 'رنگ حاشیه',                 'type' => 'color' ),
                                array( 'key' => 'mq_glow',         'label' => 'شدت Glow قرمز (۰–۱۰۰)',    'type' => 'text' ),
                                array( 'key' => 'mq_icon_color',   'label' => 'رنگ آیکون (پیش‌فرض همه)',  'type' => 'color' ),
                                array( 'key' => 'mq_text_color',   'label' => 'رنگ متن (پیش‌فرض همه)',    'type' => 'color' ),
                                array( 'key' => 'mq_text_size',    'label' => 'اندازه متن (px)',            'type' => 'text' ),
                                array( 'key' => 'mq_icon_size',    'label' => 'اندازه آیکون (px)',          'type' => 'text' ),
                                array( 'key' => 'mq_gap',          'label' => 'فاصله بین آیتم‌ها (px)',     'type' => 'text' ),
                                array( 'key' => 'mq_padding',      'label' => 'فاصله داخلی آیتم (px)',     'type' => 'text' ),
                                array( 'key' => 'mq_radius',       'label' => 'گردی گوشه (px)',            'type' => 'text' ),
                                array( 'key' => 'mq_fade',         'label' => 'Fade ابتدا و انتها',        'type' => 'toggle' ),
                                array( 'key' => 'mq_loop',         'label' => 'حرکت بینهایت (Loop)',       'type' => 'toggle' ),
                        ),
                ),

                'cats' => array(
                        'label'  => 'نوار دسته‌ها',
                        'dashicon' => 'dashicons-grid-view',
                        'section'  => true,
                        'fields' => array(
                                array( 'key' => 'items', 'label' => 'دسته‌ها', 'type' => 'repeater', 'fields' => array(
                                        array( 'key' => 'icon',  'label' => 'آیکون', 'type' => 'select', 'options' => $icon_options ),
                                        array( 'key' => 'label', 'label' => 'عنوان', 'type' => 'text' ),
                                        array( 'key' => 'link',  'label' => 'لینک',  'type' => 'url' ),
                                ) ),
                        ),
                ),

                'vip' => array(
                        'label'  => 'بنر اشتراک VIP',
                        'dashicon' => 'dashicons-star-filled',
                        'section'  => true,
                        'fields' => array(
                                array( 'key' => 'badge_title', 'label' => 'عنوان نشان',  'type' => 'text' ),
                                array( 'key' => 'badge_sub',   'label' => 'زیرنویس نشان', 'type' => 'text' ),
                                array( 'key' => 'badge_year',  'label' => 'مدت',          'type' => 'text' ),
                                array( 'key' => 'heading',     'label' => 'عنوان اصلی',    'type' => 'text' ),
                                array( 'key' => 'items', 'label' => 'ویژگی‌ها', 'type' => 'repeater', 'fields' => array(
                                        array( 'key' => 'text', 'label' => 'ویژگی', 'type' => 'text' ),
                                ) ),
                                array( 'key' => 'btn_text', 'label' => 'متن دکمه', 'type' => 'text' ),
                                array( 'key' => 'btn_link', 'label' => 'لینک دکمه', 'type' => 'url' ),
                        ),
                ),

                'rows' => array(
                        'label'  => 'ردیف‌های محتوا (پویا از نوشته‌ها)',
                        'dashicon' => 'dashicons-images-alt2',
                        'section'  => true,
                        'fields' => array(), // Dynamic content pulled from CPTs; visibility only.
                ),

                'benefits' => array(
                        'label'  => 'مزایای عضویت',
                        'dashicon' => 'dashicons-awards',
                        'section'  => true,
                        'fields' => array(
                                array( 'key' => 'items', 'label' => 'مزایا', 'type' => 'repeater', 'fields' => array(
                                        array( 'key' => 'icon',  'label' => 'آیکون', 'type' => 'select', 'options' => $icon_options ),
                                        array( 'key' => 'title', 'label' => 'عنوان', 'type' => 'text' ),
                                        array( 'key' => 'desc',  'label' => 'توضیح', 'type' => 'textarea' ),
                                ) ),
                        ),
                ),

                'testimonials' => array(
                        'label'  => 'نظرات کاربران',
                        'dashicon' => 'dashicons-format-quote',
                        'section'  => true,
                        'fields' => array(
                                array( 'key' => 'heading', 'label' => 'عنوان',    'type' => 'text' ),
                                array( 'key' => 'sub',     'label' => 'زیرعنوان', 'type' => 'text' ),
                                array( 'key' => 'items', 'label' => 'نظرات', 'type' => 'repeater', 'fields' => array(
                                        array( 'key' => 'name', 'label' => 'نام',   'type' => 'text' ),
                                        array( 'key' => 'role', 'label' => 'نقش',   'type' => 'text' ),
                                        array( 'key' => 'text', 'label' => 'متن نظر', 'type' => 'textarea' ),
                                ) ),
                        ),
                ),

                'faq' => array(
                        'label'  => 'سوالات متداول',
                        'dashicon' => 'dashicons-editor-help',
                        'section'  => true,
                        'fields' => array(
                                array( 'key' => 'heading', 'label' => 'عنوان', 'type' => 'text' ),
                                array( 'key' => 'items', 'label' => 'سوالات', 'type' => 'repeater', 'fields' => array(
                                        array( 'key' => 'q', 'label' => 'سوال', 'type' => 'text' ),
                                        array( 'key' => 'a', 'label' => 'پاسخ', 'type' => 'textarea' ),
                                ) ),
                        ),
                ),

                /* ---- Generic building blocks (Elementor-style widgets) ---- */

                'heading' => array(
                        'label'  => 'عنوان',
                        'dashicon' => 'dashicons-heading',
                        'section'  => false,
                        'fields' => array(
                                array( 'key' => 'text',  'label' => 'متن',   'type' => 'text' ),
                                array( 'key' => 'align', 'label' => 'چیدمان', 'type' => 'select', 'options' => array( 'right' => 'راست', 'center' => 'وسط', 'left' => 'چپ' ) ),
                                array( 'key' => 'size',  'label' => 'اندازه (px)', 'type' => 'text' ),
                        ),
                ),

                'text' => array(
                        'label'  => 'متن',
                        'dashicon' => 'dashicons-editor-paragraph',
                        'section'  => false,
                        'fields' => array(
                                array( 'key' => 'content', 'label' => 'محتوا', 'type' => 'textarea' ),
                                array( 'key' => 'align',   'label' => 'چیدمان', 'type' => 'select', 'options' => array( 'right' => 'راست', 'center' => 'وسط', 'left' => 'چپ' ) ),
                        ),
                ),

                'image' => array(
                        'label'  => 'تصویر',
                        'dashicon' => 'dashicons-format-image',
                        'section'  => false,
                        'fields' => array(
                                array( 'key' => 'image', 'label' => 'تصویر', 'type' => 'image' ),
                                array( 'key' => 'link',  'label' => 'لینک (اختیاری)', 'type' => 'url' ),
                                array( 'key' => 'align', 'label' => 'چیدمان', 'type' => 'select', 'options' => array( 'right' => 'راست', 'center' => 'وسط', 'left' => 'چپ' ) ),
                        ),
                ),

                'button' => array(
                        'label'  => 'دکمه',
                        'dashicon' => 'dashicons-button',
                        'section'  => false,
                        'fields' => array(
                                array( 'key' => 'text',  'label' => 'متن',   'type' => 'text' ),
                                array( 'key' => 'link',  'label' => 'لینک',  'type' => 'url' ),
                                array( 'key' => 'style', 'label' => 'سبک', 'type' => 'select', 'options' => array( 'primary' => 'قرمز پر', 'outline' => 'خطی' ) ),
                                array( 'key' => 'align', 'label' => 'چیدمان', 'type' => 'select', 'options' => array( 'right' => 'راست', 'center' => 'وسط', 'left' => 'چپ' ) ),
                        ),
                ),

                'spacer' => array(
                        'label'  => 'فاصله',
                        'dashicon' => 'dashicons-image-flip-vertical',
                        'section'  => false,
                        'fields' => array(
                                array( 'key' => 'height', 'label' => 'ارتفاع (px)', 'type' => 'text' ),
                        ),
                ),

                'html' => array(
                        'label'  => 'کد HTML',
                        'dashicon' => 'dashicons-editor-code',
                        'section'  => false,
                        'fields' => array(
                                array( 'key' => 'code', 'label' => 'کد HTML', 'type' => 'textarea' ),
                        ),
                ),
        );
}

/* =============================================================
 * 2. Default layout (seed matching the original homepage)
 * ============================================================= */

function a7v_default_home_layout() {
        $icons = a7v_builder_icon_keys();

        return array(
                array(
                        'id'      => 'hero-1',
                        'type'    => 'hero',
                        'visible' => true,
                        'settings' => array(
                                'image'    => '', // Falls back to bundled hero.jpeg at render time.
                                'title'    => 'آکادمی',
                                'accent'   => 'مافیا',
                                'tags'     => array( array( 'text' => 'دانش' ), array( 'text' => 'قدرت' ), array( 'text' => 'ثروت' ) ),
                                'desc'     => 'به آکادمی مافیا خوش آمدید. جایی که یادگیری تبدیل به قدرت می‌شود. از دوره‌های آموزشی حرفه‌ای تا مقالات استراتژیک و ابزارهای مدرن برای ساختن نسخه‌ی قدرتمندتر از خودت.',
                                'cta_text' => 'همین حالا عضو شو 👑',
                                'cta_link' => '#',
                                'sub'      => 'دسترسی کامل به تمامی محتواها با اشتراک ویژه',
                        ),
                ),
                array(
                        'id'      => 'marquee-1',
                        'type'    => 'marquee',
                        'visible' => true,
                        'settings' => array(
                                'marquee_items'  => array(
                                        array( 'icon' => 'mafia',   'text' => 'قدرت' ),
                                        array( 'icon' => 'article', 'text' => 'کاریزما' ),
                                        array( 'icon' => 'income',  'text' => 'ثروت' ),
                                        array( 'icon' => 'mafia',   'text' => 'نفوذ' ),
                                        array( 'icon' => 'article', 'text' => 'مذاکره' ),
                                        array( 'icon' => 'income',  'text' => 'کسب و کار' ),
                                        array( 'icon' => 'mafia',   'text' => 'استراتژی' ),
                                        array( 'icon' => 'article', 'text' => 'ذهنیت' ),
                                        array( 'icon' => 'crown',   'text' => 'رهبری' ),
                                        array( 'icon' => 'settings','text' => 'مدیریت' ),
                                        array( 'icon' => 'badge',   'text' => 'موفقیت' ),
                                        array( 'icon' => 'article', 'text' => 'برند شخصی' ),
                                        array( 'icon' => 'income',  'text' => 'فروش' ),
                                        array( 'icon' => 'article', 'text' => 'شبکه‌سازی' ),
                                        array( 'icon' => 'badge',   'text' => 'اعتماد به نفس' ),
                                        array( 'icon' => 'income',  'text' => 'هوش مالی' ),
                                ),
                                'mq_speed'        => '50',
                                'mq_hover_pause'  => true,
                                'mq_bg_color'     => '#0a0a0c',
                                'mq_bg_opacity'   => '85',
                                'mq_border_color' => '#e01e2b',
                                'mq_glow'         => '8',
                                'mq_icon_color'   => '#e01e2b',
                                'mq_text_color'   => '#f5f5f7',
                                'mq_text_size'    => '',
                                'mq_icon_size'    => '',
                                'mq_gap'          => '',
                                'mq_padding'      => '',
                                'mq_radius'       => '',
                                'mq_fade'         => true,
                                'mq_loop'         => true,
                        ),
                ),
                array(
                        'id'      => 'cats-1',
                        'type'    => 'cats',
                        'visible' => true,
                        'settings' => array(
                                'items' => array(
                                        array( 'icon' => 'income',  'label' => 'کسب درآمد',       'link' => '' ),
                                        array( 'icon' => 'produce', 'label' => 'تولید محتوا',     'link' => '' ),
                                        array( 'icon' => 'prompt',  'label' => 'پرامپت‌نویسی',     'link' => '' ),
                                        array( 'icon' => 'article', 'label' => 'مقالات ویژه',     'link' => '' ),
                                        array( 'icon' => 'book',    'label' => 'خلاصه کتاب',      'link' => '' ),
                                        array( 'icon' => 'all',     'label' => 'همه محصولات',     'link' => '' ),
                                        array( 'icon' => 'course',  'label' => 'دوره‌های آموزشی', 'link' => '' ),
                                ),
                        ),
                ),
                array(
                        'id'      => 'vip-1',
                        'type'    => 'vip',
                        'visible' => true,
                        'settings' => array(
                                'badge_title' => 'VIP',
                                'badge_sub'   => 'اشتراک ویژه آکادمی مافیا',
                                'badge_year'  => '۱ ساله',
                                'heading'     => 'یک سال قدرت و پیشرفت',
                                'items'       => array(
                                        array( 'text' => 'دسترسی نامحدود به بیش از ۱۵۰+ دوره آموزشی' ),
                                        array( 'text' => 'دسترسی به ۱۰۰۰+ مقاله، کتاب و محتوای کاربردی' ),
                                        array( 'text' => 'مقالات ویژه و اختصاصی آکادمی مافیا' ),
                                        array( 'text' => 'دسترسی به تمامی ابزارها و بخش‌های ویژه' ),
                                        array( 'text' => 'آپدیت رایگان و دائمی همه محتواها' ),
                                ),
                                'btn_text' => '🛒 مشاهده پلن‌ها و قیمت‌ها',
                                'btn_link' => '#',
                        ),
                ),
                array( 'id' => 'rows-1', 'type' => 'rows', 'visible' => true, 'settings' => array() ),
                array(
                        'id'      => 'benefits-1',
                        'type'    => 'benefits',
                        'visible' => true,
                        'settings' => array(
                                'items' => array(
                                        array( 'icon' => 'course',  'title' => 'دسترسی دائمی',   'desc' => 'دسترسی به جدیدترین محتواها همیشگی' ),
                                        array( 'icon' => 'article', 'title' => 'آپدیت رایگان',   'desc' => 'محتوا به صورت رایگان به‌روز می‌شود' ),
                                        array( 'icon' => 'prompt',  'title' => 'محتوای انحصاری', 'desc' => 'محتوای اختصاصی فقط برای اعضای آکادمی' ),
                                        array( 'icon' => 'mafia',   'title' => 'پشتیبانی ویژه',  'desc' => 'پشتیبانی سریع و اختصاصی اعضا' ),
                                        array( 'icon' => 'income',  'title' => 'جامعه VIP',      'desc' => 'عضویت در جامعه خصوصی و گروه‌های مشاوره' ),
                                        array( 'icon' => 'tool',    'title' => 'ابزارهای حرفه‌ای', 'desc' => 'دسترسی به ابزارهای کاربردی و مدرن' ),
                                ),
                        ),
                ),
                array(
                        'id'      => 'testi-1',
                        'type'    => 'testimonials',
                        'visible' => true,
                        'settings' => array(
                                'heading' => 'اعضای باشگاه چه می‌گویند',
                                'sub'     => 'صدای کسانی که مسیر قدرت را آغاز کرده‌اند',
                                'items'   => array(
                                        array( 'name' => 'علی ر.', 'role' => 'عضو VIP',      'text' => 'بهترین تصمیم سال من عضویت در آکادمی مافیا بود. محتوا واقعاً سطح بالاست و حس می‌کنی وارد یک باشگاه قدرتمند شدی.' ),
                                        array( 'name' => 'سارا م.', 'role' => 'عضو ۱ ساله', 'text' => 'دوره‌های هوش مالی زندگی کاری‌ام را عوض کرد. کیفیت طراحی و محتوا در حد قالب‌های خارجی‌ه.' ),
                                        array( 'name' => 'محمد ک.', 'role' => 'عضو VIP',    'text' => 'مقالات مافیایی فوق‌العاده‌اند. هر مقاله یک درس واقعی از قدرت و استراتژی است.' ),
                                ),
                        ),
                ),
                array(
                        'id'      => 'faq-1',
                        'type'    => 'faq',
                        'visible' => true,
                        'settings' => array(
                                'heading' => 'سوالات متداول',
                                'items'   => array(
                                        array( 'q' => 'اشتراک ویژه چطور کار می‌کند؟', 'a' => 'با خرید اشتراک سالانه، به تمام دوره‌ها، خلاصه کتاب‌ها، مقالات و ابزارها به‌صورت نامحدود دسترسی پیدا می‌کنید.' ),
                                        array( 'q' => 'آیا محتوای رایگان هم وجود دارد؟', 'a' => 'بله، بخشی از محتواها به‌صورت رایگان در دسترس است و محتوای ویژه با اشتراک VIP باز می‌شود.' ),
                                        array( 'q' => 'آیا امکان بازگشت وجه هست؟', 'a' => 'بله، تا ۷ روز پس از خرید در صورت عدم رضایت، مبلغ به‌صورت کامل بازگردانده می‌شود.' ),
                                        array( 'q' => 'محتواها چند وقت یک‌بار به‌روز می‌شوند؟', 'a' => 'هر هفته محتوای جدید اضافه می‌شود و آپدیت‌ها برای اعضای VIP رایگان است.' ),
                                ),
                        ),
                ),
        );
}

/* =============================================================
 * 3. Layout storage helpers
 * ============================================================= */

function a7v_get_home_layout() {
        $layout = get_option( 'a7v_home_layout', null );
        if ( empty( $layout ) || ! is_array( $layout ) ) {
                return a7v_default_home_layout();
        }
        return $layout;
}

/**
 * Recursively sanitize a stored layout before saving.
 */
function a7v_sanitize_layout( $layout ) {
        $registry = a7v_block_registry();
        $clean    = array();
        if ( ! is_array( $layout ) ) {
                return $clean;
        }
        foreach ( $layout as $block ) {
                if ( empty( $block['type'] ) || ! isset( $registry[ $block['type'] ] ) ) {
                        continue;
                }
                $type = $block['type'];
                $out  = array(
                        'id'       => isset( $block['id'] ) ? sanitize_text_field( $block['id'] ) : ( $type . '-' . wp_rand( 1000, 9999 ) ),
                        'type'     => $type,
                        'visible'  => ! empty( $block['visible'] ),
                        'settings' => array(),
                );
                $settings = isset( $block['settings'] ) && is_array( $block['settings'] ) ? $block['settings'] : array();
                foreach ( $registry[ $type ]['fields'] as $field ) {
                        $out['settings'][ $field['key'] ] = a7v_sanitize_field_value( $field, isset( $settings[ $field['key'] ] ) ? $settings[ $field['key'] ] : '' );
                }
                $clean[] = $out;
        }
        return $clean;
}

function a7v_sanitize_field_value( $field, $value ) {
        switch ( $field['type'] ) {
                case 'repeater':
                        $rows = array();
                        if ( is_array( $value ) ) {
                                foreach ( $value as $row ) {
                                        if ( ! is_array( $row ) ) { continue; }
                                        $clean_row = array();
                                        foreach ( $field['fields'] as $sub ) {
                                                $clean_row[ $sub['key'] ] = a7v_sanitize_field_value( $sub, isset( $row[ $sub['key'] ] ) ? $row[ $sub['key'] ] : '' );
                                        }
                                        $rows[] = $clean_row;
                                }
                        }
                        return $rows;
                case 'textarea':
                        return wp_kses_post( (string) $value );
                case 'html':
                        return wp_kses_post( (string) $value );
                case 'url':
                        return esc_url_raw( (string) $value );
                case 'image':
                        return esc_url_raw( (string) $value );
                case 'color':
                        return sanitize_hex_color( (string) $value );
                case 'toggle':
                        return ! empty( $value ) ? true : false;
                default:
                        return wp_kses_post( (string) $value );
        }
}

/* =============================================================
 * 4. Render engine
 * ============================================================= */

/**
 * Render the whole homepage layout. Used by front-page.php.
 * In preview mode (builder iframe) it reads the live preview transient.
 */
function a7v_render_home_layout() {
        if ( isset( $_GET['a7v_preview'] ) && current_user_can( 'edit_theme_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $preview = get_transient( 'a7v_preview_layout_' . get_current_user_id() );
                $layout  = ! empty( $preview ) ? $preview : a7v_get_home_layout();
        } else {
                $layout = a7v_get_home_layout();
        }

        foreach ( $layout as $block ) {
                if ( empty( $block['visible'] ) ) {
                        continue;
                }
                echo a7v_render_block( $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
}

/**
 * Render one block to HTML.
 */
function a7v_render_block( $block ) {
        $type = isset( $block['type'] ) ? $block['type'] : '';
        $s    = isset( $block['settings'] ) && is_array( $block['settings'] ) ? $block['settings'] : array();
        $fn   = 'a7v_render_block_' . $type;
        if ( function_exists( $fn ) ) {
                return call_user_func( $fn, $s );
        }
        return '';
}

/* ---- Individual block renderers (mirror the original markup) ---- */

/**
 * Convert hex color to RGB array.
 */
function a7v_hex_to_rgb( $hex ) {
        $hex = ltrim( $hex, '#' );
        if ( 3 === strlen( $hex ) ) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return array(
                'r' => hexdec( substr( $hex, 0, 2 ) ),
                'g' => hexdec( substr( $hex, 2, 2 ) ),
                'b' => hexdec( substr( $hex, 4, 2 ) ),
        );
}

/**
 * Render the marquee block (infinite loop scroller).
 * Uses JS requestAnimationFrame with pixel-perfect width measurement.
 */
function a7v_render_block_marquee( $s ) {
        $items = ! empty( $s['marquee_items'] ) && is_array( $s['marquee_items'] ) ? $s['marquee_items'] : array();
        if ( empty( $items ) ) { return ''; }

        /* Customizer overrides */
        $cz_loop  = get_theme_mod( 'a7v_marquee_loop', true );
        $cz_speed = get_theme_mod( 'a7v_marquee_speed', '50' );
        $cz_dir   = get_theme_mod( 'a7v_marquee_direction', 'left' );

        /* Block settings with fallbacks */
        $loop       = isset( $s['mq_loop'] ) ? (bool) $s['mq_loop'] : $cz_loop;
        $speed      = ! empty( $s['mq_speed'] ) ? max( 5, (float) $s['mq_speed'] ) : max( 5, (float) $cz_speed );
        $direction  = ! empty( $s['mq_direction'] ) ? $s['mq_direction'] : $cz_dir;
        $bg_color   = ! empty( $s['mq_bg_color'] ) ? $s['mq_bg_color'] : '#0a0a0c';
        $bg_opacity = isset( $s['mq_bg_opacity'] ) ? max( 0, min( 100, (int) $s['mq_bg_opacity'] ) ) : 85;
        $border_clr = ! empty( $s['mq_border_color'] ) ? $s['mq_border_color'] : '#e01e2b';
        $glow       = isset( $s['mq_glow'] ) ? max( 0, min( 100, (int) $s['mq_glow'] ) ) : 8;
        $icon_clr   = ! empty( $s['mq_icon_color'] ) ? $s['mq_icon_color'] : '#e01e2b';
        $text_clr   = ! empty( $s['mq_text_color'] ) ? $s['mq_text_color'] : '#f5f5f7';
        $text_sz    = ! empty( $s['mq_text_size'] ) ? (int) $s['mq_text_size'] : 13;
        $icon_sz    = ! empty( $s['mq_icon_size'] ) ? (int) $s['mq_icon_size'] : 16;
        $gap        = ! empty( $s['mq_gap'] ) ? (int) $s['mq_gap'] : 6;
        $padding    = ! empty( $s['mq_padding'] ) ? (int) $s['mq_padding'] : 10;
        $radius     = ! empty( $s['mq_radius'] ) ? (int) $s['mq_radius'] : 10;
        $fade       = isset( $s['mq_fade'] ) ? (bool) $s['mq_fade'] : true;

        /* Build rgba values */
        $bg_rgb     = a7v_hex_to_rgb( $bg_color );
        $bg_rgba    = 'rgba(' . $bg_rgb['r'] . ',' . $bg_rgb['g'] . ',' . $bg_rgb['b'] . ',' . ( $bg_opacity / 100 ) . ')';
        $b_rgb      = a7v_hex_to_rgb( $border_clr );
        $border_rgba = 'rgba(' . $b_rgb['r'] . ',' . $b_rgb['g'] . ',' . $b_rgb['b'] . ',.18)';
        $glow_px    = round( $glow * 0.15 );
        $glow_rgba  = 'rgba(' . $b_rgb['r'] . ',' . $b_rgb['g'] . ',' . $b_rgb['b'] . ',' . round( $glow * 0.008, 3 ) . ')';

        $inline = sprintf(
                '--mq-speed:%ss;--mq-gap:%dpx;--mq-pad:%dpx %dpx;--mq-radius:%dpx;' .
                '--mq-bg:%s;--mq-border:%s;--mq-glow-px:%dpx;--mq-glow-clr:%s;' .
                '--mq-icon-clr:%s;--mq-text-clr:%s;--mq-icon-sz:%dpx;--mq-text-sz:%dpx;',
                $speed, $gap, $padding, (int)( $padding * 1.8 ), $radius,
                $bg_rgba, $border_rgba, $glow_px, $glow_rgba,
                $icon_clr, $text_clr, $icon_sz, $text_sz
        );

        $cls = 'a7v-marquee-section';
        if ( $fade ) { $cls .= ' has-fade'; }

        $copies = $loop ? 2 : 1;
        $item_count = count( $items );

        ob_start(); ?>
        <section class="<?php echo esc_attr( $cls ); ?>" id="a7v-marquee" dir="ltr"
                style="<?php echo esc_attr( $inline ); ?>"
                data-mq-loop="<?php echo $loop ? '1' : '0'; ?>"
                data-mq-speed="<?php echo esc_attr( $speed ); ?>"
                data-mq-dir="<?php echo esc_attr( $direction ); ?>"
                data-mq-count="<?php echo esc_attr( $item_count ); ?>">
                <div class="a7v-marquee-track">
                        <?php
                        for ( $set = 0; $set < $copies; $set++ ) :
                                foreach ( $items as $item ) :
                                        $ic_key = isset( $item['icon'] ) ? $item['icon'] : 'mafia';
                                        $txt   = isset( $item['text'] ) ? $item['text'] : '';
                                        $is    = '';
                                        if ( ! empty( $item['icon_color'] ) ) { $is .= '--mq-item-icon-clr:' . esc_attr( $item['icon_color'] ) . ';'; }
                                        if ( ! empty( $item['text_color'] ) ) { $is .= '--mq-item-text-clr:' . esc_attr( $item['text_color'] ) . ';'; }
                                        if ( ! empty( $item['text_size'] ) )  { $is .= '--mq-item-text-sz:' . (int) $item['text_size'] . 'px;'; }
                                        if ( ! empty( $item['icon_size'] ) )  { $is .= '--mq-item-icon-sz:' . (int) $item['icon_size'] . 'px;'; }
                                        ?>
                                <div class="a7v-mq-item" dir="rtl"<?php echo $is ? ' style="' . esc_attr( $is ) . '"' : ''; ?>>
                                        <span class="a7v-mq-ic"><?php echo a7v_icon( $ic_key ); ?></span>
                                        <span class="a7v-mq-txt"><?php echo esc_html( $txt ); ?></span>
                                </div>
                                <?php endforeach;
                        endfor;
                        ?>
                </div>
        </section>

        <?php if ( $loop ) : ?>
        <script>
        (function(){
                var section = document.getElementById('a7v-marquee');
                if (!section) return;
                var track = section.querySelector('.a7v-marquee-track');
                if (!track) return;

                var count = parseInt(section.dataset.mqCount) || 0;
                if (count < 1) return;
                var items = track.children;
                if (items.length < count * 2) return;

                var duration = parseFloat(section.dataset.mqSpeed) || 50;
                var dir = section.dataset.mqDir || 'left';
                var distance = 0;
                var pos = 0;
                var lastTime = null;
                var raf = null;

                function measure() {
                        /*
                         * Clear any existing transform so getBoundingClientRect
                         * returns the natural (unshifted) layout positions.
                         */
                        track.style.transform = 'none';
                        /* Force browser to recalc layout */
                        void track.offsetHeight;

                        var firstRect = items[0].getBoundingClientRect();
                        var cloneRect = items[count].getBoundingClientRect();

                        /*
                         * Distance = left-edge of first clone minus left-edge of first original.
                         * This equals: (sum of all item widths in group 1) + (count gaps).
                         * Rounding eliminates sub-pixel seams.
                         */
                        return Math.round(cloneRect.left - firstRect.left);
                }

                function init() {
                        distance = measure();
                        if (distance < 1) return;

                        /* Set starting position */
                        if (dir === 'right') {
                                pos = -distance;
                        } else {
                                pos = 0;
                        }
                        track.style.transform = 'translateX(' + pos + 'px)';
                        run();
                }

                function run() {
                        var pxPerSec = distance / duration;
                        lastTime = null;

                        function step(ts) {
                                if (lastTime === null) lastTime = ts;
                                var dt = (ts - lastTime) / 1000;
                                lastTime = ts;
                                /* Cap delta to avoid huge jumps after tab-switch */
                                if (dt > 0.1) dt = 0.016;

                                if (dir === 'left') {
                                        pos -= pxPerSec * dt;
                                        /* When first clone reaches where first original started, reset */
                                        if (pos <= -distance) pos = 0;
                                } else {
                                        pos += pxPerSec * dt;
                                        /* When last original reaches where last clone was, reset */
                                        if (pos >= 0) pos = -distance;
                                }

                                track.style.transform = 'translateX(' + Math.round(pos) + 'px)';
                                raf = requestAnimationFrame(step);
                        }

                        raf = requestAnimationFrame(step);
                }

                /* Wait for fonts to load before measuring */
                if (document.fonts && document.fonts.ready) {
                        document.fonts.ready.then(init);
                } else {
                        window.addEventListener('load', init);
                }

                /* Re-measure on resize */
                var resizeTimer;
                window.addEventListener('resize', function() {
                        clearTimeout(resizeTimer);
                        resizeTimer = setTimeout(function() {
                                if (raf) cancelAnimationFrame(raf);
                                raf = null;
                                lastTime = null;
                                init();
                        }, 250);
                });
        })();
        </script>
        <?php
        return ob_get_clean();
}

function a7v_render_block_hero( $s ) {
        $img   = ! empty( $s['image'] ) ? $s['image'] : ( A7V_URI . '/assets/img/hero.jpeg' );
        $title = isset( $s['title'] ) ? $s['title'] : '';
        $accent = isset( $s['accent'] ) ? $s['accent'] : '';
        ob_start(); ?>
        <section class="hero" id="hero">
                <div class="hero-bg" style="background-image:url('<?php echo esc_url( $img ); ?>')"></div>
                <div class="hero-grain"></div>
                <div class="container hero-inner">
                        <div class="hero-content">
                                <h1 class="hero-title"><?php echo esc_html( $title ); ?><?php if ( '' !== $accent ) : ?> <span class="accent"><?php echo esc_html( $accent ); ?></span><?php endif; ?></h1>
                                <?php if ( ! empty( $s['tags'] ) && is_array( $s['tags'] ) ) : ?>
                                <p class="hero-tags">
                                        <?php
                                        $tags = array_values( array_filter( array_map( function( $t ) { return isset( $t['text'] ) ? trim( $t['text'] ) : ''; }, $s['tags'] ) ) );
                                        $last = count( $tags ) - 1;
                                        foreach ( $tags as $idx => $t ) {
                                                echo '<span>' . esc_html( $t ) . '</span>';
                                                if ( $idx < $last ) { echo '<i>|</i>'; }
                                        }
                                        ?>
                                </p>
                                <?php endif; ?>
                                <p class="hero-desc"><?php echo esc_html( isset( $s['desc'] ) ? $s['desc'] : '' ); ?></p>
                                <div class="hero-cta">
                                        <a class="btn btn-primary btn-lg" href="<?php echo esc_url( isset( $s['cta_link'] ) ? $s['cta_link'] : '#' ); ?>"><?php echo esc_html( isset( $s['cta_text'] ) ? $s['cta_text'] : '' ); ?></a>
                                </div>
                                <p class="hero-sub"><?php echo esc_html( isset( $s['sub'] ) ? $s['sub'] : '' ); ?></p>
                        </div>
                </div>
        </section>
        <?php
        return ob_get_clean();
}

function a7v_render_block_cats( $s ) {
        $items = ! empty( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
        ob_start(); ?>
        <section class="cats"><div class="container"><div class="cats-grid">
                <?php foreach ( $items as $c ) :
                        $link = ! empty( $c['link'] ) ? $c['link'] : '#';
                        ?>
                        <a class="cat" href="<?php echo esc_url( $link ); ?>"><div class="cat-ic"><?php echo a7v_icon( isset( $c['icon'] ) ? $c['icon'] : 'all' ); // phpcs:ignore ?></div><span><?php echo esc_html( isset( $c['label'] ) ? $c['label'] : '' ); ?></span></a>
                <?php endforeach; ?>
        </div></div></section>
        <?php
        return ob_get_clean();
}

function a7v_render_block_vip( $s ) {
        $items = ! empty( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
        ob_start(); ?>
        <section class="vip" id="vip"><div class="container">
                <div class="vip-card glass">
                        <div class="vip-badge">
                                <div class="crown">👑</div>
                                <div class="vip-title"><?php echo esc_html( isset( $s['badge_title'] ) ? $s['badge_title'] : 'VIP' ); ?></div>
                                <div class="vip-sub"><?php echo esc_html( isset( $s['badge_sub'] ) ? $s['badge_sub'] : '' ); ?></div>
                                <div class="vip-year"><?php echo esc_html( isset( $s['badge_year'] ) ? $s['badge_year'] : '' ); ?></div>
                        </div>
                        <div class="vip-body">
                                <h2><?php echo esc_html( isset( $s['heading'] ) ? $s['heading'] : '' ); ?></h2>
                                <ul class="vip-list">
                                        <?php foreach ( $items as $it ) : ?>
                                                <li><?php echo esc_html( isset( $it['text'] ) ? $it['text'] : '' ); ?></li>
                                        <?php endforeach; ?>
                                </ul>
                                <a class="btn btn-primary" href="<?php echo esc_url( isset( $s['btn_link'] ) ? $s['btn_link'] : '#' ); ?>"><?php echo esc_html( isset( $s['btn_text'] ) ? $s['btn_text'] : '' ); ?></a>
                        </div>
                </div>
        </div></section>
        <?php
        return ob_get_clean();
}

function a7v_render_block_rows( $s ) {
        ob_start(); ?>
        <section class="rows"><?php a7v_render_rows(); ?></section>
        <?php
        return ob_get_clean();
}

function a7v_render_block_benefits( $s ) {
        $items = ! empty( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
        ob_start(); ?>
        <section class="benefits" id="benefits"><div class="container"><div class="benefits-grid">
                <?php foreach ( $items as $b ) {
                        printf(
                                '<div class="benefit"><div class="b-ic">%s</div><h4>%s</h4><p>%s</p></div>',
                                a7v_icon( isset( $b['icon'] ) ? $b['icon'] : 'all' ), // phpcs:ignore
                                esc_html( isset( $b['title'] ) ? $b['title'] : '' ),
                                esc_html( isset( $b['desc'] ) ? $b['desc'] : '' )
                        );
                } ?>
        </div></div></section>
        <?php
        return ob_get_clean();
}

function a7v_render_block_testimonials( $s ) {
        $items = ! empty( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
        ob_start(); ?>
        <section class="testimonials" id="testimonials"><div class="container">
                <div class="section-head center"><h2><?php echo esc_html( isset( $s['heading'] ) ? $s['heading'] : '' ); ?></h2><p><?php echo esc_html( isset( $s['sub'] ) ? $s['sub'] : '' ); ?></p></div>
                <div class="testi-grid">
                        <?php foreach ( $items as $t ) {
                                $name = isset( $t['name'] ) ? $t['name'] : '';
                                printf(
                                        '<div class="testi"><div class="stars">★★★★★</div><p>%s</p><div class="who"><div class="av">%s</div><div><b>%s</b><small>%s</small></div></div></div>',
                                        esc_html( isset( $t['text'] ) ? $t['text'] : '' ),
                                        esc_html( mb_substr( $name, 0, 1 ) ),
                                        esc_html( $name ),
                                        esc_html( isset( $t['role'] ) ? $t['role'] : '' )
                                );
                        } ?>
                </div>
        </div></section>
        <?php
        return ob_get_clean();
}

function a7v_render_block_faq( $s ) {
        $items = ! empty( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
        ob_start(); ?>
        <section class="faq" id="faq"><div class="container">
                <div class="section-head center"><h2><?php echo esc_html( isset( $s['heading'] ) ? $s['heading'] : '' ); ?></h2></div>
                <div class="faq-list">
                        <?php foreach ( $items as $f ) {
                                printf(
                                        '<div class="faq-item"><div class="faq-q">%s<span class="ar">+</span></div><div class="faq-a">%s</div></div>',
                                        esc_html( isset( $f['q'] ) ? $f['q'] : '' ),
                                        esc_html( isset( $f['a'] ) ? $f['a'] : '' )
                                );
                        } ?>
                </div>
        </div></section>
        <?php
        return ob_get_clean();
}

/* ---- Generic widgets ---- */

function a7v_render_block_heading( $s ) {
        $align = isset( $s['align'] ) ? $s['align'] : 'right';
        $size  = isset( $s['size'] ) && $s['size'] ? 'font-size:' . (int) $s['size'] . 'px;' : '';
        return sprintf(
                '<section class="a7v-blk a7v-heading"><div class="container"><h2 style="text-align:%s;%s">%s</h2></div></section>',
                esc_attr( $align ),
                esc_attr( $size ),
                esc_html( isset( $s['text'] ) ? $s['text'] : '' )
        );
}

function a7v_render_block_text( $s ) {
        $align = isset( $s['align'] ) ? $s['align'] : 'right';
        return sprintf(
                '<section class="a7v-blk a7v-text"><div class="container" style="text-align:%s">%s</div></section>',
                esc_attr( $align ),
                wp_kses_post( wpautop( isset( $s['content'] ) ? $s['content'] : '' ) )
        );
}

function a7v_render_block_image( $s ) {
        if ( empty( $s['image'] ) ) { return ''; }
        $align = isset( $s['align'] ) ? $s['align'] : 'center';
        $img   = '<img src="' . esc_url( $s['image'] ) . '" alt="" style="max-width:100%;height:auto;border-radius:14px">';
        if ( ! empty( $s['link'] ) ) {
                $img = '<a href="' . esc_url( $s['link'] ) . '">' . $img . '</a>';
        }
        return sprintf( '<section class="a7v-blk a7v-image"><div class="container" style="text-align:%s">%s</div></section>', esc_attr( $align ), $img );
}

function a7v_render_block_button( $s ) {
        $align = isset( $s['align'] ) ? $s['align'] : 'center';
        $cls   = ( isset( $s['style'] ) && 'outline' === $s['style'] ) ? 'btn btn-outline' : 'btn btn-primary';
        return sprintf(
                '<section class="a7v-blk a7v-button"><div class="container" style="text-align:%s"><a class="%s" href="%s">%s</a></div></section>',
                esc_attr( $align ),
                esc_attr( $cls ),
                esc_url( isset( $s['link'] ) ? $s['link'] : '#' ),
                esc_html( isset( $s['text'] ) ? $s['text'] : '' )
        );
}

function a7v_render_block_spacer( $s ) {
        $h = isset( $s['height'] ) && $s['height'] ? (int) $s['height'] : 40;
        return '<div class="a7v-blk a7v-spacer" style="height:' . $h . 'px"></div>';
}

function a7v_render_block_html( $s ) {
        return '<section class="a7v-blk a7v-html"><div class="container">' . wp_kses_post( isset( $s['code'] ) ? $s['code'] : '' ) . '</div></section>';
}

/* =============================================================
 * 5. Admin page + assets
 * ============================================================= */

function a7v_builder_menu() {
        $hook = add_menu_page(
                'صفحه‌ساز A7V',
                'صفحه‌ساز A7V',
                'edit_theme_options',
                'a7v-home-builder',
                'a7v_builder_page',
                'dashicons-layout',
                3
        );
        add_action( 'load-' . $hook, 'a7v_builder_admin_enqueue' );
}
add_action( 'admin_menu', 'a7v_builder_menu' );

function a7v_builder_admin_enqueue() {
        add_action( 'admin_enqueue_scripts', function() {
                wp_enqueue_media();
                $css_ver = file_exists( A7V_DIR . '/assets/css/builder.css' ) ? filemtime( A7V_DIR . '/assets/css/builder.css' ) : A7V_VER;
                $js_ver  = file_exists( A7V_DIR . '/assets/js/builder.js' ) ? filemtime( A7V_DIR . '/assets/js/builder.js' ) : A7V_VER;
                wp_enqueue_style( 'a7v-builder', A7V_URI . '/assets/css/builder.css', array(), $css_ver );
                wp_enqueue_script( 'a7v-builder', A7V_URI . '/assets/js/builder.js', array(), $js_ver, true );

                // Build registry meta for JS.
                wp_localize_script( 'a7v-builder', 'A7VBuilder', array(
                        'ajax'       => admin_url( 'admin-ajax.php' ),
                        'nonce'      => wp_create_nonce( 'a7v_builder' ),
                        'previewUrl' => home_url( '/?a7v_preview=1' ),
                        'layout'     => a7v_get_home_layout(),
                        'registry'   => a7v_block_registry(),
                        'defaultLayout' => a7v_default_home_layout(),
                        'i18n'       => array(
                                'saved'   => 'ذخیره شد ✅',
                                'saving'  => 'در حال ذخیره…',
                                'confirmDelete' => 'این بخش حذف شود؟',
                                'confirmReset'  => 'چیدمان به حالت پیش‌فرض بازگردد؟ تغییرات ذخیره‌نشده از بین می‌رود.',
                        ),
                ) );
        } );
}

function a7v_builder_page() {
        ?>
        <div class="wrap a7v-builder-wrap">
                <div class="a7v-b-topbar">
                        <div class="a7v-b-brand">
                                <span class="dashicons dashicons-layout"></span>
                                <strong>صفحه‌ساز آکادمی مافیا</strong>
                                <span class="a7v-b-hint">— ویرایش زنده صفحه اصلی</span>
                        </div>
                        <div class="a7v-b-actions">
                                <button type="button" class="button" id="a7vAddBlock"><span class="dashicons dashicons-plus-alt2"></span> افزودن بخش</button>
                                <button type="button" class="button" id="a7vReset">بازنشانی</button>
                                <button type="button" class="button" id="a7vReloadPreview"><span class="dashicons dashicons-update"></span> نوسازی پیش‌نمایش</button>
                                <button type="button" class="button button-primary" id="a7vSave">ذخیره تغییرات</button>
                                <span id="a7vStatus" class="a7v-b-status"></span>
                        </div>
                </div>

                <div class="a7v-b-body">
                        <aside class="a7v-b-side">
                                <div class="a7v-b-side-head">بخش‌های صفحه</div>
                                <ul id="a7vBlockList" class="a7v-b-list"></ul>
                        </aside>

                        <main class="a7v-b-preview">
                                <div class="a7v-b-device-bar">
                                        <button type="button" class="a7v-dev active" data-w="100%">💻 دسکتاپ</button>
                                        <button type="button" class="a7v-dev" data-w="768px">📱 تبلت</button>
                                        <button type="button" class="a7v-dev" data-w="390px">📱 موبایل</button>
                                </div>
                                <div class="a7v-b-frame-wrap">
                                        <iframe id="a7vPreview" src="about:blank" title="preview"></iframe>
                                </div>
                        </main>

                        <section class="a7v-b-inspector" id="a7vInspector">
                                <div class="a7v-b-insp-empty">یک بخش را از لیست سمت راست انتخاب کن تا تنظیماتش اینجا نمایش داده شود.</div>
                        </section>
                </div>

                <!-- Add-block modal -->
                <div class="a7v-b-modal" id="a7vAddModal" hidden>
                        <div class="a7v-b-modal-ov" data-close></div>
                        <div class="a7v-b-modal-card">
                                <div class="a7v-b-modal-head">افزودن بخش جدید <button type="button" class="a7v-b-modal-x" data-close>✕</button></div>
                                <div class="a7v-b-add-grid" id="a7vAddGrid"></div>
                        </div>
                </div>
        </div>
        <?php
}

/* =============================================================
 * 6. AJAX endpoints
 * ============================================================= */

function a7v_ajax_save_layout() {
        check_ajax_referer( 'a7v_builder', 'nonce' );
        if ( ! current_user_can( 'edit_theme_options' ) ) {
                wp_send_json_error( 'forbidden', 403 );
        }
        $raw = isset( $_POST['layout'] ) ? json_decode( wp_unslash( $_POST['layout'] ), true ) : array(); // phpcs:ignore
        $clean = a7v_sanitize_layout( $raw );
        update_option( 'a7v_home_layout', $clean );
        delete_transient( 'a7v_preview_layout_' . get_current_user_id() );
        wp_send_json_success( array( 'count' => count( $clean ) ) );
}
add_action( 'wp_ajax_a7v_save_layout', 'a7v_ajax_save_layout' );

function a7v_ajax_preview_layout() {
        check_ajax_referer( 'a7v_builder', 'nonce' );
        if ( ! current_user_can( 'edit_theme_options' ) ) {
                wp_send_json_error( 'forbidden', 403 );
        }
        $raw = isset( $_POST['layout'] ) ? json_decode( wp_unslash( $_POST['layout'] ), true ) : array(); // phpcs:ignore
        $clean = a7v_sanitize_layout( $raw );
        set_transient( 'a7v_preview_layout_' . get_current_user_id(), $clean, HOUR_IN_SECONDS );
        wp_send_json_success();
}
add_action( 'wp_ajax_a7v_preview_layout', 'a7v_ajax_preview_layout' );

/* Admin bar shortcut to open the builder from the front-end. */
function a7v_builder_adminbar( $bar ) {
        if ( ! current_user_can( 'edit_theme_options' ) ) { return; }
        $bar->add_node( array(
                'id'    => 'a7v-builder',
                'title' => '✏️ ویرایش صفحه اصلی',
                'href'  => admin_url( 'admin.php?page=a7v-home-builder' ),
        ) );
}
add_action( 'admin_bar_menu', 'a7v_builder_adminbar', 90 );
