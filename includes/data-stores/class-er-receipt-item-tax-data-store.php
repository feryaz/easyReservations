<?php
/**
 * Class ER_Receipt_Item_Tax_Data_Store file.
 *
 * @package easyReservations\DataStores
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER Order Item Tax Data Store
 */
class ER_Receipt_Item_Tax_Data_Store extends Abstract_ER_Receipt_Item_Type_Data_Store implements
	ER_Object_Data_Store_Interface, ER_Receipt_Item_Type_Data_Store_Interface {

	/**
	 * Data stored in meta keys.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_rate_id',
		'_compound',
		'_flat',
		'_tax_total',
		'_rate_percent',
	);

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @param ER_Receipt_Item_Tax $item Tax order item object.
	 *
	 * @throws Exception If invalid order item.
	 */
	public function read( &$item ) {
		parent::read( $item );
		$id = $item->get_id();
		$item->set_props(
			array(
				'rate_id'      => get_metadata( 'receipt_item', $id, '_rate_id', true ),
				'compound'     => get_metadata( 'receipt_item', $id, '_compound', true ),
				'flat'         => get_metadata( 'receipt_item', $id, '_flat', true ),
				'tax_total'    => get_metadata( 'receipt_item', $id, '_tax_total', true ),
				'rate_percent' => get_metadata( 'receipt_item', $id, '_rate_percent', true ),
			)
		);
		$item->set_object_read( true );
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $id will be set.
	 *
	 * @param ER_Receipt_Item_Tax $item Tax order item object.
	 */
	public function save_item_data( &$item ) {
		$id                = $item->get_id();
		$meta_key_to_props = array(
			'_rate_id'      => 'rate_id',
			'_compound'     => 'compound',
			'_flat'         => 'flat',
			'_tax_total'    => 'tax_total',
			'_rate_percent' => 'rate_percent',
		);

		$props_to_update = $this->get_props_to_update( $item, $meta_key_to_props, 'receipt_item' );

		foreach ( $props_to_update as $meta_key => $prop ) {
			update_metadata( 'receipt_item', $id, $meta_key, $item->{"get_$prop"}( 'edit' ) );
		}
	}
}
