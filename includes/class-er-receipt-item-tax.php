<?php
/**
 * Receipt Item (tax)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order item tax.
 */
class ER_Receipt_Item_Tax extends ER_Receipt_Item {

	/**
	 * Data array
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'rate_id'      => 0,
		'compound'     => false,
		'flat'         => false,
		'tax_total'    => 0,
		'rate_percent' => null,
	);

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set properties based on passed in tax rate by ID.
	 *
	 * @param int $tax_rate_id Tax rate ID.
	 */
	public function set_rate( $tax_rate_id ) {
		$tax_rate = ER_Tax::get_tax_rate( $tax_rate_id );

		$this->set_rate_id( $tax_rate_id );
		$this->set_name( ER_Tax::get_rate_label( $tax_rate ) );
		$this->set_compound( ER_Tax::is_compound( $tax_rate ) );
		$this->set_flat( ER_Tax::is_flat( $tax_rate ) );
		$this->set_rate_percent( ER_Tax::get_rate_percent_value( $tax_rate ) );
	}

	/**
	 * Set tax rate id.
	 *
	 * @param int $value Rate ID.
	 */
	public function set_rate_id( $value ) {
		$this->set_prop( 'rate_id', absint( $value ) );
	}

	/**
	 * Set tax total.
	 *
	 * @param string $value Tax total.
	 */
	public function set_tax_total( $value ) {
		$this->set_prop( 'tax_total', $value ? er_format_decimal( $value ) : 0 );
	}

	/**
	 * Set compound.
	 *
	 * @param bool $value If tax is compound.
	 */
	public function set_compound( $value ) {
		$this->set_prop( 'compound', (bool) $value );
	}

	/**
	 * Set rate value.
	 *
	 * @param float $value tax rate value.
	 */
	public function set_rate_percent( $value ) {
		$this->set_prop( 'rate_percent', (float) $value );
	}

	/**
	 * Set compound.
	 *
	 * @param bool $value If tax is compound.
	 */
	public function set_flat( $value ) {
		$this->set_prop( 'flat', (bool) $value );
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
		return 'tax';
	}

	/**
	 * Get rate code/name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		$name = $this->get_prop( 'name', $context );
		if ( 'view' === $context ) {
			return $name ? $name : __( 'Tax', 'easyReservations' );
		} else {
			return $name;
		}
	}

	/**
	 * Get tax rate ID.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int
	 */
	public function get_rate_id( $context = 'view' ) {
		return $this->get_prop( 'rate_id', $context );
	}

	/**
	 * Get tax_total
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_tax_total( $context = 'view' ) {
		return $this->get_prop( 'tax_total', $context );
	}

	/**
	 * Get compound.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return bool
	 */
	public function get_compound( $context = 'view' ) {
		return $this->get_prop( 'compound', $context );
	}

	/**
	 * Is this a compound tax rate?
	 *
	 * @return boolean
	 */
	public function is_compound() {
		return $this->get_compound() ? true : false;
	}

	/**
	 * Get compound.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return bool
	 */
	public function get_flat( $context = 'view' ) {
		return $this->get_prop( 'flat', $context );
	}

	/**
	 * Is this a compound tax rate?
	 *
	 * @return boolean
	 */
	public function is_flat() {
		return $this->get_flat() ? true : false;
	}

	/**
	 * Get rate value
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return float
	 */
	public function get_rate_percent( $context = 'view' ) {
		return $this->get_prop( 'rate_percent', $context );
	}
}
