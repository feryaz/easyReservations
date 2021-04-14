<?php

defined( 'ABSPATH' ) || exit;

abstract class ER_Receipt extends ER_Data {
	use ER_Item_Totals;

	/**
	 * Receipt items will be stored here, sometimes before they persist in the DB.
	 *
	 * @var array[]
	 */
	protected $items = array();

	/**
	 * Receipt items that need deleting are stored here.
	 *
	 * @var ER_Receipt_Item[]
	 */
	protected $items_to_delete = array();

	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @var bool|array
	 */
	protected $status_transition = false;

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete orders from the database.
	| Written in abstract fashion so that the way orders are stored can be
	| changed more easily in the future.
	|
	| A save method is included for convenience (chooses update or create based
	| on if the order exists yet).
	|
	*/

	/**
	 * Save data to the database.
	 *
	 * @return int order ID
	 */
	public function save() {
		if ( ! $this->data_store ) {
			return $this->get_id();
		}

		try {
			/**
			 * Trigger action before saving to the DB. Allows you to adjust object props before save.
			 *
			 * @param ER_Data          $this The object being saved.
			 * @param ER_Data_Store_WP $data_store THe data store persisting the data.
			 */
			do_action( 'easyreservations_before_' . $this->object_type . '_object_save', $this, $this->data_store );

			if ( $this->get_id() ) {
				$this->data_store->update( $this );
			} else {
				$this->data_store->create( $this );
			}

			$this->save_items();

			/**
			 * Trigger action after saving to the DB.
			 *
			 * @param ER_Data          $this The object being saved.
			 * @param ER_Data_Store_WP $data_store THe data store persisting the data.
			 */
			do_action( 'easyreservations_after_' . $this->object_type . '_object_save', $this, $this->data_store );
		} catch ( Exception $e ) {
			$this->handle_exception( $e, $e->getMessage() );
		}

		return $this->get_id();
	}

	/**
	 * Log an error about this order is exception is encountered.
	 *
	 * @param Exception $e Exception object.
	 * @param string    $message Message regarding exception thrown.
	 */
	protected function handle_exception( $e, $message = 'Error' ) {
		er_get_logger()->error(
			$message,
			array(
				'object' => $this,
				'error'  => $e,
			)
		);
	}

	/**
	 * Save all receipt items which are part of this object.
	 */
	protected function save_items() {
		$items_changed = false;

		foreach ( $this->items_to_delete as $item ) {
			$item->delete();
			$items_changed = true;
		}

		$this->items_to_delete = array();

		// Add/save items.
		foreach ( $this->items as $item_group => $items ) {
			if ( is_array( $items ) ) {
				$items = array_filter( $items );
				foreach ( $items as $item_key => $item ) {
					$item->set_object_id( $this->get_id() );
					$item->set_object_type( $this->object_type );

					$item_id = $item->save();

					// If ID changed (new item saved to DB)...
					if ( $item_id !== $item_key ) {
						$this->items[ $item_group ][ $item_id ] = $item;

						unset( $this->items[ $item_group ][ $item_key ] );

						$items_changed = true;
					}
				}
			}
		}

		if ( $items_changed ) {
			delete_transient( 'er_' . $this->object_type . '_' . $this->get_id() . '_needs_processing' );
		}
	}

	/**
	 * Cast item into correct class
	 *
	 * @param object|int|ER_Receipt_Item $item_id
	 *
	 * @return bool|ER_Receipt_Item
	 */
	public function cast_item( $item_id ) {
		return er_receipt_get_item( $item_id, $this->get_id() );
	}

	/**
	 * Return an array of items within this object.
	 *
	 * @param string|array $types Types of line items to get (array or string).
	 *
	 * @return ER_Receipt_Item|ER_Receipt_Item_Coupon|ER_Receipt_Item_Line[]
	 */
	public function get_items( $types = array( 'reservation', 'resource' ) ) {
		$items = array();
		$types = array_filter( (array) $types );

		foreach ( $types as $type ) {
			if ( ! isset( $this->items[ $type ] ) ) {
				$this->items[ $type ] = array_filter( $this->data_store->read_items( $this, $type ) );
			}

			if ( $type && isset( $this->items[ $type ] ) ) {
				// Don't use array_merge here because keys are numeric.
				$items = $items + $this->items[ $type ];
			}
		}

		return apply_filters( 'easyreservations_' . $this->object_type . '_get_items', $items, $this, $types );
	}

