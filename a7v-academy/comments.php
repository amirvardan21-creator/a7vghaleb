<?php
/**
 * Comments template for A7V content (free + VIP).
 *
 * @package A7V
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="a7v-comments">

	<?php if ( have_comments() ) : ?>
		<h3 class="a7v-comments-title">
			<?php
			$c = get_comments_number();
			/* translators: %s: comment count */
			printf( esc_html( _n( '%s نظر', '%s نظر', $c, 'a7v' ) ), esc_html( number_format_i18n( $c ) ) );
			?>
		</h3>

		<ol class="a7v-comment-list">
			<?php
			wp_list_comments( array(
				'style'       => 'ol',
				'avatar_size' => 46,
				'short_ping'  => true,
			) );
			?>
		</ol>

		<?php
		the_comments_navigation( array(
			'prev_text' => '‹ نظرات قبلی',
			'next_text' => 'نظرات بعدی ›',
		) );
		?>

	<?php else : ?>
		<p class="a7v-comments-none">هنوز نظری ثبت نشده — اولین نفری باش که نظر می‌دهد.</p>
	<?php endif; ?>

	<?php if ( ! comments_open() ) : ?>
		<p class="a7v-comments-closed">ثبت نظر برای این محتوا بسته است.</p>
	<?php endif; ?>

	<?php
	comment_form( array(
		'title_reply'         => 'ثبت نظر',
		'title_reply_to'      => 'پاسخ به %s',
		'cancel_reply_link'   => 'انصراف',
		'label_submit'        => 'ارسال نظر',
		'class_submit'        => 'btn btn-primary',
		'comment_notes_before'=> '',
		'comment_field'       => '<p class="comment-form-comment"><label for="comment">نظر شما</label><textarea id="comment" name="comment" cols="45" rows="6" required></textarea></p>',
	) );
	?>
</div>
