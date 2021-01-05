<?php
/**
 * Receipt Data
 *
 * Functions for displaying the receipt items meta box.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * ER_Meta_Box_Receipt_Items Class.
 */
class ER_Meta_Box_Receipt_Items {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post|ER_Reservation $post
	 */
	public static function output( $post ) {
		if ( is_a( $post, 'ER_Reservation' ) ) {
			$theorder = $post;
		} else {
			global $post, $thepostid, $theorder;

			if ( ! is_int( $thepostid ) ) {
				$thepostid = $post->ID;
			}

			if ( ! is_object( $theorder ) ) {
				$theorder = er_get_order( $thepostid );
			}
		}

		$object = $theorder;

		include __DIR__ . '/views/html-receipt-items.php';
	}

	/**
	 * Save meta box data.
	 *
	 * @param int  $object
	 * @param bool $reservation
	 */
	public static function save( $object, $reservation = false ) {
		if ( ! wp_verify_nonce( wp_unslash( $_POST['easyreservations_meta_nonce'] ), 'easyreservations_save_data' ) ) {
			wp_die();
		}

		if ( $reservation === true ) {
			$object = er_get_reservation( absint( $object ) );
		} else {
			$object = er_get_order( absint( $object ) );
		}

		/**
		 * This $_POST variable's data has been validated and escaped
		 * inside `er_save_receipt_items()` function.
		 */
		er_save_receipt_items( $object, $_POST );
	}
}
