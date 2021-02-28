<?php
/**
 * Abstract_ER_Reservation_Data_Store_CPT class file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Order Data Store: Stored in CPT.
 */
class ER_Reservation_Data_Store extends Abstract_ER_Receipt_Data_Store implements ER_Object_Data_Store_Interface,
	ER_Reservation_Data_Store_Interface {

	/**
	 * Internal meta type used to store reservation data.
	 *
	 * @var string
	 */
	protected $meta_type = 'reservation';

	/**
	 * Data stored in meta keys, but not considered "meta" for an reservation.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_date_created',
		'_date_modified',
		'_form_template',
		'_discount',
		'_discount_tax',
		'_reservation_total',
		'_reservation_tax',
		'_title',
		'_slot',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new reservation in the database.
	 *
	 * @param ER_Reservation $reservation Order object.
	 */
	public function create( &$reservation ) {
		global $wpdb;

		$now = time();
		$reservation->set_date_created( $now );
		$reservation->set_date_modified( $now );

		$id = $wpdb->insert(
			$wpdb->prefix . 'reservations',
			array(
				'arrival'   => $reservation->get_arrival( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $reservation->get_arrival( 'edit' )->getTimestamp() ) : '',
				'departure' => $reservation->get_departure( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $reservation->get_departure( 'edit' )->getTimestamp() ) : '',
				'order_id'  => $reservation->get_order_id( 'edit' ),
				'resource'  => $reservation->get_resource_id( 'edit' ),
				'adults'    => $reservation->get_adults( 'edit' ),
				'children'  => $reservation->get_children( 'edit' ),
				'space'     => $reservation->get_space( 'edit' ),
				'status'    => $reservation->get_status( 'edit' ),
			),
			array(
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
				'%s',
			)
		);

		if ( $id === false ) {
			throw new ER_Data_Exception( 'er_create_reservation', 'Reservation could not be created. Error: ' . $wpdb->last_error );
		}

		if ( $id && ! is_wp_error( $id ) ) {
			$reservation->set_id( $wpdb->insert_id );
			$this->update_reservation_meta( $reservation );
			$reservation->save_meta_data();
			$reservation->apply_changes();
			$this->clear_caches( $reservation );
		}
	}

	/**
	 * Method to read an reservation from the database.
	 *
	 * @param ER_Reservation $reservation Order object.
	 *
	 * @throws ER_Data_Exception If passed reservation is invalid.
	 */
	public function read( &$reservation ) {
		global $wpdb;

		$reservation->set_defaults();
		// Get from cache if available.
		$data = wp_cache_get( 'reservation-' . $reservation->get_id(), 'reservations' );

		if ( false === $data ) {
			$data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT order_id, status, arrival, departure, resource, space, adults, children FROM " . $wpdb->prefix . "reservations WHERE id=%d",
					$reservation->get_id()
				)
			);

			wp_cache_set( 'reservation-' . $reservation->get_id(), $data, 'reservations' );
		}

		if ( ! $reservation->get_id() || ! $data ) {
			throw new ER_Data_Exception( 'er_read_reservation', __( 'Invalid reservation.', 'easyReservations' ) );
		}

		$reservation->set_props(
			array(
				'resource_id' => $data->resource,
				'space'       => $data->space,
				'status'      => $data->status,
				'arrival'     => er_string_to_timestamp( $data->arrival ),
				'departure'   => er_string_to_timestamp( $data->departure ),
				'adults'      => $data->adults,
				'children'    => $data->children,
				'order_id'    => $data->order_id,
			)
		);

		$this->read_reservation_data( $reservation, $data );
		$reservation->read_meta_data();
		$reservation->set_object_read( true );
	}

	/**
	 * Method to update an reservation in the database.
	 *
	 * @param ER_Reservation $reservation Order object.
	 */
	public function update( &$reservation ) {
		$reservation->save_meta_data();

		$now = time();

		if ( null === $reservation->get_date_created( 'edit' ) ) {
			$reservation->set_date_created( $now );
		}

		$changes = $reservation->get_changes();

		$reservation->set_date_modified( $now );

		// Only update the post when the post data changes.
		if ( array_intersect( array(
			'order_id',
			'resource_id',
			'space',
			'arrival',
			'departure',
			'status',
			'adults',
			'children',
		), array_keys( $changes ) ) ) {
			global $wpdb;

			$result = $wpdb->update(
				$wpdb->prefix . 'reservations',
				array(
					'arrival'   => $reservation->get_arrival( 'edit' ) ? $reservation->get_arrival( 'edit' )->format( 'Y-m-d H:i:s' ) : er_get_datetime()->format( 'Y-m-d H:i:s' ),
					'departure' => $reservation->get_departure( 'edit' ) ? $reservation->get_departure( 'edit' )->format( 'Y-m-d H:i:s' ) : er_get_datetime()->format( 'Y-m-d H:i:s' ),
					'order_id'  => $reservation->get_order_id( 'edit' ),
					'resource'  => $reservation->get_resource_id( 'edit' ),
					'adults'    => $reservation->get_adults( 'edit' ),
					'children'  => $reservation->get_children( 'edit' ),
					'space'     => $reservation->get_space( 'edit' ),
					'status'    => $reservation->get_status( 'edit' ),
				),
				array( 'id' => $reservation->get_id() ),
				array(
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					'%d',
					'%d',
					'%s',
				),
				array( '%d' )
			);

			if ( $result === false ) {
				throw new ER_Data_Exception( 'er_update_reservation', 'Reservation could not be updated. Error: ' . $wpdb->last_error );
			}

			$reservation->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}

		$this->update_reservation_meta( $reservation );
		$reservation->apply_changes();
		$this->clear_caches( $reservation );
	}

	/**
	 * Method to delete an reservation from the database.
	 *
	 * @param ER_Reservation $reservation Order object.
	 * @param array          $args Array of args to pass to the delete method.
	 *
	 * @return void
	 */
	public function delete( &$reservation, $args = array() ) {
		global $wpdb;

		$id   = $reservation->get_id();
		$args = wp_parse_args(
			$args,
			array(
				'force_delete' => false,
			)
		);

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			$this->delete_items( $reservation );

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}reservations WHERE id = %d; ",
					$id
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}reservationmeta WHERE reservation_id = %d;",
					$id
				)
			);

			$reservation->set_id( 0 );

			do_action( 'easyreservations_delete_reservation', $id );
		} else {
			$reservation->set_status( 'trash' );
			$reservation->save();

			do_action( 'easyreservations_trash_reservation', $id );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Read reservation data. Can be overridden by child classes to load other props.
	 *
	 * @param ER_Reservation $reservation Order object.
	 * @param object         $post_object Post object.
	 */
	protected function read_reservation_data( &$reservation, $post_object ) {
		$id = $reservation->get_id();

		$reservation->set_props(
			array(
				'date_created'       => er_string_to_timestamp( get_metadata( 'reservation', $id, '_date_created', true ) ),
				'date_modified'      => er_string_to_timestamp( get_metadata( 'reservation', $id, '_date_modified', true ) ),
				'discount_total'     => get_metadata( 'reservation', $id, '_discount', true ),
				'discount_tax'       => get_metadata( 'reservation', $id, '_discount_tax', true ),
				'total_tax'          => get_metadata( 'reservation', $id, '_reservation_tax', true ),
				'total'              => get_metadata( 'reservation', $id, '_reservation_total', true ),
				'prices_include_tax' => metadata_exists( 'reservation', $id, '_prices_include_tax' ) ? 'yes' === get_metadata( 'reservation', $id, '_prices_include_tax', true ) : 'yes' === get_option( 'reservations_prices_include_tax' ),
				'slot'               => get_metadata( 'reservation', $id, '_slot', true ),
				'title'              => get_metadata( 'reservation', $id, '_title', true ),
				'form_template'      => get_metadata( 'reservation', $id, '_form_template', true ),
			)
		);

		// Gets extra data associated with the reservation if needed.
		foreach ( $reservation->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $reservation, $function ) ) ) {
				$reservation->{$function}( get_metadata( 'reservation', $reservation->get_id(), '_' . $key, true ) );
			}
		}
	}

	/**
	 * Helper method that updates all the post meta for an reservation based on it's settings in the ER_Order class.
	 *
	 * @param ER_Reservation $reservation Order object.
	 */
	protected function update_reservation_meta( &$reservation ) {
		$updated_props     = array();
		$meta_key_to_props = array(
			'_discount'           => 'discount_total',
			'_discount_tax'       => 'discount_tax',
			'_reservation_tax'    => 'total_tax',
			'_reservation_total'  => 'total',
			'_prices_include_tax' => 'prices_include_tax',
			'_date_created'       => 'date_created',
			'_date_modified'      => 'date_modified',
			'_slot'               => 'slot',
			'_title'              => 'title',
			'_form_template'      => 'form_template',
		);

		$props_to_update = $this->get_props_to_update( $reservation, $meta_key_to_props, 'reservation' );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $reservation->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			switch ( $prop ) {
				case 'prices_include_tax':
					$value = $value ? 'yes' : 'no';
					break;
				case 'date_modified':
				case 'date_created':
					$value = ! is_null( $value ) ? $value->format( DATE_ATOM ) : '';
					break;
			}

			$updated = $this->update_or_delete_reservation_meta( $reservation, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		do_action( 'easyreservations_reservation_object_updated_props', $reservation, $updated_props );
	}

	/**
	 * Update meta data in, or delete it from, the database.
	 *
	 * Avoids storing meta when it's either an empty string or empty array.
	 * Other empty values such as numeric 0 and null should still be stored.
	 * Data-stores can force meta to exist using `must_exist_meta_keys`.
	 *
	 * Note: WordPress `get_metadata` function returns an empty string when meta data does not exist.
	 *
	 * @param ER_Data $object The ER_Data object (ER_Coupon for coupons, etc).
	 * @param string  $meta_key Meta key to update.
	 * @param mixed   $meta_value Value to save.
	 *
	 * @return bool True if updated/deleted.
	 */
	protected function update_or_delete_reservation_meta( $object, $meta_key, $meta_value ) {
		if ( in_array( $meta_value, array( array(), '' ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
			$updated = delete_metadata( 'reservation', $object->get_id(), $meta_key );
		} else {
			$updated = update_metadata( 'reservation', $object->get_id(), $meta_key, $meta_value );
		}

		return (bool) $updated;
	}

	/**
	 * Get unpaid orders after a certain date,
	 *
	 * @param int $date Timestamp.
	 *
	 * @return array
	 */
	public function get_temporary_reservations( $date ) {
		global $wpdb;

		$temporary_reservations = $wpdb->get_col(
			$wpdb->prepare(
			// @codingStandardsIgnoreStart
				"SELECT reservations.id
				FROM {$wpdb->prefix}reservations AS reservations
				INNER JOIN {$wpdb->prefix}reservationmeta AS meta ON ( meta.reservation_id = reservations.id AND meta.meta_key = '_date_modified' )
				WHERE   reservations.status = 'temporary'
				AND     meta.meta_value < %s",
				// @codingStandardsIgnoreEnd
				gmdate( 'Y-m-d H:i:s', absint( $date ) )
			)
		);

		return $temporary_reservations;
	}

	/**
	 * Clear any caches.
	 *
	 * @param ER_Reservation $reservation Order object.
	 */
	protected function clear_caches( &$reservation ) {
		wp_cache_delete( 'reservation-' . $reservation->get_id(), 'reservations' );
		wp_cache_delete( 'receipt-items-' . $reservation->get_id(), 'reservation' );
	}
}
