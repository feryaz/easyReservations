<?php
defined( 'ABSPATH' ) || exit;

/**
 * Main function for returning reservations.
 *
 * @param mixed $reservation Post object or ID of the reservation.
 *
 * @return bool|ER_Reservation
 */
function er_get_reservation( $reservation = false ) {
	global $post;

	if ( false === $reservation && is_a( $post, 'WP_Post' ) && 'easy_reservation' === get_post_type( $post ) ) {
		$reservation_id = absint( $post->ID );
	} elseif ( is_numeric( $reservation ) ) {
		$reservation_id = $reservation;
	} elseif ( $reservation instanceof ER_Reservation ) {
		$reservation_id = $reservation->get_id();
	} elseif ( ! empty( $reservation->ID ) ) {
		$reservation_id = $reservation->ID;
	} else {
		return false;
	}

	try {
		return new ER_Reservation( $reservation_id );
	} catch ( Exception $e ) {
		er_get_logger()->error( 'Cannot get reservation. ' . $e->getMessage() );

		return false;
	}
}

/**
 * When a payment is complete, we can set reservations within an order to approved.
 *
 * @param int $order_id Order ID.
 */
function er_maybe_set_reservations_pending( $order_id ) {
	$order = er_get_order( $order_id );

	if ( ! $order ) {
		return;
	}

	foreach ( $order->get_reservations() as $reservation_item ) {
		$reservation = $reservation_item->get_reservation();
		if ( $reservation ) {
			if ( $reservation->has_status( array( 'temporary' ) ) ) {
				$reservation->update_status( ER_Reservation_Status::PENDING );
			}
		}
	}
}

add_action( 'easyreservations_payment_complete', 'er_maybe_set_reservations_pending', 20 );
add_action( 'easyreservations_order_status_completed', 'er_maybe_set_reservations_pending' );
add_action( 'easyreservations_order_status_processing', 'er_maybe_set_reservations_pending' );
add_action( 'easyreservations_order_status_on-hold', 'er_maybe_set_reservations_pending' );

/**
 * When a payment is cancelled, we can set reservations within an order to pending.
 *
 * @param int $order_id Order ID.
 */
function er_maybe_set_reservation_pending( $order_id ) {
	$order = er_get_order( $order_id );

	if ( ! $order ) {
		return;
	}

	foreach ( $order->get_reservations() as $reservation_item ) {
		$reservation = $reservation_item->get_reservation();
		if ( $reservation ) {
			if ( $reservation->has_status( array( 'approved', 'checked', 'completed' ) ) ) {
				$reservation->set_status( 'pending' );
				$reservation->save();
			}
		}
	}
}

add_action( 'easyreservations_order_status_cancelled', 'er_maybe_set_reservation_pending' );
add_action( 'easyreservations_order_status_pending', 'er_maybe_set_reservation_pending' );

/**
 * Get reservation data from [tag]
 *
 * @param $tag array
 * @param $reservation ER_Reservation
 *
 * @return string
 */
function er_reservation_parse_tag( $tag, $reservation ) {
	$type = sanitize_key( $tag[0] );

	switch ( $type ) {
		case 'res_id':
		case 'ID':
		case 'res-id':
			return zeroise( $reservation->get_id(), isset( $tag[1] ) ? intval( $tag[1] ) : 0 );
			break;
		case 'name':
		case 'thename':
			return $reservation->get_name();
			break;
		case 'resource':
		case 'rooms':
			return $reservation->get_resource() ? esc_html( __( $reservation->get_resource()->get_title() ) ) : '';
			break;
		case 'resource-space':
		case 'resource-number':
		case 'resource-nr':
		case 'resourcenumber':
		case 'roomnumber':
			return $reservation->get_space() && $reservation->get_resource() ? esc_html( $reservation->get_resource()->get_space_name( $reservation->get_space() ) ) : '';
			break;
		case 'resource-link':
			return '<a href="' . esc_url( get_permalink( $reservation->get_resource_id() ) ) . '">' . esc_html( $reservation->get_resource()->get_title() ) . '</a>';
			break;
		case 'arrival':
		case 'arrivaldate':
		case 'date-from':
			$format = isset( $tag['format'] ) ? $tag['format'] : er_datetime_format();

			return $reservation->get_arrival() ? esc_html( $reservation->get_arrival()->format( $format ) ) : '';
			break;
		case 'departure':
		case 'departuredate':
		case 'date-to':
			$format = isset( $tag['format'] ) ? $tag['format'] : er_datetime_format();

			return esc_html( $reservation->get_departure()->format( $format ) );
			break;
		case 'reserved':
			$format = isset( $tag['format'] ) ? $tag['format'] : er_datetime_format();

			return esc_html( $reservation->get_date_created()->format( $format ) );
			break;
		case 'persons':
			return esc_html( $reservation->get_adults() + $reservation->get_children() );
			break;
		case 'adults':
			return esc_html( $reservation->get_adults() );
			break;
		case 'children':
		case 'childs':
			return esc_html( $reservation->get_children() );
			break;
		case 'units':
		case 'times':
		case 'nights':
		case 'days':
		case 'billing_units':
			return esc_html( $reservation->get_billing_units() );
			break;
		case 'date':
			$format = isset( $tag['format'] ) ? $tag['format'] : ( isset( $tag[1] ) ? $tag[1] : er_date_format() );

			return esc_html( date( $format, er_get_time() ) );
			break;
		case 'custom':
			$content       = '';
			$custom_fields = ER_Custom_Data::get_settings();

			if ( isset( $tag['id'] ) ) {
				$custom_id = absint( $tag['id'] );

				foreach ( $reservation->get_items( 'custom' ) as $custom_item ) {
					if ( $custom_item->get_custom_id() === $custom_id ) {
						return esc_html( $custom_item->get_custom_display() );
					}
				}

				if ( isset( $custom_fields[ $custom_id ], $custom_fields[ $custom_id ]['unused'] ) ) {
					return $custom_fields[ $custom_id ]['unused'];
				}
			}

			return $content;
			break;
		default:
			return apply_filters( 'easyreservations_reservation_parse_tag_' . $type, '' );
			break;
	}
}

