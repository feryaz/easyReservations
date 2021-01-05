<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Order_Refund extends ER_Abstract_Order {

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
	protected $data_store_name = 'order-refund';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'order_refund';

	/**
	 * Stores extra data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'amount'           => '',
		'reason'           => '',
		'refunded_by'      => 0,
		'refunded_payment' => false,
	);

	/**
	 * Get internal type (post type.)
	 *
	 * @return string
	 */
	public function get_type() {
		return 'easy_order_refund';
	}

	/**
	 * Get status - always completed for refunds.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return 'completed';
	}

	/**
	 * Get refunded amount.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int|float
	 */
	public function get_amount( $context = 'view' ) {
		return $this->get_prop( 'amount', $context );
	}

	/**
	 * Get refund reason.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int|float
	 */
	public function get_reason( $context = 'view' ) {
		return $this->get_prop( 'reason', $context );
	}

	/**
	 * Get ID of user who did the refund.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_refunded_by( $context = 'view' ) {
		return $this->get_prop( 'refunded_by', $context );
	}

	/**
	 * Return if the payment was refunded via API.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return bool
	 */
	public function get_refunded_payment( $context = 'view' ) {
		return $this->get_prop( 'refunded_payment', $context );
	}

	/**
	 * Get formatted refunded amount.
	 *
	 * @return string
	 */
	public function get_formatted_refund_amount() {
		return apply_filters( 'easyreservations_formatted_refund_amount', er_price( $this->get_amount(), true ), $this );
	}

	/**
	 * Set refunded amount.
	 *
	 * @param string $value Value to set.
	 *
	 * @throws ER_Data_Exception Exception if the amount is invalid.
	 */
	public function set_amount( $value ) {
		$this->set_prop( 'amount', er_format_decimal( $value ) );
	}

	/**
	 * Set refund reason.
	 *
	 * @param string $value Value to set.
	 *
	 * @throws ER_Data_Exception Exception if the amount is invalid.
	 */
	public function set_reason( $value ) {
		$this->set_prop( 'reason', sanitize_text_field( $value ) );
	}

	/**
	 * Set refunded by.
	 *
	 * @param int $value Value to set.
	 *
	 * @throws ER_Data_Exception Exception if the amount is invalid.
	 */
	public function set_refunded_by( $value ) {
		$this->set_prop( 'refunded_by', absint( $value ) );
	}

	/**
	 * Set if the payment was refunded via API.
	 *
	 * @param bool $value Value to set.
	 */
	public function set_refunded_payment( $value ) {
		$this->set_prop( 'refunded_payment', (bool) $value );
	}
}
