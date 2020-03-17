<?php
/**
 * Order Item Data Store Interface
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER Order Item Data Store Interface
 *
 * Functions that must be defined by the order item data store (for functions).
 */
interface ER_Receipt_Item_Data_Store_Interface {

	/**
	 * Add an order item to an order.
	 *
	 * @param int    $object_id object ID.
	 * @param string $object_type object type (order/reservation).
	 * @param array  $item receipt_item_name and receipt_item_type.
	 *
	 * @return int   Receipt Item ID
	 */
	public function add_receipt_item( $object_id, $object_type, $item );

	/**
	 * Update an order item.
	 *
	 * @param int   $item_id Item ID.
	 * @param array $item receipt_item_name or receipt_item_type.
	 *
	 * @return boolean
	 */
	public function update_receipt_item( $item_id, $item );

	/**
	 * Delete an order item.
	 *
	 * @param int $item_id Item ID.
	 */
	public function delete_receipt_item( $item_id );

	/**
	 * Update term meta.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @param string $prev_value Previous value (default: '').
	 *
	 * @return bool
	 */
	public function update_metadata( $item_id, $meta_key, $meta_value, $prev_value = '' );

	/**
	 * Add term meta.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @param bool   $unique Unique? (default: false).
	 *
	 * @return int    New row ID or 0
	 */
	public function add_metadata( $item_id, $meta_key, $meta_value, $unique = false );

	/**
	 * Delete term meta.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $meta_key Meta key.
	 * @param string $meta_value Meta value (default: '').
	 * @param bool   $delete_all Delete all matching entries? (default: false).
	 *
	 * @return bool
	 */
	public function delete_metadata( $item_id, $meta_key, $meta_value = '', $delete_all = false );

	/**
	 * Get term meta.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $key Meta key.
	 * @param bool   $single Store as single value and not serialised (default: true).
	 *
	 * @return mixed
	 */
	public function get_metadata( $item_id, $key, $single = true );

	/**
	 * Get order ID by order item ID.
	 *
	 * @param int $item_id Item ID.
	 *
	 * @return int
	 */
	public function get_object_id_by_receipt_item_id( $item_id );

	/**
	 * Get the order item type based on Item ID.
	 *
	 * @param int $item_id Item ID.
	 *
	 * @return string
	 */
	public function get_receipt_item_type( $item_id );
}
