<?php

defined( 'ABSPATH' ) || exit;

abstract class ER_Reservation_Status {

	//In database only to checkout - does not affect availability, gets deleted automatically
	const TEMPORARY = 'temporary';

	//Order in system - does not affect availability, has to be paid or approved
	const PENDING = 'pending';

	//Automatically or manually approved
	const APPROVED = 'approved';

	//Set manually by admin - same as approved
	const CHECKED = 'checked';

	//Set manually by admin - same as approved
	const COMPLETED = 'completed';

	//Cancelled by user or admin -> comes from order
	const CANCELLED = 'cancelled';

	//Trashed by admin
	const TRASH = 'trash';

	/**
	 * Reservation statuses array
	 *
	 * @return array
	 */
	public static function get_statuses() {
		$reservation_statuses = apply_filters( 'easyreservations_reservation_statuses', array(
			'temporary' => _x( 'Temporary', 'Reservation status', 'easyReservations' ),
			'pending'   => _x( 'Pending', 'Reservation status', 'easyReservations' ),
			'approved'  => _x( 'Approved', 'Reservation status', 'easyReservations' ),
			'checked'   => _x( 'Checked in', 'Reservation status', 'easyReservations' ),
			'completed' => _x( 'Completed', 'Reservation status', 'easyReservations' ),
			'cancelled' => _x( 'Canceled', 'Reservation status', 'easyReservations' ),
			'trash'     => _x( 'Trashed', 'Reservation status', 'easyReservations' ),
		) );

		return $reservation_statuses;
	}

	/**
	 * Get reservatio status title
	 *
	 * @param string $status
	 *
	 * @return bool|string
	 */
	public static function get_title( $status ) {
		$statuses = ER_Reservation_Status::get_statuses();

		if ( isset( $statuses[ $status ] ) ) {
			return $statuses[ $status ];
		}

		return false;
	}
}

abstract class ER_Order_Status {

	const PENDING = 'pending';
	const PROCESSING = 'processing';
	const ONHOLD = 'on-hold';
	const COMPLETED = 'completed';
	const CANCELLED = 'cancelled';
	const REFUNDED = 'refunded';
	const FAILED = 'failed';

	/**
	 * Order statuses array
	 *
	 * @return array
	 */
	public static function get_statuses() {
		$order_statuses = apply_filters( 'easyreservations_order_statuses', array(
			'pending'    => _x( 'Pending payment', 'Order status', 'easyReservations' ),
			'processing' => _x( 'Processing', 'Order status', 'easyReservations' ),
			'on-hold'    => _x( 'On hold', 'Order status', 'easyReservations' ),
			'completed'  => _x( 'Completed', 'Order status', 'easyReservations' ),
			'cancelled'  => _x( 'Cancelled', 'Order status', 'easyReservations' ),
			'refunded'   => _x( 'Refunded', 'Order status', 'easyReservations' ),
			'failed'     => _x( 'Failed', 'Order status', 'easyReservations' ),
		) );

		return $order_statuses;
	}

	/**
	 * Get order status title
	 *
	 * @param string $status
	 *
	 * @return bool|string
	 */
	public static function get_title( $status ) {
		$statuses = ER_Order_Status::get_statuses();

		if ( isset( $statuses[ $status ] ) ) {
			return $statuses[ $status ];
		}

		return false;
	}
}