	/**
	 * Return array of values for calculations.
	 *
	 * @param string $field Field name to return.
	 *
	 * @return array Array of values.
	 */
	protected function get_values_for_total( $field ) {
		$items = array_map(
			function ( $item ) use ( $field ) {
				return er_add_number_precision( $item, false );
			},
			er_list_pluck( $this->get_items(), 'get_' . $field )
		);

		return $items;
	}

	/**
	 * Get an receipt item object, based on its type.
	 *
	 * @param int  $item_id ID of item to get.
	 * @param bool $load_from_db items variable instead.
	 *
	 * @return ER_Receipt_Item|false
	 */
	public function get_item( $item_id, $load_from_db = true ) {
		if ( $load_from_db ) {
			return $this->cast_item( $item_id );
		}

		// Search for item id.
		if ( $this->items ) {
			foreach ( $this->items as $group => $items ) {
				if ( isset( $items[ $item_id ] ) ) {
					return $items[ $item_id ];
				}
			}
		}

		// Load all items of type and cache.
		$type = $this->data_store->get_receipt_item_type( $item_id );

		if ( ! $type ) {
			return false;
		}

		$items = $this->get_items( $type );

		return ! empty( $items[ $item_id ] ) ? $items[ $item_id ] : false;
	}

	/**
	 * Get all types of receipt items
	 *
	 * @return array
	 */
	public function get_item_types() {
		return apply_filters( 'easyreservations_receipt_types', array(
			'reservation',
			'custom',
			'resource',
			'coupon',
			'tax'
		) );
	}

	/**
	 * Return the object status.
	 *
	 * @param string $context View or edit context.
	 *
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		$status = $this->get_prop( 'status', $context );

		if ( empty( $status ) && 'view' === $context ) {
			// In view context, return the default status if no status has been set.
			$status = apply_filters( 'easyreservations_default_' . $this->object_type . '_status', 'pending' );
		}

		return $status;
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get date_created.
	 *
	 * @param string $context View or edit context.
	 *
	 * @return ER_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get date_modified.
	 *
	 * @param string $context View or edit context.
	 *
	 * @return ER_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Get post type
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->object_type;
	}

	/**
	 * Gets the count of receipt items of a certain type.
	 *
	 * @param string $item_type Item type to lookup.
	 *
	 * @return int|string
	 */
	public function get_item_count( $item_type = '' ) {
		$items = $this->get_items( empty( $item_type ) ? array( 'reservation', 'resource' ) : $item_type );

		return apply_filters( 'easyreservations_get_item_count', count( $items ), $item_type, $this );
	}

	/**
	 * Get discount_total.
	 *
	 * @param string $context View or edit context.
	 *
	 * @return float
	 */
	public function get_discount_total( $context = 'view' ) {
		return $this->get_prop( 'discount_total', $context );
	}

	/**
	 * Get discount_tax.
	 *
	 * @param string $context View or edit context.
	 *
	 * @return float
	 */
	public function get_discount_tax( $context = 'view' ) {
		return $this->get_prop( 'discount_tax', $context );
	}

	/**
	 * Gets order grand total. incl. taxes. Used in gateways.
	 *
	 * @param string $context View or edit context.
	 *
	 * @return float
	 */
	public function get_total( $context = 'view' ) {
		return $this->get_prop( 'total', $context );
	}

	/**
	 * Get total tax amount.
	 *
	 * @param string $context View or edit context.
	 *
	 * @return float
	 */
	public function get_total_tax( $context = 'view' ) {
		return $this->get_prop( 'total_tax', $context );
	}

	/**
	 * Get prices_include_tax.
	 *
	 * @param string $context View or edit context.
	 *
	 * @return bool
	 */
	public function get_prices_include_tax( $context = 'view' ) {
		return $this->get_prop( 'prices_include_tax', $context );
	}

	/**
	 * Return an array of taxes within this object.
	 *
	 * @return ER_Receipt_Item_Tax[]
	 */
	public function get_taxes() {
		return $this->get_items( 'tax' );
	}

	/**
	 * Return an array of taxes within this object.
	 *
	 * @return ER_Receipt_Item_Fee[]
	 */
	public function get_fees() {
		return $this->get_items( 'fee' );
	}

	/**
	 * Gets the total discount amount.
	 *
	 * @param bool $ex_tax Show discount excl any tax.
	 *
	 * @return float
	 */
	public function get_total_discount( $ex_tax = true ) {
		if ( $ex_tax ) {
			$total_discount = $this->get_discount_total();
		} else {
			$total_discount = $this->get_discount_total() + $this->get_discount_tax();
		}

		return apply_filters( 'easyreservations_receipt_get_total_discount', ER_Number_Util::round( $total_discount, er_get_rounding_precision() ), $this );
	}

