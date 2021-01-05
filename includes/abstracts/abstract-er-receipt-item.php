<?php
defined( 'ABSPATH' ) || exit;

abstract class ER_Receipt_Item extends ER_Data {

	/**
	 * Receipt item data
	 *
	 * @var array
	 */
	protected $data = array(
		'object_id'   => 0,
		'object_type' => 0,
		'name'        => '',
		'type'        => '',
	);

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'receipt-items';

	/**
	 * This is the name of this object meta type.
	 *
	 * @var string
	 */
	protected $meta_type = 'receipt_item';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'receipt_item';

	/**
	 * ER_Receipt_Item constructor.
	 *
	 * @param int|object|array $item ID to load from the DB, or ER_Receipt_Item object.
	 */
	public function __construct( $item = 0 ) {
		parent::__construct( $item );

		if ( $item instanceof ER_Receipt_Item ) {
			$this->set_id( $item->get_id() );
		} elseif ( is_numeric( $item ) && $item > 0 ) {
			$this->set_id( $item );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = ER_Data_Store::load( 'receipt-item-' . $this->get_type() );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get item name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Get tax status.
	 *
	 * @return string
	 */
	public function get_tax_status() {
		return 'taxable';
	}

	/**
	 * Get identifier of which rates this item can apply to.
	 *
	 * @return string
	 */
	public function get_applicable_tax_rates() {
		return 'none';
	}

	/**
	 * Get items type
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->get_prop( 'type' );
	}

	/**
	 * Get object
	 *
	 * @return bool|ER_Order|ER_Reservation
	 */
	public function get_object() {
		if ( $this->get_object_id() ) {
			if ( $this->get_object_type() === 'reservation' ) {
				return er_get_reservation( $this->get_object_id() );
			} else {
				return er_get_order( $this->get_object_id() );
			}
		}

		return false;
	}

	/**
	 * Get items type
	 *
	 * @return string
	 */
	public function get_object_id() {
		return $this->get_prop( 'object_id' );
	}

	/**
	 * Get items type
	 *
	 * @return string
	 */
	public function get_object_type() {
		return $this->get_prop( 'object_type' );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set item name.
	 *
	 * @param string $value Item name.
	 */
	public function set_name( $value ) {
		$this->set_prop( 'name', wp_check_invalid_utf8( $value ) );
	}

	/**
	 * Set object id.
	 *
	 * @param string $value Item name.
	 */
	public function set_object_id( $value ) {
		$this->set_prop( 'object_id', sanitize_key( $value ) );
	}

	/**
	 * Set object id.
	 *
	 * @param string $value Item name.
	 */
	public function set_object_type( $value ) {
		$this->set_prop( 'object_type', sanitize_key( $value ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Other Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Type checking.
	 *
	 * @param string|array $type Type.
	 *
	 * @return boolean
	 */
	public function is_type( $type ) {
		return is_array( $type ) ? in_array( $this->get_type(), $type, true ) : $type === $this->get_type();
	}

	/**
	 * Calculate item taxes.
	 *
	 * @param array $tax_rates Tax rates to apply. Required.
	 *
	 * @return bool  True if taxes were calculated.
	 */
	public function calculate_taxes( $tax_rates ) {
		if ( 'taxable' === $this->get_tax_status() && er_tax_enabled() ) {
			$taxes = ER_Tax::calc_tax( $this->get_total(), $tax_rates, false );

			if ( method_exists( $this, 'get_subtotal' ) ) {
				$subtotal_taxes = ER_Tax::calc_tax( $this->get_subtotal(), $tax_rates, false );
				$this->set_taxes(
					array(
						'total'    => $taxes,
						'subtotal' => $subtotal_taxes,
					)
				);
			} else {
				$this->set_taxes( array( 'total' => $taxes ) );
			}
		} else {
			$this->set_taxes( false );
		}

		do_action( 'easyreservations_receipt_item_after_calculate_taxes', $this, $tax_rates );

		return true;
	}

	/**
	 * Expands things like term slugs before return.
	 *
	 * @param string $hideprefix Meta data prefix, (default: _).
	 *
	 * @return array
	 */
	public function get_formatted_meta_data( $hideprefix = '_' ) {
		$formatted_meta    = array();
		$meta_data         = $this->get_meta_data();
		$hideprefix_length = ! empty( $hideprefix ) ? strlen( $hideprefix ) : 0;

		foreach ( $meta_data as $meta ) {
			if ( empty( $meta->id ) || '' === $meta->value || ! is_scalar( $meta->value ) || ( $hideprefix_length && substr( $meta->key, 0, $hideprefix_length ) === $hideprefix ) ) {
				continue;
			}

			$meta->key     = rawurldecode( (string) $meta->key );
			$meta->value   = rawurldecode( (string) $meta->value );
			$display_value = wp_kses_post( $meta->value );

			$formatted_meta[ $meta->key ] = (object) array(
				'key'           => $meta->key,
				'value'         => $meta->value,
				'display_key'   => apply_filters( 'easyreservations_receipt_item_display_meta_key', $meta->key, $this ),
				'display_value' => wpautop( make_clickable( apply_filters( 'easyreservations_receipt_item_display_meta_value', $display_value, $this ) ) ),
			);
		}

		return apply_filters( 'easyreservations_receipt_item_get_formatted_meta_data', $formatted_meta, $this );
	}
}