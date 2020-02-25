<?php
/**
 * Receipt Item Type Data Store Interface
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER Order Item Data Store Interface
 *
 * Functions that must be defined by order item store classes.
 */
interface ER_Receipt_Item_Type_Data_Store_Interface {
	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $item->get_id() will be set.
	 *
	 * @param ER_Receipt_Item $item Item object.
	 */
	public function save_item_data( &$item );
}