	/**
	 * Gets subtotal.
	 *
	 * @return float
	 */
	public function get_subtotal() {
		$subtotal = ER_Number_Util::round( $this->get_cart_subtotal(), er_get_price_decimals() );

		return apply_filters( 'easyreservations_receipt_get_subtotal', (float) $subtotal, $this );
	}

	/**
	 * Get taxes, merged by id, formatted ready for output.
	 *
	 * @return array
	 */
	public function get_tax_totals() {
		$tax_totals = array();

		foreach ( $this->get_taxes() as $key => $tax ) {
			$code = $tax->get_rate_id();

			if ( ! isset( $tax_totals[ $code ] ) ) {
				$tax_totals[ $code ]         = new stdClass();
				$tax_totals[ $code ]->amount = 0;
			}

			$tax_totals[ $code ]->rate_id          = $tax->get_rate_id();
			$tax_totals[ $code ]->is_compound      = $tax->is_compound();
			$tax_totals[ $code ]->is_flat          = $tax->is_flat();
			$tax_totals[ $code ]->label            = $tax->get_name();
			$tax_totals[ $code ]->amount           += (float) $tax->get_tax_total();
			$tax_totals[ $code ]->formatted_amount = er_price( $tax_totals[ $code ]->amount, true );
		}

		if ( apply_filters( 'easyreservations_order_hide_zero_taxes', true ) ) {
			$amounts    = array_filter( wp_list_pluck( $tax_totals, 'amount' ) );
			$tax_totals = array_intersect_key( $tax_totals, $amounts );
		}

		return apply_filters( 'easyreservations_receipt_get_tax_totals', $tax_totals, $this );
	}

	/**
	 * Get all tax classes for items in the order.
	 *
	 * @return ER_Receipt_Item_Tax[]
	 */
	public function get_items_taxes() {
		$found_tax_classes = array();

		foreach ( $this->get_items() as $item ) {
			if ( is_callable( array( $item, 'get_tax_status' ) ) && $item->get_tax_status() === 'taxable' ) {
				$found_tax_classes[] = $item->get_tax_class();
			}
		}

		return array_unique( $found_tax_classes );
	}

	/**
	 * Get taxes merged by id
	 *
	 * @return array
	 */
	public function get_taxes_by_id() {
		$taxes = array();

		foreach ( $this->get_taxes() as $key => $tax ) {
			$code = $tax->get_rate_id();

			$taxes[ $code ] = $tax;
		}

		return apply_filters( 'easyreservations_receipt_get_taxes_by_id', $taxes, $this );
	}

	/**
	 * Get tax totals merged by id
	 *
	 * @return array
	 */
	public function get_taxes_totals() {
		$taxes = array();

		foreach ( $this->get_taxes() as $key => $tax ) {
			$code = $tax->get_rate_id();

			$taxes[ $code ] = $tax->get_tax_total();
		}

		return apply_filters( 'easyreservations_receipt_get_taxes_totals', $taxes, $this );
	}

	/**
	 * Get item subtotal - this is the cost before discount.
	 *
	 * @param ER_Receipt_Item $item Item to get total from.
	 * @param bool            $inc_tax (default: false).
	 * @param bool            $round (default: true).
	 *
	 * @return float
	 */
	public function get_item_subtotal( $item, $inc_tax = false, $round = true ) {
		$subtotal = 0;

		if ( is_callable( array( $item, 'get_subtotal' ) ) ) {
			if ( $inc_tax ) {
				$subtotal = $item->get_subtotal() + $item->get_subtotal_tax();
			} else {
				$subtotal = $item->get_subtotal();
			}

			$subtotal = $round ? ER_Number_Util::round( (float) $subtotal, er_get_price_decimals() ) : $subtotal;
		}

		return apply_filters( 'easyreservations_receipt_item_subtotal', $subtotal, $this, $item, $inc_tax, $round );
	}

	/**
	 * Calculate item cost - useful for gateways.
	 *
	 * @param object $item Item to get total from.
	 * @param bool   $inc_tax (default: false).
	 * @param bool   $round (default: true).
	 *
	 * @return float
	 */
	public function get_item_total( $item, $inc_tax = false, $round = true ) {
		$total = 0;

		if ( is_callable( array( $item, 'get_total' ) ) ) {
			if ( $inc_tax ) {
				$total = $item->get_total() + $item->get_total_tax();
			} else {
				$total = $item->get_total();
			}

			$total = $round ? ER_Number_Util::round( $total, er_get_price_decimals() ) : $total;
		}

		return apply_filters( 'easyreservations_receipt_amount_item_total', $total, $this, $item, $inc_tax, $round );
	}

