<?php
defined( 'ABSPATH' ) || exit;

class ER_Receipt_Item_Custom extends ER_Receipt_Item {

	/**
	 * Data array
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'custom_id'      => 0,
		'custom_value'   => '',
		'custom_display' => '',
	);

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
		return 'custom';
	}

	/**
	 * Get custom field ID
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int
	 */
	public function get_custom_id( $context = 'view' ) {
		return absint( $this->get_prop( 'custom_id', $context ) );
	}

	/**
	 * Get custom field value
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_custom_value( $context = 'view' ) {
		return $this->get_prop( 'custom_value', $context );
	}

	/**
	 * Get custom field display
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_custom_display( $context = 'view' ) {
		return $this->get_prop( 'custom_display', $context );
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
	public function set_custom_id( $value ) {
		$this->set_prop( 'custom_id', absint( $value ) );
	}

	/**
	 * Set custom field value
	 *
	 * @param string $value custom field value.
	 */
	public function set_custom_value( $value ) {
		$this->set_prop( 'custom_value', $value );
	}

	/**
	 * Set custom field display
	 *
	 * @param string $value custom field display.
	 */
	public function set_custom_display( $value ) {
		$this->set_prop( 'custom_display', $value );
	}
}