<?php
/**
 * Class ER_Order_Refund_Data_Store_CPT file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER Order Refund Data Store: Stored in CPT.
 */
class ER_Order_Refund_Data_Store_CPT extends Abstract_ER_Order_Data_Store_CPT implements ER_Object_Data_Store_Interface,
	ER_Order_Refund_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_refund_amount',
		'_refunded_by',
		'_refunded_payment',
		'_refund_reason',
		'_discount',
		'_discount_tax',
		'_order_tax',
		'_order_total',
		'_prices_include_tax',
		'_payment_tokens',
	);

	/**
	 * Delete a refund - no trash is supported.
	 *
	 * @param ER_Order $order Order object.
	 * @param array    $args Array of args to pass to the delete method.
	 */
	public function delete( &$order, $args = array() ) {
		$id = $order->get_id();
		$parent_order_id = $order->get_parent_id();
		$refund_cache_key = ER_Cache_Helper::get_cache_prefix( 'orders' ) . 'refunds' . $parent_order_id;

		if ( ! $id ) {
			return;
		}

		$this->delete_items( $order );
		wp_delete_post( $id, true );
		wp_cache_delete( $refund_cache_key, 'orders' );
		$order->set_id( 0 );
		do_action( 'easyreservations_delete_order_refund', $id );
	}

	/**
	 * Read refund data. Can be overridden by child classes to load other props.
	 *
	 * @param ER_Order_Refund $refund Refund object.
	 * @param object          $post_object Post object.
	 */
	protected function read_order_data( &$refund, $post_object ) {
		parent::read_order_data( $refund, $post_object );
		$id = $refund->get_id();
		$refund->set_props(
			array(
				'amount'           => get_post_meta( $id, '_refund_amount', true ),
				'refunded_by'      => metadata_exists( 'post', $id, '_refunded_by' ) ? get_post_meta( $id, '_refunded_by', true ) : absint( $post_object->post_author ),
				'refunded_payment' => er_string_to_bool( get_post_meta( $id, '_refunded_payment', true ) ),
				'reason'           => metadata_exists( 'post', $id, '_refund_reason' ) ? get_post_meta( $id, '_refund_reason', true ) : $post_object->post_excerpt,
			)
		);
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the ER_Order class.
	 *
	 * @param ER_Order_Refund $refund Refund object.
	 */
	protected function update_post_meta( &$refund ) {
		parent::update_post_meta( $refund );

		$updated_props     = array();
		$meta_key_to_props = array(
			'_refund_amount'    => 'amount',
			'_refunded_by'      => 'refunded_by',
			'_refunded_payment' => 'refunded_payment',
			'_refund_reason'    => 'reason',
		);

		$props_to_update = $this->get_props_to_update( $refund, $meta_key_to_props );
		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $refund->{"get_$prop"}( 'edit' );
			update_post_meta( $refund->get_id(), $meta_key, $value );
			$updated_props[] = $prop;
		}

		do_action( 'easyreservations_order_refund_object_updated_props', $refund, $updated_props );
	}

	/**
	 * Get a title for the new post type.
	 *
	 * @return string
	 */
	protected function get_post_title() {
		return sprintf(
		/* translators: %s: Order date */
			__( 'Refund &ndash; %s', 'easyReservations' ),
			strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'easyReservations' ) ) // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment, WordPress.WP.I18n.UnorderedPlaceholdersText
		);
	}
}
