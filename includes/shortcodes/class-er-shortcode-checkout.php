<?php
defined( 'ABSPATH' ) || exit;

class ER_Shortcode_Checkout {

	/**
	 * Get the shortcode content.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function get( $atts ) {
		return ER_Shortcodes::shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function output( $atts ) {
		global $wp;

		// Check cart class is loaded or abort.
		if ( is_null( ER()->cart ) ) {
			return;
		}

		wp_enqueue_script( 'er-checkout' );

		// Handle checkout actions.
		if ( ! empty( $wp->query_vars['order-payment'] ) ) {

			self::order_pay( $wp->query_vars['order-payment'] );
		} elseif ( isset( $wp->query_vars['order-received'] ) ) {

			self::order_received( $wp->query_vars['order-received'] );
		} else {

			self::checkout();
		}
	}

	/**
	 * Show the pay page.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @throws Exception When validate fails.
	 */
	private static function order_pay( $order_id ) {

		do_action( 'before_easyreservations_pay' );

		$order_id = absint( $order_id );

		// Pay for existing order.
		if ( isset( $_GET['pay_for_order'], $_GET['key'] ) && $order_id ) { // WPCS: input var ok, CSRF ok.
			try {
				$order_key = isset( $_GET['key'] ) ? er_clean( wp_unslash( $_GET['key'] ) ) : ''; // WPCS: input var ok, CSRF ok.
				$order     = er_get_order( $order_id );

				// Order or payment link is invalid.
				if ( ! $order || $order->get_id() !== $order_id || ! hash_equals( $order->get_order_key(), $order_key ) ) {
					throw new Exception( __( 'Sorry, this order is invalid and cannot be paid for.', 'easyReservations' ) );
				}

				// Logged out customer does not have permission to pay for this order.
				if ( ! current_user_can( 'easy_pay_for_order', $order_id ) && ! is_user_logged_in() ) {
					echo '<div class="easyreservations-info">' . esc_html__( 'Please log in to your account below to continue to the payment form.', 'easyReservations' ) . '</div>';
					easyreservations_login_form(
						array(
							'redirect' => $order->get_checkout_payment_url(),
						)
					);

					return;
				}

				// Add notice if logged in customer is trying to pay for guest order.
				if ( ! $order->get_user_id() && is_user_logged_in() ) {
					// If order has does not have same billing email then current logged in user then show warning.
					if ( $order->get_email() !== wp_get_current_user()->user_email ) {
						er_print_notice( __( 'You are paying for a guest order. Please continue with payment only if you recognize this order.', 'easyReservations' ), 'error' );
					}
				}

				// Logged in customer trying to pay for someone else's order.
				if ( ! current_user_can( 'easy_pay_for_order', $order_id ) ) {
					throw new Exception( __( 'This order cannot be paid for. Please contact us if you need assistance.', 'easyReservations' ) );
				}

				// Does not need payment.
				if ( ! $order->needs_payment() ) {
					/* translators: %s: order status */
					throw new Exception( sprintf( __( 'This order&rsquo;s status is &ldquo;%s&rdquo;&mdash;it cannot be paid for. Please contact us if you need assistance.', 'easyReservations' ), ER_Order_Status::get_title( $order->get_status() ) ) );
				}

				$reservations    = $order->get_reservations();
				$reservation_ids = er_list_pluck( $reservations, 'get_reservation_id' );

				foreach ( $reservations as $reservation_item ) {
					$reservation = $reservation_item->get_reservation();

					if ( ! $reservation || ! $reservation->get_resource() ) {
						throw new Exception( __( 'Sorry, this order is invalid and cannot be paid for.', 'easyReservations' ) );
					}

					$availability = $reservation->check_availability( $reservation_ids );

					if ( is_wp_error( $availability ) ) {
						throw new Exception( __( 'Sorry, reservation %s is not available anymore. Please contact us if you need assistance.', 'easyReservations' ) );
					}
				}

				do_action( 'easyreservations_pay_order', $order );
			} catch ( Exception $e ) {
				er_print_notice( $e->getMessage(), 'error' );
			}
		} elseif ( $order_id ) {

			// Pay for order after checkout step.
			$order_key = isset( $_GET['key'] ) ? er_clean( wp_unslash( $_GET['key'] ) ) : ''; // WPCS: input var ok, CSRF ok.
			$order     = er_get_order( $order_id );

			if ( $order && $order->get_id() === $order_id && hash_equals( $order->get_order_key(), $order_key ) ) {
				if ( $order->needs_payment() ) {
					er_get_template( 'checkout/order-receipt.php', array( 'order' => $order ) );
					do_action( 'easyreservations_pay_order', $order );
				} else {
					/* translators: %s: order status */
					er_print_notice( sprintf( __( 'This order&rsquo;s status is &ldquo;%s&rdquo;&mdash;it cannot be paid for. Please contact us if you need assistance.', 'easyReservations' ), ER_Order_Status::get_title( $order->get_status() ) ), 'error' );
				}
			} else {
				er_print_notice( __( 'Sorry, this order is invalid and cannot be paid for.', 'easyReservations' ), 'error' );
			}
		} else {
			er_print_notice( __( 'Invalid order.', 'easyReservations' ), 'error' );
		}

		do_action( 'after_easyreservations_pay' );
	}

