<?php
/**
 * ER_Order_Data_Store_CPT class file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER Order Data Store: Stored in CPT.
 */
class ER_Order_Data_Store_CPT extends Abstract_ER_Order_Data_Store_CPT implements ER_Object_Data_Store_Interface,
	ER_Order_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_customer_user',
		'_order_key',
		'_locale',
		'_first_name',
		'_last_name',
		'_company',
		'_address_1',
		'_address_2',
		'_city',
		'_state',
		'_postcode',
		'_country',
		'_email',
		'_phone',
		'_address_index',
		'_edit_lock',
		'_edit_last',
		'_discount',
		'_discount_tax',
		'_paid',
		'_order_tax',
		'_order_total',
		'_payment_method',
		'_payment_method_title',
		'_transaction_id',
		'_customer_ip_address',
		'_customer_user_agent',
		'_created_via',
		'_order_version',
		'_prices_include_tax',
		'_date_completed',
		'_date_paid',
		'_paid',
		'_payment_tokens'
	);

	/**
	 * Method to create a new order in the database.
	 *
	 * @param ER_Order $order Order object.
	 */
	public function create( &$order ) {
		if ( '' === $order->get_order_key() ) {
			$order->set_order_key( er_generate_order_key() );
		}

		parent::create( $order );
		do_action( 'easyreservations_new_order', $order->get_id(), $order );
	}

	/**
	 * Read order data. Can be overridden by child classes to load other props.
	 *
	 * @param ER_Order $order Order object.
	 * @param object   $post_object Post object.
	 */
	protected function read_order_data( &$order, $post_object ) {
		parent::read_order_data( $order, $post_object );
		$id = $order->get_id();

		$order->set_props(
			array(
				'order_key'            => get_post_meta( $id, '_easy_order_key', true ),
				'customer_id'          => get_post_meta( $id, '_customer_user', true ),
				'locale'               => get_post_meta( $id, '_locale', true ),
				'first_name'           => get_post_meta( $id, '_first_name', true ),
				'last_name'            => get_post_meta( $id, '_last_name', true ),
				'company'              => get_post_meta( $id, '_company', true ),
				'address_1'            => get_post_meta( $id, '_address_1', true ),
				'address_2'            => get_post_meta( $id, '_address_2', true ),
				'city'                 => get_post_meta( $id, '_city', true ),
				'state'                => get_post_meta( $id, '_state', true ),
				'postcode'             => get_post_meta( $id, '_postcode', true ),
				'country'              => get_post_meta( $id, '_country', true ),
				'email'                => get_post_meta( $id, '_email', true ),
				'phone'                => get_post_meta( $id, '_phone', true ),
				'payment_method'       => get_post_meta( $id, '_payment_method', true ),
				'payment_method_title' => get_post_meta( $id, '_payment_method_title', true ),
				'transaction_id'       => get_post_meta( $id, '_transaction_id', true ),
				'customer_ip_address'  => get_post_meta( $id, '_customer_ip_address', true ),
				'customer_user_agent'  => get_post_meta( $id, '_customer_user_agent', true ),
				'created_via'          => get_post_meta( $id, '_created_via', true ),
				'date_completed'       => get_post_meta( $id, '_date_completed', true ),
				'paid'                 => get_post_meta( $id, '_paid', true ),
				'date_paid'            => get_post_meta( $id, '_date_paid', true ),
				'cart_hash'            => get_post_meta( $id, '_cart_hash', true ),
				'customer_note'        => $post_object->post_excerpt,
			)
		);
	}

	/**
	 * Method to update an order in the database.
	 *
	 * @param ER_Order $order Order object.
	 */
	public function update( &$order ) {
		// Also grab the current status so we can compare.
		$previous_status = get_post_status( $order->get_id() );

		// Update the order.
		parent::update( $order );

		// Fire a hook depending on the status - this should be considered a creation if it was previously draft status.
		$new_status = $order->get_status( 'edit' );

		if ( $new_status !== $previous_status && in_array( $previous_status, array( 'new', 'auto-draft', 'draft' ), true ) ) {
			do_action( 'easyreservations_new_order', $order->get_id(), $order );
		} else {
			do_action( 'easyreservations_update_order', $order->get_id(), $order );
		}
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the ER_Order class.
	 *
	 * @param ER_Order $order Order object.
	 */
	protected function update_post_meta( &$order ) {
		$updated_props     = array();
		$id                = $order->get_id();
		$meta_key_to_props = array(
			'_easy_order_key'       => 'order_key',
			'_locale'               => 'locale',
			'_customer_user'        => 'customer_id',
			'_order_key'            => 'order_key',
			'_payment_method'       => 'payment_method',
			'_payment_method_title' => 'payment_method_title',
			'_transaction_id'       => 'transaction_id',
			'_customer_ip_address'  => 'customer_ip_address',
			'_customer_user_agent'  => 'customer_user_agent',
			'_created_via'          => 'created_via',
			'_date_completed'       => 'date_completed',
			'_date_paid'            => 'date_paid',
			'_paid'                 => 'paid',
			'_cart_hash'            => 'cart_hash',
		);

		$props_to_update = $this->get_props_to_update( $order, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $order->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;
			switch ( $prop ) {
				case 'date_paid':
				case 'date_completed':
					$value = ! is_null( $value ) ? $value->format( DATE_ATOM ) : '';
					break;
			}

			$updated = $this->update_or_delete_post_meta( $order, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		$address_props = array(
			'_first_name' => 'address_first_name',
			'_last_name'  => 'address_last_name',
			'_company'    => 'address_company',
			'_address_1'  => 'address_address_1',
			'_address_2'  => 'address_address_2',
			'_city'       => 'address_city',
			'_state'      => 'address_state',
			'_postcode'   => 'address_postcode',
			'_country'    => 'address_country',
			'_email'      => 'address_email',
			'_phone'      => 'address_phone',
		);

		$props_to_update = $this->get_props_to_update( $order, $address_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$prop    = str_replace( array( 'address_address_', 'address_', 'temp_' ), array( 'temp_', '', 'address_' ), $prop );
			$value   = $order->{"get_$prop"}( 'edit' );
			$value   = is_string( $value ) ? wp_slash( $value ) : $value;
			$updated = $this->update_or_delete_post_meta( $order, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
				$updated_props[] = 'address';
			}
		}

		parent::update_post_meta( $order );

		if ( in_array( 'address', $updated_props, true ) || ! metadata_exists( 'post', $order->get_id(), '_address_index' ) ) {
			update_post_meta( $order->get_id(), '_address_index', implode( ' ', $order->get_address() ) );
		}

		// Mark user account as active.
		if ( in_array( 'customer_id', $updated_props, true ) ) {
			er_update_user_last_active( $order->get_customer_id() );
		}

		do_action( 'easyreservations_order_object_updated_props', $order, $updated_props );
	}

	/**
	 * Excerpt for post.
	 *
	 * @param ER_Order $order Order object.
	 *
	 * @return string
	 */
	protected function get_post_excerpt( $order ) {
		return $order->get_customer_note();
	}

	/**
	 * Get amount already refunded.
	 *
	 * @param ER_Order $order Order object.
	 *
	 * @return float
	 */
	public function get_total_refunded( $order ) {
		global $wpdb;

		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM( postmeta.meta_value )
				FROM $wpdb->postmeta AS postmeta
				INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'easy_order_refund' AND posts.post_parent = %d )
				WHERE postmeta.meta_key = '_refund_amount'
				AND postmeta.post_id = posts.ID",
				$order->get_id()
			)
		);

		return floatval( $total );
	}

	/**
	 * Get the total tax refunded.
	 *
	 * @param ER_Order $order Order object.
	 *
	 * @return float
	 */
	public function get_total_tax_refunded( $order ) {
		global $wpdb;

		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM( receipt_itemmeta.meta_value )
				FROM {$wpdb->prefix}receipt_itemmeta AS receipt_itemmeta
				INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'easy_order_refund' AND posts.post_parent = %d )
				INNER JOIN {$wpdb->prefix}receipt_items AS receipt_items ON ( receipt_items.receipt_object_id = posts.ID AND receipt_items.receipt_item_type = 'tax' AND receipt_items.receipt_object_type = 'order_refund' )
				WHERE receipt_itemmeta.receipt_object_id = receipt_items.receipt_object_id
				AND receipt_itemmeta.meta_key = '_tax_total'",
				$order->get_id()
			)
		);

		return abs( $total );
	}

	/**
	 * Finds an Order ID based on an order key.
	 *
	 * @param string $order_key An order key has generated by.
	 *
	 * @return int The ID of an order, or 0 if the order could not be found
	 */
	public function get_order_id_by_order_key( $order_key ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_easy_order_key' AND meta_value = %s", $order_key ) );
	}

	/**
	 * Return count of orders with a specific status.
	 *
	 * @param string $status Order status. Function er_get_order_statuses() returns a list of valid statuses.
	 *
	 * @return int
	 */
	public function get_order_count( $status ) {
		global $wpdb;

		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = 'easy_order' AND post_status = %s", $status ) ) );
	}

	/**
	 * Generate meta query for er_get_orders.
	 *
	 * @param array  $values List of customers ids or emails.
	 * @param string $relation 'or' or 'and' relation used to build the WP meta_query.
	 *
	 * @return array
	 */
	private function get_orders_generate_customer_meta_query( $values, $relation = 'or' ) {
		$meta_query = array(
			'relation'        => strtoupper( $relation ),
			'customer_emails' => array(
				'key'     => '_billing_email',
				'value'   => array(),
				'compare' => 'IN',
			),
			'customer_ids'    => array(
				'key'     => '_customer_user',
				'value'   => array(),
				'compare' => 'IN',
			),
		);
		foreach ( $values as $value ) {
			if ( is_array( $value ) ) {
				$query_part = $this->get_orders_generate_customer_meta_query( $value, 'and' );
				if ( is_wp_error( $query_part ) ) {
					return $query_part;
				}
				$meta_query[] = $query_part;
			} elseif ( is_email( $value ) ) {
				$meta_query['customer_emails']['value'][] = sanitize_email( $value );
			} elseif ( is_numeric( $value ) ) {
				$meta_query['customer_ids']['value'][] = strval( absint( $value ) );
			} else {
				return new WP_Error( 'easyreservations_query_invalid', __( 'Invalid customer query.', 'easyReservations' ), $values );
			}
		}

		if ( empty( $meta_query['customer_emails']['value'] ) ) {
			unset( $meta_query['customer_emails'] );
			unset( $meta_query['relation'] );
		}

		if ( empty( $meta_query['customer_ids']['value'] ) ) {
			unset( $meta_query['customer_ids'] );
			unset( $meta_query['relation'] );
		}

		return $meta_query;
	}

	/**
	 * Get unpaid orders after a certain date,
	 *
	 * @param int $date Timestamp.
	 *
	 * @return array
	 */
	public function get_unpaid_orders( $date ) {
		global $wpdb;

		$unpaid_orders = $wpdb->get_col(
			$wpdb->prepare(
			// @codingStandardsIgnoreStart
				"SELECT posts.ID
				FROM {$wpdb->posts} AS posts
				WHERE   posts.post_type   = 'easy_order'
				AND     posts.post_status = 'pending'
				AND     posts.post_modified < %s",
				// @codingStandardsIgnoreEnd
				gmdate( 'Y-m-d H:i:s', absint( $date ) )
			)
		);

		return $unpaid_orders;
	}

	/**
	 * Search order data for a term and return ids.
	 *
	 * @param string $term Searched term.
	 *
	 * @return array of ids
	 */
	public function search_orders( $term ) {
		global $wpdb;

		/**
		 * Searches on meta data can be slow - this lets you choose what fields to search.
		 * 3.0.0 added _billing_address and _shipping_address meta which contains all address data to make this faster.
		 * This however won't work on older orders unless updated, so search a few others (expand this using the filter if needed).
		 *
		 * @var array
		 */
		$search_fields = array_map(
			'er_clean',
			apply_filters(
				'easyreservations_order_search_fields',
				array(
					'_address_index',
					'_last_name',
					'_email',
				)
			)
		);
		$order_ids     = array();

		if ( is_numeric( $term ) ) {
			$order_ids[] = absint( $term );
		}

		if ( ! empty( $search_fields ) ) {
			$order_ids = array_unique(
				array_merge(
					$order_ids,
					$wpdb->get_col(
						$wpdb->prepare(
							"SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1 WHERE p1.meta_value LIKE %s AND p1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $search_fields ) ) . "')", // @codingStandardsIgnoreLine
							'%' . $wpdb->esc_like( er_clean( $term ) ) . '%'
						)
					),
					$wpdb->get_col(
						$wpdb->prepare(
							"SELECT receipt_object_id
							FROM {$wpdb->prefix}receipt_items as order_items
							WHERE receipt_item_name LIKE %s
							AND receipt_object_type = %s",
							'%' . $wpdb->esc_like( er_clean( $term ) ) . '%',
							'order'
						)
					)
				)
			);
		}

		return apply_filters( 'easyreservations_order_search_results', $order_ids, $term, $search_fields );
	}

	/**
	 * Gets information about whether sales were recorded.
	 *
	 * @param ER_Order|int $order Order ID or order object.
	 *
	 * @return bool
	 */
	public function get_recorded_sales( $order ) {
		return er_string_to_bool( get_post_meta( is_integer( $order ) ? $order : $order->get_id(), '_recorded_sales', true ) );
	}

	/**
	 * Stores information about whether sales were recorded.
	 *
	 * @param ER_Order|int $order Order ID or order object.
	 * @param bool         $set True or false.
	 */
	public function set_recorded_sales( $order, $set ) {
		update_post_meta( is_integer( $order ) ? $order : $order->get_id(), '_recorded_sales', er_bool_to_string( $set ) );
	}

	/**
	 * Gets information about whether coupon counts were updated.
	 *
	 * @param ER_Order|int $order Order ID or order object.
	 *
	 * @return bool
	 */
	public function get_recorded_coupon_usage_counts( $order ) {
		return er_string_to_bool( get_post_meta( is_integer( $order ) ? $order : $order->get_id(), '_recorded_coupon_usage_counts', true ) );
	}

	/**
	 * Stores information about whether coupon counts were updated.
	 *
	 * @param ER_Order|int $order Order ID or order object.
	 * @param bool         $set True or false.
	 */
	public function set_recorded_coupon_usage_counts( $order, $set ) {
		update_post_meta( is_integer( $order ) ? $order : $order->get_id(), '_recorded_coupon_usage_counts', er_bool_to_string( $set ) );
	}

	/**
	 * Return array of coupon_code => meta_key for coupon which have usage limit and have tentative keys.
	 * Pass $coupon_id if key for only one of the coupon is needed.
	 *
	 * @param ER_Order $order Order object.
	 * @param int      $coupon_id If passed, will return held key for that coupon.
	 *
	 * @return array|string Key value pair for coupon code and meta key name. If $coupon_id is passed, returns meta_key for only that coupon.
	 */
	public function get_coupon_held_keys( $order, $coupon_id = null ) {
		$held_keys = $order->get_meta( '_coupon_held_keys' );
		if ( $coupon_id ) {
			return isset( $held_keys[ $coupon_id ] ) ? $held_keys[ $coupon_id ] : null;
		}

		return $held_keys;
	}

	/**
	 * Return array of coupon_code => meta_key for coupon which have usage limit per customer and have tentative keys.
	 *
	 * @param ER_Order $order Order object.
	 * @param int      $coupon_id If passed, will return held key for that coupon.
	 *
	 * @return mixed
	 */
	public function get_coupon_held_keys_for_users( $order, $coupon_id = null ) {
		$held_keys_for_user = $order->get_meta( '_coupon_held_keys_for_users' );
		if ( $coupon_id ) {
			return isset( $held_keys_for_user[ $coupon_id ] ) ? $held_keys_for_user[ $coupon_id ] : null;
		}

		return $held_keys_for_user;
	}

	/**
	 * Add/Update list of meta keys that are currently being used by this order to hold a coupon.
	 * This is used to figure out what all meta entries we should delete when order is cancelled/completed.
	 *
	 * @param ER_Order $order Order object.
	 * @param array    $held_keys Array of coupon_code => meta_key.
	 * @param array    $held_keys_for_user Array of coupon_code => meta_key for held coupon for user.
	 *
	 * @return mixed
	 */
	public function set_coupon_held_keys( $order, $held_keys, $held_keys_for_user ) {
		if ( is_array( $held_keys ) && 0 < count( $held_keys ) ) {
			$order->update_meta_data( '_coupon_held_keys', $held_keys );
		}
		if ( is_array( $held_keys_for_user ) && 0 < count( $held_keys_for_user ) ) {
			$order->update_meta_data( '_coupon_held_keys_for_users', $held_keys_for_user );
		}
	}

	/**
	 * Release all coupons held by this order.
	 *
	 * @param ER_Order $order Current order object.
	 * @param bool     $save Whether to delete keys from DB right away. Could be useful to pass `false` if you are building a bulk request.
	 */
	public function release_held_coupons( $order, $save = true ) {
		$coupon_held_keys = $this->get_coupon_held_keys( $order );
		if ( is_array( $coupon_held_keys ) ) {
			foreach ( $coupon_held_keys as $coupon_id => $meta_key ) {
				delete_post_meta( $coupon_id, $meta_key );
			}
		}
		$order->delete_meta_data( '_coupon_held_keys' );

		$coupon_held_keys_for_users = $this->get_coupon_held_keys_for_users( $order );
		if ( is_array( $coupon_held_keys_for_users ) ) {
			foreach ( $coupon_held_keys_for_users as $coupon_id => $meta_key ) {
				delete_post_meta( $coupon_id, $meta_key );
			}
		}
		$order->delete_meta_data( '_coupon_held_keys_for_users' );

		if ( $save ) {
			$order->save_meta_data();
		}
	}

	/**
	 * Get the order type based on Order ID.
	 *
	 * @param int|WP_Post $order Order | Order id.     *
	 *
	 * @return string
	 */
	public function get_order_type( $order ) {
		return get_post_type( $order );
	}

	/**
	 * Get valid WP_Query args from a ER_Order_Query's query variables.
	 *
	 * @param array $query_vars query vars from a ER_Order_Query.
	 *
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {

		// Map query vars to ones that get_wp_query_args or WP_Query recognize.
		$key_mapping = array(
			'customer_id'    => 'customer_user',
			'status'         => 'post_status',
			'discount_total' => 'discount',
			'discount_tax'   => 'discount_tax',
			'cart_tax'       => 'order_tax',
			'total'          => 'order_total',
			'page'           => 'paged',
		);

		foreach ( $key_mapping as $query_key => $db_key ) {
			if ( isset( $query_vars[ $query_key ] ) ) {
				$query_vars[ $db_key ] = $query_vars[ $query_key ];
				unset( $query_vars[ $query_key ] );
			}
		}

		$wp_query_args = parent::get_wp_query_args( $query_vars );

		if ( ! isset( $wp_query_args['date_query'] ) ) {
			$wp_query_args['date_query'] = array();
		}
		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array();
		}

		$date_queries = array(
			'date_created'   => 'post_date',
			'date_modified'  => 'post_modified',
			'date_completed' => '_date_completed',
			'date_paid'      => '_date_paid',
		);
		foreach ( $date_queries as $query_var_key => $db_key ) {
			if ( isset( $query_vars[ $query_var_key ] ) && '' !== $query_vars[ $query_var_key ] ) {

				// Remove any existing meta queries for the same keys to prevent conflicts.
				$existing_queries = wp_list_pluck( $wp_query_args['meta_query'], 'key', true );
				$meta_query_index = array_search( $db_key, $existing_queries, true );
				if ( false !== $meta_query_index ) {
					unset( $wp_query_args['meta_query'][ $meta_query_index ] );
				}

				$wp_query_args = $this->parse_date_for_wp_query( $query_vars[ $query_var_key ], $db_key, $wp_query_args );
			}
		}

		if ( isset( $query_vars['customer'] ) && '' !== $query_vars['customer'] && array() !== $query_vars['customer'] ) {
			$values         = is_array( $query_vars['customer'] ) ? $query_vars['customer'] : array( $query_vars['customer'] );
			$customer_query = $this->get_orders_generate_customer_meta_query( $values );
			if ( is_wp_error( $customer_query ) ) {
				$wp_query_args['errors'][] = $customer_query;
			} else {
				$wp_query_args['meta_query'][] = $customer_query;
			}
		}

		if ( isset( $query_vars['anonymized'] ) ) {
			if ( $query_vars['anonymized'] ) {
				$wp_query_args['meta_query'][] = array(
					'key'   => '_anonymized',
					'value' => 'yes',
				);
			} else {
				$wp_query_args['meta_query'][] = array(
					'key'     => '_anonymized',
					'compare' => 'NOT EXISTS',
				);
			}
		}

		if ( ! isset( $query_vars['paginate'] ) || ! $query_vars['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		return apply_filters( 'easyreservations_order_data_store_cpt_get_orders_query', $wp_query_args, $query_vars, $this );
	}

	/**
	 * Query for Orders matching specific criteria.
	 *
	 * @param array $query_vars query vars from a ER_Order_Query.
	 *
	 * @return array|object
	 */
	public function query( $query_vars ) {
		$args = $this->get_wp_query_args( $query_vars );

		if ( ! empty( $args['errors'] ) ) {
			$query = (object) array(
				'posts'         => array(),
				'found_posts'   => 0,
				'max_num_pages' => 0,
			);
		} else {
			$query = new WP_Query( $args );
		}

		$orders = ( isset( $query_vars['return'] ) && 'ids' === $query_vars['return'] ) ? $query->posts : array_filter( array_map( 'er_get_order', $query->posts ) );

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			return (object) array(
				'orders'        => $orders,
				'total'         => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
			);
		}

		return $orders;
	}

	//TODO caching from w.c
}
