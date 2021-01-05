<?php
defined( 'ABSPATH' ) || exit;

class ER_Receipt_Item_Resource extends ER_Receipt_Item_Line {

	/**
	 * Data array
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'resource_id'  => 0,
		'subtotal'     => 0,
		'subtotal_tax' => 0,
		'total'        => 0,
		'total_tax'    => 0,
		'taxes'        => array(
			'subtotal' => array(),
			'total'    => array(),
		),
	);

	/**
	 * Data stored in meta keys.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_resource_id',
		'_line_subtotal',
		'_line_subtotal_tax',
		'_line_total',
		'_line_tax',
		'_line_tax_data'
	);

	/**
	 * Read/populate data properties specific to this order item.
	 */
	public function read() {
		parent::read();
		$id = $this->get_id();
		$this->set_props(
			array(
				'resource_id' => get_metadata( 'receipt_item', $id, '_resource_id', true ),
				'subtotal'    => get_metadata( 'receipt_item', $id, '_line_subtotal', true ),
				'total'       => get_metadata( 'receipt_item', $id, '_line_total', true ),
				'taxes'       => get_metadata( 'receipt_item', $id, '_line_tax_data', true ),
			)
		);
		$this->set_object_read( true );
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $id will be set.
	 */
	public function save_item_data() {
		$id                = $this->get_id();
		$meta_key_to_props = array(
			'_resource_id'       => 'resource_id',
			'_line_subtotal'     => 'subtotal',
			'_line_subtotal_tax' => 'subtotal_tax',
			'_line_total'        => 'total',
			'_line_tax'          => 'total_tax',
			'_line_tax_data'     => 'taxes',
		);
		$props_to_update   = $this->get_props_to_update( $meta_key_to_props, 'receipt_item' );

		foreach ( $props_to_update as $meta_key => $prop ) {
			update_metadata( 'receipt_item', $id, $meta_key, $this->{"get_$prop"}( 'edit' ) );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order item type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'resource';
	}

	/**
	 * Get custom field ID
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int
	 */
	public function get_resource_id( $context = 'view' ) {
		return absint( $this->get_prop( 'resource_id', $context ) );
	}

	/**
	 * Get fee value
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_value( $context = 'view' ) {
		return $this->get_prop( 'value', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set custom field ID
	 *
	 * @param int $value Reservation ID.
	 */
	public function set_resource_id( $value ) {
		$this->set_prop( 'resource_id', absint( $value ) );
	}

	/**
	 * Set fee value
	 *
	 * @param string $value custom field value.
	 */
	public function set_value( $value ) {
		$this->set_prop( 'value', $value );
	}
}