	/**
	 * Get item tax - useful for gateways.
	 *
	 * @param mixed $item Item to get total from.
	 * @param bool  $round (default: true).
	 *
	 * @return float
	 */
	public function get_item_tax( $item, $round = true ) {
		$tax = 0;

		if ( is_callable( array( $item, 'get_total_tax' ) ) ) {
			$tax = $round ? er_round_tax_total( $tax ) : $tax;
		}

		return apply_filters( 'easyreservations_receipt_amount_item_tax', $tax, $item, $round, $this );
	}

	/**
	 * Calculate fees for all line items.
	 *
	 * @return float Fee total.
	 */
	public function get_total_fees() {
		return array_reduce(
			$this->get_fees(),
			function ( $carry, $item ) {
				return $carry + $item->get_total();
			}
		);
	}

	/**
	 * Helper function.
	 * If you add all items in this order in cart again, this would be the cart subtotal (assuming all other settings are same).
	 *
	 * @return float Cart subtotal.
	 */
	protected function get_cart_subtotal() {
		return er_remove_number_precision(
			$this->get_rounded_items_total(
				$this->get_values_for_total( 'subtotal' )
			)
		);
	}

	/**
	 * Helper function.
	 * If you add all items in this order in cart again, this would be the cart total (assuming all other settings are same).
	 *
	 * @return float Cart total.
	 */
	protected function get_cart_total() {
		return er_remove_number_precision(
			$this->get_rounded_items_total(
				$this->get_values_for_total( 'total' )
			)
		);
	}

	/**
	 * Gets line subtotal - formatted for display.
	 *
	 * @param ER_Receipt_Item $item Item to get total from.
	 * @param string          $tax_display Incl or excl tax display mode.
	 *
	 * @return string
	 */
	public function get_formatted_item_subtotal( $item, $tax_display = '' ) {
		$tax_display = $tax_display ? $tax_display : get_option( 'reservations_tax_display_cart' );

		if ( 'excl' === $tax_display ) {
			$ex_tax_label = $this->get_prices_include_tax() ? 1 : 0;

			$subtotal = er_price(
				$this->get_item_subtotal( $item ),
				true,
				$ex_tax_label
			);
		} else {
			$subtotal = er_price( $this->get_item_subtotal( $item, true ), true );
		}

		return apply_filters( 'easyreservations_receipt_formatted_item_subtotal', $subtotal, $item, $this );
	}

	/**
	 * Get the discount amount (formatted).
	 *
	 * @param string $tax_display Excl or incl tax display mode.
	 *
	 * @return string
	 */
	public function get_formatted_discount( $tax_display = '' ) {
		$tax_display = $tax_display ? $tax_display : get_option( 'reservations_tax_display_cart' );

		return apply_filters(
			'easyreservations_receipt_formatted_discount',
			er_price( $this->get_total_discount( 'excl' === $tax_display && 'excl' === get_option( 'reservations_tax_display_cart' ) ), true ),
			$this
		);
	}

	/**
	 * Get subtotal html
	 *
	 * @param string $tax_display (default: the tax_display_cart value).
	 *
	 * @return string
	 */
	public function get_formatted_subtotal( $tax_display = '' ) {
		$tax_display = $tax_display ? $tax_display : get_option( 'reservations_tax_display_cart' );
		$subtotal    = 0;

		foreach ( $this->get_items() as $item ) {
			$subtotal += $item->get_subtotal();

			if ( 'incl' === $tax_display ) {
				$subtotal += $item->get_subtotal_tax();
			}
		}

		$subtotal = er_price( $subtotal, true );

		if ( 'excl' === $tax_display && $this->get_prices_include_tax() && er_tax_enabled() ) {
			$subtotal .= ' <small class="tax_label">' . ER()->countries->ex_tax_or_vat() . '</small>';
		}

		return apply_filters( 'easyreservations_receipt_formatted_subtotal', $subtotal, $this );
	}

