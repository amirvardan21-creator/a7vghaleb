<?php
/**
 * Admin — subscriptions / members management view.
 *
 * @package MafiaAcademyMembership
 * @var array $members  WP_User[]
 * @var int   $total
 * @var int   $pages
 * @var int   $paged
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
$base  = admin_url( 'admin.php?page=mam-subscriptions' );
$plans = MAM_Settings::plans();
$post_url = admin_url( 'admin-post.php' );
?>
<div class="wrap mam-wrap">
	<div class="mam-head"><h1><span class="mam-logo">M</span> مدیریت اشتراک‌ها</h1><span class="mam-sub">مجموع: <?php echo esc_html( number_format_i18n( $total ) ); ?> عضو</span></div>

	<div class="mam-card">
		<div class="mam-table-wrap">
			<table class="mam-table">
				<thead>
					<tr><th>کاربر</th><th>ایمیل</th><th>پلن</th><th>وضعیت</th><th>پایان</th><th>باقی‌مانده</th><th>عملیات</th></tr>
				</thead>
				<tbody>
					<?php if ( empty( $members ) ) : ?>
						<tr><td colspan="7" class="mam-empty">هنوز عضوی ثبت نشده است.</td></tr>
					<?php else : foreach ( $members as $u ) :
						$st = mam_membership( $u->ID );
						?>
						<tr>
							<td><?php echo esc_html( $u->display_name ); ?><div class="mam-mono">#<?php echo (int) $u->ID; ?></div></td>
							<td><?php echo esc_html( $u->user_email ); ?></td>
							<td><?php echo esc_html( $st['plan_label'] ? $st['plan_label'] : '—' ); ?></td>
							<td>
								<span class="mam-badge mam-badge-<?php echo 'active' === $st['state'] ? 'ok' : ( 'expired' === $st['state'] ? 'bad' : 'muted' ); ?>"><?php echo esc_html( $st['state_label'] ); ?></span>
							</td>
							<td><?php echo esc_html( $st['end'] ? mysql2date( 'Y/m/d', $st['end'] ) : '—' ); ?></td>
							<td><?php echo 'active' === $st['state'] ? esc_html( number_format_i18n( $st['days_left'] ) . ' روز' ) : '—'; ?></td>
							<td>
								<details class="mam-details">
									<summary class="button">مدیریت</summary>
									<div class="mam-details-body">
										<form method="post" action="<?php echo esc_url( $post_url ); ?>" class="mam-inline-form">
											<input type="hidden" name="action" value="mam_edit_member">
											<input type="hidden" name="user_id" value="<?php echo (int) $u->ID; ?>">
											<?php wp_nonce_field( 'mam_edit_member', 'mam_member_nonce' ); ?>
											<label>تمدید با پلن:</label>
											<select name="plan_key">
												<?php foreach ( $plans as $p ) : ?>
													<option value="<?php echo esc_attr( $p['key'] ); ?>"><?php echo esc_html( $p['label'] . ' (' . $p['days'] . ' روز)' ); ?></option>
												<?php endforeach; ?>
											</select>
											<button class="button button-primary" name="member_action" value="extend">تمدید</button>
										</form>
										<form method="post" action="<?php echo esc_url( $post_url ); ?>" class="mam-inline-form">
											<input type="hidden" name="action" value="mam_edit_member">
											<input type="hidden" name="user_id" value="<?php echo (int) $u->ID; ?>">
											<?php wp_nonce_field( 'mam_edit_member', 'mam_member_nonce' ); ?>
											<label>تاریخ پایان دستی:</label>
											<input type="date" name="end_date" value="<?php echo esc_attr( $st['end'] ? mysql2date( 'Y-m-d', $st['end'] ) : '' ); ?>">
											<button class="button" name="member_action" value="set_date">ثبت تاریخ</button>
										</form>
										<form method="post" action="<?php echo esc_url( $post_url ); ?>" class="mam-inline-form" onsubmit="return confirm('اشتراک این کاربر لغو شود؟');">
											<input type="hidden" name="action" value="mam_edit_member">
											<input type="hidden" name="user_id" value="<?php echo (int) $u->ID; ?>">
											<?php wp_nonce_field( 'mam_edit_member', 'mam_member_nonce' ); ?>
											<button class="button mam-danger" name="member_action" value="cancel">لغو اشتراک</button>
										</form>
									</div>
								</details>
							</td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<?php if ( $pages > 1 ) : ?>
		<div class="mam-pager">
			<?php for ( $i = 1; $i <= $pages; $i++ ) : ?>
				<a class="<?php echo $i === $paged ? 'on' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'paged', $i, $base ) ); ?>"><?php echo (int) $i; ?></a>
			<?php endfor; ?>
		</div>
	<?php endif; ?>
</div>
