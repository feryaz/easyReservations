<?php
defined( 'ABSPATH' ) || exit;

class ER_Receipt_Item_Fee extends ER_Receipt_Item_Line {

	/**
	 * Data array
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'custom_id'    => 0,
		'value'        => '',
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
	 * Calculate item taxes.
	 *
	 * @param array $tax_rates Tax rates to apply. Required.
	 *
	 * @return bool  True if taxes were calculated.
	 */
	public function calculate_taxes( $tax_rates ) {
		// Use regular calculation unless the fee is negative.
		if ( 0 <= $this->get_total() ) {
			return parent::calculate_taxes( $tax_rates );
		}

		$object = er_tax_enabled() ? $this->get_object() : false;

		if ( $object ) {
			// Apportion taxes to order items, shipping, and fees.
			$tax_amount   = 0;
			$total_amount = 0;

			foreach ( $object->get_items( array( 'reservation', 'fee', 'resource' ) ) as $item ) {
				if ( 0 > $item->get_total() ) {
					continue;
				}

				$total_amount = $total_amount + $item->get_total();

				if ( 'taxable' === $item->get_tax_status() ) {
					$tax_amount = $tax_amount + $item->get_total();
				}
			}

			$proportion               = $tax_amount / $total_amount;
			$cart_discount_proportion = $this->get_total() * $proportion;

			$discount_taxes = ER_Tax::calc_tax( $cart_discount_proportion, $tax_rates );

			$this->set_taxes( array( 'total' => $discount_taxes ) );
		} else {
			$this->set_taxes( false );
		}

		do_action( 'easyreservations_receipt_item_fee_after_calculate_taxes', $this, $tax_rates );

		return true;
	}

	/**
	 * Get item costs grouped by tax class.
	 *
	 * @param ER_Order $order Order object.
	 *
	 * @return array
	 */
	protected function get_tax_class_costs( $order ) {
		$order_item_tax_classes = $order->get_items_tax_classes();
		$costs                  = array_fill_keys( $order_item_tax_classes, 0 );
		$costs['non-taxable']   = 0;

		foreach ( $order->get_items( array( 'reservation', 'fee', 'resource' ) ) as $item ) {
			if ( 0 > $item->get_total() ) {
				continue;
			}
			if ( 'taxable' !== $item->get_tax_status() ) {
				$costs['non-taxable'] += $item->get_total();
			} else {
				$costs[ $item->get_tax_class() ] += $item->get_total();
			}
		}

		return array_filter( $costs );
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
		return 'fee';
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
	public function set_custom_id( $value ) {
		$this->set_prop( 'custom_id', absint( $value ) );
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