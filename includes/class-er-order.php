<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Order extends ER_Abstract_Order {
	/**
	 * Order Data array.
	 *
	 * @var array
	 */
	protected $data = array(
		// Abstract order props.
		'status'               => '',
		'prices_include_tax'   => false,
		'date_created'         => null,
		'date_modified'        => null,
		'discount_total'       => 0,
		'discount_tax'         => 0,
		'total'                => 0,
		'total_tax'            => 0,
		'paid'                 => 0,

		// Order props.
		'customer_id'          => 0,
		'order_key'            => '',
		'address'              => array(
			'first_name' => '',
			'last_name'  => '',
			'company'    => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
			'email'      => '',
			'phone'      => '',
		),
		'payment_method'       => '',
		'payment_method_title' => '',
		'transaction_id'       => '',
		'customer_ip_address'  => '',
		'customer_user_agent'  => '',
		'created_via'          => '',
		'locale'               => '',
		'customer_note'        => '',
		'date_completed'       => null,
		'date_paid'            => null,
		'cart_hash'            => '',
	);

	/**
	 * Untrash order.
	 */
	public function untrash() {
		wp_untrash_post( $this->get_id() );
	}

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
		$this->maybe_set_user_email();
		parent::save();
		$this->status_transition();

		return $this->get_id();
	}

	/**
	 * Log an error about this order is exception is encountered.
	 * @param Exception $e Exception object.
	 * @param string    $message Message regarding exception thrown.
	 */
	protected function handle_exception( $e, $message = 'Error' ) {
		er_get_logger()->error(
			$message,
			array(
				'order' => $this,
				'error' => $e,
			)
		);
		$this->add_order_note( $message . ' ' . $e->getMessage() );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the order object.
	|
	*/

	/**
	 * Get all class data in array format.
	 *
	 * @return array
	 */
	public function get_data() {
		return array_merge(
			array(
				'id' => $this->get_id(),
			),
			$this->data,
			array(
				'number'            => $this->get_order_number(),
				'meta_data'         => $this->get_meta_data(),
				'reservation_lines' => $this->get_items( 'reservation' ),
				'fee_lines'         => $this->get_items( 'fee' ),
				'tax_lines'         => $this->get_items( 'tax' ),
				'coupon_lines'      => $this->get_items( 'coupon' ),
				'custom'            => $this->get_items( 'custom' ),
			)
		);
	}

	/**
	 * Expands the addrss information in the changes array.
	 */
	public function get_changes() {
		$changed_props = parent::get_changes();

		if ( ! empty( $changed_props['address'] ) ) {
			foreach ( $changed_props['address'] as $sub_prop => $value ) {
				$changed_props[ 'address_' . $sub_prop ] = $value;
			}
		}

		if ( isset( $changed_props['customer_note'] ) ) {
			$changed_props['post_excerpt'] = $changed_props['customer_note'];
		}

		return $changed_props;
	}

	/**
	 * Gets the order number for display (by default, order ID).
	 *
	 * @return string
	 */
	public function get_order_number() {
		return (string) apply_filters( 'easyreservations_order_number', $this->get_id(), $this );
	}

	/**
	 * Get order key.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_order_key( $context = 'view' ) {
		return $this->get_prop( 'order_key', $context );
	}

	/**
	 * Get customer_id.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_customer_id( $context = 'view' ) {
		return $this->get_prop( 'customer_id', $context );
	}

	/**
	 * Alias for get_customer_id().
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_user_id( $context = 'view' ) {
		return $this->get_customer_id( $context );
	}

	/**
	 * Get the user associated with the order. False for guests.
	 *
	 * @return WP_User|false
	 */
	public function get_user() {
		return $this->get_user_id() ? get_user_by( 'id', $this->get_user_id() ) : false;
	}

	/**
	 * Gets a prop for a getter method.
	 *
	 * @param string $prop Name of prop to get.
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return mixed
	 */
	public function get_address_prop( $prop, $context = 'view' ) {
		$value = null;

		if ( array_key_exists( $prop, $this->data['address'] ) ) {
			$value = isset( $this->changes['address'][ $prop ] ) ? $this->changes['address'][ $prop ] : $this->data['address'][ $prop ];

			if ( 'view' === $context ) {
				$value = apply_filters( $this->get_hook_prefix() . 'address_' . $prop, $value, $this );
			}
		}

		return $value;
	}

	/**
	 * Get billing first name.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_first_name( $context = 'view' ) {
		return $this->get_address_prop( 'first_name', $context );
	}

	/**
	 * Get billing last name.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_last_name( $context = 'view' ) {
		return $this->get_address_prop( 'last_name', $context );
	}

	/**
	 * Get billing company.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_company( $context = 'view' ) {
		return $this->get_address_prop( 'company', $context );
	}

	/**
	 * Get billing address line 1.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_address_1( $context = 'view' ) {
		return $this->get_address_prop( 'address_1', $context );
	}

	/**
	 * Get billing address line 2.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_address_2( $context = 'view' ) {
		return $this->get_address_prop( 'address_2', $context );
	}

	/**
	 * Get billing city.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_city( $context = 'view' ) {
		return $this->get_address_prop( 'city', $context );
	}

	/**
	 * Get billing state.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_state( $context = 'view' ) {
		return $this->get_address_prop( 'state', $context );
	}

	/**
	 * Get billing postcode.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_postcode( $context = 'view' ) {
		return $this->get_address_prop( 'postcode', $context );
	}

	/**
	 * Get billing country.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_country( $context = 'view' ) {
		return $this->get_address_prop( 'country', $context );
	}

	/**
	 * Get billing email.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_email( $context = 'view' ) {
		return $this->get_address_prop( 'email', $context );
	}

	/**
	 * Get billing phone.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_phone( $context = 'view' ) {
		return $this->get_address_prop( 'phone', $context );
	}

	/**
	 * Get the payment method.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_payment_method( $context = 'view' ) {
		return $this->get_prop( 'payment_method', $context );
	}

	/**
	 * Get payment method title.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_payment_method_title( $context = 'view' ) {
		return $this->get_prop( 'payment_method_title', $context );
	}

	/**
	 * Get transaction d.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_transaction_id( $context = 'view' ) {
		return $this->get_prop( 'transaction_id', $context );
	}

	/**
	 * Get customer ip address.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_customer_ip_address( $context = 'view' ) {
		return $this->get_prop( 'customer_ip_address', $context );
	}

	/**
	 * Get customer user agent.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_customer_user_agent( $context = 'view' ) {
		return $this->get_prop( 'customer_user_agent', $context );
	}

	/**
	 * Get created via.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_created_via( $context = 'view' ) {
		return $this->get_prop( 'created_via', $context );
	}

	/**
	 * Get created via.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_locale( $context = 'view' ) {
		return $this->get_prop( 'locale', $context );
	}

	/**
	 * Get customer note.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_customer_note( $context = 'view' ) {
		return $this->get_prop( 'customer_note', $context );
	}

	/**
	 * Get date completed.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return ER_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_completed( $context = 'view' ) {
		return $this->get_prop( 'date_completed', $context );
	}

	/**
	 * Get date paid.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return ER_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_paid( $context = 'view' ) {
		return $this->get_prop( 'date_paid', $context );
	}

	/**
	 * Get date paid.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float
	 */
	public function get_paid( $context = 'view' ) {
		return $this->get_prop( 'paid', $context );
	}

	/**
	 * Get cart hash.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_cart_hash( $context = 'view' ) {
		return $this->get_prop( 'cart_hash', $context );
	}

	/**
	 * Returns the requested address in raw, non-formatted way.
	 * Note: Merges raw data with get_prop data so changes are returned too.
	 *
	 * @return array The stored address after filter.
	 */
	public function get_address() {
		return apply_filters( 'easyreservations_get_order_address', array_merge( $this->data['address'], $this->get_prop( 'address', 'view' ) ), $this );
	}

	/**
	 * Get a formatted billing full name.
	 *
	 * @return string
	 */
	public function get_formatted_full_name() {
		/* translators: 1: first name 2: last name */
		return sprintf( _x( '%1$s %2$s', 'full name', 'easyReservations' ), $this->get_first_name(), $this->get_last_name() );
	}

	/**
	 * Get a formatted address for the order.
	 *
	 * @param string $empty_content Content to show if no address is present.
	 *
	 * @return string
	 */
	public function get_formatted_address( $empty_content = '' ) {
		$raw_address = apply_filters( 'easyreservations_order_formatted_address', $this->get_address(), $this );
		$address     = ER()->countries->get_formatted_address( $raw_address );

		/**
		 * Filter orders formatterd billing address.
		 *
		 * @param string   $address Formatted billing address string.
		 * @param array    $raw_address Raw billing address.
		 * @param ER_Order $order Order data.
		 */
		return apply_filters( 'easyreservations_order_get_formatted_address', $address ? $address : $empty_content, $raw_address, $this );
	}

	/**
	 * Gets order total - formatted for display.
	 *
	 * @param string $tax_display Type of tax display.
	 * @param bool   $display_refunded If should include refunded value.
	 *
	 * @return string
	 */
	public function get_formatted_total( $tax_display = '', $display_refunded = true ) {
		$formatted_total = er_price( $this->get_total(), true );
		$order_total     = $this->get_total();
		$total_refunded  = $this->get_total_refunded();
		$tax_string      = '';

		// Tax for inclusive prices.
		if ( er_tax_enabled() && 'incl' === $tax_display ) {
			$tax_string_array = array();
			$tax_totals       = $this->get_tax_totals();

			if ( 'itemized' === get_option( 'reservations_tax_total_display' ) ) {
				foreach ( $tax_totals as $code => $tax ) {
					$tax_amount         = ( $total_refunded && $display_refunded ) ? er_price( ER_Tax::round( $tax->amount - $this->get_total_tax_refunded_by_rate_id( $tax->rate_id ) ), true ) : $tax->formatted_amount;
					$tax_string_array[] = sprintf( '%s %s', $tax_amount, $tax->label );
				}
			} elseif ( ! empty( $tax_totals ) ) {
				$tax_amount         = ( $total_refunded && $display_refunded ) ? $this->get_total_tax() - $this->get_total_tax_refunded() : $this->get_total_tax();
				$tax_string_array[] = sprintf( '%s %s', er_price( $tax_amount, true ), ER()->countries->tax_or_vat() );
			}

			if ( ! empty( $tax_string_array ) ) {
				/* translators: %s: taxes */
				$tax_string = ' <small class="includes_tax">' . sprintf( __( '(includes %s)', 'easyReservations' ), implode( ', ', $tax_string_array ) ) . '</small>';
			}
		}

		if ( $total_refunded && $display_refunded ) {
			$formatted_total = '<del>' . wp_strip_all_tags( $formatted_total ) . '</del> <ins>' . er_price( $order_total - $total_refunded, true ) . $tax_string . '</ins>';
		} else {
			$formatted_total .= $tax_string;
		}

		/**
		 * Filter easyReservations formatted order total.
		 *
		 * @param string   $formatted_total Total to display.
		 * @param ER_Order $order Order data.
		 * @param string   $tax_display Type of tax display.
		 * @param bool     $display_refunded If should include refunded value.
		 */
		return apply_filters( 'easyreservations_get_formatted_order_total', $formatted_total, $this, $tax_display, $display_refunded );
	}

	/**
	 * Get amoun to pay
	 *
	 * @return float
	 */
	public function get_amount_due() {
		return apply_filters( 'easyreservations_order_amount_to_pay', $this->get_total() - $this->get_paid() );
	}

	/**
	 * Get link to edit order
	 *
	 * @return string
	 */
	public function get_edit_link() {
		return '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $this->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $this->get_id() ) . ' ' . esc_html( $this->get_first_name() ) . ' ' . esc_html( $this->get_last_name() ) . '</strong></a>';
	}

	/**
	 * Return an array of coupons within this order.
	 *
	 * @return ER_Receipt_Item_Coupon[]
	 */
	public function get_coupons() {
		return $this->get_items( 'coupon' );
	}

	/**
	 * Return an array of reservations within this order.
	 *
	 * @return ER_Receipt_Item_Reservation[]
	 */
	public function get_reservations() {
		return $this->get_items( 'reservation' );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting order data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object. However, for backwards compatibility pre 3.0.0 some of these
	| setters may handle both.
	|
	*/

	/**
	 * Sets a prop for a setter method.
	 *
	 * @param string $prop Name of prop to set.
	 * @param mixed  $value Value of the prop.
	 */
	public function set_address_prop( $prop, $value ) {
		if ( array_key_exists( $prop, $this->data['address'] ) ) {
			if ( true === $this->object_read ) {
				if ( $value !== $this->data['address'][ $prop ] || ( isset( $this->changes['address'] ) && array_key_exists( $prop, $this->changes['address'] ) ) ) {
					$this->changes['address'][ $prop ] = $value;
				}
			} else {
				$this->data['address'][ $prop ] = $value;
			}
		}
	}

	/**
	 * Set order key.
	 *
	 * @param string $value Max length 22 chars.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_order_key( $value ) {
		$this->set_prop( 'order_key', substr( $value, 0, 22 ) );
	}

	/**
	 * Set customer id.
	 *
	 * @param int $value Customer ID.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_customer_id( $value ) {
		$this->set_prop( 'customer_id', absint( $value ) );
	}

	/**
	 * Set cart hash.
	 *
	 * @param string $value locale.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_cart_hash( $value ) {
		$this->set_prop( 'cart_hash', sanitize_key( $value ) );
	}

	/**
	 * Set order locale.
	 *
	 * @param string $value locale.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_locale( $value ) {
		$this->set_prop( 'locale', sanitize_title( $value ) );
	}

	/**
	 * Set first name.
	 *
	 * @param string $value first name.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_first_name( $value ) {
		$this->set_address_prop( 'first_name', $value );
	}

	/**
	 * Set last name.
	 *
	 * @param string $value last name.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_last_name( $value ) {
		$this->set_address_prop( 'last_name', $value );
	}

	/**
	 * Set company.
	 *
	 * @param string $value company.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_company( $value ) {
		$this->set_address_prop( 'company', $value );
	}

	/**
	 * Set address line 1.
	 *
	 * @param string $value address line 1.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_address_1( $value ) {
		$this->set_address_prop( 'address_1', $value );
	}

	/**
	 * Set address line 2.
	 *
	 * @param string $value address line 2.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_address_2( $value ) {
		$this->set_address_prop( 'address_2', $value );
	}

	/**
	 * Set city.
	 *
	 * @param string $value city.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_city( $value ) {
		$this->set_address_prop( 'city', $value );
	}

	/**
	 * Set state.
	 *
	 * @param string $value state.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_state( $value ) {
		$this->set_address_prop( 'state', $value );
	}

	/**
	 * Set postcode.
	 *
	 * @param string $value postcode.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_postcode( $value ) {
		$this->set_address_prop( 'postcode', $value );
	}

	/**
	 * Set country.
	 *
	 * @param string $value country.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_country( $value ) {
		$this->set_address_prop( 'country', $value );
	}

	/**
	 * Maybe set empty email to that of the user who owns the order.
	 */
	protected function maybe_set_user_email() {
		$user = $this->get_user();
		if ( ! $this->get_email() && $user ) {
			try {
				$this->set_email( $user->user_email );
			} catch ( ER_Data_Exception $e ) {
				unset( $e );
			}
		}
	}

	/**
	 * Set email.
	 *
	 * @param string $value email.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_email( $value ) {
		if ( $value && ! is_email( $value ) ) {
			$this->error( 'order_invalid_email', __( 'Invalid email address', 'easyReservations' ) );
		}
		$this->set_address_prop( 'email', sanitize_email( $value ) );
	}

	/**
	 * Set phone.
	 *
	 * @param string $value phone.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_phone( $value ) {
		$this->set_address_prop( 'phone', $value );
	}

	/**
	 * Set the payment method.
	 *
	 * @param string $payment_method Supports ER_Payment_Gateway
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_payment_method( $payment_method = '' ) {
		if ( is_object( $payment_method ) ) {
			$this->set_payment_method( $payment_method->id );
			$this->set_payment_method_title( $payment_method->get_title() );
		} elseif ( '' === $payment_method ) {
			$this->set_prop( 'payment_method', '' );
			$this->set_prop( 'payment_method_title', '' );
		} else {
			$this->set_prop( 'payment_method', $payment_method );
		}
	}

	/**
	 * Set payment method title.
	 *
	 * @param string $value Payment method title.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_payment_method_title( $value ) {
		$this->set_prop( 'payment_method_title', $value );
	}

	/**
	 * Set transaction id.
	 *
	 * @param string $value Transaction id.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_transaction_id( $value ) {
		$this->set_prop( 'transaction_id', $value );
	}

	/**
	 * Set customer ip address.
	 *
	 * @param string $value Customer ip address.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_customer_ip_address( $value ) {
		$this->set_prop( 'customer_ip_address', $value );
	}

	/**
	 * Set customer user agent.
	 *
	 * @param string $value Customer user agent.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_customer_user_agent( $value ) {
		$this->set_prop( 'customer_user_agent', $value );
	}

	/**
	 * Set created via.
	 *
	 * @param string $value Created via.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_created_via( $value ) {
		$this->set_prop( 'created_via', $value );
	}

	/**
	 * Set customer note.
	 *
	 * @param string $value Customer note.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_customer_note( $value ) {
		$this->set_prop( 'customer_note', $value );
	}

	/**
	 * Set date completed.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_date_completed( $date = null ) {
		$this->set_date_prop( 'date_completed', $date );
	}

	/**
	 * Set date paid.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_date_paid( $date = null ) {
		$this->set_date_prop( 'date_paid', $date );
	}

	/**
	 * Set amount paid.
	 *
	 * @param string $paid Value to set.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_paid( $paid = null ) {
		$this->set_prop( 'paid', er_format_decimal( $paid ) );
	}

	/**
	 * Set order status.
	 *
	 * @param string $new_status Status to change the order to.
	 * @param string $note Optional note to add.
	 * @param bool   $manual_update Is this a manual order status change?.
	 *
	 * @return array
	 */
	public function set_status( $new_status, $note = '', $manual_update = false ) {
		$result = parent::set_status( $new_status, $note, $manual_update );
		if ( $this->get_id() ) {
			$this->maybe_set_date_paid();
		}

		return $result;
	}

	/**
	 * See if order matches cart_hash.
	 *
	 * @param string $cart_hash Cart hash.
	 *
	 * @return bool
	 */
	public function has_cart_hash( $cart_hash = '' ) {
		return hash_equals( $this->get_cart_hash(), $cart_hash ); // @codingStandardsIgnoreLine
	}

	/**
	 * Checks if an order can be edited, specifically for use on the Edit Order screen.
	 *
	 * @return bool
	 */
	public function is_editable() {
		return apply_filters( 'easyreservations_order_is_editable', in_array( $this->get_status(), array(
			'pending',
			'on-hold',
			'auto-draft',
			'pending'
		), true ), $this );
	}

	/**
	 * Returns if an order has been paid for based on the order status.
	 *
	 * @return bool
	 */
	public function is_paid() {
		return apply_filters( 'easyreservations_order_is_paid', $this->has_status( er_get_is_paid_statuses() ), $this );
	}

	/**
	 * Check if order has been created via admin, checkout, or in another way.
	 *
	 * @param string $modus Way of creating the order to test for.
	 *
	 * @return bool
	 */
	public function is_created_via( $modus ) {
		return apply_filters( 'easyreservations_order_is_created_via', $modus === $this->get_created_via(), $this, $modus );
	}

	/**
	 * Checks if an order needs payment, based on status and order total.
	 *
	 * @return bool
	 */
	public function needs_payment() {
		$valid_order_statuses = apply_filters( 'easyreservations_valid_order_statuses_for_payment', array( 'pending', 'failed' ), $this );

		return apply_filters( 'easyreservations_order_needs_payment', ( $this->has_status( $valid_order_statuses ) && $this->get_total() > 0 ), $this, $valid_order_statuses );
	}

	/*
	|--------------------------------------------------------------------------
	| Refunds
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order refunds.
	 *
	 * @return ER_Order_Refund[]
	 */
	public function get_refunds() {
		$cache_key   = er_get_cache_prefix( 'orders' ) . 'refunds' . $this->get_id();
		$cached_data = wp_cache_get( $cache_key, 'orders' );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		$refunds = er_get_orders(
			array(
				'post_type'   => 'easy_order_refund',
				'post_status' => 'completed',
				'post_parent' => $this->get_id(),
				'limit'       => - 1,
			)
		);

		wp_cache_set( $cache_key, $refunds, 'orders' );

		return $refunds;
	}

	/**
	 * Get amount already refunded.
	 *
	 * @return string
	 */
	public function get_total_refunded() {
		$cache_key   = er_get_cache_prefix( 'orders' ) . 'total_refunded' . $this->get_id();
		$cached_data = wp_cache_get( $cache_key, $this->cache_group );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		$total_refunded = $this->data_store->get_total_refunded( $this );

		wp_cache_set( $cache_key, $total_refunded, $this->cache_group );

		return $total_refunded;
	}

	/**
	 * Get the total tax refunded.
	 *
	 * @return float
	 */
	public function get_total_tax_refunded() {
		$cache_key   = er_get_cache_prefix( 'orders' ) . 'total_tax_refunded' . $this->get_id();
		$cached_data = wp_cache_get( $cache_key, $this->cache_group );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		$total_refunded = $this->data_store->get_total_tax_refunded( $this );

		wp_cache_set( $cache_key, $total_refunded, $this->cache_group );

		return $total_refunded;
	}

	/**
	 * Gets the count of order items of a certain type that have been refunded.
	 *
	 * @param string $item_type Item type.
	 *
	 * @return string
	 */
	public function get_item_count_refunded( $item_type = '' ) {
		if ( empty( $item_type ) ) {
			$item_type = array( 'reservation', 'fee' );
		}
		if ( ! is_array( $item_type ) ) {
			$item_type = array( $item_type );
		}
		$count = 0;

		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				$count ++;
			}
		}

		return apply_filters( 'easyreservations_get_item_count_refunded', $count, $item_type, $this );
	}

	/**
	 * Get the refunded amount for a line item.
	 *
	 * @param int    $item_id ID of the item we're checking.
	 * @param string $item_type Type of the item we're checking, if not a line_item.
	 *
	 * @return int
	 */
	public function get_total_refunded_for_item( $item_id, $item_type = 'reservation' ) {
		$total = 0;
		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				if ( absint( $refunded_item->get_meta( '_refunded_item_id' ) ) === $item_id ) {
					$total += $refunded_item->get_total();
				}
			}
		}

		return $total * - 1;
	}

	/**
	 * Get the refunded tax amount for a line item.
	 *
	 * @param int    $item_id ID of the item we're checking.
	 * @param int    $tax_id ID of the tax we're checking.
	 * @param string $item_type Type of the item we're checking, if not a line_item.
	 *
	 * @return double
	 */
	public function get_tax_refunded_for_item( $item_id, $tax_id, $item_type = 'line_item' ) {
		$total = 0;
		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				$refunded_item_id = (int) $refunded_item->get_meta( '_refunded_item_id' );
				if ( $refunded_item_id === $item_id ) {
					$taxes = $refunded_item->get_taxes();
					$total += isset( $taxes['total'][ $tax_id ] ) ? (float) $taxes['total'][ $tax_id ] : 0;
					break;
				}
			}
		}

		return er_round_tax_total( $total ) * - 1;
	}

	/**
	 * Get total tax refunded by rate ID.
	 *
	 * @param int $rate_id Rate ID.
	 *
	 * @return float
	 */
	public function get_total_tax_refunded_by_rate_id( $rate_id ) {
		$total = 0;
		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_taxes() as $refunded_item ) {
				if ( absint( $refunded_item->get_rate_id() ) === $rate_id ) {
					$total += abs( $refunded_item->get_tax_total() );
				}
			}
		}

		return $total;
	}

	/**
	 * How much money is left to refund?
	 *
	 * @return string
	 */
	public function get_remaining_refund_amount() {
		return er_format_decimal( $this->get_total() - $this->get_total_refunded(), er_get_price_decimals() );
	}

	/**
	 * How many items are left to refund?
	 *
	 * @return int
	 */
	public function get_remaining_refund_items() {
		return absint( $this->get_item_count() - $this->get_item_count_refunded() );
	}

	/*
	|--------------------------------------------------------------------------
	| Totals for display
	|--------------------------------------------------------------------------
	*/

	/**
	 * Add total row for the payment method.
	 *
	 * @param array  $total_rows Total rows.
	 * @param string $tax_display Tax to display.
	 */
	protected function add_order_item_totals_payment_method_row( &$total_rows, $tax_display ) {
		if ( $this->get_total() > 0 && $this->get_payment_method() && 'other' !== $this->get_payment_method() ) {
			$total_rows['payment_method'] = array(
				'label' => __( 'Payment method:', 'easyReservations' ),
				'value' => $this->get_payment_method(),
			);
		}
	}

	/**
	 * Add total row for refunds.
	 *
	 * @param array  $total_rows Total rows.
	 * @param string $tax_display Tax to display.
	 */
	protected function add_order_item_totals_refund_rows( &$total_rows, $tax_display ) {
		$refunds = $this->get_refunds();

		if ( $refunds ) {
			foreach ( $refunds as $id => $refund ) {
				$total_rows[ 'refund_' . $id ] = array(
					'label' => $refund->get_reason() ? $refund->get_reason() : __( 'Refund', 'easyReservations' ) . ':',
					'value' => er_price( '-' . $refund->get_amount(), true ),
				);
			}
		}
	}

	/**
	 * Get totals for display on pages and in emails.
	 *
	 * @param string $tax_display Tax to display.
	 *
	 * @return array
	 */
	public function get_order_item_totals( $tax_display = '' ) {
		$tax_display = $tax_display ? $tax_display : get_option( 'reservations_tax_display_cart' );
		$total_rows  = array();

		$this->add_order_item_totals_subtotal_row( $total_rows, $tax_display );
		$this->add_order_item_totals_discount_row( $total_rows, $tax_display );
		$this->add_order_item_totals_fee_rows( $total_rows, $tax_display );
		$this->add_order_item_totals_tax_rows( $total_rows, $tax_display );
		$this->add_order_item_totals_payment_method_row( $total_rows, $tax_display );
		$this->add_order_item_totals_refund_rows( $total_rows, $tax_display );
		$this->add_order_item_totals_total_row( $total_rows, $tax_display );

		if ( $this->get_paid() ) {
			$total_rows['paid'] = array(
				'label' => __( 'Already paid', 'easyReservations' ) . ':',
				'value' => er_price( $this->get_paid(), true ),
			);

			$total_rows['due'] = array(
				'label' => __( 'Due', 'easyReservations' ) . ':',
				'value' => er_price( $this->get_amount_due(), true ),
			);
		}

		return apply_filters( 'easyreservations_get_order_item_totals', $total_rows, $this, $tax_display );
	}

	/*
	|--------------------------------------------------------------------------
	| URLs and Endpoints
	|--------------------------------------------------------------------------
	*/

	/**
	 * Generates a URL so that a customer can pay for their (unpaid - pending) order. Pass 'true' for the checkout version which doesn't offer gateway choices.
	 *
	 * @param bool $on_checkout If on checkout.
	 *
	 * @return string
	 */
	public function get_checkout_payment_url( $on_checkout = false ) {
		$pay_url = er_get_endpoint_url( 'order-payment', $this->get_id(), er_get_checkout_url() );

		if ( $on_checkout ) {
			$pay_url = add_query_arg( 'key', $this->get_order_key(), $pay_url );
		} else {
			$pay_url = add_query_arg(
				array(
					'pay_for_order' => 'true',
					'key'           => $this->get_order_key(),
				), $pay_url
			);
		}

		return apply_filters( 'easyreservations_get_checkout_payment_url', $pay_url, $this );
	}

	/**
	 * Generates a URL for the thanks page (order received).
	 *
	 * @return string
	 */
	public function get_checkout_order_received_url() {
		$order_received_url = er_get_endpoint_url( 'order-received', $this->get_id(), er_get_checkout_url() );

		$order_received_url = add_query_arg( 'key', $this->get_order_key(), $order_received_url );

		return apply_filters( 'easyreservations_get_checkout_order_received_url', $order_received_url, $this );
	}

	/**
	 * Generates a URL so that a customer can cancel their (unpaid - pending) order.
	 *
	 * @param string $redirect Redirect URL.
	 *
	 * @return string
	 */
	public function get_cancel_order_url( $redirect = '' ) {
		return apply_filters(
			'easyreservations_get_cancel_order_url', wp_nonce_url(
				add_query_arg(
					array(
						'cancel_order' => 'true',
						'order'        => $this->get_order_key(),
						'order_id'     => $this->get_id(),
						'redirect'     => $redirect,
					), $this->get_cancel_endpoint()
				), 'easyreservations-cancel_order'
			)
		);
	}

	/**
	 * Generates a raw (unescaped) cancel-order URL for use by payment gateways.
	 *
	 * @param string $redirect Redirect URL.
	 *
	 * @return string The unescaped cancel-order URL.
	 */
	public function get_cancel_order_url_raw( $redirect = '' ) {
		return apply_filters(
			'easyreservations_get_cancel_order_url_raw', add_query_arg(
				array(
					'cancel_order' => 'true',
					'order'        => $this->get_order_key(),
					'order_id'     => $this->get_id(),
					'redirect'     => $redirect,
					'_wpnonce'     => wp_create_nonce( 'easyreservations-cancel_order' ),
				), $this->get_cancel_endpoint()
			)
		);
	}

	/**
	 * Helper method to return the cancel endpoint.
	 *
	 * @return string the cancel endpoint; either the cart page or the home page.
	 */
	public function get_cancel_endpoint() {
		$cancel_endpoint = er_get_cart_url();
		if ( ! $cancel_endpoint ) {
			$cancel_endpoint = home_url();
		}

		if ( false === strpos( $cancel_endpoint, '?' ) ) {
			$cancel_endpoint = trailingslashit( $cancel_endpoint );
		}

		return $cancel_endpoint;
	}

	/**
	 * Generates a URL to view an order from the my account page.
	 *
	 * @return string
	 */
	public function get_view_order_url() {
		return apply_filters( 'easyreservations_get_view_order_url', er_get_endpoint_url( 'view-order', $this->get_id(), er_get_page_permalink( 'myaccount' ) ), $this );
	}

	/**
	 * Get's the URL to edit the order in the backend.
	 *
	 * @return string
	 */
	public function get_edit_order_url() {
		return apply_filters( 'easyreservations_get_edit_order_url', get_admin_url( null, 'post.php?post=' . $this->get_id() . '&action=edit' ), $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Order notes.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Adds a note (comment) to the order. Order must exist. Enables reservations to add notes to their order.
	 *
	 * @param string $note Note to add.
	 * @param int    $is_customer_note Is this a note for the customer?.
	 * @param bool   $added_by_user Was the note added by a user?.
	 *
	 * @return int                       Comment ID.
	 */
	public function add_order_note( $note, $is_customer_note = 0, $added_by_user = false ) {
		if ( ! $this->get_id() ) {
			return 0;
		}

		return er_order_add_note( $this->get_id(), $note, $is_customer_note, $added_by_user );
	}

	/**
	 * List order notes (public) for the customer.
	 *
	 * @return array
	 */
	public function get_customer_order_notes() {
		$notes = array();
		$args  = array(
			'post_id' => $this->get_id(),
			'approve' => 'approve',
			'type'    => '',
		);

		remove_filter( 'comments_clauses', array( 'ER_Comments', 'exclude_order_comments' ) );

		$comments = get_comments( $args );

		foreach ( $comments as $comment ) {
			if ( ! get_comment_meta( $comment->comment_ID, 'is_customer_note', true ) ) {
				continue;
			}
			$comment->comment_content = make_clickable( $comment->comment_content );
			$notes[]                  = $comment;
		}

		add_filter( 'comments_clauses', array( 'ER_Comments', 'exclude_order_comments' ) );

		return $notes;
	}

	/**
	 * Add reservation to order
	 *
	 * @param int|ER_Reservation $reservation
	 * @param bool               $validate
	 * @param bool|float         $price
	 *
	 * @return bool
	 */
	public function add_reservation( $reservation, $validate = true, $price = false ) {
		if ( is_numeric( $reservation ) ) {
			$reservation = ER()->reservation_manager()->get( absint( $reservation ) );
		}

		if ( $reservation ) {
			$errors = $validate ? $reservation->validate( new WP_Error(), ER()->cart->get_reservations(), true ) : false;

			if ( ! is_wp_error( $errors ) || ! $errors->has_errors() ) {
				$taxes = $reservation->get_taxes_totals();

				$item = new ER_Receipt_Item_Reservation();
				$item->set_reservation_id( $reservation->get_id() );
				$item->set_resource_id( $reservation->get_resource_id() );
				$item->set_name( $reservation->get_name() );
				$item->set_subtotal( $price ? $price : $reservation->get_subtotal() + $reservation->get_discount_total() );
				$item->set_total( $price ? $price : $reservation->get_total() + $reservation->get_discount_total() );

				//Total and subtotal taxes are the same as coupons only get applied to orders
				$item->set_taxes( array(
					'total'    => $taxes,
					'subtotal' => $taxes,
				) );

				$this->add_item( $item );
			} else {
				throw new Exception( sprintf( __( 'Cannot order %s. %s.', 'easyReservations' ), $reservation->get_name(), $errors->get_error_message() ) );
			}
		} else {
			throw new Exception( __( 'Invalid reservation', 'easyReservations' ) );
		}

		return true;
	}

	/**
	 * Finalize an order and its reservations after they got validated
	 */
	public function finalize() {
		$i = 1;

		foreach ( $this->get_reservations() as $reservation_item ) {
			$reservation = $reservation_item->get_reservation();

			if ( $reservation ) {
				$reservation->set_status( ER_Reservation_Status::PENDING );
				$reservation->set_order_id( $this->get_id() );
				$reservation->set_title( $this->get_formatted_full_name() . ' ' . $i );

				$reservation = apply_filters( 'easyreservations_before_add_reservation_to_order', $reservation, $this );

				$reservation->save();

				$i ++;
			}
		}

		do_action( 'easyreservations_finalize_order', $this );
	}

	/**
	 * Does order need processing to be completed
	 *
	 * @return bool
	 */
	public function needs_processing() {
		return apply_filters( 'easyreservations_needs_processing', true, $this );
	}

	/**
	 * When a payment is complete this function is called.
	 *
	 * Most of the time this should mark an order as 'processing' so that admin can process/post the items.
	 * If the cart contains only downloadable items then the order is 'completed' since the admin needs to take no action.
	 * Stock levels are reduced at this point.
	 * Sales are also recorded for resources.
	 * Finally, record the date of payment.
	 *
	 * @param string     $transaction_id Optional transaction id to store in post meta.
	 * @param bool|float $paid Optional amount paid to store in post meta.
	 *
	 * @return bool success
	 */
	public function payment_complete( $transaction_id = '', $paid = false ) {
		if ( ! $this->get_id() ) { // Order must exist.
			return false;
		}

		try {
			do_action( 'easyreservations_before_payment_complete', $this->get_id() );

			if ( ER()->session ) {
				ER()->session->set( 'order_awaiting_payment', false );
			}

			if ( $this->has_status( apply_filters( 'easyreservations_valid_order_statuses_for_payment_complete', array(
					'auto-draft',
					'draft',
					'',
					'on-hold',
					'pending',
					'failed',
					'cancelled'
				), $this ) ) || $paid ) {
				if ( ! empty( $transaction_id ) ) {
					$this->set_transaction_id( $transaction_id );
				}

				if ( $paid ) {
					$this->add_to_paid( $paid );
				}

				if ( ! $this->get_date_paid() ) {
					$this->set_date_paid( time() );
				}

				$this->delete_meta_data( 'amount_to_pay' );
				$this->set_status( apply_filters( 'easyreservations_payment_complete_order_status', $this->needs_processing() ? 'processing' : 'completed', $this->get_id(), $this ) );
				$this->save();

				do_action( 'easyreservations_payment_complete', $this->get_id() );
			} else {
				do_action( 'easyreservations_payment_complete_order_status_' . $this->get_status(), $this->get_id() );
			}
		} catch ( Exception $e ) {
			/**
			 * If there was an error completing the payment, log to a file and add an order note so the admin can take action.
			 */
			er_get_logger()->error(
				sprintf( 'Error completing payment for order #%d', $this->get_id() )
			);
			$this->add_order_note( __( 'Payment complete event failed.', 'easyReservations' ) . ' ' . $e->getMessage() );

			return false;
		}

		return true;
	}

	/**
	 * Adds amount to paid amount
	 *
	 * @param float $amount
	 */
	public function add_to_paid( $amount ) {
		$this->set_paid( $this->get_paid( 'edit' ) + $amount );
	}

	/**
	 * Adds amount to paid amount
	 *
	 * @param float $amount
	 */
	public function substract_from_paid( $amount ) {
		$this->set_paid( max( 0, $this->get_paid( 'edit' ) - $amount ) );
	}

	/**
	 * Maybe set date paid.
	 *
	 * Sets the date paid variable when transitioning to the payment complete
	 * order status. This is either processing or completed. This is not filtered
	 * to avoid infinite loops e.g. if loading an order via the filter.
	 *
	 * Date paid is set once in this manner - only when it is not already set.
	 * This ensures the data exists even if a gateway does not use the
	 * `payment_complete` method.
	 */
	public function maybe_set_date_paid() {
		// This logic only runs if the date_paid prop has not been set yet.
		if ( ! $this->get_date_paid( 'edit' ) ) {
			$payment_completed_status = apply_filters( 'easyreservations_payment_complete_order_status', $this->needs_processing() ? 'processing' : 'completed', $this->get_id(), $this );

			if ( $this->has_status( $payment_completed_status ) ) {
				// If payment complete status is reached, set paid now.
				$this->set_date_paid( time() );
			} elseif ( 'processing' === $payment_completed_status && $this->has_status( 'completed' ) ) {
				// If payment complete status was processing, but we've passed that and still have no date, set it now.
				$this->set_date_paid( time() );
			}
		}
	}
}

