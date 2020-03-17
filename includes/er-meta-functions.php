<?php
defined( 'ABSPATH' ) || exit;

// hook into init for single site, priority 0 = highest priority
add_action( 'init', 'er_reservation_meta_integrate_wpdb', 0 );
// hook in to switch blog to support multisite
add_action( 'switch_blog', 'er_reservation_meta_integrate_wpdb', 0 );

/**
 * Integrates reservationmeta table with $wpdb
 *
 */
function er_reservation_meta_integrate_wpdb() {
	global $wpdb;

	$wpdb->reservationmeta  = $wpdb->prefix . 'reservationmeta';
	$wpdb->receipt_itemmeta = $wpdb->prefix . 'receipt_itemmeta';
	$wpdb->tables[]         = 'reservationmeta';
	$wpdb->tables[]         = 'receipt_itemmeta';

	return;
}

/**
 * Adds meta data field to a reservation.
 *
 * @param int    $reservation_id Badge ID.
 * @param string $meta_key Metadata name.
 * @param mixed  $meta_value Metadata value.
 * @param bool   $unique Optional, default is false. Whether the same key should not be added.
 *
 * @return int|false Meta ID on success, false on failure.
 */
function add_reservation_meta( $reservation_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'reservation', $reservation_id, $meta_key, $meta_value, $unique );
}

/**
 * Removes metadata matching criteria from a reservation.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @param int    $reservation_id Badge ID
 * @param string $meta_key Metadata name.
 * @param mixed  $meta_value Optional. Metadata value.
 *
 * @return bool True on success, false on failure.
 */
function delete_reservation_meta( $reservation_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'reservation', $reservation_id, $meta_key, $meta_value );
}

/**
 * Retrieve meta field for a reservation.
 *
 * @param int    $reservation_id Badge ID.
 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool   $single Whether to return a single value.
 *
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
 */
function get_reservation_meta( $reservation_id, $key = '', $single = true ) {
	return get_metadata( 'reservation', $reservation_id, $key, $single );
}

/**
 * Update reservation meta field based on reservation ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and reservation ID.
 *
 * If the meta field for the user does not exist, it will be added.
 *
 * @param int    $reservation_id Badge ID.
 * @param string $meta_key Metadata key.
 * @param mixed  $meta_value Metadata value.
 * @param mixed  $prev_value Optional. Previous value to check before removing.
 *
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function update_reservation_meta( $reservation_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'reservation', $reservation_id, $meta_key, $meta_value, $prev_value );
}
