<?php
defined( 'ABSPATH' ) || exit;

/**
 * Register order post type
 */
function er_order_register_post_type() {
	$args = array(
		'labels'              => array(
			'name'                  => __( 'Orders', 'easyReservations' ),
			'singular_name'         => _x( 'Order', 'easy_order post type singular name', 'easyReservations' ),
			'add_new'               => __( 'Add order', 'easyReservations' ),
			'add_new_item'          => __( 'Add new order', 'easyReservations' ),
			'edit'                  => __( 'Edit', 'easyReservations' ),
			'edit_item'             => __( 'Edit order', 'easyReservations' ),
			'new_item'              => __( 'New order', 'easyReservations' ),
			'view_item'             => __( 'View order', 'easyReservations' ),
			'search_items'          => __( 'Search orders', 'easyReservations' ),
			'not_found'             => __( 'No orders found', 'easyReservations' ),
			'not_found_in_trash'    => __( 'No orders found in trash', 'easyReservations' ),
			'parent'                => __( 'Parent orders', 'easyReservations' ),
			'menu_name'             => _x( 'Orders', 'Admin menu name', 'easyReservations' ),
			'filter_items_list'     => __( 'Filter orders', 'easyReservations' ),
			'items_list_navigation' => __( 'Orders navigation', 'easyReservations' ),
			'items_list'            => __( 'Orders list', 'easyReservations' ),
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'map_meta_cap'        => true,
		'show_in_menu'        => current_user_can( 'manage_easyreservations' ) ? 'reservations' : true,
		'query_var'           => false,
		'rewrite'             => false,
		'capability_type'     => 'easy_order',
		'has_archive'         => false,
		'hierarchical'        => false,
		'menu_position'       => null,
		'show_in_nav_menus'   => false,
		'delete_with_user'    => false,
		'supports'            => array(
			'title',
			'excerpts',
			'comments',
			'custom-fields',
		)
	);

	register_post_type( 'easy_order', apply_filters( 'easyreservations_register_order_post_type', $args ) );

	register_post_type(
		'easy_order_refund',
		apply_filters(
			'easyreservations_register_order_refund_post_type',
			array(
				'label'           => __( 'Refunds', 'easyReservations' ),
				'capability_type' => 'easy_order',
				'public'          => false,
				'hierarchical'    => false,
				'supports'        => false,
				'rewrite'         => false,
			)
		)
	);

	do_action( 'easyreservations_after_register_order_post_type' );
}

add_action( 'init', 'er_order_register_post_type' );

/**
 * Register order post statuses
 */
function er_order_register_post_status() {
	$order_statuses = apply_filters(
		'easyreservations_register_order_post_statuses',
		array(
			'pending'    => array(
				'label'                     => _x( 'Pending payment', 'Order status', 'easyReservations' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Pending payment <span class="count">(%s)</span>', 'Pending payment <span class="count">(%s)</span>', 'easyReservations' ),
			),
			'processing' => array(
				'label'                     => _x( 'Processing', 'Order status', 'easyReservations' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>', 'easyReservations' ),
			),
			'on-hold'    => array(
				'label'                     => _x( 'On hold', 'Order status', 'easyReservations' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'On hold <span class="count">(%s)</span>', 'On hold <span class="count">(%s)</span>', 'easyReservations' ),
			),
			'completed'  => array(
				'label'                     => _x( 'Completed', 'Order status', 'easyReservations' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'easyReservations' ),
			),
			'cancelled'  => array(
				'label'                     => _x( 'Cancelled', 'Order status', 'easyReservations' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'easyReservations' ),
			),
			'refunded'   => array(
				'label'                     => _x( 'Refunded', 'Order status', 'easyReservations' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'easyReservations' ),
			),
			'failed'     => array(
				'label'                     => _x( 'Failed', 'Order status', 'easyReservations' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'easyReservations' ),
			),
		)
	);

	foreach ( $order_statuses as $order_status => $values ) {
		register_post_status( $order_status, $values );
	}
}

add_action( 'init', 'er_order_register_post_status' );

/**
 * Main function for returning orders.
 *
 * @param mixed $order Post object or post ID of the order.
 *
 * @return bool|ER_Order|ER_Order_Refund
 */
function er_get_order( $order = false ) {
	global $post;

	if ( ! did_action( 'easyreservations_after_register_order_post_type' ) ) {
		_doing_it_wrong( __FUNCTION__, 'er_get_order should not be called before post types are registered (easyreservations_after_register_order_post_type action)', '2.5' );

		return false;
	}

	if ( false === $order && is_a( $post, 'WP_Post' ) && 'easy_order' === get_post_type( $post ) ) {
		$order_id = absint( $post->ID );
	} elseif ( is_numeric( $order ) ) {
		$order_id = $order;
	} elseif ( $order instanceof ER_Abstract_Order ) {
		$order_id = $order->get_id();
	} elseif ( ! empty( $order->ID ) ) {
		$order_id = $order->ID;
	} else {
		return false;
	}

	$order_type = ER_Data_Store::load( 'order' )->get_order_type( $order_id );
	if ( $order_type === 'easy_order_refund' ) {
		$classname = 'ER_Order_Refund';
	} else {
		$classname = 'ER_Order';
	}

	// Filter classname so that the class can be overridden if extended.
	$classname = apply_filters( 'easyreservations_order_class', $classname, $order_type, $order_id );

	if ( ! class_exists( $classname ) ) {
		return false;
	}

	try {
		return new $classname( $order_id );
	} catch ( Exception $e ) {
		er_get_logger()->error( 'Cannot get order. ' . $e->getMessage() );

		return false;
	}
}

/**
 * Get order data from [tag]
 *
 * @param array $tag
 * @param ER_Order $order
 *
 * @return string
 */
function er_order_parse_tag( $tag, $order ) {
	$type = sanitize_key( $tag[0] );

	switch ( $type ) {
		case 'custom':
			$content = '';

			if ( isset( $tag['id'] ) ) {
				$custom_id = absint( $tag['id'] );

				foreach ( $order->get_items( 'custom' ) as $custom_item ) {
					if ( $custom_item->get_custom_id() === $custom_id ) {
						return $custom_item->get_custom_display();
					}
				}

				$custom_fields = ER_Custom_Data::get_settings();

				if ( isset( $custom_fields[ $custom_id ], $custom_fields[ $custom_id ]['unused'] ) ) {
					return $custom_fields[ $custom_id ]['unused'];
				}
			}

			return $content;
			break;
		default:
			return apply_filters( 'easyreservations_order_parse_tag_' . $type, '', $order, $tag );
			break;
	}
}

/**
 * Cancel all unpaid orders after held duration to prevent stock lock for those resources.
 */
function er_cancel_unpaid_orders() {
	$held_duration = get_option( 'reservations_wait_for_payment_minutes' );

	if ( $held_duration < 1 ) {
		return;
	}

	$data_store    = ER_Data_Store::load( 'order' );
	$unpaid_orders = $data_store->get_unpaid_orders( strtotime( '-' . absint( $held_duration ) . ' MINUTES', current_time( 'timestamp' ) ) );

	if ( $unpaid_orders ) {
		foreach ( $unpaid_orders as $unpaid_order ) {
			$order = er_get_order( $unpaid_order );

			if ( apply_filters( 'easyreservations_cancel_unpaid_order', 'checkout' === $order->get_created_via(), $order ) ) {
				$order->update_status( 'cancelled', __( 'Unpaid order cancelled - time limit reached.', 'easyReservations' ) );
			}
		}
	}

	wp_clear_scheduled_hook( 'easyreservations_cancel_unpaid_orders' );
	wp_schedule_single_event( time() + ( absint( $held_duration ) * 60 ), 'easyreservations_cancel_unpaid_orders' );
}

add_action( 'easyreservations_cancel_unpaid_orders', 'er_cancel_unpaid_orders' );

/**
 * Get list of statuses which are considered 'accepted'.
 *
 * @return array
 */
function er_get_is_accepted_statuses() {
	return apply_filters( 'easyreservations_order_is_accepted_statuses', array( 'processing', 'completed' ) );
}

/**
 * Get list of statuses which are considered 'paid'.
 *
 * @return array
 */
function er_get_is_paid_statuses() {
	return apply_filters( 'easyreservations_order_is_paid_statuses', array( 'processing', 'completed' ) );
}

/**
 * Get list of statuses which are considered 'pending payment'.
 *
 * @return array
 */
function er_get_is_pending_statuses() {
	return apply_filters( 'easyreservations_order_is_pending_statuses', array( 'pending' ) );
}

/**
 * Main function for returning refunds.
 *
 * @param mixed $the_order Post object or post ID of the order.
 *
 * @return bool|ER_Order_Refund
 */
function er_get_order_refund( $the_order = false ) {
	if ( ! did_action( 'easyreservations_after_register_order_post_type' ) ) {
		_doing_it_wrong( __FUNCTION__, 'er_get_order should not be called before post types are registered (easyreservations_after_register_order_post_type action)', '2.5' );

		return false;
	}

	return new ER_Order_Refund( $the_order );
}

/**
 * Get orders
 *
 * @param array $args
 *
 * @return ER_Order[]|ER_Order_Refund[]
 */
function er_get_orders( $args ) {
	$query = new ER_Order_Query( $args );

	return $query->get_orders();
}

/**
 * Get order id from order key
 *
 * @param $order_key
 *
 * @return null|string
 */
function er_get_order_id_by_order_key( $order_key ) {
	global $wpdb;

	return $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_order_key' AND meta_value = %s", $order_key ) );
}

/**
 * Get payment gateway class by order data.
 *
 * @param int|ER_Order $order Order instance.
 *
 * @return ER_Payment_Gateway|bool
 */
function er_get_payment_gateway_by_order( $order ) {
	if ( ER()->payment_gateways() ) {
		$payment_gateways = ER()->payment_gateways()->payment_gateways();
	} else {
		$payment_gateways = array();
	}

	if ( ! is_object( $order ) ) {
		$order_id = absint( $order );
		$order    = er_get_order( $order_id );
	}

	return is_a( $order, 'ER_Order' ) && isset( $payment_gateways[ $order->get_payment_method() ] ) ? $payment_gateways[ $order->get_payment_method() ] : false;
}

/**
 * Generate an order key.
 *
 * @param string $key Order key without a prefix. By default generates a 13 digit secret.
 *
 * @return string The order key.
 */
function er_generate_order_key( $key = ''  ) {
	if ( '' === $key ) {
		$key = wp_generate_password( 13, false );
	}

	return 'er_' . apply_filters( 'easyreservations_generate_order_key', 'order_' . $key );
}

/**
 * Get an order note.
 *
 * @param int|WP_Comment $data Note ID (or WP_Comment instance for internal use only).
 *
 * @return stdClass|null        Object with order note details or null when does not exists.
 */
function er_get_order_note( $data ) {
	if ( is_numeric( $data ) ) {
		$data = get_comment( $data );
	}

	if ( ! is_a( $data, 'WP_Comment' ) ) {
		return null;
	}

	return (object) apply_filters(
		'easyreservations_get_order_note',
		array(
			'id'            => (int) $data->comment_ID,
			'date_created'  => er_string_to_datetime( $data->comment_date ),
			'content'       => $data->comment_content,
			'customer_note' => (bool) get_comment_meta( $data->comment_ID, 'is_customer_note', true ),
			'added_by'      => __( 'easyReservations', 'easyReservations' ) === $data->comment_author ? 'system' : $data->comment_author,
		),
		$data
	);
}

/**
 * Get order notes.
 *
 * @param array $args Query arguments {
 *     Array of query parameters.
 *
 * @type string $limit Maximum number of notes to retrieve.
 *                                 Default empty (no limit).
 * @type int    $order_id Limit results to those affiliated with a given order ID.
 *                                 Default 0.
 * @type array  $order__in Array of order IDs to include affiliated notes for.
 *                                 Default empty.
 * @type array  $order__not_in Array of order IDs to exclude affiliated notes for.
 *                                 Default empty.
 * @type string $orderby Define how should sort notes.
 *                                 Accepts 'date_created', 'date_created_gmt' or 'id'.
 *                                 Default: 'id'.
 * @type string $order How to order retrieved notes.
 *                                 Accepts 'ASC' or 'DESC'.
 *                                 Default: 'DESC'.
 * @type string $type Define what type of note should retrieve.
 *                                 Accepts 'customer', 'internal' or empty for both.
 *                                 Default empty.
 * }
 * @return stdClass[]              Array of stdClass objects with order notes details.
 */
function er_get_order_notes( $args ) {
	$key_mapping = array(
		'limit'         => 'number',
		'order_id'      => 'post_id',
		'order__in'     => 'post__in',
		'order__not_in' => 'post__not_in',
	);

	foreach ( $key_mapping as $query_key => $db_key ) {
		if ( isset( $args[ $query_key ] ) ) {
			$args[ $db_key ] = $args[ $query_key ];
			unset( $args[ $query_key ] );
		}
	}

	// Define orderby.
	$orderby_mapping = array(
		'date_created'     => 'comment_date',
		'date_created_gmt' => 'comment_date_gmt',
		'id'               => 'comment_ID',
	);

	$args['orderby'] = ! empty( $args['orderby'] ) && in_array( $args['orderby'], array(
		'date_created',
		'date_created_gmt',
		'id'
	), true ) ? $orderby_mapping[ $args['orderby'] ] : 'comment_ID';

	// Set easyReservations order type.
	if ( isset( $args['type'] ) && 'customer' === $args['type'] ) {
		$args['meta_query'] = array( // WPCS: slow query ok.
		                             array(
			                             'key'     => 'is_customer_note',
			                             'value'   => 1,
			                             'compare' => '=',
		                             ),
		);
	} elseif ( isset( $args['type'] ) && 'internal' === $args['type'] ) {
		$args['meta_query'] = array( // WPCS: slow query ok.
		                             array(
			                             'key'     => 'is_customer_note',
			                             'compare' => 'NOT EXISTS',
		                             ),
		);
	}

	// Set correct comment type.
	$args['type'] = 'er_order_note';

	// Always approved.
	$args['status'] = 'approve';

	// Does not support 'count' or 'fields'.
	unset( $args['count'], $args['fields'] );

	remove_filter( 'comments_clauses', array( 'ER_Comments', 'exclude_order_comments' ), 10 );

	$notes = get_comments( $args );

	add_filter( 'comments_clauses', array( 'ER_Comments', 'exclude_order_comments' ), 10 );

	return array_filter( array_map( 'er_get_order_note', $notes ) );
}

/**
 * Delete an order note.
 *
 * @param int $note_id Order note.
 *
 * @return bool         True on success, false on failure.
 */
function er_delete_order_note( $note_id ) {
	return wp_delete_comment( $note_id, true );
}

/**
 * Adds a note (comment) to the order. Order must exist.
 *
 * @param int    $order_id order id
 * @param string $note Note to add.
 * @param int    $is_customer_note Is this a note for the customer?.
 * @param bool   $added_by_user Was the note added by a user?.
 *
 * @return int                       Comment ID.
 */
function er_order_add_note( $order_id, $note, $is_customer_note = 0, $added_by_user = false ) {
	if ( is_user_logged_in() && current_user_can( 'edit_easy_order', $order_id ) && $added_by_user ) {
		$user                 = get_user_by( 'id', get_current_user_id() );
		$comment_author       = $user->display_name;
		$comment_author_email = $user->user_email;
	} else {
		$comment_author       = __( 'easyReservations', 'easyReservations' );
		$comment_author_email = strtolower( __( 'easyReservations', 'easyReservations' ) ) . '@';
		$comment_author_email .= isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : 'noreply.com'; // WPCS: input var ok.
		$comment_author_email = sanitize_email( $comment_author_email );
	}
	$commentdata = apply_filters(
		'easyreservations_new_order_note_data',
		array(
			'comment_post_ID'      => $order_id,
			'comment_author'       => $comment_author,
			'comment_author_email' => $comment_author_email,
			'comment_author_url'   => '',
			'comment_content'      => $note,
			'comment_agent'        => 'easyReservations',
			'comment_type'         => 'er_order_note',
			'comment_parent'       => 0,
			'comment_approved'     => 1,
		),
		array(
			'order_id'         => $order_id,
			'is_customer_note' => $is_customer_note,
		)
	);

	$comment_id = wp_insert_comment( $commentdata );

	if ( $is_customer_note ) {
		add_comment_meta( $comment_id, 'is_customer_note', 1 );

		do_action(
			'easyreservations_new_customer_note',
			array(
				'order_id'      => $order_id,
				'customer_note' => $commentdata['comment_content'],
			)
		);
	}

	/**
	 * Action hook fired after an order note is added.
	 *
	 * @param int      $order_note_id Order note ID.
	 * @param ER_Order $order Order data.
	 */
	do_action( 'easyreservations_order_note_added', $comment_id, $order_id );

	return $comment_id;
}

/**
 * Clear all transients cache for order data.
 *
 * @param int|ER_Order $order Order instance or ID.
 */
function er_delete_easy_order_transients( $order = 0 ) {
	if ( is_numeric( $order ) ) {
		$order = er_get_order( $order );
	}

	$transients_to_clear = array(
		'er_admin_report',
	);
	/**
	 * $reports = ER_Admin_Reports::get_reports();
	 * foreach ( $reports as $report_group ) {
	 * foreach ( $report_group['reports'] as $report_key => $report ) {
	 * $transients_to_clear[] = 'wc_report_' . $report_key;
	 * }
	 * }**/

	foreach ( $transients_to_clear as $transient ) {
		delete_transient( $transient );
	}

	// Clear money spent for user associated with order.
	if ( is_a( $order, 'ER_Order' ) ) {
		$order_id = $order->get_id();
		delete_user_meta( $order->get_customer_id(), '_money_spent' );
		delete_user_meta( $order->get_customer_id(), '_order_count' );
	} else {
		$order_id = 0;
	}

	// Increments the transient version to invalidate cache.
	er_get_transient_version( 'orders', true );

	// Do the same for regular cache.
	er_invalidate_cache_group( 'orders' );

	do_action( 'easyreservations_delete_easy_order_transients', $order_id );
}

/**
 * Search orders.
 *
 * @param string $term Term to search.
 *
 * @return array List of orders ID.
 */
function er_order_search( $term ) {
	$data_store = ER_Data_Store::load( 'order' );

	return $data_store->search_orders( str_replace( 'Order #', '', er_clean( $term ) ) );
}

/**
 * Create a new order refund programmatically.
 *
 * Returns a new refund object on success which can then be used to add additional data.
 *
 * @param array $args New refund arguments.
 *
 * @return ER_Order_Refund|WP_Error
 * @throws Exception Throws exceptions when fail to create, but returns WP_Error instead.
 */
function er_create_refund( $args = array() ) {
	$default_args = array(
		'amount'         => 0,
		'reason'         => null,
		'order_id'       => 0,
		'refund_id'      => 0,
		'line_items'     => array(),
		'refund_payment' => false,
		'restock_items'  => false,
	);

	try {
		$args  = wp_parse_args( $args, $default_args );
		$order = er_get_order( $args['order_id'] );

		if ( ! $order ) {
			throw new Exception( __( 'Invalid order ID.', 'easyReservations' ) );
		}

		$remaining_refund_amount = $order->get_remaining_refund_amount();
		$refund_item_count       = 0;
		$refund                  = new ER_Order_Refund( $args['refund_id'] );

		if ( 0 > $args['amount'] || $args['amount'] > $remaining_refund_amount ) {
			throw new Exception( __( 'Invalid refund amount.', 'easyReservations' ) );
		}

		$refund->set_amount( $args['amount'] );
		$refund->set_parent_id( absint( $args['order_id'] ) );
		$refund->set_refunded_by( get_current_user_id() ? get_current_user_id() : 1 );

		if ( ! is_null( $args['reason'] ) ) {
			$refund->set_reason( $args['reason'] );
		}

		// Negative line items.
		if ( count( $args['line_items'] ) > 0 ) {
			foreach ( $order->get_items() as $item_id => $item ) {
				if ( ! isset( $args['line_items'][ $item_id ] ) ) {
					continue;
				}

				$refund_total = $args['line_items'][ $item_id ]['refund_total'];
				$refund_tax   = isset( $args['line_items'][ $item_id ]['refund_tax'] ) ? array_filter( (array) $args['line_items'][ $item_id ]['refund_tax'] ) : array();

				if ( empty( $refund_total ) && empty( $args['line_items'][ $item_id ]['refund_tax'] ) ) {
					continue;
				}

				$class         = get_class( $item );
				$refunded_item = new $class( $item );
				$refunded_item->set_id( 0 );
				$refunded_item->update_meta_data( '_refunded_item_id', $item_id, true );
				$refunded_item->set_total( er_format_refund_total( $refund_total ) );
				$refunded_item->set_taxes(
					array(
						'total'    => array_map( 'er_format_refund_total', $refund_tax ),
						'subtotal' => array_map( 'er_format_refund_total', $refund_tax ),
					)
				);

				if ( is_callable( array( $refunded_item, 'set_subtotal' ) ) ) {
					$refunded_item->set_subtotal( er_format_refund_total( $refund_total ) );
				}

				$refund->add_item( $refunded_item );
				$refund_item_count += 1;
			}
		}

		$refund->update_taxes( false );
		$refund->calculate_totals( false );
		$refund->set_total( $args['amount'] * - 1 );

		// this should remain after update_taxes(), as this will save the order, and write the current date to the db
		// so we must wait until the order is persisted to set the date.
		if ( isset( $args['date_created'] ) ) {
			$refund->set_date_created( $args['date_created'] );
		}

		/**
		 * Action hook to adjust refund before save.
		 */
		do_action( 'easyreservations_create_refund', $refund, $args );

		if ( $refund->save() ) {
			if ( $args['refund_payment'] ) {
				$result = ER()->payment_gateways()->refund( $order, $refund );

				if ( $result ) {
					if ( is_wp_error( $result ) ) {
						$refund->delete();

						return $result;
					}

					$refund->set_refunded_payment( true );
					$refund->save();
				}
			}

			if ( $args['restock_items'] ) {
				foreach ( $order->get_reservations() as $reservation_item ) {
					$reservation = $reservation_item->get_reservation();
					if ( $reservation ) {
						$reservation->set_status( 'cancelled' );
						$reservation->save();
					}
				}
			}

			// Trigger notification emails.
			if ( ( $remaining_refund_amount - $args['amount'] ) > 0 ) {
				do_action( 'easyreservations_order_partially_refunded', $order->get_id(), $refund->get_id() );
			} else {
				do_action( 'easyreservations_order_fully_refunded', $order->get_id(), $refund->get_id() );

				$parent_status = apply_filters( 'easyreservations_order_fully_refunded_status', 'refunded', $order->get_id(), $refund->get_id() );

				if ( $parent_status ) {
					$order->update_status( $parent_status );
				}
			}

			$order->save();
		}

		do_action( 'easyreservations_refund_created', $refund->get_id(), $args );
		do_action( 'easyreservations_order_refunded', $order->get_id(), $refund->get_id() );
	} catch ( Exception $e ) {
		if ( isset( $refund ) && is_a( $refund, 'ER_Order_Refund' ) ) {
			wp_delete_post( $refund->get_id(), true );
		}

		return new WP_Error( 'error', $e->getMessage() );
	}

	return $refund;
}

/**
 * Wether the status of the order can be set to an completed status
 *
 * @param ER_Order $order
 *
 * @return bool
 */
function er_order_reservations_approved_and_existing( $order ){
	$reservation_items = $order->get_reservations();

	if ( $reservation_items ) {
		foreach ( $reservation_items as $item ) {
			$reservation_id = $item->get_reservation_id();
			$reservation    = er_get_reservation( $reservation_id );

			if ( ! $reservation ) {
				return false;
			} elseif ( ! in_array( $reservation->get_status(), er_reservation_get_approved_statuses() ) ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Returns full name of a country
 *
 * @param string $country
 *
 * @return string
 */
function er_get_country_name( $country ) {
	if ( ! empty( $country ) ) {
		$country_array = er_form_country_options();
		if ( isset( $country_array[ $country ] ) ) {
			return $country_array[ $country ];
		} else {
			return __( 'Unknown', 'easyReservations' );
		}
	}

	return $country;
}
