<?php
/**
 * Cart session handling class.
 *
 * @package easyReservations/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER_Cart_Session class.
 */
final class ER_Cart_Session {

	/**
	 * Reference to cart object.
	 *
	 * @var ER_Cart
	 */
	protected $cart;

	/**
	 * Sets up the items provided, and calculate totals.
	 *
	 * @param ER_Cart $cart Cart object to calculate totals for.
	 *
	 * @throws Exception If missing ER_Cart object.
	 */
	public function __construct( &$cart ) {
		if ( ! is_a( $cart, 'ER_Cart' ) ) {
			throw new Exception( 'A valid ER_Cart object is required' );
		}

		$this->cart = $cart;
	}

	/**
	 * Register methods for this object on the appropriate WordPress hooks.
	 */
	public function init() {
		add_action( 'wp_loaded', array( $this, 'get_cart_from_session' ) );
		add_action( 'easyreservations_cart_emptied', array( $this, 'destroy_cart_session' ) );
		add_action( 'easyreservations_cart_after_calculate_totals', array( $this, 'set_session' ) );
		add_action( 'easyreservations_cart_loaded_from_session', array( $this, 'set_session' ) );
		add_action( 'easyreservations_removed_coupon', array( $this, 'set_session' ) );

		add_action( 'easyreservations_cart_updated', array( $this, 'persistent_cart_update' ) );
		add_action( 'easyreservations_cart_item_removed', array( $this, 'persistent_cart_update' ) );
		add_action( 'easyreservations_cart_item_restored', array( $this, 'persistent_cart_update' ) );

		// Cookie events - cart cookies need to be set before headers are sent.
		add_action( 'easyreservations_add_to_cart', array( $this, 'maybe_set_cart_cookies' ) );
		add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 );
		add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 );
	}

	/**
	 * Get the cart data from the PHP session and store it in class variables.
	 */
	public function get_cart_from_session() {
		do_action( 'easyreservations_load_cart_from_session' );

		$this->cart->set_totals( ER()->session->get( 'cart_totals', null ) );
		$this->cart->set_applied_coupons( ER()->session->get( 'applied_coupons', array() ) );
		$this->cart->set_removed_cart_contents( ER()->session->get( 'removed_cart_contents', array() ) );

		$update_cart_session = false;
		$cart                = ER()->session->get( 'cart', null );

		$merge_saved_cart = (bool) get_user_meta( get_current_user_id(), '_easyreservations_load_saved_cart_after_login', true );
		if ( is_null( $cart ) || $merge_saved_cart ) {
			$saved_cart          = $this->get_saved_cart();
			$cart                = is_null( $cart ) ? array() : $cart;
			$cart                = array_merge( $saved_cart, $cart );
			$update_cart_session = true;

			delete_user_meta( get_current_user_id(), '_easyreservations_load_saved_cart_after_login' );
		}

		$cart_contents       = array();
		$reservation_manager = ER()->reservation_manager();

		foreach ( $cart as $key => $values ) {
			if ( ! is_customize_preview() && 'customize-preview' === $key ) {
				continue;
			}

			//Custom fields are not integer and do not get checked at this point
			if ( ! is_integer( $values ) ) {
				$cart_contents[ $key ] = apply_filters( 'easyreservations_get_cart_item_custom_from_session', $values, $key );
			} else {
				$reservation = $reservation_manager->get( absint( $values ) );

				if ( apply_filters( 'easyreservations_pre_remove_cart_item_from_session', false, $key, $values ) ) {
					$update_cart_session = true;
					do_action( 'easyreservations_remove_cart_item_from_session', $key, $values );
				} elseif ( ! $reservation || empty( $reservation ) ) {
					$update_cart_session = true;
					er_add_notice( __( 'A reservation has been removed from your cart due to inactivity. Please reserve again.', 'easyReservations' ), 'notice' );
					do_action( 'easyreservations_remove_cart_item_from_session', $key, $values );
				} elseif ( ! $reservation->get_resource() || empty( $reservation->get_resource() ) ) {
					$update_cart_session = true;
					er_add_notice( __( 'A reservation has been removed from your cart because the resource has since been deleted. Please reserve again.', 'easyReservations' ), 'notice' );
					do_action( 'easyreservations_remove_cart_item_from_session', $key, $values );
				} else {
					$cart_contents[ $key ] = apply_filters( 'easyreservations_get_cart_item_custom_from_session', $values, $key );
				}
			}
		}

		$this->cart->set_cart_contents( $cart_contents );

		do_action( 'easyreservations_cart_loaded_from_session', $this->cart );

		if ( $update_cart_session || is_null( ER()->session->get( 'cart', null ) ) ) {
			ER()->session->set( 'cart', $this->get_cart_for_session() );
			$this->cart->calculate_totals();
		}
	}

	/**
	 * Destroy cart session data.
	 */
	public function destroy_cart_session() {
		ER()->session->set( 'cart', null );
		ER()->session->set( 'cart_totals', null );
		ER()->session->set( 'applied_coupons', null );
		ER()->session->set( 'removed_cart_contents', null );
		ER()->session->set( 'order_awaiting_payment', null );
	}

	/**
	 * Will set cart cookies if needed and when possible.
	 */
	public function maybe_set_cart_cookies() {
		if ( ! headers_sent() && did_action( 'wp_loaded' ) ) {
			if ( ! $this->cart->is_empty() ) {
				$this->set_cart_cookies( true );
			} elseif ( isset( $_COOKIE['easyreservations_items_in_cart'] ) ) { // WPCS: input var ok.
				$this->set_cart_cookies( false );
			}
		}
	}

	/**
	 * Sets the php session data for the cart and coupons.
	 */
	public function set_session() {
		ER()->session->set( 'cart', $this->get_cart_for_session() );
		ER()->session->set( 'cart_totals', $this->cart->get_totals() );
		ER()->session->set( 'applied_coupons', $this->cart->get_applied_coupons() );
		ER()->session->set( 'removed_cart_contents', $this->cart->get_removed_cart_contents() );

		do_action( 'easyreservations_cart_updated' );
	}

	/**
	 * Returns the contents of the cart in an array without the 'data' element.
	 *
	 * @return array contents of the cart
	 */
	public function get_cart_for_session() {
		$array = array();
		foreach ( $this->cart->get_cart() as $key => $cart_content ) {
			$array[ $key ] = $cart_content;
		}

		return $array;
	}

	/**
	 * Save the persistent cart when the cart is updated.
	 */
	public function persistent_cart_update() {
		if ( get_current_user_id() && apply_filters( 'easyreservations_persistent_cart_enabled', true ) ) {
			update_user_meta(
				get_current_user_id(),
				'_easyreservations_persistent_cart_' . get_current_blog_id(),
				array(
					'cart' => $this->get_cart_for_session(),
				)
			);
		}
	}

	/**
	 * Delete the persistent cart permanently.
	 */
	public function persistent_cart_destroy() {
		if ( get_current_user_id() ) {
			delete_user_meta( get_current_user_id(), '_easyreservations_persistent_cart_' . get_current_blog_id() );
		}
	}

	/**
	 * Set cart hash cookie and items in cart if not already set.
	 *
	 * @param bool $set Should cookies be set (true) or unset.
	 */
	private function set_cart_cookies( $set = true ) {
		if ( $set ) {
			$setcookies = array(
				'easyreservations_items_in_cart' => '1',
				'easyreservations_cart_hash'     => ER()->cart->get_cart_hash(),
			);

			foreach ( $setcookies as $name => $value ) {
				if ( ! isset( $_COOKIE[ $name ] ) || $_COOKIE[ $name ] !== $value ) {
					er_set_cookie( $name, $value );
				}
			}
		} else {
			$unsetcookies = array(
				'easyreservations_items_in_cart',
				'easyreservations_cart_hash',
			);

			foreach ( $unsetcookies as $name ) {
				if ( isset( $_COOKIE[ $name ] ) ) {
					er_set_cookie( $name, 0, time() - HOUR_IN_SECONDS );
					unset( $_COOKIE[ $name ] );
				}
			}
		}

		do_action( 'easyreservations_set_cart_cookies', $set );
	}

	/**
	 * Get the persistent cart from the database.
	 *
	 * @return array
	 */
	private function get_saved_cart() {
		$saved_cart = array();

		if ( apply_filters( 'easyreservations_persistent_cart_enabled', true ) ) {
			$saved_cart_meta = get_user_meta( get_current_user_id(), '_easyreservations_persistent_cart_' . get_current_blog_id(), true );

			if ( isset( $saved_cart_meta['cart'] ) ) {
				$saved_cart = array_filter( (array) $saved_cart_meta['cart'] );
			}
		}

		return $saved_cart;
	}
}
