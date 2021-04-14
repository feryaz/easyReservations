<?php
/**
 * Abstract_ER_Order_Data_Store_CPT class file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Order Data Store: Stored in CPT.
 */
abstract class Abstract_ER_Order_Data_Store_CPT extends Abstract_ER_Receipt_Data_Store implements
	ER_Object_Data_Store_Interface, ER_Abstract_Order_Data_Store_Interface {

	/**
	 * Internal meta type used to store order data.
	 *
	 * @var string
	 */
	protected $meta_type = 'post';

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_discount',
		'_discount_tax',
		'_order_tax',
		'_order_total',
		'_prices_include_tax',
		'_payment_tokens',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new order in the database.
	 *
	 * @param ER_Order $order Order object.
	 */
	public function create( &$order ) {
		if ( ! $order->get_date_created() ) {
			$order->set_date_created( time() );
		}

		$order_status = $order->get_status( 'edit' );

		if ( ! $order_status ) {
			$order_status = apply_filters( 'easyreservations_default_order_status', 'pending' );
		}

		$id = wp_insert_post(
			apply_filters(
				'easyreservations_new_order_data',
				array(
					'post_date'     => gmdate( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getOffsetTimestamp() ),
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
					'post_type'     => $order->get_type(),
					'post_status'   => $order_status,
					'ping_status'   => 'closed',
					'post_parent'   => $order->get_parent_id() ? $order->get_parent_id() : 0,
					'post_author'   => $order->get_user_id() ? $order->get_user_id() : 1,
					'post_title'    => $this->get_post_title(),
					'post_password' => er_generate_order_key(),
					'post_excerpt'  => $this->get_post_excerpt( $order ),
				)
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$order->set_id( $id );
			$this->update_post_meta( $order );
			$order->save_meta_data();
			$order->apply_changes();
			$this->clear_caches( $order );
		}
	}

	/**
	 * Method to read an order from the database.
	 *
	 * @param ER_Order $order Order object.
	 *
	 * @throws Exception If passed order is invalid.
	 */
	public function read( &$order ) {
		$order->set_defaults();
		$post_object = get_post( $order->get_id() );

		if ( ! $order->get_id() || ! $post_object || ! in_array( $post_object->post_type, array(
				'easy_order',
				'easy_order_refund'
			), true ) ) {
			throw new Exception( __( 'Invalid order.', 'easyReservations' ) );
		}

		$order->set_props(
			array(
				'parent_id'     => $post_object->post_parent,
				'date_created'  => $this->string_to_timestamp( $post_object->post_date_gmt ),
				'date_modified' => $this->string_to_timestamp( $post_object->post_modified_gmt ),
				'status'        => $post_object->post_status,
			)
		);

		$this->read_order_data( $order, $post_object );
		$order->read_meta_data();
		$order->set_object_read( true );
	}

	/**
	 * Method to update an order in the database.
	 *
	 * @param ER_Order $order Order object.
	 */
	public function update( &$order ) {
		$order->save_meta_data();

		if ( null === $order->get_date_created( 'edit' ) ) {
			$order->set_date_created( time() );
		}

		$changes = $order->get_changes();

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'date_created', 'date_modified', 'status', 'parent_id', 'post_excerpt', 'customer_id' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_date'         => gmdate( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
				'post_status'       => $order->get_status(),
				'post_author'       => $order->get_user_id(),
				'post_parent'       => $order->get_parent_id(),
				'post_excerpt'      => $this->get_post_excerpt( $order ),
				'post_modified'     => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $order->get_date_modified( 'edit' )->getOffsetTimestamp() ) : current_time( 'mysql' ),
				'post_modified_gmt' => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $order->get_date_modified( 'edit' )->getTimestamp() ) : current_time( 'mysql', 1 ),
			);

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $order->get_id() ) );
				clean_post_cache( $order->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $order->get_id() ), $post_data ) );
			}

			$order->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}

		$this->update_post_meta( $order );
		$order->apply_changes();
		$this->clear_caches( $order );
	}

	/**
	 * Method to delete an order from the database.
	 *
	 * @param ER_Order $order Order object.
	 * @param array    $args Array of args to pass to the delete method.
	 *
	 * @return void
	 */
	public function delete( &$order, $args = array() ) {
		$id   = $order->get_id();
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
			wp_delete_post( $id );
			$order->set_id( 0 );
			do_action( 'easyreservations_delete_order', $id );
		} else {
			wp_trash_post( $id );
			$order->set_status( 'trash' );
			do_action( 'easyreservations_trash_order', $id );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Excerpt for post.
	 *
	 * @param ER_order $order Order object.
	 *
	 * @return string
	 */
	protected function get_post_excerpt( $order ) {
		return '';
	}

	/**
	 * Get a title for the new post type.
	 *
	 * @return string
	 */
	protected function get_post_title() {
		// @codingStandardsIgnoreStart
		/* translators: %s: Order date */
		return sprintf( __( 'Order &ndash; %s', 'easyReservations' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'easyReservations' ) ) );
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Read order data. Can be overridden by child classes to load other props.
	 *
	 * @param ER_Order $order Order object.
	 * @param object   $post_object Post object.
	 */
	protected function read_order_data( &$order, $post_object ) {
		$id = $order->get_id();

		$order->set_props(
			array(
				'discount_total'     => get_post_meta( $id, '_discount', true ),
				'discount_tax'       => get_post_meta( $id, '_discount_tax', true ),
				'total_tax'          => get_post_meta( $id, '_order_tax', true ),
				'total'              => get_post_meta( $id, '_order_total', true ),
				'prices_include_tax' => metadata_exists( 'post', $id, '_prices_include_tax' ) ? 'yes' === get_post_meta( $id, '_prices_include_tax', true ) : er_prices_include_tax(),
			)
		);

		// Gets extra data associated with the order if needed.
		foreach ( $order->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $order, $function ) ) ) {
				$order->{$function}( get_post_meta( $order->get_id(), '_' . $key, true ) );
			}
		}
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the ER_Order class.
	 *
	 * @param ER_Order $order Order object.
	 */
	protected function update_post_meta( &$order ) {
		$updated_props     = array();
		$meta_key_to_props = array(
			'_discount'           => 'discount_total',
			'_discount_tax'       => 'discount_tax',
			'_order_tax'          => 'total_tax',
			'_order_total'        => 'total',
			'_prices_include_tax' => 'prices_include_tax',
		);

		$props_to_update = $this->get_props_to_update( $order, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $order->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			if ( 'prices_include_tax' === $prop ) {
				$value = $value ? 'yes' : 'no';
			}

			$updated = $this->update_or_delete_post_meta( $order, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		do_action( 'easyreservations_order_object_updated_props', $order, $updated_props );
	}

	/**
	 * Clear any caches.
	 *
	 * @param ER_Order $order Order object.
	 */
	protected function clear_caches( &$order ) {
		clean_post_cache( $order->get_id() );
		er_delete_easy_order_transients( $order );
		wp_cache_delete( 'receipt-items-' . $order->get_id(), 'order' );
	}

	/**
	 * Get token ids for an order.
	 *
	 * @param ER_Order $order Order object.
	 *
	 * @return array
	 */
	public function get_payment_token_ids( $order ) {
		$token_ids = array_filter( (array) get_post_meta( $order->get_id(), '_payment_tokens', true ) );

		return $token_ids;
	}

	/**
	 * Update token ids for an order.
	 *
	 * @param ER_Order $order Order object.
	 * @param array    $token_ids Payment token ids.
	 */
	public function update_payment_token_ids( $order, $token_ids ) {
		update_post_meta( $order->get_id(), '_payment_tokens', $token_ids );
	}
}