	/**
	 * Get total html
	 *
	 * @return string
	 */
	public function get_formatted_total() {
		$value = er_price( $this->get_total(), true );

		if ( er_tax_enabled() && 'incl' === get_option( 'reservations_tax_display_shop' ) ) {
			$tax_string_array = array();
			$tax_totals       = $this->get_tax_totals();

			if ( get_option( 'reservations_tax_total_display' ) === 'itemized' ) {
				foreach ( $tax_totals as $tax_item ) {
					$tax_string_array[] = sprintf( '%s %s', er_price( $tax_item->amount, true ), esc_html( $tax_item->label ) );
				}
			} elseif ( ! empty( $tax_totals ) ) {
				$tax_string_array[] = sprintf( '%s %s', er_price( $this->get_total_tax(), true ), ER()->countries->tax_or_vat() );
			}

			if ( ! empty( $tax_string_array ) ) {
				/* translators: %s: tax information */
				$value .= '<small class="includes_tax">' . sprintf( __( '(includes %s)', 'easyReservations' ), implode( ', ', $tax_string_array ) ) . '</small>';
			}
		}

		return apply_filters( 'easyreservations_receipt_formatted_total', $value, $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set date_created.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 *
	 * @throws ER_Data_Exception Exception may be thrown if value is invalid.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set date_modified.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 *
	 * @throws ER_Data_Exception Exception may be thrown if value is invalid.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Set prices_include_tax.
	 *
	 * @param bool $value Value to set.
	 *
	 * @throws ER_Data_Exception Exception may be thrown if value is invalid.
	 */
	public function set_prices_include_tax( $value ) {
		$this->set_prop( 'prices_include_tax', (bool) $value );
	}

	/**
	 * Set discount_total.
	 *
	 * @param string $value Value to set.
	 *
	 * @throws ER_Data_Exception Exception may be thrown if value is invalid.
	 */
	public function set_discount_total( $value ) {
		$this->set_prop( 'discount_total', er_format_decimal( $value ) );
	}

	/**
	 * Set discount_tax.
	 *
	 * @param string $value Value to set.
	 *
	 * @throws ER_Data_Exception Exception may be thrown if value is invalid.
	 */
	public function set_discount_tax( $value ) {
		$this->set_prop( 'discount_tax', er_format_decimal( $value ) );
	}

	/**
	 * Sets tax (sum of cart and shipping tax). Used internally only.
	 *
	 * @param string $value Value to set.
	 *
	 * @throws ER_Data_Exception Exception may be thrown if value is invalid.
	 */
	public function set_total_tax( $value ) {
		$this->set_prop( 'total_tax', er_format_decimal( $value ) );
	}

	/**
	 * Set total.
	 *
	 * @param string $value Value to set.
	 *
	 * @return bool|void
	 * @throws ER_Data_Exception Exception may be thrown if value is invalid.
	 */
	public function set_total( $value ) {
		$this->set_prop( 'total', er_format_decimal( $value, er_get_price_decimals() ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Item functions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Remove item from the receipt.
	 *
	 * @param int $item_id Item ID to delete.
	 *
	 * @return false|void
	 */
	public function remove_item( $item_id ) {
		$item       = $this->get_item( $item_id, false );
		$items_type = $item->get_type();

		if ( ! $items_type ) {
			return false;
		}

		// Unset and remove later.
		$this->items_to_delete[] = $item;
		unset( $this->items[ $items_type ][ $item->get_id() ] );
	}

	/**
	 * Adds an receipt item to this object.
	 *
	 * @param ER_Receipt_Item $item Receipt item object (line, fee, coupon, tax).
	 *
	 * @return false|void
	 */
	public function add_item( $item ) {
		$items_type = $item->get_type();

		if ( ! $items_type ) {
			return false;
		}

		// Make sure existing items are loaded so we can append this new one.
		if ( ! isset( $this->items[ $items_type ] ) ) {
			$this->items[ $items_type ] = $this->get_items( $item->get_type() );
		}

		$item->set_object_id( $this->get_id() );
		$item->set_object_type( $this->object_type );

		// Append new row with generated temporary ID.
		$item_id = $item->get_id( true );

		if ( $item_id ) {
			$this->items[ $items_type ][ $item_id ] = $item;
		} else {
			$item_id = 'new:' . $items_type . '_' . count( $this->items[ $items_type ] );
			$item->set_temporary_id( $item_id );
			$this->items[ $items_type ][ $item_id ] = $item;
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Other functions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Calculate taxes for all line items and shipping, and store the totals and tax rows.
	 *
	 * If by default the taxes are based on the shipping address and the current order doesn't
	 * have any, it would use the billing address rather than using the Shopping base location.
	 *
	 * Will use the base country unless customer addresses are set.
	 *
	 * @param bool $save save.
	 */
	public function calculate_taxes( $save = true ) {
		do_action( 'easyreservations_receipt_before_calculate_taxes', $this );

		$tax_rates_custom = ER_Tax::get_rates( 'custom' );

		// Trigger tax recalculation for all items.
		foreach ( $this->get_items() as $item_id => $item ) {
			if ( $item->get_type() === 'reservation' ) {
				continue;
			}

			if ( $item->get_type() === 'fee' ) {
				$tax_rates = $tax_rates_custom;
			} else {
				$tax_rate_apply = method_exists( $item, 'get_resource_id' ) ? $item->get_resource_id() : 'order';
				$tax_rates      = ER_Tax::get_rates( $tax_rate_apply );
			}

			$item->calculate_taxes( $tax_rates );
		}

		$this->update_taxes( $save );
	}

	/**
	 * Update tax lines for the receipt based on the line item taxes themselves.
	 *
	 * @param bool $save save.
	 */
	public function update_taxes( $save = true ) {
		$total_taxes    = array();
		$existing_taxes = $this->get_taxes();
		$saved_rate_ids = array();

		foreach ( $this->get_items() as $item_id => $item ) {
			$taxes = $item->get_taxes();

			foreach ( $taxes['total'] as $tax_rate_id => $tax ) {
				$tax_amount = (float) $this->round_line_tax( $tax, false );

				$total_taxes[ $tax_rate_id ] = isset( $total_taxes[ $tax_rate_id ] ) ? (float) $total_taxes[ $tax_rate_id ] + $tax_amount : $tax_amount;
			}
		}

		foreach ( $existing_taxes as $tax ) {
			// Remove taxes which no longer exist for cart/shipping.
			if ( ! array_key_exists( $tax->get_rate_id(), $total_taxes ) || in_array( $tax->get_rate_id(), $saved_rate_ids, true ) ) {
				$this->remove_item( $tax->get_id() );
				continue;
			}

			$saved_rate_ids[] = $tax->get_rate_id();

			$tax->set_name( ER_Tax::get_rate_label( $tax->get_rate_id() ) );
			$tax->set_tax_total( isset( $total_taxes[ $tax->get_rate_id() ] ) ? $total_taxes[ $tax->get_rate_id() ] : 0 );

			if ( $save ) {
				$tax->save();
			}
		}

		$new_rate_ids = wp_parse_id_list( array_diff( array_keys( $total_taxes ), $saved_rate_ids ) );

		// New taxes.
		foreach ( $new_rate_ids as $tax_rate_id ) {
			$item = new ER_Receipt_Item_Tax();
			$item->set_rate( $tax_rate_id );
			$item->set_tax_total( isset( $total_taxes[ $tax_rate_id ] ) ? $total_taxes[ $tax_rate_id ] : 0 );
			$this->add_item( $item );
		}

		$this->set_total_tax( array_sum( $total_taxes ) );

		if ( $save ) {
			$this->save();
		}
	}

	/**
	 * Calculate totals by looking at the contents of the receipt. Stores the totals and returns the final total.
	 *
	 * @param bool $and_taxes Calc taxes if true.
	 *
	 * @return float calculated grand total.
	 */
	public function calculate_totals( $and_taxes = true ) {
		do_action( 'easyreservations_before_calculate_totals', $and_taxes, $this );

		$fees_total   = 0;
		$subtotal_tax = 0;
		$total_tax    = 0;

		$subtotal = $this->get_cart_subtotal();
		$total    = $this->get_cart_total();

		// Sum fee costs.
		foreach ( $this->get_fees() as $item ) {
			$fee_total = $item->get_total();

			if ( 0 > $fee_total && 0 > $fee_total ) {
				$max_discount = ER_Number_Util::round( $total + $fees_total, er_get_price_decimals() ) * - 1;

				if ( $fee_total < $max_discount ) {
					$item->set_total( $max_discount );
				}
			}

			$fees_total += $item->get_total();
		}

		// Calculate taxes for items. Note; this also triggers save().
		if ( $and_taxes ) {
			$this->calculate_taxes();
		}

		// Sum taxes again so we can work out how much tax was discounted. This uses original values, not those possibly rounded to 2dp.
		foreach ( $this->get_items() as $item ) {
			$taxes = $item->get_taxes();

			foreach ( $taxes['total'] as $tax_rate_id => $tax ) {
				$total_tax += (float) $tax;
			}

			foreach ( $taxes['subtotal'] as $tax_rate_id => $tax ) {
				$subtotal_tax += (float) $tax;
			}
		}

		$this->set_discount_total( ER_Number_Util::round( $subtotal - $total, er_get_price_decimals() ) );
		$this->set_discount_tax( er_round_tax_total( $subtotal_tax - $total_tax ) );
		$this->set_total( ER_Number_Util::round( $total + $fees_total + $this->get_total_tax(), er_get_price_decimals() ) );

		do_action( 'easyreservations_after_calculate_totals', $and_taxes, $this );

		if ( $this->get_type() === 'reservation' && $this->get_order_id() && 1 == 2 ) {
			//If is a reservation attached to an order update order line and totals
			$order = er_get_order( $this->get_order_id() );

			if ( $order ) {

				$item = $order->find_reservation( $this->get_id() );
				if ( $item ) {
					$item->set_resource_id( $this->get_resource_id() );
					$item->set_name( $this->get_name() );
					$item->set_subtotal( $this->get_subtotal() );
					$item->set_total( $this->get_subtotal() );

					$taxes = $this->get_taxes_totals();

					//Total and subtotal taxes are the same as coupons only get applied to orders
					$item->set_taxes( array(
						'total'    => $taxes,
						'subtotal' => $taxes,
					) );

					$item->save();

					$order->calculate_totals( true );
					$order->save();
				}
			}
		}

		return $this->get_total();
	}

	/*
	|--------------------------------------------------------------------------
	| Custom functions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Add custom data as receipt item
	 *
	 * @param array $custom
	 * @param bool  $add_fee
	 */
	public function add_custom( $custom, $add_fee = true ) {
		$item = new ER_Receipt_Item_Custom( 0 );
		$item->set_custom_id( $custom['custom_id'] );
		$item->set_custom_value( $custom['custom_value'] );
		$item->set_custom_display( $custom['custom_display'] );
		$item->set_name( $custom['custom_title'] );

		$this->add_item( $item );

		if ( $add_fee && isset( $custom['custom_total'] ) ) {
			$item = new ER_Receipt_Item_Fee( 0 );
			$item->set_custom_id( $custom['custom_id'] );
			$item->set_name( $custom['custom_title'] );
			$item->set_value( $custom['custom_display'] );
			$item->set_subtotal( $custom['custom_total'] );
			$item->set_total( $custom['custom_total'] );

			$this->add_item( $item );
		}
	}

	/**
	 * Get array of custom data ordered by id
	 *
	 * @return array
	 */
	function get_custom_data() {
		$return = array();

		foreach ( $this->get_items( 'custom' ) as $custom ) {
			$return[ $custom->get_id() ] = array(
				'value' => $custom->get_custom_value()
			);
		}

		return $return;
	}

	/**
	 * Get a formatted custom data for the object.
	 *
	 * @param string $empty_content Content to show if no address is present.
	 *
	 * @return array
	 */
	public function get_formatted_custom( $empty_content = '' ) {
		$items             = $this->get_items( 'custom' );
		$formatted_customs = array();

		foreach ( $items as $item ) {
			$formatted_customs[ $item->get_custom_id() ] = (object) array(
				'key'           => $item->get_custom_id(),
				'value'         => $item->get_custom_value(),
				'display_key'   => apply_filters( 'easyreservations_custom_display_title', $item->get_name(), $this ),
				'display_value' => wpautop( make_clickable( apply_filters( 'easyreservations_receipt_item_display_meta_value', $item->get_custom_display(), $this ) ) ),
			);
		}

		return $formatted_customs;
	}

	/*
	|--------------------------------------------------------------------------
	| Status
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set object status.
	 *
	 * @param string $new_status Status to change to.
	 * @param string $note Optional note to add.
	 * @param bool   $manual_update Is this a manual status change?.
	 *
	 * @return array
	 */
	public function set_status( $new_status, $note = '', $manual_update = false ) {
		$old_status   = $this->get_status();
		$order_status = $this->get_type() === 'reservation' ? 'ER_Reservation_Status' : 'ER_Order_Status';

		// Only allow valid new status.
		if ( ! $order_status::get_title( $new_status ) ) {
			$new_status = 'pending';
		}

		// If the old status is set but unknown (e.g. draft) assume its pending for action usage.
		if ( $old_status && ! $order_status::get_title( $old_status ) ) {
			$old_status = 'pending';
		}

		$this->set_prop( 'status', $new_status );

		$result = array(
			'from' => $old_status,
			'to'   => $new_status,
		);

		if ( true === $this->object_read && $this->get_id() && ! empty( $result['from'] ) && $result['from'] !== $result['to'] ) {
			$this->status_transition = array(
				'from'   => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $result['from'],
				'to'     => $result['to'],
				'note'   => $note,
				'manual' => (bool) $manual_update,
			);

			if ( $manual_update ) {
				do_action( 'easyreservations_' . $this->get_type() . '_edit_status', $this->get_id(), $result['to'] );
			}
		}

		return $result;
	}

	/**
	 * Updates status immediately.
	 *
	 * @param string $new_status Status to change to. No internal er- prefix is required.
	 * @param string $note Optional note to add.
	 * @param bool   $manual Is this a manual status change?.
	 *
	 * @return bool
	 */
	public function update_status( $new_status, $note = '', $manual = false ) {
		if ( ! $this->get_id() ) { // Object must exist.
			return false;
		}

		try {
			$this->set_status( $new_status, $note, $manual );
			$this->save();
		} catch ( Exception $e ) {
			$object_name = $this->get_type();
			$logger      = er_get_logger();
			$logger->error(
				sprintf(
					'Error updating status for %s #%d',
					$object_name,
					$this->get_id()
				),
				array(
					$object_name => $this,
					'error'      => $e,
				)
			);

			$this->add_order_note( __( 'Update status event failed.', 'easyReservations' ) . ' ' . $e->getMessage() );

			return false;
		}

		return true;
	}

	/**
	 * Handle the status transition.
	 */
	protected function status_transition() {
		$status_transition = $this->status_transition;

		// Reset status transition variable.
		$this->status_transition = false;

		if ( $status_transition ) {
			try {
				do_action( 'easyreservations_' . $this->object_type . '_status_' . $status_transition['to'], $this->get_id(), $this );

				if ( ! empty( $status_transition['from'] ) ) {
					/* translators: 1: old order status 2: new order status */
					if( $this->object_type == 'order' ){
						$transition_note = sprintf( __( 'Order status changed from %1$s to %2$s.', 'easyReservations' ), ER_Order_Status::get_title( $status_transition['from'] ), ER_Order_Status::get_title( $status_transition['to'] ) );
					} else {
						$transition_note = sprintf( __( 'Reservation #%1$d status changed from %2$s to %3$s.', 'easyReservations' ), $this->get_id(), ER_Reservation_Status::get_title( $status_transition['from'] ), ER_Reservation_Status::get_title( $status_transition['to'] ) );
					}

					// Note the transition occurred.
					$this->add_status_transition_note( $transition_note, $status_transition );

					do_action( 'easyreservations_' . $this->object_type . '_status_' . $status_transition['from'] . '_to_' . $status_transition['to'], $this->get_id(), $this );
					do_action( 'easyreservations_' . $this->object_type . '_status_changed', $this->get_id(), $status_transition['from'], $status_transition['to'], $this );
				} else {
					/* translators: %s: new order status */
					if ( $this->object_type == 'order' ) {
						$transition_note = sprintf( __( 'Order status set to %s.', 'easyReservations' ), ER_Order_Status::get_title( $status_transition['to'] ) );
					} else {
						$transition_note = sprintf( __( 'Reservation #%1$d status set to %2$s.', 'easyReservations' ), $this->get_id(), ER_Reservation_Status::get_title( $status_transition['to'] ) );
					}

					$this->add_status_transition_note( $transition_note, $status_transition );
				}
			} catch ( Exception $e ) {
				$object_name = $this->get_type();
				$logger      = er_get_logger();
				$logger->error(
					sprintf(
						'Status transition of %s #%d error!',
						$object_name,
						$this->get_id()
					)
				);
				$this->add_order_note( __( 'Error during status transition.', 'easyReservations' ) . ' ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Add an order note for status transition
	 *
	 * @param string $note Note to be added giving status transition from and to details.
	 * @param bool   $transition Details of the status transition.
	 *
	 * @return int                  Comment ID.
	 * @uses ER_Order::add_order_note()
	 */
	private function add_status_transition_note( $note, $transition ) {
		return $this->add_order_note( trim( $transition['note'] . ' ' . $note ), 0, $transition['manual'] );
	}

	/**
	 * Checks the order status against a passed in status.
	 *
	 * @param array|string $status Status to check.
	 *
	 * @return bool
	 */
	public function has_status( $status ) {
		return apply_filters( 'easyreservations_' . $this->object_type . '_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status, true ) ) || $this->get_status() === $status, $this, $status );
	}

	/**
	 * Adds a note (comment) to the order. Order must exist.
	 *
	 * @param string $note Note to add.
	 * @param int    $is_customer_note Is this a note for the customer?.
	 * @param bool   $added_by_user Was the note added by a user?.
	 *
	 * @return int                       Comment ID.
	 */
	public function add_order_note( $note, $is_customer_note = 0, $added_by_user = false ) {
		if ( method_exists( $this, 'get_order_id' ) ) {
			return er_order_add_note( $this->get_order_id(), $note, $is_customer_note, $added_by_user );
		} else {
			return er_order_add_note( $this->get_id(), $note, $is_customer_note, $added_by_user );
		}
	}
}