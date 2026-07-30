<?php
/**
 * Admin dashboard view.
 *
 * @package MafiaAcademyMembership
 * @var int   $members
 * @var int   $new_users
 * @var float $sales_today
 * @var float $sales_month
 * @var float $revenue_all
 * @var array $labels
 * @var array $rev_series
 * @var array $sub_series
 * @var array $breakdown
 * @var array $latest
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$chart_data = array(
	'labels'    => $labels,
	'revenue'   => array_map( 'floatval', $rev_series ),
	'subs'      => array_map( 'intval', $sub_series ),
	'breakdown' => array(
		(int) $breakdown['active'],
		(int) $breakdown['expired'],
		(int) $breakdown['pending'],
	),
);
?>
<div class="wrap mam-wrap">
	<div class="mam-head">
		<h1><span class="mam-logo">M</span> پنل مدیریت | پیشخوان</h1>
		<span class="mam-sub"><?php echo esc_html( mam_get_setting( 'brand_name', 'آکادمی مافیا' ) ); ?></span>
	</div>

	<div class="mam-stats">
		<div class="mam-stat">
			<div class="mam-stat-label">اعضای ویژه</div>
			<div class="mam-stat-value"><?php echo esc_html( number_format_i18n( $members ) ); ?></div>
			<div class="mam-stat-ic">👑</div>
		</div>
		<div class="mam-stat">
			<div class="mam-stat-label">کاربران جدید (۳۰ روز)</div>
			<div class="mam-stat-value"><?php echo esc_html( number_format_i18n( $new_users ) ); ?></div>
			<div class="mam-stat-ic">🧑‍🤝‍🧑</div>
		</div>
		<div class="mam-stat">
			<div class="mam-stat-label">فروش امروز</div>
			<div class="mam-stat-value"><?php echo esc_html( $this->fmt( $sales_today ) ); ?><small>ت</small></div>
			<div class="mam-stat-ic">💰</div>
		</div>
		<div class="mam-stat">
			<div class="mam-stat-label">فروش این ماه</div>
			<div class="mam-stat-value"><?php echo esc_html( $this->fmt( $sales_month ) ); ?><small>ت</small></div>
			<div class="mam-stat-ic">📈</div>
		</div>
		<div class="mam-stat mam-stat-gold">
			<div class="mam-stat-label">درآمد کل</div>
			<div class="mam-stat-value"><?php echo esc_html( $this->fmt( $revenue_all ) ); ?><small>ت</small></div>
			<div class="mam-stat-ic">🏆</div>
		</div>
	</div>

	<div class="mam-grid-2">
		<div class="mam-card">
			<div class="mam-card-title">نمودار فروش (۱۴ روز اخیر)</div>
			<canvas id="mamSalesChart" height="120"></canvas>
		</div>
		<div class="mam-card">
			<div class="mam-card-title">نسبت اشتراک‌ها</div>
			<canvas id="mamDonut" height="120"></canvas>
			<div class="mam-legend">
				<span><i class="dot active"></i> فعال (<?php echo (int) $breakdown['active']; ?>)</span>
				<span><i class="dot expired"></i> منقضی (<?php echo (int) $breakdown['expired']; ?>)</span>
				<span><i class="dot pending"></i> در انتظار (<?php echo (int) $breakdown['pending']; ?>)</span>
			</div>
		</div>
	</div>

	<div class="mam-card">
		<div class="mam-card-title">نمودار رشد اعضا (۱۴ روز اخیر)</div>
		<canvas id="mamSubsChart" height="90"></canvas>
	</div>

	<div class="mam-card">
		<div class="mam-card-title">آخرین خریدها و پرداخت‌ها</div>
		<div class="mam-table-wrap">
			<table class="mam-table">
				<thead>
					<tr><th>کاربر</th><th>پلن</th><th>مبلغ</th><th>روش</th><th>وضعیت</th><th>تاریخ</th></tr>
				</thead>
				<tbody>
					<?php if ( empty( $latest ) ) : ?>
						<tr><td colspan="6" class="mam-empty">هنوز پرداختی ثبت نشده است.</td></tr>
					<?php else : foreach ( $latest as $r ) :
						$user = get_userdata( $r->user_id );
						$name = $user ? $user->display_name : ( 'کاربر #' . $r->user_id );
						?>
						<tr>
							<td><?php echo esc_html( $name ); ?></td>
							<td><?php echo esc_html( $r->plan_label ); ?></td>
							<td><?php echo esc_html( $this->fmt( $r->amount ) ); ?> ت</td>
							<td><?php echo esc_html( mam_method_label( $r->method ) ); ?></td>
							<td><?php echo mam_status_badge( $r->status ); // phpcs:ignore ?></td>
							<td><?php echo esc_html( mysql2date( 'Y/m/d', $r->created_at ) ); ?></td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<script type="application/json" id="mam-chart-data"><?php echo wp_json_encode( $chart_data ); ?></script>
</div>
