<?php
/**
 * Handles reservations if they need to be accessed at multiple times during a call, but never changed.
 */

//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Reservation_Manager {

	/**
	 * The single instance of the class.
	 *
	 * @var ER_Reservation_Manager|null
	 */
	protected static $instance = null;
	/**
	 * Array of loaded reservations
	 *
	 * @var ER_Reservation[]
	 */
	protected $reservations = array();

	/**
	 * Main ER_Reservation_Manager Instance.
	 *
	 * Ensures only one instance of ER_Reservation_Manager is loaded or can be loaded.
	 *
	 * @static
	 * @return ER_Reservation_Manager Main instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get reservation/s
	 *
	 * Only use these to get information never change these reservations
	 *
	 * @param bool $id
	 *
	 * @return bool|ER_Reservation|ER_Reservation[]
	 */
	public function get( $id = false ) {
		if ( $id ) {
			if ( is_integer( $id ) ) {
				if ( isset( $this->reservations[ $id ] ) ) {
					return $this->reservations[ $id ];
				} else {
					return er_get_reservation( $id );
				}
			}

			return false;
		}

		return $this->reservations;
	}

	/**
	 * @param array $data
	 *
	 * @return ER_Reservation|WP_Error
	 */
	public function create( $data ) {
		try {
			$reservation = new ER_Reservation( 0 );
			$errors      = $reservation->set_props( $data );

			if ( $reservation && $errors === true ) {
				return $reservation;
			}

			return $errors;
		} catch ( ER_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}
	}
}
