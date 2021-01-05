<?php
/**
 * Class ER_Receipt_Item_Fee_Data_Store file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER Receipt Item Fee Data Store
 */
class ER_Receipt_Item_Fee_Data_Store extends Abstract_ER_Receipt_Item_Type_Data_Store implements
	ER_Object_Data_Store_Interface, ER_Receipt_Item_Type_Data_Store_Interface {

	/**
	 * Data stored in meta keys.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_custom_id',
		'_value',
		'_line_subtotal',
		'_line_subtotal_tax',
		'_line_total',
		'_line_tax',
		'_line_tax_data'
	);

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @param ER_Receipt_Item_Fee $item Fee order item object.
	 */
	public function read( &$item ) {
		parent::read( $item );
		$id = $item->get_id();
		$item->set_props(
			array(
				'custom_id' => get_metadata( 'receipt_item', $id, '_custom_id', true ),
				'value'     => get_metadata( 'receipt_item', $id, '_value', true ),
				'subtotal'  => get_metadata( 'receipt_item', $id, '_line_subtotal', true ),
				'total'     => get_metadata( 'receipt_item', $id, '_line_total', true ),
				'taxes'     => get_metadata( 'receipt_item', $id, '_line_tax_data', true ),
			)
		);
		$item->set_object_read( true );
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $id will be set.
	 *
	 * @param ER_Receipt_Item_Fee $item Fee order item object.
	 */
	public function save_item_data( &$item ) {
		$id          = $item->get_id();
		$save_values = array(
			'_custom_id'         => $item->get_custom_id( 'edit' ),
			'_value'             => $item->get_value( 'edit' ),
			'_line_subtotal'     => $item->get_subtotal( 'edit' ),
			'_line_subtotal_tax' => $item->get_subtotal_tax( 'edit' ),
			'_line_total'        => $item->get_total( 'edit' ),
			'_line_tax'          => $item->get_total_tax( 'edit' ),
			'_line_tax_data'     => $item->get_taxes( 'edit' ),
		);
		foreach ( $save_values as $key => $value ) {
			update_metadata( 'receipt_item', $id, $key, $value );
		}
	}
}
