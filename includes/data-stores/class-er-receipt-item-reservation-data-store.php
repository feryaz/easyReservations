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
class ER_Receipt_Item_Reservation_Data_Store extends Abstract_ER_Receipt_Item_Type_Data_Store implements
	ER_Object_Data_Store_Interface, ER_Receipt_Item_Type_Data_Store_Interface {

	/**
	 * Data stored in meta keys.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_reservation_id',
		'_resource_id',
		'_line_subtotal',
		'_line_subtotal_tax',
		'_line_total',
		'_line_tax',
		'_line_tax_data'
	);

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @param ER_Receipt_Item_Reservation $item Receipt item object.
	 */
	public function read( &$item ) {
		parent::read( $item );
		$id = $item->get_id();
		$item->set_props(
			array(
				'reservation_id' => get_metadata( 'receipt_item', $id, '_reservation_id', true ),
				'resource_id'    => get_metadata( 'receipt_item', $id, '_resource_id', true ),
				'subtotal'       => get_metadata( 'receipt_item', $id, '_line_subtotal', true ),
				'total'          => get_metadata( 'receipt_item', $id, '_line_total', true ),
				'taxes'          => get_metadata( 'receipt_item', $id, '_line_tax_data', true ),
			)
		);
		$item->set_object_read( true );
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $id will be set.
	 *
	 * @param ER_Receipt_Item_Reservation $item Receipt item object.
	 */
	public function save_item_data( &$item ) {
		$id                = $item->get_id();
		$meta_key_to_props = array(
			'_reservation_id'    => 'reservation_id',
			'_resource_id'       => 'resource_id',
			'_line_subtotal'     => 'subtotal',
			'_line_subtotal_tax' => 'subtotal_tax',
			'_line_total'        => 'total',
			'_line_tax'          => 'total_tax',
			'_line_tax_data'     => 'taxes',
		);
		$props_to_update   = $this->get_props_to_update( $item, $meta_key_to_props, 'receipt_item' );

		foreach ( $props_to_update as $meta_key => $prop ) {
			update_metadata( 'receipt_item', $id, $meta_key, $item->{"get_$prop"}( 'edit' ) );
		}
	}
}
