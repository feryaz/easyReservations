<?php
/**
 * Class ER_Receipt_Item_Data_Store file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER Order Item Data Store: Misc Order Item Data functions.
 */
class ER_Receipt_Item_Data_Store implements ER_Receipt_Item_Data_Store_Interface {

	/**
	 * Add an order item to an order.
	 *
	 * @param int    $object_id object ID.
	 * @param string $object_type object type (order/reservation).
	 * @param array  $item receipt_item_name and receipt_item_type.
	 *
	 * @return int Receipt Item ID
	 */
	public function add_receipt_item( $object_id, $object_type, $item ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'receipt_items',
			array(
				'receipt_item_name'   => $item['receipt_item_name'],
				'receipt_item_type'   => $item['receipt_item_type'],
				'receipt_object_type' => $object_type,
				'receipt_object_id'   => $object_id,
			),
			array(
				'%s',
				'%s',
				'%d',
			)
		);

		$item_id = absint( $wpdb->insert_id );

		$this->clear_caches( $item_id, $object_id, $object_type );

		return $item_id;	}

	/**
	 * Update an order item.
	 *
	 * @param int   $item_id Item ID.
	 * @param array $item receipt_item_name or receipt_item_type.
	 *
	 * @return boolean
	 */
	public function update_receipt_item( $item_id, $item ) {
		global $wpdb;
		$updated = $wpdb->update( $wpdb->prefix . 'receipt_items', $item, array( 'receipt_item_id' => $item_id ) );
		$this->clear_caches( $item_id, null, null );

		return $updated;
	}

	/**
	 * Delete an order item.
	 *
	 * @param int $item_id Item ID.
	 */
	public function delete_receipt_item( $item_id ) {
		global $wpdb;

		// Load the receipt data before the deletion, since after, it won't exist in the database.
		$object_id = $this->get_object_id_by_receipt_item_id( $item_id );
		$object_type = $this->get_object_type_by_receipt_item_id( $item_id );

		$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}receipt_itemmeta itemmeta INNER JOIN {$wpdb->prefix}receipt_items items WHERE itemmeta.receipt_item_id = items.receipt_item_id and items.receipt_object_id = %d", $item_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}receipt_items WHERE receipt_object_id = %d", $item_id ) );

		$this->clear_caches( $item_id, $object_id, $object_type );
	}

	/**
	 * Update term meta.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @param string $prev_value (default: '').
	 *
	 * @return bool
	 */
	public function update_metadata( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_metadata( 'receipt_item', $item_id, wp_slash( $meta_key ), is_string( $meta_value ) ? wp_slash( $meta_value ) : $meta_value, $prev_value );
	}

	/**
	 * Add term meta.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @param bool   $unique (default: false).
	 *
	 * @return int    New row ID or 0
	 */
	public function add_metadata( $item_id, $meta_key, $meta_value, $unique = false ) {
		return add_metadata( 'receipt_item', $item_id, $meta_key, is_string( $meta_value ) ? wp_slash( $meta_value ) : $meta_value, $unique );
	}

	/**
	 * Delete term meta.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $meta_key Meta key.
	 * @param string $meta_value (default: '').
	 * @param bool   $delete_all (default: false).
	 *
	 * @return bool
	 */
	public function delete_metadata( $item_id, $meta_key, $meta_value = '', $delete_all = false ) {
		return delete_metadata( 'receipt_item', $item_id, $meta_key, is_string( $meta_value ) ? wp_slash( $meta_value ) : $meta_value, $delete_all );
	}

	/**
	 * Get term meta.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $key Meta key.
	 * @param bool   $single (default: true).
	 *
	 * @return mixed
	 */
	public function get_metadata( $item_id, $key, $single = true ) {
		return get_metadata( 'receipt_item', $item_id, $key, $single );
	}

	/**
	 * Get order ID by order item ID.
	 *
	 * @param int $item_id Item ID.
	 *
	 * @return int
	 */
	public function get_object_id_by_receipt_item_id( $item_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT receipt_object_id FROM {$wpdb->prefix}receipt_items WHERE receipt_item_id = %d",
				$item_id
			)
		);
	}

	/**
	 * Get order ID by order item ID.
	 *
	 * @param int $item_id Item ID.
	 *
	 * @return string
	 */
	public function get_object_type_by_receipt_item_id( $item_id ) {
		global $wpdb;

		return sanitize_key(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT receipt_object_type FROM {$wpdb->prefix}receipt_items WHERE receipt_item_id = %d",
					$item_id
				)
			)
		);
	}

	/**
	 * Get the order item type based on Item ID.
	 *
	 * @param int $item_id Item ID.
	 *
	 * @return string|null Order item type or null if no order item entry found.
	 */
	public function get_receipt_item_type( $item_id ) {
		global $wpdb;
		$order_item_type = $wpdb->get_var( $wpdb->prepare( "SELECT receipt_item_type FROM {$wpdb->prefix}receipt_items WHERE receipt_item_id = %d LIMIT 1;", $item_id ) );

		return $order_item_type;
	}

	/**
	 * Clear meta cache.
	 *
	 * @param int      $item_id Item ID.
	 * @param int|null $object_id Order ID. If not set, it will be loaded using the item ID.
	 */
	protected function clear_caches( $item_id, $object_id, $object_type ) {
		wp_cache_delete( 'item-' . $item_id, 'receipt-items' );

		if ( ! $object_id ) {
			$object_id = $this->get_object_id_by_receipt_item_id( $item_id );
			$object_type = $this->get_object_type_by_receipt_item_id( $item_id );
		}
		if ( $object_id ) {
			wp_cache_delete( 'receipt-items-' . $object_id, $object_type );
		}
	}
}
