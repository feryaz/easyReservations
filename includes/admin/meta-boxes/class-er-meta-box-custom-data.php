<?php
/**
 * Custom Data
 *
 * Functions for saving the custom data meta box.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * ER_Meta_Box_Order_Data Class.
 */
class ER_Meta_Box_Custom_Data {

	/**
	 * Save meta box data.
	 *
	 * @param int $order_id Object ID.
	 */
	public static function save( $object_id ) {
		if ( isset( $_POST['custom_data'] ) ) {
			if ( ! wp_verify_nonce( wp_unslash( $_POST['easyreservations_meta_nonce'] ), 'easyreservations_save_data' ) ) {
				wp_die();
			}

			$custom_data = array_unique( array_map( 'absint', $_POST['custom_data'] ) );

			foreach ( $custom_data as $item_id ) {
				$item = er_receipt_get_item( absint( $item_id ), $object_id );

				if ( ! $item ) {
					continue;
				}

				$custom_id = $item->get_custom_id();

				if ( isset( $_POST[ 'er-custom-' . $custom_id ] ) ) {
					$custom = ER_Custom_Data::get_data( $custom_id );

					if ( $custom ) {
						$item->set_custom_id( $custom['custom_id'] );
						$item->set_custom_value( $custom['custom_value'] );
						$item->set_custom_display( $custom['custom_display'] );
						$item->set_name( $custom['custom_title'] );

						// Allow other plugins to change item object before it is saved.
						do_action( 'easyreservations_before_save_order_item', $item );

						$item->save();
					}
				}
			}
		}
	}
}
