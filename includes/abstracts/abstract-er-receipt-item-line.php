<?php
defined( 'ABSPATH' ) || exit;

/**
 * For items that appear on the invoice
 */
abstract class ER_Receipt_Item_Line extends ER_Receipt_Item {

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get subtotal tax.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_subtotal_tax( $context = 'view' ) {
		return $this->get_prop( 'subtotal_tax', $context );
	}

	/**
	 * Get total tax.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_total_tax( $context = 'view' ) {
		return $this->get_prop( 'total_tax', $context );
	}

	/**
	 * Get taxes.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return array
	 */
	public function get_taxes( $context = 'view' ) {
		return $this->get_prop( 'taxes', $context );
	}

	/**
	 * Get subtotal.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_subtotal( $context = 'view' ) {
		return $this->get_prop( 'subtotal', $context );
	}

	/**
	 * Get total.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_total( $context = 'view' ) {
		return $this->get_prop( 'total', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Line total (after discounts).
	 *
	 * @param string $value Total.
	 */
	public function set_total( $value ) {
		$value = er_format_decimal( $value );

		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$this->set_prop( 'total', $value );

		// Subtotal cannot be less than total.
		if ( '' === $this->get_subtotal() || $this->get_subtotal() < $this->get_total() ) {
			$this->set_subtotal( $value );
		}
	}

	/**
	 * Line subtotal (before discounts).
	 *
	 * @param string $value Subtotal.
	 */
	public function set_subtotal( $value ) {
		$value = er_format_decimal( $value );

		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$this->set_prop( 'subtotal', $value );
	}

	/**
	 * Set line taxes and totals for passed in taxes.
	 *
	 * @param array|string $raw_tax_data Raw tax data.
	 */
	public function set_taxes( $raw_tax_data ) {
		$raw_tax_data = maybe_unserialize( $raw_tax_data );
		$tax_data     = array(
			'total'    => array(),
			'subtotal' => array(),
		);

		if ( ! empty( $raw_tax_data['total'] ) && ! empty( $raw_tax_data['subtotal'] ) ) {
			$tax_data['subtotal'] = array_map( 'er_format_decimal', $raw_tax_data['subtotal'] );
			$tax_data['total']    = array_map( 'er_format_decimal', $raw_tax_data['total'] );

			// Subtotal cannot be less than total!
			if ( array_sum( $tax_data['subtotal'] ) < array_sum( $tax_data['total'] ) ) {
				$tax_data['subtotal'] = $tax_data['total'];
			}
		}

		$this->set_prop( 'taxes', $tax_data );

		if ( 'yes' === get_option( 'reservations_tax_round_at_subtotal' ) ) {
			$this->set_total_tax( array_sum( $tax_data['total'] ) );
			$this->set_subtotal_tax( array_sum( $tax_data['subtotal'] ) );
		} else {
			$this->set_total_tax( array_sum( array_map( 'er_round_tax_total', $tax_data['total'] ) ) );
			$this->set_subtotal_tax( array_sum( array_map( 'er_round_tax_total', $tax_data['subtotal'] ) ) );
		}
	}

	/**
	 * Line total tax (after discounts).
	 *
	 * @param string $value Total tax.
	 */
	public function set_total_tax( $value ) {
		$this->set_prop( 'total_tax', er_format_decimal( $value ) );
	}

	/**
	 * Line subtotal tax (before discounts).
	 *
	 * @param string $value Subtotal tax.
	 */
	public function set_subtotal_tax( $value ) {
		$this->set_prop( 'subtotal_tax', er_format_decimal( $value ) );
	}
}