/**
 * For a given order ID, get all bookings that belong to it.
 *
 * @param int|array $order_id
 *
 * @return int
 */
function er_reservation_get_ids_from_order_id( $order_id ) {
	global $wpdb;

	$order_ids = wp_parse_id_list( is_array( $order_id ) ? $order_id : array( $order_id ) );

	return wp_parse_id_list(
		$wpdb->get_col(
			"SELECT ID FROM {$wpdb->prefix}reservations WHERE order_id IN (" . implode( ',', array_map( 'esc_sql', $order_ids ) ) . ');'
		)
	);
}

/**
 * Multiply an amount by reservation data
 *
 * @param ER_Reservation $reservation
 * @param string         $mode
 * @param float          $amount
 * @param float|int      $full
 *
 * @return float|int
 */
function er_reservation_multiply_amount( $reservation, $mode, $amount, $full = 0 ) {
	if ( empty( $mode ) || ! $mode || $mode == "price_res" ) {
		return $amount;
	} elseif ( $mode == "price_pers" ) {
		return $amount * ( $reservation->get_adults() + $reservation->get_children() );
	} elseif ( $mode == "price_adul" ) {
		return $amount * $reservation->get_adults();
	} elseif ( $mode == "price_child" ) {
		return $amount * $reservation->get_children();
	} elseif ( $mode == "price_both" ) {
		return $amount * ( $reservation->get_adults() + $reservation->get_children() ) * $reservation->get_billing_units();
	} elseif ( $mode == "price_day_adult" ) {
		return $amount * $reservation->get_adults() * $reservation->get_billing_units();
	} elseif ( $mode == "price_day_child" ) {
		return $amount * $reservation->get_children() * $reservation->get_billing_units();
	} elseif ( $mode == "price_halfhour" ) {
		return $amount * $reservation->get_resource()->get_billing_units( $reservation->get_arrival(), $reservation->get_departure(), 1800, 0 );
	} elseif ( $mode == "price_hour" ) {
		return $amount * $reservation->get_resource()->get_billing_units( $reservation->get_arrival(), $reservation->get_departure(), HOUR_IN_SECONDS, 0 );
	} elseif ( $mode == "price_realday" ) {
		return $amount * $reservation->get_resource()->get_billing_units( $reservation->get_arrival(), $reservation->get_departure(), DAY_IN_SECONDS, 0 );
	} elseif ( $mode == "price_night" ) {
		return $amount * $reservation->get_resource()->get_billing_units( $reservation->get_arrival(), $reservation->get_departure(), DAY_IN_SECONDS, 3 );
	} elseif ( $mode == "price_week" ) {
		return $amount * $reservation->get_resource()->get_billing_units( $reservation->get_arrival(), $reservation->get_departure(), WEEK_IN_SECONDS, 0 );
	} elseif ( $mode == "price_month" ) {
		return $amount * $reservation->get_resource()->get_billing_units( $reservation->get_arrival(), $reservation->get_departure(), MONTH_IN_SECONDS, 0 );
	} elseif ( $mode == "price_day" ) {
		return $amount * $reservation->get_billing_units();
	} elseif ( $mode == '%' || $mode == 'price_perc' ) {
		return $full / 100 * (int) $amount;
	}

	return $amount;
}

/**
 * Get list of statuses which are considered 'approved'.
 *
 * @return array
 */
function er_reservation_get_approved_statuses() {
	return apply_filters( 'easyreservations_order_approved_statuses', array( 'approved', 'checked', 'completed' ) );
}

/**
 * Get list of statuses which are considered 'pending'.
 *
 * @return array
 */
function er_reservation_get_pending_statuses() {
	return apply_filters( 'easyreservations_order_pending_statuses', array( 'pending' ) );
}

/**
 * Delete temporary reservations in database
 */
function er_delete_temporary_reservations() {
	if ( get_option( 'reservations_db_version' ) !== ER()->version ) {
		return;
	}

	$wait_for_order_duration = get_option( 'reservations_wait_for_ordering_minutes', '60' );

	if ( ! is_numeric( $wait_for_order_duration ) || $wait_for_order_duration === '0' ) {
		$wait_for_order_duration = '60';
	}

	$wait_for_order_duration = absint( $wait_for_order_duration );

	if ( $wait_for_order_duration < 1 ) {
		return;
	}

	$data_store             = ER_Data_Store::load( 'reservation' );
	$temporary_reservations = $data_store->get_temporary_reservations( strtotime( '-' . absint( $wait_for_order_duration ) . ' MINUTES', current_time( 'timestamp' ) ) );

	if ( $temporary_reservations ) {
		foreach ( $temporary_reservations as $temporary_reservation ) {
			$reservation = er_get_reservation( absint( $temporary_reservation ) );

			if ( $reservation && apply_filters( 'easyreservations_delete_temporary_reservation', true, $reservation ) ) {
				$reservation->delete( true );
			}
		}
	}

	wp_clear_scheduled_hook( 'easyreservations_delete_temporary_reservations' );
	wp_schedule_single_event( time() + ( absint( $wait_for_order_duration ) * 60 ), 'easyreservations_delete_temporary_reservations' );
}

add_action( 'easyreservations_delete_temporary_reservations', 'er_delete_temporary_reservations' );
add_action( 'init', 'er_delete_temporary_reservations' );
