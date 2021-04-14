<?php
/**
 * easyReservations cart
 *
 * The easyReservations cart class stores cart data and active coupons as well as handling customer sessions and some cart related urls.
 *
 * @package easyReservations/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * ER_Cart class.
 */
class ER_Cart {

	/**
	 * Contains an array of cart items.
	 *
	 * @var int[]
	 */
	public $cart_contents = array();

	/**
	 * Contains an array of removed cart items so we can restore them if needed.
	 *
	 * @var array
	 */
	public $removed_cart_contents = array();

	/**
	 * Contains an array of coupon codes applied to the cart.
	 *
	 * @var array
	 */
	public $applied_coupons = array();

	/**
	 * Total defaults used to reset.
	 *
	 * @var array
	 */
	protected $default_totals = array(
		'subtotal'            => 0,
		'subtotal_tax'        => 0,
		'discount_total'      => 0,
		'discount_tax'        => 0,
		'cart_contents_total' => 0,
		'cart_contents_tax'   => 0,
		'cart_contents_taxes' => array(),
		'fee_total'           => 0,
		'fee_tax'             => 0,
		'fee_taxes'           => array(),
		'total'               => 0,
		'total_tax'           => 0,
	);

	/**
	 * Store calculated totals.
	 *
	 * @var array
	 */
	protected $totals = array();

	/**
	 * Temporary order.
	 *
	 * @var ER_Order
	 */
	protected $order = null;

	/**
	 * Reference to the cart session handling class.
	 *
	 * @var ER_Cart_Session
	 */
	protected $session;

	/**
	 * When cloning, ensure object properties are handled.
	 *
	 * These properties store a reference to the cart, so we use new instead of clone.
	 */
	public function __clone() {
		$this->session = clone $this->session;
	}

