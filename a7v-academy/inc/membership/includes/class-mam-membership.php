<?php
/**
 * Membership logic: status, dates, remaining time, activation, expiry.
 *
 * State is stored in user meta so the existing WP/WooCommerce account is
 * never modified — we only ADD meta keys:
 *   _mam_expiry  (Y-m-d H:i:s)   subscription end
 *   _mam_start   (Y-m-d H:i:s)   subscription start
 *   _mam_plan    (plan key)
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_Membership {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'mam_daily_expiry_check', array( $this, 'run_expiry_check' ) );
	}

	/**
	 * Get full status for a user.
	 *
	 * @return array { state: active|expired|none, start, end, plan, days_left, total_days, percent }
	 */
	public function get_status( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		$out = array(
			'state'      => 'none',
			'state_label'=> 'بدون اشتراک',
			'start'      => '',
			'end'        => '',
			'plan'       => '',
			'plan_label' => '',
			'days_left'  => 0,
			'total_days' => 0,
			'percent'    => 0,
		);
		if ( ! $user_id ) {
			return $out;
		}

		$end   = get_user_meta( $user_id, '_mam_expiry', true );
		$start = get_user_meta( $user_id, '_mam_start', true );
		$plan  = get_user_meta( $user_id, '_mam_plan', true );

		if ( ! $end ) {
			return $out;
		}

		$now_ts   = current_time( 'timestamp' );
		$end_ts   = strtotime( $end );
		$start_ts = $start ? strtotime( $start ) : $now_ts;

		$out['start']      = $start;
		$out['end']        = $end;
		$out['plan']       = $plan;
		$plan_obj          = $plan ? MAM_Settings::plan( $plan ) : null;
		$out['plan_label'] = $plan_obj ? $plan_obj['label'] : $plan;

		if ( $end_ts > $now_ts ) {
			$out['state']       = 'active';
			$out['state_label'] = 'عضو ویژه';
			$out['days_left']   = (int) ceil( ( $end_ts - $now_ts ) / DAY_IN_SECONDS );
			$total              = max( 1, (int) round( ( $end_ts - $start_ts ) / DAY_IN_SECONDS ) );
			$out['total_days']  = $total;
			$elapsed            = max( 0, $total - $out['days_left'] );
			$out['percent']     = min( 100, max( 0, (int) round( ( $out['days_left'] / $total ) * 100 ) ) );
			unset( $elapsed );
		} else {
			$out['state']       = 'expired';
			$out['state_label'] = 'منقضی شده';
			$out['days_left']   = 0;
			$out['percent']     = 0;
		}

		return $out;
	}

	/** Is the user an active VIP member? */
	public function is_active( $user_id = 0 ) {
		$s = $this->get_status( $user_id );
		return 'active' === $s['state'];
	}

	/**
	 * Activate / extend a membership.
	 *
	 * @param int    $user_id User.
	 * @param string $plan_key Plan key.
	 * @param int    $days     Optional explicit duration (else from plan).
	 * @return array New status.
	 */
	public function activate( $user_id, $plan_key, $days = 0 ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return $this->get_status( 0 );
		}
		$plan = MAM_Settings::plan( $plan_key );
		if ( ! $days ) {
			$days = $plan ? (int) $plan['days'] : 30;
		}

		$now_ts     = current_time( 'timestamp' );
		$current_end= get_user_meta( $user_id, '_mam_expiry', true );
		$base_ts    = ( $current_end && strtotime( $current_end ) > $now_ts ) ? strtotime( $current_end ) : $now_ts;

		// Keep original start if extending an active membership.
		$start = get_user_meta( $user_id, '_mam_start', true );
		if ( ! $start || strtotime( (string) $current_end ) <= $now_ts ) {
			$start = gmdate( 'Y-m-d H:i:s', $now_ts );
		}
		$end = gmdate( 'Y-m-d H:i:s', $base_ts + ( $days * DAY_IN_SECONDS ) );

		update_user_meta( $user_id, '_mam_start', $start );
		update_user_meta( $user_id, '_mam_expiry', $end );
		update_user_meta( $user_id, '_mam_plan', $plan_key );

		do_action( 'mam_membership_activated', $user_id, $plan_key, $start, $end );

		return $this->get_status( $user_id );
	}

	/** Manually set the expiry date (admin edit). */
	public function set_expiry( $user_id, $end_datetime ) {
		update_user_meta( (int) $user_id, '_mam_expiry', sanitize_text_field( $end_datetime ) );
	}

	/** Cancel / revoke a membership. */
	public function cancel( $user_id ) {
		update_user_meta( (int) $user_id, '_mam_expiry', gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - DAY_IN_SECONDS ) );
		do_action( 'mam_membership_canceled', (int) $user_id );
	}

	/**
	 * Count members by state (active / expired). Uses a user meta query.
	 */
	public function count_by_status( $state = 'active' ) {
		$now = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
		$compare = 'active' === $state ? '>' : '<=';
		$q = new WP_User_Query( array(
			'meta_key'    => '_mam_expiry',
			'meta_value'  => $now,
			'meta_compare'=> $compare,
			'meta_type'   => 'DATETIME',
			'fields'      => 'ID',
			'number'      => -1,
			'count_total' => true,
		) );
		return (int) $q->get_total();
	}

	/** Count users registered in the last N days. */
	public function new_users_count( $days = 30 ) {
		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days", current_time( 'timestamp' ) ) );
		$q = new WP_User_Query( array(
			'date_query'  => array( array( 'after' => $since, 'column' => 'user_registered' ) ),
			'fields'      => 'ID',
			'number'      => -1,
			'count_total' => true,
		) );
		return (int) $q->get_total();
	}

	/** Daily cron: fire an action for freshly expired members (hook for emails etc.). */
	public function run_expiry_check() {
		if ( ! mam_get_setting( 'auto_expire', 1 ) ) {
			return;
		}
		do_action( 'mam_expiry_check_ran' );
	}
}
