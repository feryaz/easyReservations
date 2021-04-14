<?php
/**
 * Post Data
 *
 * Standardises certain post data on save.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post data class.
 */
class ER_Post_Data {
	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'set_object_terms', array( __CLASS__, 'delete_resource_query_transients' ) );
		add_action( 'deleted_term_relationships', array( __CLASS__, 'delete_resource_query_transients' ) );
		add_action( 'easyreservations_resource_set_visibility', array( __CLASS__, 'delete_resource_query_transients' ) );

		add_filter( 'update_receipt_item_metadata', array( __CLASS__, 'update_receipt_item_metadata' ), 10, 5 );
		add_filter( 'update_post_metadata', array( __CLASS__, 'update_post_metadata' ), 10, 5 );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'wp_insert_post_data' ) );
		add_filter( 'oembed_response_data', array( __CLASS__, 'filter_oembed_response_data' ), 10, 2 );
		add_filter( 'wp_untrash_post_status', array( __CLASS__, 'wp_untrash_post_status' ), 10, 3 );

		// Status transitions.
		add_action( 'delete_post', array( __CLASS__, 'delete_post' ) );
		add_action( 'wp_trash_post', array( __CLASS__, 'trash_post' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'untrash_post' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'before_delete_order' ) );
		add_action( 'transition_post_status', array( __CLASS__, 'transition_post_status' ), 10, 3 );

		// Meta cache flushing.
		add_action( 'updated_post_meta', array( __CLASS__, 'flush_object_meta_cache' ), 10, 4 );
		add_action( 'updated_order_item_meta', array( __CLASS__, 'flush_object_meta_cache' ), 10, 4 );
	}

	/**
	 * Ensure floats are correctly converted to strings based on PHP locale.
	 *
	 * @param null   $check Whether to allow updating metadata for the given type.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value. Must be serializable if non-scalar.
	 * @param mixed  $prev_value If specified, only update existing metadata entries with the specified value. Otherwise, update all entries.
	 *
	 * @return null|bool
	 */
	public static function update_receipt_item_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( ! empty( $meta_value ) && is_float( $meta_value ) ) {

			// Convert float to string.
			$meta_value = er_float_to_string( $meta_value );

			// Update meta value with new string.
			update_metadata( 'receipt_item', $object_id, $meta_key, $meta_value, $prev_value );

			return true;
		}

		return $check;
	}

	/**
	 * Ensure floats are correctly converted to strings based on PHP locale.
	 *
	 * @param null   $check Whether to allow updating metadata for the given type.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value. Must be serializable if non-scalar.
	 * @param mixed  $prev_value If specified, only update existing metadata entries with the specified value. Otherwise, update all entries.
	 *
	 * @return null|bool
	 */
	public static function update_post_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		// Delete resource cache if someone uses meta directly.
		if ( get_post_type( $object_id ) === 'easy-rooms' ) {
			wp_cache_delete( 'resource-' . $object_id, 'resources' );
		}

		if ( ! empty( $meta_value ) && is_float( $meta_value ) && ! registered_meta_key_exists( 'post', $meta_key ) && in_array( get_post_type( $object_id ), array(
				'easy-rooms',
				'easy_order',
				'easy_order_refund'
			), true ) ) {

			// Convert float to string.
			$meta_value = er_float_to_string( $meta_value );

			// Update meta value with new string.
			update_metadata( 'post', $object_id, $meta_key, $meta_value, $prev_value );

			return true;
		}

		return $check;
	}

	/**
	 * Forces the order posts to have a title in a certain format (containing the date).
	 * Forces certain resource data based on the resource's type, e.g. grouped resources cannot have a parent.
	 *
	 * @param array $data An array of slashed post data.
	 *
	 * @return array
	 */
	public static function wp_insert_post_data( $data ) {
		if ( 'easy_order' === $data['post_type'] && isset( $data['post_date'] ) ) {
			$order_title = 'Order';
			if ( $data['post_date'] ) {
				$order_title .= ' &ndash; ' . date_i18n( 'F j, Y @ h:i A', strtotime( $data['post_date'] ) );
			}
			$data['post_title'] = $order_title;
		} elseif ( 'easy_coupon' === $data['post_type'] ) {
			// Coupons should never allow unfiltered HTML.
			$data['post_title'] = wp_filter_kses( $data['post_title'] );
		}
		return $data;
	}

	/**
	 * Change embed data for certain post types.
	 *
	 * @param array   $data The response data.
	 * @param WP_Post $post The post object.
	 *
	 * @return array
	 */
	public static function filter_oembed_response_data( $data, $post ) {
		if ( in_array( $post->post_type, array( 'easy_order' ), true ) ) {
			return array();
		}

		return $data;
	}

	/**
	 * Removes variations etc belonging to a deleted post, and clears transients.
	 *
	 * @param mixed $id ID of post being deleted.
	 */
	public static function delete_post( $id ) {
		global $wpdb;

		if ( ! current_user_can( 'delete_posts' ) || ! $id ) {
			return;
		}

		$post_type = get_post_type( $id );

		switch ( $post_type ) {
			case 'easy-rooms':

				break;
			case 'easy_order':
				//Delete refunds
				$refunds = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'easy_order_refund' AND post_parent = %d", $id ) );

				if ( ! is_null( $refunds ) ) {
					foreach ( $refunds as $refund ) {
						wp_delete_post( $refund->ID, true );
					}
				}

				er_delete_easy_order_transients( $id );

				break;
		}
	}

	/**
	 * Trash post.
	 *
	 * @param mixed $id Post ID.
	 */
	public static function trash_post( $id ) {
		global $wpdb;

		if ( ! $id ) {
			return;
		}

		$post_type = get_post_type( $id );

		// If this is an order, trash any refunds too.
		if ( $post_type === 'easy_order' ) {
			$order = er_get_order( $id );

			//Reactivate refunds
			$refunds = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'easy_order_refund' AND post_parent = %d", $id ) );

			foreach ( $refunds as $refund ) {
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'trash' ), array( 'ID' => $refund->ID ) );
			}

			//Set reservations status to canceled
			foreach ( $order->get_reservations() as $reservation_item ) {
				$reservation = $reservation_item->get_reservation();

				if ( $reservation ) {
					$reservation->set_status( ER_Reservation_Status::CANCELLED );
					$reservation->save();
				}
			}

			er_delete_easy_order_transients( $id );
		}
	}

	/**
	 * Untrash post.
	 *
	 * @param mixed $id Post ID.
	 */
	public static function untrash_post( $id ) {
		if ( ! $id ) {
			return;
		}

		$post_type = get_post_type( $id );

		if ( $post_type === 'easy_order' ) {
			global $wpdb;

			$order = er_get_order( $id );

			//Reactivate refunds
			$refunds = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'easy_order_refund' AND post_parent = %d", $order->get_id() ) );

			foreach ( $refunds as $refund ) {
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'completed' ), array( 'ID' => $refund->ID ) );
			}

			//Set reservation status back to pending
			foreach ( $order->get_reservations() as $reservation_item ) {
				$reservation = $reservation_item->get_reservation();

				if ( $reservation ) {
					$reservation->set_status( ER_Reservation_Status::PENDING );
					$reservation->save();
				}
			}

			er_delete_easy_order_transients( $id );
		}
	}

	/**
	 * Before deleting an order, do some cleanup.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function before_delete_order( $order_id ) {
		if ( in_array( get_post_type( $order_id ), array( 'easy_order', 'easy_order_refund' ), true ) ) {
			// Clean up user.
			$order = er_get_order( $order_id );

			// Check for `get_customer_id`, since this may be e.g. a refund order (which doesn't implement it).
			$customer_id = is_callable( array( $order, 'get_customer_id' ) ) ? $order->get_customer_id() : 0;

			if ( $customer_id > 0 && 'easy_order' === $order->get_type() ) {
				$customer    = new ER_Customer( $customer_id );
				$order_count = $customer->get_order_count();
				$order_count --;

				if ( 0 === $order_count ) {
					$customer->set_is_paying_customer( false );
					$customer->save();
				}

				// Delete order count and last order meta.
				delete_user_meta( $customer_id, '_order_count' );
				delete_user_meta( $customer_id, '_last_order' );
			}

			if ( 'easy_order' === $order->get_type() ) {
				foreach ( $order->get_reservations() as $reservation_item ) {
					$reservation = $reservation_item->get_reservation();

					if ( $reservation ) {
						$reservation->delete( true );
					}
				}
			}

			// Clean up items.
			self::delete_order_items( $order_id );
		}
	}

	/**
	 * Remove item meta on permanent deletion.
	 *
	 * @param int $postid Post ID.
	 */
	public static function delete_order_items( $postid ) {
		global $wpdb;

		if ( in_array( get_post_type( $postid ), array( 'easy_order', 'easy_order_refund' ), true ) ) {
			do_action( 'easyreservations_delete_delete_items', $postid );

			$wpdb->query(
				"
				DELETE {$wpdb->prefix}receipt_items, {$wpdb->prefix}receipt_itemmeta
				FROM {$wpdb->prefix}receipt_items
				JOIN {$wpdb->prefix}receipt_itemmeta ON {$wpdb->prefix}receipt_items.receipt_item_id = {$wpdb->prefix}receipt_itemmeta.receipt_item_id
				WHERE {$wpdb->prefix}receipt_items.receipt_object_id = '{$postid}';
				"
			); // WPCS: unprepared SQL ok.

			do_action( 'easyreservations_deleted_receipt_items', $postid );
		}
	}

	/**
	 * Flush meta cache for CRUD objects on direct update.
	 *
	 * @param int    $meta_id Meta ID.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key Meta key.
	 * @param string $meta_value Meta value.
	 */
	public static function flush_object_meta_cache( $meta_id, $object_id, $meta_key, $meta_value ) {
		er_invalidate_cache_group( 'object_' . $object_id );
	}

	/**
	 * Ensure statuses are correctly reassigned when restoring orders and products.
	 *
	 * @param string $new_status The new status of the post being restored.
	 * @param int    $post_id The ID of the post being restored.
	 * @param string $previous_status The status of the post at the point where it was trashed.
	 *
	 * @return string
	 */
	public static function wp_untrash_post_status( $new_status, $post_id, $previous_status ) {
		$post_types = array( 'easy_order', 'easy_coupon', 'easy-rooms' );

		if ( in_array( get_post_type( $post_id ), $post_types, true ) ) {
			$new_status = $previous_status;
		}

		return $new_status;
	}

	/**
	 * When a post status changes.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post Post data.
	 */
	public static function transition_post_status( $new_status, $old_status, $post ) {
		if ( ( 'publish' === $new_status || 'publish' === $old_status ) && $post->post_type === 'easy-rooms' ) {
			self::delete_resource_query_transients();
		}
	}

	/**
	 * Delete resource view transients when needed e.g. when post status changes, or visibility/stock status is modified.
	 */
	public static function delete_resource_query_transients() {
		er_get_transient_version( 'resource_query', true );
	}
}

ER_Post_Data::init();