	/**
	 * Constructor for the cart class. Loads options and hooks in the init method.
	 */
	public function __construct() {
		$this->session = new ER_Cart_Session( $this );

		// Register hooks for the objects.
		$this->session->init();

		add_action( 'easyreservations_add_to_cart', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'easyreservations_applied_coupon', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'easyreservations_removed_coupon', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'easyreservations_cart_item_removed', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'easyreservations_cart_item_restored', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'easyreservations_check_cart_items', array( $this, 'check_cart_items' ), 1 );
		add_action( 'easyreservations_check_cart_items', array( $this, 'check_cart_coupons' ), 1 );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters.
	|--------------------------------------------------------------------------
	|
	| Methods to retrieve class properties and avoid direct access.
	*/

	/**
	 * Returns the cart merged.
	 *
	 * @return array merged taxes
	 */
	public function get_taxes() {
		return apply_filters( 'easyreservations_cart_get_taxes', er_array_merge_recursive_numeric( $this->get_cart_contents_taxes(), $this->get_fee_taxes() ), $this );
	}

	/**
	 * Gets cart contents.
	 *
	 * @return int|array[]
	 */
	public function get_cart_contents() {
		return apply_filters( 'easyreservations_get_cart_contents', (array) $this->cart_contents );
	}

	/**
	 * Return items removed from the cart.
	 *
	 * @return array
	 */
	public function get_removed_cart_contents() {
		return (array) $this->removed_cart_contents;
	}

	/**
	 * Gets the array of applied coupon codes.
	 *
	 * @return array of applied coupons
	 */
	public function get_applied_coupons() {
		return (array) $this->applied_coupons;
	}

	/**
	 * Get temporary
	 *
	 * @return ER_Order
	 */
	public function get_order() {
		if ( is_null( $this->order ) ) {
			$this->order = $this->create_temp_order();
		}

		return $this->order;
	}

	/**
	 * Return all calculated totals.
	 *
	 * @return array
	 */
	public function get_totals() {
		return empty( $this->totals ) ? $this->default_totals : $this->totals;
	}

	/**
	 * Get a total.
	 *
	 * @param string $key Key of element in $totals array.
	 *
	 * @return mixed
	 */
	protected function get_totals_var( $key ) {
		return isset( $this->totals[ $key ] ) ? $this->totals[ $key ] : $this->default_totals[ $key ];
	}

	/**
	 * Get subtotal.
	 *
	 * @return float
	 */
	public function get_subtotal() {
		return apply_filters( 'easyreservations_cart_' . __FUNCTION__, $this->get_totals_var( 'subtotal' ) );
	}

	/**
	 * Get subtotal.
	 *
	 * @return float
	 */
	public function get_subtotal_tax() {
		return apply_filters( 'easyreservations_cart_' . __FUNCTION__, $this->get_totals_var( 'subtotal_tax' ) );
	}

	/**
	 * Get discount_total.
	 *
	 * @return float
	 */
	public function get_discount_total() {
		return apply_filters( 'easyreservations_cart_' . __FUNCTION__, $this->get_totals_var( 'discount_total' ) );
	}

	/**
	 * Get discount_tax.
	 *
	 * @return float
	 */
	public function get_discount_tax() {
		return apply_filters( 'easyreservations_cart_' . __FUNCTION__, $this->get_totals_var( 'discount_tax' ) );
	}

	/**
	 * Gets cart total. This is the total of items in the cart, but after discounts. Subtotal is before discounts.
	 *
	 * @return float
	 */
	public function get_cart_contents_total() {
		return apply_filters( 'easyreservations_cart_' . __FUNCTION__, $this->get_totals_var( 'cart_contents_total' ) );
	}

	/**
	 * Gets cart tax amount.
	 *
	 * @return float
	 */
	public function get_cart_contents_tax() {
		return apply_filters( 'easyreservations_cart_' . __FUNCTION__, $this->get_totals_var( 'cart_contents_tax' ) );
	}

	/**
	 * Gets cart total after calculation.
	 *
	 * @param string $context If the context is view, the value will be formatted for display. This keeps it compatible with pre-3.2 versions.
	 *
	 * @return float
	 */
	public function get_total( $context = 'view' ) {
		$total = apply_filters( 'easyreservations_cart_' . __FUNCTION__, $this->get_totals_var( 'total' ) );

		return 'view' === $context ? apply_filters( 'easyreservations_cart_total', er_price( $total, true ) ) : $total;
	}

	/**
	 * Get total tax amount.
	 *
	 * @return float
	 */
	public function get_total_tax() {
		return apply_filters( 'easyreservations_cart_' . __FUNCTION__, $this->get_totals_var( 'total_tax' ) );
	}

	/**
	 * Get total fee amount.
	 *
	 * @return float
	 */
	public function get_fee_total() {
		return apply_filters( 'easyreservations_cart_' . __FUNCTION__, $this->get_totals_var( 'fee_total' ) );
	}

	/**
	 * Get total fee tax amount.
	 *
	 * @return float
	 */
	public function get_fee_tax() {
		return apply_filters( 'easyreservations_cart_' . __FUNCTION__, $this->get_totals_var( 'fee_tax' ) );
	}

	/**
	 * Get taxes.
	 *
	 */
	public function get_cart_contents_taxes() {
		return apply_filters( 'easyreservations_cart_' . __FUNCTION__, $this->get_totals_var( 'cart_contents_taxes' ) );
	}

	/**
	 * Get taxes.
	 *
	 */
	public function get_fee_taxes() {
		return apply_filters( 'easyreservations_cart_' . __FUNCTION__, $this->get_totals_var( 'fee_taxes' ) );
	}

	/**
	 * Get a tax amount.
	 *
	 * @param string $tax_rate_id ID of the tax rate to get taxes for.
	 *
	 * @return float amount
	 */
	public function get_tax_amount( $tax_rate_id ) {
		$taxes = er_array_merge_recursive_numeric( $this->get_cart_contents_taxes(), $this->get_fee_taxes() );

		return isset( $taxes[ $tax_rate_id ] ) ? $taxes[ $tax_rate_id ] : 0;
	}

	/**
	 * Return whether or not the cart is displaying prices including tax, rather than excluding tax.
	 *
	 * @return bool
	 */
	public function display_prices_including_tax() {
		return apply_filters( 'easyreservations_cart_' . __FUNCTION__, 'incl' === $this->get_tax_price_display_mode() );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters.
	|--------------------------------------------------------------------------
	|
	| Methods to set class properties and avoid direct access.
	*/

	/**
	 * Sets the contents of the cart.
	 *
	 * @param array $value Cart array.
	 */
	public function set_cart_contents( $cart_content ) {
		$this->cart_contents = array();
		foreach ( $cart_content as $key => $value ) {
			$this->cart_contents[ $key ] = $value;
		}
	}

	/**
	 * Set items removed from the cart.
	 *
	 * @param array $value Item array.
	 */
	public function set_removed_cart_contents( $value = array() ) {
		$this->removed_cart_contents = (array) $value;
	}

	/**
	 * Sets the array of applied coupon codes.
	 *
	 * @param array $value List of applied coupon codes.
	 */
	public function set_applied_coupons( $value = array() ) {
		$this->applied_coupons = (array) $value;
	}

	/**
	 * Set all calculated totals.
	 *
	 * @param array $value Value to set.
	 */
	public function set_totals( $value = array() ) {
		$this->totals = wp_parse_args( $value, $this->default_totals );
	}

	/**
	 * Set subtotal.
	 *
	 * @param string $value Value to set.
	 */
	public function set_subtotal( $value ) {
		$this->totals['subtotal'] = er_format_decimal( $value, er_get_price_decimals() );
	}

	/**
	 * Set subtotal.
	 *
	 * @param string $value Value to set.
	 */
	public function set_subtotal_tax( $value ) {
		$this->totals['subtotal_tax'] = $value;
	}

	/**
	 * Set discount_total.
	 *
	 * @param string $value Value to set.
	 */
	public function set_discount_total( $value ) {
		$this->totals['discount_total'] = $value;
	}

	/**
	 * Set discount_tax.
	 *
	 * @param string $value Value to set.
	 */
	public function set_discount_tax( $value ) {
		$this->totals['discount_tax'] = $value;
	}

	/**
	 * Set cart_contents_total.
	 *
	 * @param string $value Value to set.
	 */
	public function set_cart_contents_total( $value ) {
		$this->totals['cart_contents_total'] = er_format_decimal( $value, er_get_price_decimals() );
	}

	/**
	 * Set cart tax amount.
	 *
	 * @param string $value Value to set.
	 */
	public function set_cart_contents_tax( $value ) {
		$this->totals['cart_contents_tax'] = $value;
	}

	/**
	 * Set cart total.
	 *
	 * @param string $value Value to set.
	 */
	public function set_total( $value ) {
		$this->totals['total'] = er_format_decimal( $value, er_get_price_decimals() );
	}

	/**
	 * Set total tax amount.
	 *
	 * @param string $value Value to set.
	 */
	public function set_total_tax( $value ) {
		// We round here because this is a total entry, as opposed to line items in other setters.
		$this->totals['total_tax'] = er_round_tax_total( $value );
	}

	/**
	 * Set fee amount.
	 *
	 * @param string $value Value to set.
	 */
	public function set_fee_total( $value ) {
		$this->totals['fee_total'] = er_format_decimal( $value, er_get_price_decimals() );
	}

	/**
	 * Set fee tax.
	 *
	 * @param string $value Value to set.
	 */
	public function set_fee_tax( $value ) {
		$this->totals['fee_tax'] = $value;
	}

	/**
	 * Set taxes.
	 *
	 * @param array $value Tax values.
	 */
	public function set_cart_contents_taxes( $value ) {
		$this->totals['cart_contents_taxes'] = (array) $value;
	}

	/**
	 * Set taxes.
	 *
	 * @param array $value Tax values.
	 */
	public function set_fee_taxes( $value ) {
		$this->totals['fee_taxes'] = (array) $value;
	}

	/*
	|--------------------------------------------------------------------------
	| Helper methods.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns the contents of the cart in an array.
	 *
	 * @return ER_Reservation[]|ER_Custom[]
	 */
	public function get_cart() {
		if ( ! did_action( 'wp_loaded' ) ) {
			_doing_it_wrong( __FUNCTION__, 'Get cart should not be called before the wp_loaded action.', '6.0' );
		}
		if ( ! did_action( 'easyreservations_load_cart_from_session' ) ) {
			$this->session->get_cart_from_session();
		}

		return $this->get_cart_contents();
	}

	/**
	 * Returns the reservations ids of the cart in an array.
	 *
	 * @return int[]
	 */
	public function get_reservations() {
		$reservations = array();

		foreach ( $this->get_cart() as $key => $item ) {
			if ( is_integer( $item ) ) {
				$reservations[] = $item;
			}
		}

		return $reservations;
	}

	/**
	 * Returns the custom fields of the cart in an array.
	 *
	 * @return ER_Custom[]
	 */
	public function get_customs() {
		$customs = array();

		foreach ( $this->get_cart() as $key => $item ) {
			if ( ! is_integer( $item ) ) {
				$customs[ $key ] = $item;
			}
		}

		return $customs;
	}

	/**
	 * Returns a specific item in the cart.
	 *
	 * @param string $item_key Cart item key.
	 *
	 * @return int|ER_Custom
	 */
	public function get_cart_item( $item_key ) {
		return isset( $this->cart_contents[ $item_key ] ) ? $this->cart_contents[ $item_key ] : false;
	}

	/**
	 * Checks if the cart is empty.
	 *
	 * @return bool
	 */
	public function is_empty() {
		return 0 === count( $this->get_cart() );
	}

	/**
	 * Empties the cart and optionally the persistent cart too.
	 *
	 * @param bool $clear_persistent_cart Should the persistant cart be cleared too. Defaults to true.
	 */
	public function empty_cart( $clear_persistent_cart = true ) {

		do_action( 'easyreservations_before_cart_emptied', $clear_persistent_cart );

		$this->order                 = null;
		$this->cart_contents         = array();
		$this->removed_cart_contents = array();
		$this->applied_coupons       = array();

		if ( $clear_persistent_cart ) {
			$this->session->persistent_cart_destroy();
		}

		do_action( 'easyreservations_cart_emptied', $clear_persistent_cart );
	}

	/**
	 * Get number of items in the cart.
	 *
	 * @return int
	 */
	public function count() {
		return apply_filters( 'easyreservations_cart_contents_count', count( $this->get_cart() ) );
	}

	/**
	 * Check all cart items for errors.
	 */
	public function check_cart_items() {
		$return = true;
		$result = $this->check_cart_item_validity();

		if ( is_wp_error( $result ) ) {
			er_add_notice( $result->get_error_message(), 'error' );
			$return = false;
		}

		$result = $this->check_cart_item_availability();

		if ( is_wp_error( $result ) ) {
			er_add_notice( $result->get_error_message(), 'error' );
			$return = false;
		}

		return $return;
	}

	/**
	 * Looks through cart items and checks the posts are not trashed or deleted.
	 *
	 * @return bool|WP_Error
	 */
	public function check_cart_item_validity() {
		$return = true;

		foreach ( $this->get_reservations() as $reservation_id ) {
			$reservation = ER()->reservation_manager()->get( absint( $reservation_id ) );

			if ( ! $reservation ) {
				$return = new WP_Error( 'invalid', __( 'A reservation which is no longer existing was removed from your cart.', 'easyReservations' ) );
			} elseif ( ! $reservation->get_resource() || 'trash' === $reservation->get_resource()->get_status() ) {
				$return = new WP_Error( 'invalid', __( 'A reservation which is no longer available was removed from your cart.', 'easyReservations' ) );
			}
		}

		return true;
	}

	/**
	 * Looks through the cart to check each item is in stock. If not, add an error.
	 *
	 * @return bool|WP_Error
	 */
	public function check_cart_item_availability() {
		$errors          = new WP_Error();
		$reservation_ids = $this->get_reservations();

		foreach ( $reservation_ids as $reservation_id ) {
			$reservation = ER()->reservation_manager()->get( absint( $reservation_id ) );

			if ( $reservation ) {
				$reservation->validate( $errors, $reservation_ids, true );
			}
		}

		return is_wp_error( $errors ) && $errors->has_errors() ? $errors : true;
	}

	/**
	 * Check cart coupons for errors.
	 */
	public function check_cart_coupons() {
		foreach ( $this->get_applied_coupons() as $code ) {
			$coupon = new ER_Coupon( $code );

			if ( ! $coupon ) {
				$coupon->add_coupon_message( ER_Coupon::E_ER_COUPON_INVALID_REMOVED );
				$this->remove_coupon( $code );
			}
		}
	}

	/**
	 * Determines the value that the customer spent and the subtotal
	 * displayed, used for things like coupon validation.
	 *
	 * Since the coupon lines are displayed based on the TAX DISPLAY value
	 * of cart, this is used to determine the spend.
	 *
	 * If cart totals are shown including tax, use the subtotal.
	 * If cart totals are shown excluding tax, use the subtotal ex tax
	 * (tax is shown after coupons).
	 *
	 * @return string
	 */
	public function get_displayed_subtotal() {
		return $this->display_prices_including_tax() ? $this->get_subtotal() + $this->get_subtotal_tax() : $this->get_subtotal();
	}

	/**
	 * Add a reservation to the cart.
	 *
	 * @param ER_Reservation $reservation
	 *
	 * @return string|bool
	 */
	public function add_reservation_to_cart( $reservation ) {
		if ( array_search( $reservation->get_id(), $this->cart_contents ) ) {
			return false;
		}

		$cart_item_key = md5( $reservation->get_id() . time() );

		$this->cart_contents[ $cart_item_key ] = apply_filters(
			'easyreservations_add_reservation_cart_item',
			$reservation->get_id(),
			$reservation,
			$cart_item_key
		);

		$this->cart_contents = apply_filters( 'easyreservations_cart_contents_changed', $this->cart_contents );
		do_action( 'easyreservations_add_to_cart', $reservation );

		return $cart_item_key;
	}

	/**
	 * Add custom data to the cart.
	 *
	 * @param array $custom
	 *
	 * @return string|bool
	 */
	public function add_custom_to_cart( $custom ) {
		foreach ( $this->cart_contents as $content ) {
			if ( is_array( $content ) && isset( $content['id'] ) && $content['id'] === $custom['id'] ) {
				return false;
			}
		}

		$key           = sanitize_key( $custom['custom_id'] );
		$cart_item_key = md5( $key . time() );

		$this->cart_contents[ $cart_item_key ] = apply_filters(
			'easyreservations_add_custom_cart_item',
			$custom,
			$key,
			$cart_item_key
		);

		$this->cart_contents = apply_filters( 'easyreservations_cart_contents_changed', $this->cart_contents );
		do_action( 'easyreservations_add_to_cart', $custom );

		return $cart_item_key;
	}

	/**
	 * Remove a cart item.
	 *
	 * @param string $cart_item_key Cart item key to remove from the cart.
	 *
	 * @return bool
	 */
	public function remove_cart_item( $cart_item_key ) {
		if ( isset( $this->cart_contents[ $cart_item_key ] ) ) {
			$this->removed_cart_contents[ $cart_item_key ] = $this->cart_contents[ $cart_item_key ];

			do_action( 'easyreservations_remove_cart_item', $cart_item_key, $this );

			unset( $this->cart_contents[ $cart_item_key ] );

			do_action( 'easyreservations_cart_item_removed', $cart_item_key, $this );

			return true;
		}

		return false;
	}

	/**
	 * Remove multiple cart items
	 *
	 * @param array $cart_item_keys
	 */
	public function remove_cart_items( $cart_item_keys ){
		foreach($cart_item_keys as $cart_item_key){
			$this->remove_cart_item( $cart_item_key );
		}
	}

	/**
	 * Restore a cart item.
	 *
	 * @param string $cart_item_key Cart item key to restore to the cart.
	 *
	 * @return bool
	 */
	public function restore_cart_item( $cart_item_key ) {
		if ( isset( $this->removed_cart_contents[ $cart_item_key ] ) ) {
			$restore_item                          = $this->removed_cart_contents[ $cart_item_key ];
			$this->cart_contents[ $cart_item_key ] = $restore_item;

			do_action( 'easyreservations_restore_cart_item', $cart_item_key, $this );

			unset( $this->removed_cart_contents[ $cart_item_key ] );

			do_action( 'easyreservations_cart_item_restored', $cart_item_key, $this );

			return true;
		}

		return false;
	}

	/**
	 * Create temp order for calculations
	 *
	 * @return ER_Order
	 */
	private function create_temp_order() {
		$temp_order = new ER_Order( '' );
		$temp_order->set_customer_id( get_current_user_id() );
		$temp_order->set_prices_include_tax( er_prices_include_tax() );

		foreach ( $this->get_cart_contents() as $cart_key => $item ) {
			if ( is_numeric( $item ) ) {
				try {
					$temp_order->add_reservation( absint( $item ), false );
				} catch ( Exception $e ) {
					$this->remove_cart_item( $cart_key );
					er_add_notice( $e->getMessage(), 'error' );
				}
			} else {
				$temp_order->add_custom( $item );
			}
		}

		if ( ! empty( $this->get_applied_coupons() ) ) {
			foreach ( $this->get_applied_coupons() as $coupon_code ) {
				$errors = apply_filters( 'easyreservations_add_order_coupon', $coupon_code, $temp_order, true );
			}
		}

		$temp_order->calculate_taxes( false );
		$temp_order->calculate_totals( false );

		return $temp_order;
	}

	/**
	 * Calculate totals for the items in the cart.
	 *
	 * @param ER_Reservation $temporary_reservation
	 */
	public function calculate_totals( $temporary_reservation = false ) {
		$this->reset_totals();

		if ( ! $temporary_reservation && $this->is_empty() ) {
			$this->session->set_session();

			return;
		}

		do_action( 'easyreservations_cart_before_calculate_totals', $this );

		$order = $this->get_order();

		if ( $temporary_reservation ) {
			$order->add_reservation( $temporary_reservation, false );

			$errors  = new WP_Error();
			$customs = ER()->checkout()->get_form_data_custom( $errors, $order, 'checkout' );

			if ( $customs && ! $errors->has_errors() ) {
				foreach ( $customs as $custom ) {
					$order->add_custom( $custom );
				}
			}
		}

		$order->calculate_taxes( false );
		$order->calculate_totals( false );

		$this->set_total( $order->get_total() );
		$this->set_total_tax( $order->get_total_tax() );

		$this->set_subtotal_tax( $order->get_total_tax() - $order->get_discount_tax() );
		$this->set_subtotal( $order->get_subtotal() );

		$this->set_discount_total( $order->get_discount_total() );
		$this->set_discount_tax( $order->get_discount_tax() );

		$cart_content_taxes = array();
		$cart_content_total = 0;

		foreach ( $order->get_items() as $item ) {
			$taxes              = $item->get_taxes();
			$cart_content_taxes = er_array_merge_recursive_numeric( $taxes['total'], $cart_content_taxes );
			$cart_content_total = $this->round_item_subtotal( $item->get_total() + $cart_content_total );
		}

		$cart_content_taxes = array_map( array( $this, 'round_line_tax' ), $cart_content_taxes );

		$this->set_cart_contents_total( ER_Number_Util::round( $cart_content_total ) );
		$this->set_cart_contents_tax( array_sum( $cart_content_taxes ) );
		$this->set_cart_contents_taxes( $cart_content_taxes );

		$fees_taxes = array();
		$fees_total = 0;

		foreach ( $order->get_fees() as $item ) {
			$taxes      = $item->get_taxes();
			$fees_taxes = er_array_merge_recursive_numeric( $taxes['total'], $fees_taxes );
			$fees_total = $this->round_item_subtotal( $item->get_total() + $fees_total );
		}

		$fees_taxes = array_map( array( $this, 'round_line_tax' ), $fees_taxes );

		$this->set_fee_total( ER_Number_Util::round( $fees_total ) );
		$this->set_fee_tax( array_sum( $fees_taxes ) );
		$this->set_fee_taxes( $fees_taxes );

		if ( !$temporary_reservation ) {
			do_action( 'easyreservations_cart_after_calculate_totals', $this );
		}
	}

	protected function round_at_subtotal() {
		return 'yes' === get_option( 'reservations_tax_round_at_subtotal' );
	}

	/**
	 * Apply rounding to an array of taxes before summing. Rounds to store DP setting, ignoring precision.
	 *
	 * @param float $value Tax value.
	 *
	 * @return float
	 */
	protected function round_line_tax( $value ) {
		if ( ! $this->round_at_subtotal() ) {
			$value = er_round_tax_total( $value, 0 );
		}

		return $value;
	}

	/**
	 * Apply rounding to item subtotal before summing.
	 *
	 * @param float $value Item subtotal value.
	 *
	 * @return float
	 */
	protected function round_item_subtotal( $value ) {
		if ( ! $this->round_at_subtotal() ) {
			$value = ER_Number_Util::round( $value );
		}

		return $value;
	}

	/**
	 * Looks at the totals to see if payment is actually required.
	 *
	 * @return bool
	 */
	public function needs_payment() {
		return apply_filters( 'easyreservations_cart_needs_payment', false, $this );
	}

	/**
	 * Returns whether or not a discount has been applied.
	 *
	 * @param string $coupon_code Coupon code to check.
	 *
	 * @return bool
	 */
	public function has_discount( $coupon_code = '' ) {
		return $coupon_code ? in_array( er_coupon_format_code( $coupon_code ), $this->applied_coupons, true ) : count( $this->applied_coupons ) > 0;
	}

	/**
	 * Applies a coupon code passed to the method.
	 *
	 * @param string $coupon_code - The code to apply.
	 *
	 * @return bool True if the coupon is applied, false if it does not exist or cannot be applied.
	 */
	public function apply_coupon( $coupon_code ) {
		// Coupons are globally disabled.
		if ( ! function_exists( 'er_coupons_enabled' ) || ! er_coupons_enabled() ) {
			er_add_notice( __( 'Coupons not enabled.', 'easyReservations' ), 'error' );

			return false;
		}

		// Sanitize coupon code.
		$coupon_code = er_coupon_format_code( $coupon_code );

		// Get the coupon.
		$the_coupon = apply_filters( 'easyreservations_apply_cart_coupon', $coupon_code, $this->get_order() );

		if ( $the_coupon ) {
			if ( ! is_wp_error( $the_coupon ) || ! $the_coupon->has_errors() ) {
				$this->applied_coupons[] = $coupon_code;

				er_add_notice( __( 'Coupon code applied successfully.', 'easyReservations' ) );

				do_action( 'easyreservations_applied_coupon', $coupon_code );

				return true;
			} else {
				foreach ( $the_coupon->get_error_messages() as $message ) {
					er_add_notice( $message, 'error' );
				}
			}
		} else {
			er_add_notice( __( 'Coupons not enabled.', 'easyReservations' ), 'error' );
		}

		return false;
	}

	/**
	 * Remove coupons from the cart of a defined type. Type 1 is before tax, type 2 is after tax.
	 */
	public function remove_coupons() {
		$this->set_applied_coupons( array() );
		$this->calculate_totals();
		$this->session->set_session();
	}

	/**
	 * Remove a single coupon by code.
	 *
	 * @param string $coupon_code Code of the coupon to remove.
	 *
	 * @return bool
	 */
	public function remove_coupon( $coupon_code ) {
		$coupon_code = er_coupon_format_code( $coupon_code );
		$position    = array_search( $coupon_code, $this->get_applied_coupons(), true );

		if ( false !== $position ) {
			unset( $this->applied_coupons[ $position ] );

			er_add_notice( __( 'Coupon removed.', 'easyReservations' ) );
		}

		ER()->session->set( 'refresh_totals', true );

		do_action( 'easyreservations_removed_coupon', $coupon_code );

		return true;
	}

	/**
	 * Gets the cart tax (after calculation).
	 *
	 * @return string formatted price
	 */
	public function get_cart_tax() {
		$cart_total_tax = er_round_tax_total( $this->get_total_tax() );

		return apply_filters( 'easyreservations_get_cart_tax', $cart_total_tax ? er_price( $cart_total_tax, true ) : '' );
	}

	/**
	 * Get taxes, merged by code, formatted ready for output.
	 *
	 * @return array
	 */
	public function get_tax_totals() {
		$taxes      = $this->get_taxes();
		$tax_totals = array();

		foreach ( $taxes as $key => $tax ) {
			$rate = ER_Tax::get_tax_rate( $key );

			if ( $rate || apply_filters( 'easyreservations_cart_remove_taxes_zero_rate_id', 'zero-rated' ) === $key ) {
				if ( ! isset( $tax_totals[ $key ] ) ) {
					$tax_totals[ $key ]         = new stdClass();
					$tax_totals[ $key ]->amount = 0;
				}

				$tax_totals[ $key ]->rate_id          = $key;
				$tax_totals[ $key ]->is_compound      = ER_Tax::is_compound( $rate );
				$tax_totals[ $key ]->is_flat          = ER_Tax::is_flat( $rate );
				$tax_totals[ $key ]->label            = ER_Tax::get_rate_label( $rate );
				$tax_totals[ $key ]->amount           += (float) $tax;
				$tax_totals[ $key ]->formatted_amount = er_price( $tax_totals[ $key ]->amount, true );
			}
		}

		if ( apply_filters( 'easyreservations_cart_hide_zero_taxes', true ) ) {
			$amounts    = array_filter( wp_list_pluck( $tax_totals, 'amount' ) );
			$tax_totals = array_intersect_key( $tax_totals, $amounts );
		}

		return apply_filters( 'easyreservations_cart_tax_totals', $tax_totals, $this );
	}

	/**
	 * Get tax row amounts with or without compound taxes includes.
	 *
	 * @param bool $compound True if getting compound taxes.
	 * @param bool $display True if getting total to display.
	 *
	 * @return float price
	 */
	public function get_taxes_total( $compound = true, $display = true ) {
		$total = 0;
		$taxes = $this->get_taxes();

		foreach ( $taxes as $key => $tax ) {
			if ( ! $compound && ER_Tax::is_compound( $key ) ) {
				continue;
			}
			$total += $tax;
		}

		if ( $display ) {
			$total = er_format_decimal( $total, er_get_price_decimals() );
		}

		return apply_filters( 'easyreservations_cart_taxes_total', $total, $compound, $display, $this );
	}

	/**
	 * Return all added fees
	 *
	 * @return ER_Receipt_Item_Fee[]
	 */
	public function get_fees() {
		return $this->get_order()->get_fees();
	}

	/**
	 * Gets the cart contents total (after calculation).
	 *
	 * @return string formatted price
	 */
	public function get_cart_total() {
		return apply_filters( 'easyreservations_cart_contents_total', er_price( er_prices_include_tax() ? $this->get_total() + $this->get_total_tax() : $this->get_total() ), true );
	}

	/**
	 * Gets the sub total (after calculation).
	 *
	 * @return string formatted price
	 */
	public function get_cart_subtotal() {
		if ( $this->display_prices_including_tax() ) {
			$cart_subtotal = er_price( $this->get_subtotal() + $this->get_subtotal_tax(), true );

			if ( $this->get_subtotal_tax() > 0 && ! er_prices_include_tax() ) {
				$cart_subtotal .= ' <small class="tax_label">' . ER()->countries->inc_tax_or_vat() . '</small>';
			}
		} else {
			$cart_subtotal = er_price( $this->get_subtotal(), true );

			if ( $this->get_subtotal_tax() > 0 && er_prices_include_tax() ) {
				$cart_subtotal .= ' <small class="tax_label">' . ER()->countries->ex_tax_or_vat() . '</small>';
			}
		}

		return apply_filters( 'easyreservations_cart_subtotal', $cart_subtotal, $this );
	}

	/**
	 * Reset cart totals to the defaults. Useful before running calculations.
	 */
	public function reset_totals() {
		$this->totals = $this->default_totals;
		do_action( 'easyreservations_cart_reset', $this, false );
	}

	/**
	 * Returns 'incl' if tax should be included in cart, otherwise returns 'excl'.
	 *
	 * @return string
	 */
	public function get_tax_price_display_mode() {
		if ( ER()->customer && ER()->customer->get_is_vat_exempt() ) {
			return 'excl';
		}

		return get_option( 'reservations_tax_display_cart' );
	}

	/**
	 * Returns the hash based on cart contents.
	 *
	 * @return string hash for cart content
	 */
	public function get_cart_hash() {
		$cart_session = $this->session->get_cart_for_session();
		$hash         = $cart_session ? md5( wp_json_encode( $cart_session ) . $this->get_total() ) : '';

		return apply_filters( 'easyreservations_cart_hash', $hash, $cart_session );
	}
}
