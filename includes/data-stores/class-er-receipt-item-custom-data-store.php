<?php
/**
 * Class ER_Receipt_Item_Reservation_Data_Store file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER Receipt Item Reservation Data Store
 */
class ER_Receipt_Item_Custom_Data_Store extends Abstract_ER_Receipt_Item_Type_Data_Store implements
	ER_Object_Data_Store_Interface, ER_Receipt_Item_Type_Data_Store_Interface {

	/**
	 * Data stored in meta keys.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_custom_id',
		'_custom_value',
		'_custom_display',
	);

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @param ER_Receipt_Item_Custom $item Resource order item object.
	 */
	public function read( &$item ) {
		parent::read( $item );
		$id = $item->get_id();
		$item->set_props(
			array(
				'custom_id'      => get_metadata( 'receipt_item', $id, '_custom_id', true ),
				'custom_value'   => get_metadata( 'receipt_item', $id, '_custom_value', true ),
				'custom_display' => get_metadata( 'receipt_item', $id, '_custom_display', true ),
			)
		);
		$item->set_object_read( true );
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $id will be set.
	 *
	 * @param ER_Receipt_Item_Custom $item Resource order item object.
	 */
	public function save_item_data( &$item ) {
		$id                = $item->get_id();
		$meta_key_to_props = array(
			'_custom_id'      => 'custom_id',
			'_custom_value'   => 'custom_value',
			'_custom_display' => 'custom_display',
		);
		$props_to_update   = $this->get_props_to_update( $item, $meta_key_to_props, 'receipt_item' );

		foreach ( $props_to_update as $meta_key => $prop ) {
			update_metadata( 'receipt_item', $id, $meta_key, $item->{"get_$prop"}( 'edit' ) );
		}
	}
}
