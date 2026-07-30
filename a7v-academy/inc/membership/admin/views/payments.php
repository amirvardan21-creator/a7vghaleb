<?php
/**
 * Admin — payments management view.
 *
 * @package MafiaAcademyMembership
 * @var array $records
 * @var int   $total
 * @var int   $pages
 * @var int   $paged
 * @var string $status
 * @var string $method
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
$base = admin_url( 'admin.php?page=mam-payments' );
?>
<div class="wrap mam-wrap">
	<div class="mam-head"><h1><span class="mam-logo">M</span> مدیریت پرداخت‌ها</h1></div>

	<div class="mam-filters">
		<a class="mam-chip <?php echo '' === $status ? 'on' : ''; ?>" href="<?php echo esc_url( $base ); ?>">همه</a>
		<a class="mam-chip <?php echo 'pending' === $status ? 'on' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'status', 'pending', $base ) ); ?>">در انتظار تأیید</a>
		<a class="mam-chip <?php echo 'completed' === $status ? 'on' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'status', 'completed', $base ) ); ?>">تکمیل شده</a>
		<a class="mam-chip <?php echo 'failed' === $status ? 'on' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'status', 'failed', $base ) ); ?>">ناموفق</a>
		<a class="mam-chip <?php echo 'card' === $method ? 'on' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'method', 'card', $base ) ); ?>">کارت به کارت</a>
	</div>

	<div class="mam-card">
		<div class="mam-table-wrap">
			<table class="mam-table">
				<thead>
					<tr><th>#</th><th>کاربر</th><th>پلن</th><th>مبلغ</th><th>روش</th><th>پیگیری/رسید</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr>
				</thead>
				<tbody>
					<?php if ( empty( $records ) ) : ?>
						<tr><td colspan="9" class="mam-empty">رکوردی یافت نشد.</td></tr>
					<?php else : foreach ( $records as $r ) :
						$user = get_userdata( $r->user_id );
						$name = $user ? $user->display_name : ( 'کاربر #' . $r->user_id );
						$receipt = $r->receipt_id ? wp_get_attachment_url( $r->receipt_id ) : '';
						?>
						<tr id="mam-row-<?php echo (int) $r->id; ?>">
							<td>#<?php echo (int) $r->id; ?></td>
							<td><?php echo esc_html( $name ); ?></td>
							<td><?php echo esc_html( $r->plan_label ); ?></td>
							<td><?php echo esc_html( number_format( (float) $r->amount ) ); ?> ت</td>
							<td><?php echo esc_html( mam_method_label( $r->method ) ); ?></td>
							<td>
								<?php if ( $r->txn_ref ) : ?><div class="mam-mono"><?php echo esc_html( $r->txn_ref ); ?></div><?php endif; ?>
								<?php if ( $receipt ) : ?><a href="<?php echo esc_url( $receipt ); ?>" target="_blank">مشاهده رسید</a><?php endif; ?>
							</td>
							<td class="mam-status-cell"><?php echo mam_status_badge( $r->status ); // phpcs:ignore ?></td>
							<td><?php echo esc_html( mysql2date( 'Y/m/d H:i', $r->created_at ) ); ?></td>
							<td>
								<?php if ( 'pending' === $r->status ) : ?>
									<button class="button button-primary mam-act" data-do="approve" data-id="<?php echo (int) $r->id; ?>">تأیید</button>
									<button class="button mam-act" data-do="reject" data-id="<?php echo (int) $r->id; ?>">رد</button>
								<?php else : ?>
									—
								<?php endif; ?>
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
