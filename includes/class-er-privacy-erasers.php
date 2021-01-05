<?php
/**
 * Personal data erasers.
 *
 * @package easyReservations\Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * ER_Privacy_Erasers Class.
 */
class ER_Privacy_Erasers {
	/**
	 * Finds and erases customer data by email address.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page Page.
	 *
	 * @return array An array of personal data in name value pairs
	 */
	public static function customer_data_eraser( $email_address, $page ) {
		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		$user = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.

		if ( ! $user instanceof WP_User ) {
			return $response;
		}

		$customer = new ER_Customer( $user->ID );

		if ( ! $customer ) {
			return $response;
		}

		$props_to_erase = apply_filters(
			'easyreservations_privacy_erase_customer_personal_data_props',
			array(
				'address_first_name' => __( 'First Name', 'easyReservations' ),
				'address_last_name'  => __( 'Last Name', 'easyReservations' ),
				'address_company'    => __( 'Company', 'easyReservations' ),
				'address_address_1'  => __( 'Address 1', 'easyReservations' ),
				'address_address_2'  => __( 'Address 2', 'easyReservations' ),
				'address_city'       => __( 'City', 'easyReservations' ),
				'address_postcode'   => __( 'Postal/Zip Code', 'easyReservations' ),
				'address_state'      => __( 'State', 'easyReservations' ),
				'address_country'    => __( 'Country / Region', 'easyReservations' ),
				'address_phone'      => __( 'Phone Number', 'easyReservations' ),
				'address_email'      => __( 'Email Address', 'easyReservations' ),
			),
			$customer
		);

		foreach ( $props_to_erase as $prop => $label ) {
			$erased = false;

			if ( is_callable( array( $customer, 'get_' . $prop ) ) && is_callable( array( $customer, 'set_' . $prop ) ) ) {
				$value = $customer->{"get_$prop"}( 'edit' );

				if ( $value ) {
					$customer->{"set_$prop"}( '' );
					$erased = true;
				}
			}

			$erased = apply_filters( 'easyreservations_privacy_erase_customer_personal_data_prop', $erased, $prop, $customer );

			if ( $erased ) {
				/* Translators: %s Prop name. */
				$response['messages'][]    = sprintf( __( 'Removed customer "%s"', 'easyReservations' ), $label );
				$response['items_removed'] = true;
			}
		}

		$customer->save();

		/**
		 * Allow extensions to remove data for this customer and adjust the response.
		 *
		 * @param array    $response Array response data. Must include messages, num_items_removed, num_items_retained, done.
		 * @param ER_Order $order A customer object.
		 */
		return apply_filters( 'easyreservations_privacy_erase_personal_data_customer', $response, $customer );
	}

	/**
	 * Finds and erases data which could be used to identify a person from easyReservations data associated with an email address.
	 *
	 * Orders are erased in blocks of 10 to avoid timeouts.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page Page.
	 *
	 * @return array An array of personal data in name value pairs
	 */
	public static function order_data_eraser( $email_address, $page ) {
		$page            = (int) $page;
		$user            = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$erasure_enabled = er_string_to_bool( get_option( 'reservations_erasure_request_removes_order_data', 'no' ) );
		$response        = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		$order_query = array(
			'limit'    => 10,
			'page'     => $page,
			'customer' => array( $email_address ),
		);

		if ( $user instanceof WP_User ) {
			$order_query['customer'][] = (int) $user->ID;
		}

		$orders = er_get_orders( $order_query );

		if ( 0 < count( $orders ) ) {
			foreach ( $orders as $order ) {
				if ( apply_filters( 'easyreservations_privacy_erase_order_personal_data', $erasure_enabled, $order ) ) {
					self::remove_order_personal_data( $order );

					/* Translators: %s Order number. */
					$response['messages'][]    = sprintf( __( 'Removed personal data from order %s.', 'easyReservations' ), $order->get_order_number() );
					$response['items_removed'] = true;
				} else {
					/* Translators: %s Order number. */
					$response['messages'][]     = sprintf( __( 'Personal data within order %s has been retained.', 'easyReservations' ), $order->get_order_number() );
					$response['items_retained'] = true;
				}
			}
			$response['done'] = 10 > count( $orders );
		} else {
			$response['done'] = true;
		}

		return $response;
	}