	/**
	 * Show the thanks page.
	 *
	 * @param int $order_id Order ID.
	 */
	private static function order_received( $order_id = 0 ) {
		$order = false;

		// Get the order.
		$order_id  = apply_filters( 'easyreservations_thankyou_order_id', absint( $order_id ) );
		$order_key = apply_filters( 'easyreservations_thankyou_order_key', empty( $_GET['key'] ) ? '' : er_clean( wp_unslash( $_GET['key'] ) ) ); // WPCS: input var ok, CSRF ok.

		if ( $order_id > 0 ) {
			$order = er_get_order( $order_id );
			if ( ! $order || ! hash_equals( $order->get_order_key(), $order_key ) ) {
				$order = false;
			}
		}

		// Empty awaiting payment session.
		unset( ER()->session->order_awaiting_payment );

		// In case order is created from admin, but paid by the actual customer, store the ip address of the payer
		// when they visit the payment confirmation page.
		if ( $order && $order->is_created_via( 'admin' ) ) {
			$order->set_customer_ip_address( er_get_ip_address() );
			$order->save();
		}

		// Empty current cart.
		er_empty_cart();

		er_get_template( 'checkout/thankyou.php', array( 'order' => $order ) );
	}

	/**
	 * Output checkout
	 */
	public static function checkout() {
		// Show non-cart errors.
		do_action( 'easyreservations_before_checkout_form_cart_notices' );

		// Check cart has contents.
		if ( ER()->cart->is_empty() && ! is_customize_preview() && apply_filters( 'easyreservations_checkout_redirect_empty_cart', true ) ) {
			return;
		}

		// Check cart contents for errors.
		do_action( 'easyreservations_check_cart_items' );

		// Calc totals.
		ER()->cart->calculate_totals();

		$checkout = ER()->checkout();

		if ( empty( $_POST ) && er_notice_count( 'error' ) > 0 ) { // WPCS: input var ok, CSRF ok.
			er_get_template( 'checkout/cart-errors.php' );
			er_clear_notices();
		} else {
			$non_js_checkout = ! empty( $_POST['easyreservations_checkout_update_totals'] ); // WPCS: input var ok, CSRF ok.

			if ( er_notice_count( 'error' ) === 0 && $non_js_checkout ) {
				er_add_notice( __( 'The order totals have been updated. Please confirm your order by pressing the "Place order" button at the bottom of the page.', 'easyReservations' ) );
			}

			er_get_template( 'checkout/checkout.php' );
		}
	}
}