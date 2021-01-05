<?php
defined( 'ABSPATH' ) || exit;

class ER_Receipt_Item_Reservation extends ER_Receipt_Item_Line {

	/**
	 * Data array
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'reservation_id' => 0,
		'resource_id'    => 0,
		'subtotal'       => 0,
		'subtotal_tax'   => 0,
		'total'          => 0,
		'total_tax'      => 0,
		'taxes'          => array(
			'subtotal' => array(),
			'total'    => array(),
		),
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
		return 'reservation';
	}

	/**
	 * Get reservation ID
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int
	 */
	public function get_reservation_id( $context = 'view' ) {
		return absint( $this->get_prop( 'reservation_id', $context ) );
	}

	/**
	 * Get resource ID
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int
	 */
	public function get_resource_id( $context = 'view' ) {
		return absint( $this->get_prop( 'resource_id', $context ) );
	}

	/**
	 * Get the associated resource.
	 *
	 * @return ER_Reservation|bool
	 */
	public function get_reservation() {
		$reservation = false;

		if ( $this->get_reservation_id() ) {
			$reservation = er_get_reservation( $this->get_reservation_id() );
		}

		return apply_filters( 'easyreservations_receipt_item_resource', $reservation, $this );
	}

	/**
	 * Get the associated resource.
	 *
	 * @return ER_Resource|bool
	 */
	public function get_resource() {
		$resource = false;

		if ( $this->get_resource_id() ) {
			$resource = ER()->resources()->get( $this->get_resource_id() );
		}

		return apply_filters( 'easyreservations_receipt_item_resource', $resource, $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set reservation ID
	 *
	 * @param int $value Reservation ID.
	 */
	public function set_reservation_id( $value ) {
		$this->set_prop( 'reservation_id', absint( $value ) );
	}

	/**
	 * Set resource ID
	 *
	 * @param int $value Reservation ID.
	 */
	public function set_resource_id( $value ) {
		$this->set_prop( 'resource_id', absint( $value ) );
	}

	/**
	 * Remove an receipt item from the database.
	 *
	 * @param bool $force_delete Should the date be deleted permanently.
	 *
	 * @return bool result
	 */
	public function delete( $force_delete = false ) {
		//Set order id of reservation to 0 whe deleting reservation item in order
		if ( $this->get_reservation_id() ) {
			$reservation = $this->get_reservation();
			if ( $reservation ) {
				$reservation->set_order_id( 0 );
				$reservation->save();
			}
		}

		return parent::delete( $force_delete );
	}

	/**
	 * Save should create or update based on object existence.
	 *
	 * @return int
	 */
	public function save() {
		$updating = false;

		if ( $this->get_id() ) {
			$updating = ! empty( $this->changes );
		}

		parent::save();

		//if this is an update, update the reservation's total as well
		if ( $updating ) {
			$reservation = $this->get_reservation();

			if ( $reservation ) {
				$taxes      = $reservation->get_taxes();
				$item_taxes = $this->get_taxes();

				foreach ( $taxes as $tax ) {
					$tax->set_tax_total( isset( $item_taxes['total'][ $tax->get_rate_id() ] ) ? $item_taxes['total'][ $tax->get_rate_id() ] : 0 );
					$tax->save();
				}

				$reservation->set_total( ER_Number_Util::round( $this->get_total() + $this->get_total_tax(), er_get_price_decimals() ) );
				$reservation->set_total_tax( $this->get_total_tax() );
				$reservation->save();
			}
		}

		return $this->get_id();
	}
}