	/**
	 * Remove personal data specific to easyReservations from an order object.
	 *
	 * Note; this will hinder order processing for obvious reasons!
	 *
	 * @param ER_Order $order Order object.
	 */
	public static function remove_order_personal_data( $order ) {
		$anonymized_data = array();

		/**
		 * Allow extensions to remove their own personal data for this order first, so order data is still available.
		 *
		 * @param ER_Order $order A customer object.
		 */
		do_action( 'easyreservations_privacy_before_remove_order_personal_data', $order );

		/**
		 * Expose props and data types we'll be anonymizing.
		 *
		 * @param array    $props Keys are the prop names, values are the data type we'll be passing to wp_privacy_anonymize_data().
		 * @param ER_Order $order A customer object.
		 */
		$props_to_remove = apply_filters(
			'easyreservations_privacy_remove_order_personal_data_props',
			array(
				'customer_ip_address' => 'ip',
				'customer_user_agent' => 'text',
				'first_name'          => 'text',
				'last_name'           => 'text',
				'company'             => 'text',
				'address_1'           => 'text',
				'address_2'           => 'text',
				'city'                => 'text',
				'postcode'            => 'text',
				'state'               => 'address_state',
				'country'             => 'address_country',
				'phone'               => 'phone',
				'email'               => 'email',
				'customer_id'         => 'numeric_id',
				'transaction_id'      => 'numeric_id',
			),
			$order
		);

		if ( ! empty( $props_to_remove ) && is_array( $props_to_remove ) ) {
			foreach ( $props_to_remove as $prop => $data_type ) {
				// Get the current value in edit context.
				$value = $order->{"get_$prop"}( 'edit' );

				// If the value is empty, it does not need to be anonymized.
				if ( empty( $value ) || empty( $data_type ) ) {
					continue;
				}

				$anon_value = function_exists( 'wp_privacy_anonymize_data' ) ? wp_privacy_anonymize_data( $data_type, $value ) : '';

				/**
				 * Expose a way to control the anonymized value of a prop via 3rd party code.
				 *
				 * @param string   $anon_value Value of this prop after anonymization.
				 * @param string   $prop Name of the prop being removed.
				 * @param string   $value Current value of the data.
				 * @param string   $data_type Type of data.
				 * @param ER_Order $order An order object.
				 */
				$anonymized_data[ $prop ] = apply_filters( 'easyreservations_privacy_remove_order_personal_data_prop_value', $anon_value, $prop, $value, $data_type, $order );
			}
		}

		// Set all new props and persist the new data to the database.
		$order->set_props( $anonymized_data );

		// Remove meta data.
		$meta_to_remove = apply_filters(
			'easyreservations_privacy_remove_order_personal_data_meta',
			array(
				'Payer first name'     => 'text',
				'Payer last name'      => 'text',
				'Payer PayPal address' => 'email',
				'Transaction ID'       => 'numeric_id',
			)
		);

		if ( ! empty( $meta_to_remove ) && is_array( $meta_to_remove ) ) {
			foreach ( $meta_to_remove as $meta_key => $data_type ) {
				$value = $order->get_meta( $meta_key );

				// If the value is empty, it does not need to be anonymized.
				if ( empty( $value ) || empty( $data_type ) ) {
					continue;
				}

				$anon_value = function_exists( 'wp_privacy_anonymize_data' ) ? wp_privacy_anonymize_data( $data_type, $value ) : '';

				/**
				 * Expose a way to control the anonymized value of a value via 3rd party code.
				 *
				 * @param string   $anon_value Value of this data after anonymization.
				 * @param string   $prop meta_key key being removed.
				 * @param string   $value Current value of the data.
				 * @param string   $data_type Type of data.
				 * @param ER_Order $order An order object.
				 */
				$anon_value = apply_filters( 'easyreservations_privacy_remove_order_personal_data_meta_value', $anon_value, $meta_key, $value, $data_type, $order );

				if ( $anon_value ) {
					$order->update_meta_data( $meta_key, $anon_value );
				} else {
					$order->delete_meta_data( $meta_key );
				}
			}
		}

		$order->update_meta_data( '_anonymized', 'yes' );
		$order->save();

		// Delete order notes which can contain PII.
		$notes = er_get_order_notes(
			array(
				'order_id' => $order->get_id(),
			)
		);

		foreach ( $notes as $note ) {
			er_delete_order_note( $note->id );
		}

		// Add note that this event occurred.
		$order->add_order_note( __( 'Personal data removed.', 'easyReservations' ) );

		/**
		 * Allow extensions to remove their own personal data for this order.
		 *
		 * @param ER_Order $order A customer object.
		 */
		do_action( 'easyreservations_privacy_remove_order_personal_data', $order );
	}

	/**
	 * Finds and erases customer tokens by email address.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page Page.
	 *
	 * @return array An array of personal data in name value pairs
	 */
	public static function customer_tokens_eraser( $email_address, $page ) {
		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		$user = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.

		if ( ! $user instanceof WP_User ) {
			return $response;
		}

		$tokens = ER_Payment_Tokens::get_tokens(
			array(
				'user_id' => $user->ID,
			)
		);

		if ( empty( $tokens ) ) {
			return $response;
		}

		foreach ( $tokens as $token ) {
			ER_Payment_Tokens::delete( $token->get_id() );

			/* Translators: %s Prop name. */
			$response['messages'][]    = sprintf( __( 'Removed payment token "%d"', 'easyReservations' ), $token->get_id() );
			$response['items_removed'] = true;
		}

		/**
		 * Allow extensions to remove data for tokens and adjust the response.
		 *
		 * @param array $response Array response data. Must include messages, num_items_removed, num_items_retained, done.
		 * @param array $tokens Array of tokens.
		 */
		return apply_filters( 'easyreservations_privacy_erase_personal_data_tokens', $response, $tokens );
	}
}
