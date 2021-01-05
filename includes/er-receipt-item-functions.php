<?php
/**
 * easyReservations Receipt Item Functions
 *
 * Functions for receipt specific things.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add a item to an receipt (for example a line item).
 *
 * @param int    $object_id Order ID.
 * @param string $object_type Object type.
 * @param array  $item_array Items list.
 *
 * @return int|bool         Item ID or false
 * @throws Exception        When `ER_Data_Store::load` validation fails.
 */
function er_add_receipt_item( $object_id, $object_type, $item_array ) {
	$object_id = absint( $object_id );

	if ( ! $object_id ) {
		return false;
	}

	$defaults = array(
		'receipt_item_name' => '',
		'receipt_item_type' => 'line_item',
	);

	$item_array = wp_parse_args( $item_array, $defaults );
	$data_store = ER_Data_Store::load( 'receipt-item' );
	$item_id    = $data_store->add_receipt_item( $object_id, $item_array );
	$item       = er_receipt_get_item( $item_id, $object_id );

	do_action( 'easyreservations_new_receipt_item', $item_id, $item, $object_id, $object_type );

	return $item_id;
}

/**
 * Update an item for an receipt.
 *
 * @param int   $item_id Item ID.
 * @param array $args Either `receipt_item_type` or `receipt_item_name`.
 *
 * @return bool          True if successfully updated, false otherwise.
 * @throws Exception     When `ER_Data_Store::load` validation fails.
 */
function er_update_receipt_item( $item_id, $args ) {
	$data_store = ER_Data_Store::load( 'receipt-item' );
	$update     = $data_store->update_receipt_item( $item_id, $args );

	if ( false === $update ) {
		return false;
	}

	do_action( 'easyreservations_update_receipt_item', $item_id, $args );

	return true;
}

/**
 * Delete an item from the receipt it belongs to based on item id.
 *
 * @param int $item_id Item ID.
 *
 * @return bool
 * @throws Exception    When `ER_Data_Store::load` validation fails.
 */
function er_delete_receipt_item( $item_id ) {
	$item_id = absint( $item_id );

	if ( ! $item_id ) {
		return false;
	}

	$data_store = ER_Data_Store::load( 'receipt-item' );

	do_action( 'easyreservations_before_delete_receipt_item', $item_id );

	$data_store->delete_receipt_item( $item_id );

	do_action( 'easyreservations_delete_receipt_item', $item_id );

	return true;
}

/**
 * easyReservations Order Item Meta API - Update term meta.
 *
 * @param int    $item_id Item ID.
 * @param string $meta_key Meta key.
 * @param string $meta_value Meta value.
 * @param string $prev_value Previous value (default: '').
 *
 * @return bool
 * @throws Exception         When `ER_Data_Store::load` validation fails.
 */
function er_update_receipt_item_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
	$data_store = ER_Data_Store::load( 'receipt-item' );
	if ( $data_store->update_metadata( $item_id, $meta_key, $meta_value, $prev_value ) ) {
		er_invalidate_cache_group( 'object_' . $item_id ); // Invalidate cache.

		return true;
	}

	return false;
}

/**
 * easyReservations Order Item Meta API - Add term meta.
 *
 * @param int    $item_id Item ID.
 * @param string $meta_key Meta key.
 * @param string $meta_value Meta value.
 * @param bool   $unique If meta data should be unique (default: false).
 *
 * @return int               New row ID or 0.
 * @throws Exception         When `ER_Data_Store::load` validation fails.
 */
function er_add_receipt_item_meta( $item_id, $meta_key, $meta_value, $unique = false ) {
	$data_store = ER_Data_Store::load( 'receipt-item' );
	$meta_id    = $data_store->add_metadata( $item_id, $meta_key, $meta_value, $unique );

	if ( $meta_id ) {
		er_invalidate_cache_group( 'object_' . $item_id ); // Invalidate cache.

		return $meta_id;
	}

	return 0;
}

/**
 * easyReservations Order Item Meta API - Delete term meta.
 *
 * @param int    $item_id Item ID.
 * @param string $meta_key Meta key.
 * @param string $meta_value Meta value (default: '').
 * @param bool   $delete_all Delete all meta data, defaults to `false`.
 *
 * @return bool
 * @throws Exception         When `ER_Data_Store::load` validation fails.
 */
function er_delete_receipt_item_meta( $item_id, $meta_key, $meta_value = '', $delete_all = false ) {
	$data_store = ER_Data_Store::load( 'receipt-item' );
	if ( $data_store->delete_metadata( $item_id, $meta_key, $meta_value, $delete_all ) ) {
		er_invalidate_cache_group( 'object_' . $item_id ); // Invalidate cache.

		return true;
	}

	return false;
}

/**
 * easyReservations Order Item Meta API - Get term meta.
 *
 * @param int    $item_id Item ID.
 * @param string $key Meta key.
 * @param bool   $single Whether to return a single value. (default: true).
 *
 * @return mixed
 * @throws Exception      When `ER_Data_Store::load` validation fails.
 */
function er_get_receipt_item_meta( $item_id, $key, $single = true ) {
	$data_store = ER_Data_Store::load( 'receipt-item' );

	return $data_store->get_metadata( $item_id, $key, $single );
}

/**
 * Get object ID by receipt item ID.
 *
 * @param int $item_id Item ID.
 *
 * @return int
 * @throws Exception    When `ER_Data_Store::load` validation fails.
 */
function er_get_object_id_by_receipt_item_id( $item_id ) {
	$data_store = ER_Data_Store::load( 'receipt-item' );

	return $data_store->get_object_id_by_receipt_item_id( $item_id );
}