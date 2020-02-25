<?php
/**
 * Class Abstract_ER_Receipt_Item_Type_Data_Store file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER Receipt Item Data Store
 */
abstract class Abstract_ER_Receipt_Item_Type_Data_Store extends ER_Data_Store_WP implements ER_Object_Data_Store_Interface {

	/**
	 * Meta type. This should match up with
	 * the types available at https://developer.wordpress.org/reference/functions/add_metadata/.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 *
	 * @var string
	 */
	protected $meta_type = 'receipt_item';

	/**
	 * This only needs set if you are using a custom metadata type (for example payment tokens.
	 * This should be the name of the field your table uses for associating meta with objects.
	 * For example, in payment_tokenmeta, this would be payment_token_id.
	 *
	 * @var string
	 */
	protected $object_id_field_for_meta = 'receipt_item_id';

	/**
	 * Create a new order item in the database.
	 *
	 * @param ER_Receipt_Item $item Receipt item object.
	 */
	public function create( &$item ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'receipt_items', array(
                'receipt_item_name'   => $item->get_name(),
                'receipt_item_type'   => $item->get_type(),
                'receipt_object_type' => $item->get_object_type(),
                'receipt_object_id'   => $item->get_object_id(),
			)
		);
		$item->set_id( $wpdb->insert_id );
		$this->save_item_data( $item );
		$item->save_meta_data();
		$item->apply_changes();
		$this->clear_cache( $item );

		do_action( 'easyreservations_new_receipt_item', $item->get_id(), $item, $item->get_object_id(), $item->get_object_type() );
	}

	/**
	 * Update a order item in the database.
	 *
	 * @param ER_Receipt_Item $item Order item object.
	 */
	public function update( &$item ) {
		global $wpdb;

		$changes = $item->get_changes();

		if ( array_intersect( array( 'name', 'object_id', 'object_type', 'type' ), array_keys( $changes ) ) ) {
			$wpdb->update(
				$wpdb->prefix . 'receipt_items', array(
                    'receipt_item_name'   => $item->get_name(),
                    'receipt_item_type'   => $item->get_type(),
                    'receipt_object_type' => $item->get_object_type(),
                    'receipt_object_id'   => $item->get_object_id(),
                ), array( 'receipt_item_id' => $item->get_id() )
			);
		}

		$this->save_item_data( $item );
		$item->save_meta_data();
		$item->apply_changes();
		$this->clear_cache( $item );

		do_action( 'easyreservations_update_receipt_item', $item->get_id(), $item, $item->get_object_id(), $item->get_object_type() );
	}

	/**
	 * Remove an order item from the database.
	 *
	 * @param ER_Receipt_Item $item Order item object.
	 * @param array         $args Array of args to pass to the delete method.
	 */
	public function delete( &$item, $args = array() ) {
		if ( $item->get_id() ) {
			global $wpdb;
			do_action( 'easyreservations_before_delete_receipt_item', $item->get_id() );
			$wpdb->delete( $wpdb->prefix . 'receipt_items', array( 'receipt_item_id' => $item->get_id() ) );
			$wpdb->delete( $wpdb->prefix . 'receipt_itemmeta', array( 'receipt_item_id' => $item->get_id() ) );
			do_action( 'easyreservations_delete_receipt_item', $item->get_id() );
			$this->clear_cache( $item );
		}
	}

	/**
	 * Read a order item from the database.
	 *
	 * @param ER_Receipt_Item $item Order item object.
	 *
	 * @throws Exception If invalid order item.
	 */
	public function read( &$item ) {
		global $wpdb;

		$item->set_defaults();

		// Get from cache if available.
		$data = wp_cache_get( 'item-' . $item->get_id(), 'receipt-items' );

		if ( false === $data ) {
            $data = $wpdb->get_row( $wpdb->prepare( "SELECT receipt_object_id, receipt_object_type, receipt_item_id, receipt_item_name, receipt_item_type FROM {$wpdb->prefix}receipt_items WHERE receipt_item_id = %d LIMIT 1;", $item->get_id() ) );
            wp_cache_set( 'item-' . $item->get_id(), $data, 'receipt-items' );
		}

		if ( ! $data ) {
			throw new Exception( __( 'Invalid receipt item.', 'easyReservations' ) );
		}

		$item->set_props(
			array(
                'object_id'   => $data->receipt_object_id,
                'object_type' => $data->receipt_object_type,
                'name'        => $data->receipt_item_name,
                'type'        => $data->receipt_item_type,
            )
		);
		$item->read_meta_data();
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $item->get_id() will be set.
	 *
	 * @param ER_Receipt_Item $item Order item object.
	 */
	public function save_item_data( &$item ) {}

	/**
	 * Clear meta cache.
	 *
	 * @param ER_Receipt_Item $item Order item object.
	 */
	public function clear_cache( &$item ) {
		wp_cache_delete( 'item-' . $item->get_id(), 'receipt-items' );
		wp_cache_delete( 'receipt-items-' . $item->get_object_id(), $item->get_object_type() );
		wp_cache_delete( $item->get_id(), $this->meta_type . '_meta' );
	}
}
