<?php
defined( 'ABSPATH' ) || exit;

/**
 * Load item from db and cast into correct class
 *
 * @param object|int|ER_Receipt_Item $item_id
 *
 * @return bool|ER_Receipt_Item
 */
function er_receipt_get_item( $item_id, $object_id ) {
	if ( is_numeric( $item_id ) ) {
		$item_type = er_receipt_get_item_type( $item_id, $object_id );
		$id        = $item_id;
	} elseif ( $item_id instanceof ER_Receipt_Item ) {
		$item_type = $item_id->get_type();
		$id        = $item_id->get_id();
	} elseif ( is_object( $item_id ) && ! empty( $item_id->receipt_item_type ) ) {
		$id        = $item_id->receipt_item_id;
		$item_type = $item_id->receipt_item_type;
	} else {
		$item_type = false;
		$id        = false;
	}

	if ( $id && $item_type ) {
		$class_name = false;

		switch ( $item_type ) {
			case 'line_item':
			case 'fee':
				$class_name = 'ER_Receipt_Item_Fee';
				break;
			case 'reservation':
				$class_name = 'ER_Receipt_Item_Reservation';
				break;
			case 'resource':
				$class_name = 'ER_Receipt_Item_Resource';
				break;
			case 'coupon':
				$class_name = 'ER_Receipt_Item_Coupon';
				break;
			case 'custom':
				$class_name = 'ER_Receipt_Item_Custom';
				break;
			case 'tax':
				$class_name = 'ER_Receipt_Item_Tax';
				break;
		}

		$class_name = apply_filters( 'easyreservations_receipt_item_class_name', $class_name, $item_type, $id );

		if ( $class_name && class_exists( $class_name ) ) {
			try {

				return new $class_name( absint( $id ) );
			} catch ( Exception $e ) {
				return false;
			}
		}
	}

	return false;
}

/**
 * Save order items. Uses the CRUD.
 *
 * @param ER_Reservation|ER_Order $object Order ID.
 * @param array                   $items Order items to save.
 */
function er_save_receipt_items( $object, $items ) {
	// Allow other plugins to check change in receipt items before they are saved.
	do_action( 'easyreservations_before_save_receipt_items', $object, $items );

	// Line items and fees.
	if ( isset( $items['receipt_item_id'] ) ) {
		$data_keys = array(
			'line_tax'          => array(),
			'line_subtotal_tax' => array(),
			'receipt_item_name' => null,
			'line_total'        => null,
			'line_subtotal'     => null,
		);

		foreach ( $items['receipt_item_id'] as $item_id ) {
			$item = er_receipt_get_item( absint( $item_id ), $object->get_id() );

			if ( ! $item ) {
				continue;
			}

			$item_data = array();

			foreach ( $data_keys as $key => $default ) {
				$item_data[ $key ] = isset( $items[ $key ][ $item_id ] ) ? er_check_invalid_utf8( wp_unslash( $items[ $key ][ $item_id ] ) ) : $default;
			}

			$item->set_props(
				array(
					'name'     => $item_data['receipt_item_name'],
					'total'    => $item_data['line_total'],
					'subtotal' => $item_data['line_subtotal'],
					'taxes'    => array(
						'total'    => $item_data['line_tax'],
						'subtotal' => $item_data['line_subtotal_tax'],
					),
				)
			);

			if ( isset( $items['meta_key'][ $item_id ], $items['meta_value'][ $item_id ] ) ) {
				foreach ( $items['meta_key'][ $item_id ] as $meta_id => $meta_key ) {
					$meta_key   = substr( wp_unslash( $meta_key ), 0, 255 );
					$meta_value = isset( $items['meta_value'][ $item_id ][ $meta_id ] ) ? wp_unslash( $items['meta_value'][ $item_id ][ $meta_id ] ) : '';

					if ( '' === $meta_key && '' === $meta_value ) {
						if ( ! strstr( $meta_id, 'new-' ) ) {
							$item->delete_meta_data_by_mid( $meta_id );
						}
					} elseif ( strstr( $meta_id, 'new-' ) ) {
						$item->add_meta_data( $meta_key, $meta_value, false );
					} else {
						$item->update_meta_data( $meta_key, $meta_value, $meta_id );
					}
				}
			}

			// Allow other plugins to change item object before it is saved.
			do_action( 'easyreservations_before_save_receipt_item', $item );

			$item->save();
		}
	}

	$object->update_taxes( false );
	$object->calculate_totals( false );
	$object->save();

	// Inform other plugins that the items have been saved.
	do_action( 'easyreservations_saved_receipt_items', $object, $items );
}

/**
 * Get an receipt items type
 *
 * @param int $item__id
 * @param int $object_id
 *
 * @return null|string
 */
function er_receipt_get_item_type( $item_id, $object_id ) {
	global $wpdb;

	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT DISTINCT receipt_item_type FROM {$wpdb->prefix}receipt_items WHERE receipt_object_id = %d and receipt_item_id = %d;",
			absint( $object_id ),
			absint( $item_id )
		)
	);
}

/**
 * Delete an item from the order it belongs to based on item id.
 *
 * @param int $item_id Item ID.
 * @param int $order_id Order ID.
 *
 * @return bool
 */
function er_receipt_delete_item( $item_id, $object_id ) {
	$item_id = absint( $item_id );

	if ( ! $item_id ) {
		return false;
	}

	$item = er_receipt_get_item( $item_id, $object_id );

	if ( $item ) {
		do_action( 'easyreservations_before_delete_receipt_item', $item_id );

		$item->delete();

		do_action( 'easyreservations_delete_receipt_item', $item_id );

		return true;
	}

	return false;
}
