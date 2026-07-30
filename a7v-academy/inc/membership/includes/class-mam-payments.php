<?php
/**
 * Payments / subscriptions data layer (custom table CRUD + reporting).
 *
 * @package MafiaAcademyMembership
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MAM_Payments {

	public static function table() {
		return MAM_Install::table();
	}

	/**
	 * Insert a new payment/subscription record.
	 *
	 * @param array $data Column => value.
	 * @return int Inserted id (0 on failure).
	 */
	public static function create( $data ) {
		global $wpdb;
		$now  = current_time( 'mysql' );
		$row  = wp_parse_args( $data, array(
			'user_id'       => 0,
			'plan_key'      => '',
			'plan_label'    => '',
			'amount'        => 0,
			'method'        => '',
			'status'        => 'pending',
			'txn_ref'       => '',
			'authority'     => '',
			'duration_days' => 0,
			'start_date'    => null,
			'end_date'      => null,
			'receipt_id'    => 0,
			'form_data'     => '',
			'note'          => '',
			'created_at'    => $now,
			'updated_at'    => $now,
		) );

		if ( is_array( $row['form_data'] ) ) {
			$row['form_data'] = wp_json_encode( $row['form_data'] );
		}

		$wpdb->insert( self::table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->insert_id;
	}

	/** Update a record by id. */
	public static function update( $id, $data ) {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql' );
		if ( isset( $data['form_data'] ) && is_array( $data['form_data'] ) ) {
			$data['form_data'] = wp_json_encode( $data['form_data'] );
		}
		return $wpdb->update( self::table(), $data, array( 'id' => (int) $id ) ); // phpcs:ignore
	}

	/** Get a single record. */
	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', (int) $id ) ); // phpcs:ignore
	}

	/** Get a record by ZarinPal/Zibal authority. */
	public static function get_by_authority( $authority ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE authority = %s ORDER BY id DESC LIMIT 1', $authority ) ); // phpcs:ignore
	}

	/**
	 * Query records with optional filters.
	 *
	 * @param array $args status, method, user_id, search, limit, offset, orderby, order.
	 * @return array
	 */
	public static function query( $args = array() ) {
		global $wpdb;
		$args = wp_parse_args( $args, array(
			'status'  => '',
			'method'  => '',
			'user_id' => 0,
			'limit'   => 20,
			'offset'  => 0,
			'orderby' => 'id',
			'order'   => 'DESC',
		) );

		$where = array( '1=1' );
		$prep  = array();

		if ( $args['status'] ) { $where[] = 'status = %s'; $prep[] = $args['status']; }
		if ( $args['method'] ) { $where[] = 'method = %s'; $prep[] = $args['method']; }
		if ( $args['user_id'] ) { $where[] = 'user_id = %d'; $prep[] = (int) $args['user_id']; }

		$orderby = in_array( $args['orderby'], array( 'id', 'amount', 'created_at', 'end_date' ), true ) ? $args['orderby'] : 'id';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$sql  = 'SELECT * FROM ' . self::table() . ' WHERE ' . implode( ' AND ', $where );
		$sql .= " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$prep[] = (int) $args['limit'];
		$prep[] = (int) $args['offset'];

		return $wpdb->get_results( $wpdb->prepare( $sql, $prep ) ); // phpcs:ignore
	}

	/** Count records for a filter. */
	public static function count( $args = array() ) {
		global $wpdb;
		$where = array( '1=1' );
		$prep  = array();
		if ( ! empty( $args['status'] ) ) { $where[] = 'status = %s'; $prep[] = $args['status']; }
		if ( ! empty( $args['method'] ) ) { $where[] = 'method = %s'; $prep[] = $args['method']; }
		$sql = 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE ' . implode( ' AND ', $where );
		if ( $prep ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $prep ) ); // phpcs:ignore
		}
		return (int) $wpdb->get_var( $sql ); // phpcs:ignore
	}

	/* ------------------------------------------------------------------
	 *  Reporting helpers for the admin dashboard
	 * ------------------------------------------------------------------ */

	/** Sum of completed revenue for a date range (mysql datetime strings). */
	public static function revenue_between( $from, $to ) {
		global $wpdb;
		return (float) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
			'SELECT COALESCE(SUM(amount),0) FROM ' . self::table() . " WHERE status = 'completed' AND updated_at BETWEEN %s AND %s",
			$from,
			$to
		) );
	}

	/** Total lifetime revenue. */
	public static function revenue_total() {
		global $wpdb;
		return (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM " . self::table() . " WHERE status = 'completed'" ); // phpcs:ignore
	}

	/** Revenue grouped by day for the last N days. Returns [ 'Y-m-d' => amount ]. */
	public static function revenue_by_day( $days = 14 ) {
		global $wpdb;
		$since = gmdate( 'Y-m-d 00:00:00', strtotime( "-{$days} days", current_time( 'timestamp' ) ) );
		$rows  = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			'SELECT DATE(updated_at) AS d, COALESCE(SUM(amount),0) AS total FROM ' . self::table() .
			" WHERE status = 'completed' AND updated_at >= %s GROUP BY DATE(updated_at) ORDER BY d ASC",
			$since
		) );
		$out = array();
		foreach ( (array) $rows as $r ) {
			$out[ $r->d ] = (float) $r->total;
		}
		return $out;
	}

	/** New subscribers (completed) grouped by day for last N days. */
	public static function subs_by_day( $days = 14 ) {
		global $wpdb;
		$since = gmdate( 'Y-m-d 00:00:00', strtotime( "-{$days} days", current_time( 'timestamp' ) ) );
		$rows  = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			'SELECT DATE(updated_at) AS d, COUNT(*) AS c FROM ' . self::table() .
			" WHERE status = 'completed' AND updated_at >= %s GROUP BY DATE(updated_at) ORDER BY d ASC",
			$since
		) );
		$out = array();
		foreach ( (array) $rows as $r ) {
			$out[ $r->d ] = (int) $r->c;
		}
		return $out;
	}

	/** Count status breakdown across active members. */
	public static function status_breakdown() {
		return array(
			'active'  => MAM_Membership::instance()->count_by_status( 'active' ),
			'expired' => MAM_Membership::instance()->count_by_status( 'expired' ),
			'pending' => self::count( array( 'status' => 'pending' ) ),
		);
	}
}
