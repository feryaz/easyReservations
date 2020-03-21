<?php
/**
 * Personal data exporters.
 *
 * @package easyReservations\Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * ER_Privacy_Exporters Class.
 */
class ER_Privacy_Exporters {
	/**
	 * Finds and exports customer data by email address.
	 *
	 * @param string $email_address The user email address.
	 * @return array An array of personal data in name value pairs
	 */
	public static function customer_data_exporter( $email_address ) {
		$user           = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$data_to_export = array();

		if ( $user instanceof WP_User ) {
			$customer_personal_data = self::get_customer_personal_data( $user );
			if ( ! empty( $customer_personal_data ) ) {
				$data_to_export[] = array(
					'group_id'    => 'easyreservations_customer',
					'group_label' => __( 'Customer Data', 'easyReservations' ),
					'item_id'     => 'user',
					'data'        => $customer_personal_data,
				);
			}
		}

		return array(
			'data' => $data_to_export,
			'done' => true,
		);
	}

	/**
	 * Finds and exports data which could be used to identify a person from easyReservations data associated with an email address.
	 *
	 * Orders are exported in blocks of 10 to avoid timeouts.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 *
	 * @return array An array of personal data in name value pairs
	 */
	public static function order_data_exporter( $email_address, $page ) {
		$done           = true;
		$page           = (int) $page;
		$user           = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$data_to_export = array();
		$order_query    = array(
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
				$data_to_export[] = array(
					'group_id'    => 'easyreservations_orders',
					'group_label' => __( 'Orders', 'easyReservations' ),
					'item_id'     => 'order-' . $order->get_id(),
					'data'        => self::get_order_personal_data( $order ),
				);
			}
			$done = 10 > count( $orders );
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}

