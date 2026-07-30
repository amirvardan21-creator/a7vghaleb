<?php
/**
 * Front page (homepage).
 *
 * The homepage is now rendered from the A7V Home Builder layout
 * (option `a7v_home_layout`). Edit it visually from:
 *   پیشخوان وردپرس → صفحه‌ساز A7V
 *
 * @package A7V
 */

get_header();

a7v_render_home_layout();

get_footer();
