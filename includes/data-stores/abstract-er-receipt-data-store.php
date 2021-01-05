<?php
/**
 * Abstract_ER_Receipt_Data_Store class file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Order Data Store: Stored in CPT.
 */
abstract class Abstract_ER_Receipt_Data_Store extends ER_Data_Store_WP {

	/**
	 * Read order items of a specific type from the database for this order.
	 *
	 * @param ER_Order|ER_Reservation $object object.
	 * @param string                  $type Order item type.
	 *
	 * @return array
	 */
	public function read_items( $object, $type ) {
		global $wpdb;

		// Get from cache if available.
		$items = 0 < $object->get_id() ? wp_cache_get( 'receipt-items-' . $object->get_id(), $object->get_object_type() ) : false;

		if ( false === $items ) {
			$items = $wpdb->get_results(
				$wpdb->prepare( "SELECT receipt_object_id, receipt_object_type, receipt_item_id, receipt_item_name, receipt_item_type FROM {$wpdb->prefix}receipt_items WHERE receipt_object_id = %d ORDER BY receipt_item_id;", $object->get_id() )
			);

			foreach ( $items as $item ) {
				wp_cache_set( 'item-' . $item->receipt_item_id, $item, 'receipt-items' );
			}
			if ( 0 < $object->get_id() ) {
				wp_cache_set( 'receipt-items-' . $object->get_id(), $items, $object->get_object_type() );
			}
		}

		$items = wp_list_filter( $items, array( 'receipt_item_type' => $type ) );

		if ( ! empty( $items ) ) {
			$items = array_map( array(
				$object,
				'cast_item'
			), array_combine( wp_list_pluck( $items, 'receipt_item_id' ), $items ) );
		} else {
			$items = array();
		}

		return $items;
	}

	/**
	 * Remove all line items (reservations, coupons, taxes) from the order.
	 *
	 * @param ER_Order|ER_Reservation $object object.
	 * @param string                  $type Order item type. Default null.
	 */
	public function delete_items( $object, $type = null ) {
		global $wpdb;
		if ( ! empty( $type ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}receipt_itemmeta itemmeta INNER JOIN {$wpdb->prefix}receipt_items items WHERE itemmeta.receipt_item_id = items.receipt_item_id AND items.receipt_object_id = %d AND items.receipt_item_type = %s", $object->get_id(), $type ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}receipt_items WHERE receipt_object_id = %d AND receipt_item_type = %s", $object->get_id(), $type ) );
		} else {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}receipt_itemmeta itemmeta INNER JOIN {$wpdb->prefix}receipt_items items WHERE itemmeta.receipt_item_id = items.receipt_item_id and items.receipt_object_id = %d", $object->get_id() ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}receipt_items WHERE receipt_object_id = %d", $object->get_id() ) );
		}
		$this->clear_caches( $object );
	}

	/**
	 * Return the receipt type of a given item
	 *
	 * @param int $receipt_item_id Receipt item id.
	 *
	 * @return string Receipt Item type
	 */
	public function get_receipt_item_type( $receipt_item_id ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT receipt_item_type FROM {$wpdb->prefix}receipt_items WHERE  receipt_item_id = %d;", $receipt_item_id ) );
	}

	/**
	 * Clear any caches.
	 *
	 * @param ER_Order|ER_Reservation $object object.
	 */
	protected function clear_caches( &$object ) {
		wp_cache_delete( 'receipt-items-' . $object->get_id(), $object->get_object_type() );
	}
}