	/**
	 * Finds and exports customer download logs by email address.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @throws Exception When ER_Data_Store validation fails.
	 * @return array An array of personal data in name value pairs
	 */
	public static function download_data_exporter( $email_address, $page ) {
		$done            = true;
		$page            = (int) $page;
		$user            = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$data_to_export  = array();
		$downloads_query = array(
			'limit' => 10,
			'page'  => $page,
		);

		if ( $user instanceof WP_User ) {
			$downloads_query['user_id'] = (int) $user->ID;
		} else {
			$downloads_query['user_email'] = $email_address;
		}

		$customer_download_data_store     = ER_Data_Store::load( 'customer-download' );
		$customer_download_log_data_store = ER_Data_Store::load( 'customer-download-log' );
		$downloads                        = $customer_download_data_store->get_downloads( $downloads_query );

		if ( 0 < count( $downloads ) ) {
			foreach ( $downloads as $download ) {
				$data_to_export[] = array(
					'group_id'    => 'easyreservations_downloads',
					/* translators: This is the headline for a list of downloads purchased from the store for a given user. */
					'group_label' => __( 'Purchased Downloads', 'easyReservations' ),
					'item_id'     => 'download-' . $download->get_id(),
					'data'        => self::get_download_personal_data( $download ),
				);

				$download_logs = $customer_download_log_data_store->get_download_logs_for_permission( $download->get_id() );

				foreach ( $download_logs as $download_log ) {
					$data_to_export[] = array(
						'group_id'    => 'easyreservations_download_logs',
						/* translators: This is the headline for a list of access logs for downloads purchased from the store for a given user. */
						'group_label' => __( 'Access to Purchased Downloads', 'easyReservations' ),
						'item_id'     => 'download-log-' . $download_log->get_id(),
						'data'        => array(
							array(
								'name'  => __( 'Download ID', 'easyReservations' ),
								'value' => $download_log->get_permission_id(),
							),
							array(
								'name'  => __( 'Timestamp', 'easyReservations' ),
								'value' => $download_log->get_timestamp(),
							),
							array(
								'name'  => __( 'IP Address', 'easyReservations' ),
								'value' => $download_log->get_user_ip_address(),
							),
						),
					);
				}
			}
			$done = 10 > count( $downloads );
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}

	/**
	 * Get personal data (key/value pairs) for a user object.
	 *
	 * @param WP_User $user user object.
	 * @throws Exception If customer cannot be read/found and $data is set to ER_Customer class.
	 * @return array
	 */
	protected static function get_customer_personal_data( $user ) {
		$personal_data = array();
		$customer      = new ER_Customer( $user->ID );

		if ( ! $customer ) {
			return array();
		}

		$props_to_export = apply_filters(
			'easyreservations_privacy_export_customer_personal_data_props',
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

		foreach ( $props_to_export as $prop => $description ) {
			$value = '';

			if ( is_callable( array( $customer, 'get_' . $prop ) ) ) {
				$value = $customer->{"get_$prop"}( 'edit' );
			}

			$value = apply_filters( 'easyreservations_privacy_export_customer_personal_data_prop_value', $value, $prop, $customer );

			if ( $value ) {
				$personal_data[] = array(
					'name'  => $description,
					'value' => $value,
				);
			}
		}

		/**
		 * Allow extensions to register their own personal data for this customer for the export.
		 *
		 * @param array    $personal_data Array of name value pairs.
		 * @param ER_Order $order A customer object.
		 */
		$personal_data = apply_filters( 'easyreservations_privacy_export_customer_personal_data', $personal_data, $customer );

		return $personal_data;
	}

	/**
	 * Get personal data (key/value pairs) for an order object.
	 *
	 * @param ER_Order $order Order object.
	 * @return array
	 */
	protected static function get_order_personal_data( $order ) {
		$personal_data   = array();
		$props_to_export = apply_filters(
			'easyreservations_privacy_export_order_personal_data_props',
			array(
				'order_number'        => __( 'Order Number', 'easyReservations' ),
				'date_created'        => __( 'Order Date', 'easyReservations' ),
				'total'               => __( 'Order Total', 'easyReservations' ),
				'items'               => __( 'Items Purchased', 'easyReservations' ),
				'customer_ip_address' => __( 'IP Address', 'easyReservations' ),
				'customer_user_agent' => __( 'Browser User Agent', 'easyReservations' ),
				'formatted_address'   => __( 'Address', 'easyReservations' ),
				'phone'               => __( 'Phone Number', 'easyReservations' ),
				'email'               => __( 'Email Address', 'easyReservations' ),
			),
			$order
		);

		foreach ( $props_to_export as $prop => $name ) {
			$value = '';

			switch ( $prop ) {
				case 'items':
					$item_names = array();
					foreach ( $order->get_items() as $item ) {
						$item_names[] = $item->get_name();
					}
					$value = implode( ', ', $item_names );
					break;
				case 'date_created':
					$value = er_format_datetime( $order->get_date_created(), get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) );
					break;
				case 'formatted_billing_address':
				case 'formatted_shipping_address':
					$value = preg_replace( '#<br\s*/?>#i', ', ', $order->{"get_$prop"}() );
					break;
				default:
					if ( is_callable( array( $order, 'get_' . $prop ) ) ) {
						$value = $order->{"get_$prop"}();
					}
					break;
			}

			$value = apply_filters( 'easyreservations_privacy_export_order_personal_data_prop', $value, $prop, $order );

			if ( $value ) {
				$personal_data[] = array(
					'name'  => $name,
					'value' => $value,
				);
			}
		}

		// Export meta data.
		$meta_to_export = apply_filters(
			'easyreservations_privacy_export_order_personal_data_meta',
			array(
				'Payer first name'     => __( 'Payer first name', 'easyReservations' ),
				'Payer last name'      => __( 'Payer last name', 'easyReservations' ),
				'Payer PayPal address' => __( 'Payer PayPal address', 'easyReservations' ),
				'Transaction ID'       => __( 'Transaction ID', 'easyReservations' ),
			)
		);

		if ( ! empty( $meta_to_export ) && is_array( $meta_to_export ) ) {
			foreach ( $meta_to_export as $meta_key => $name ) {
				$value = apply_filters( 'easyreservations_privacy_export_order_personal_data_meta_value', $order->get_meta( $meta_key ), $meta_key, $order );

				if ( $value ) {
					$personal_data[] = array(
						'name'  => $name,
						'value' => $value,
					);
				}
			}
		}

		/**
		 * Allow extensions to register their own personal data for this order for the export.
		 *
		 * @param array    $personal_data Array of name value pairs to expose in the export.
		 * @param ER_Order $order An order object.
		 */
		$personal_data = apply_filters( 'easyreservations_privacy_export_order_personal_data', $personal_data, $order );

		return $personal_data;
	}

	/**
	 * Finds and exports payment tokens by email address for a customer.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function customer_tokens_exporter( $email_address, $page ) {
		$user           = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$data_to_export = array();

		if ( ! $user instanceof WP_User ) {
			return array(
				'data' => $data_to_export,
				'done' => true,
			);
		}

		$tokens = ER_Payment_Tokens::get_tokens(
			array(
				'user_id' => $user->ID,
				'limit'   => 10,
				'page'    => $page,
			)
		);

		if ( 0 < count( $tokens ) ) {
			foreach ( $tokens as $token ) {
				$data_to_export[] = array(
					'group_id'    => 'easyreservations_tokens',
					'group_label' => __( 'Payment Tokens', 'easyReservations' ),
					'item_id'     => 'token-' . $token->get_id(),
					'data'        => array(
						array(
							'name'  => __( 'Token', 'easyReservations' ),
							'value' => $token->get_display_name(),
						),
					),
				);
			}
			$done = 10 > count( $tokens );
		} else {
			$done = true;
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}
}
