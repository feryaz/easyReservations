<?php
defined( 'ABSPATH' ) || exit;

class ER_Payment {

	/**
	 * The single instance of the class.
	 *
	 * @var ER_Payment
	 */
	protected static $_instance = null;

	/**
	 * Payment gateway classes.
	 *
	 * @var ER_Payment_Gateway[]
	 */
	public $payment_gateways = array();

	/**
	 * Main ER_Payment Instance.
	 *
	 * Ensures only one instance of ER_Payment is loaded or can be loaded.
	 *
	 * @return ER_Payment Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Initialize payment gateways.
	 */
	public function __construct() {
		$this->init();

		//remove_action( 'easyreservations_checkout_after_order_review', 'easyreservations_checkout_terms', 10 );
		//remove_action( 'easyreservations_checkout_after_order_review', 'easyreservations_checkout_submit', 20 );
	}

	/**
	 * Load gateways and hook in functions.
	 */
	public function init() {
		// Filter.
		$load_gateways = apply_filters( 'easyreservations_payment_gateways', array() );

		// Get sort order option.
		$ordering  = (array) get_option( 'reservations_gateway_order' );
		$order_end = 999;

		// Load gateways in order.
		foreach ( $load_gateways as $gateway ) {
			if ( is_string( $gateway ) && class_exists( $gateway ) ) {
				$gateway = new $gateway();
			}

			// Gateways need to be valid and extend ER_Payment_Gateway.
			if ( ! is_a( $gateway, 'ER_Payment_Gateway' ) ) {
				continue;
			}

			if ( isset( $ordering[ $gateway->id ] ) && is_numeric( $ordering[ $gateway->id ] ) ) {
				// Add in position.
				$this->payment_gateways[ $ordering[ $gateway->id ] ] = $gateway;
			} else {
				// Add to end of the array.
				$this->payment_gateways[ $order_end ] = $gateway;
				$order_end ++;
			}
		}

		ksort( $this->payment_gateways );
	}

	/**
	 * Get gateways.
	 *
	 * @return ER_Payment_Gateway[]
	 */
	public function payment_gateways() {
		$_available_gateways = array();

		if ( count( $this->payment_gateways ) > 0 ) {
			foreach ( $this->payment_gateways as $gateway ) {
				$_available_gateways[ $gateway->id ] = $gateway;
			}
		}

		return $_available_gateways;
	}

	/**
	 * Get array of registered gateway ids
	 *
	 * @return array of strings
	 */
	public function get_payment_gateway_ids() {
		return wp_list_pluck( $this->payment_gateways, 'id' );
	}

	/**
	 * Get available gateways.
	 *
	 * @return ER_Payment_Gateway[]
	 */
	public function get_available_payment_gateways() {
		$_available_gateways = array();

		foreach ( $this->payment_gateways as $gateway ) {
			if ( $gateway->is_available() ) {
				$_available_gateways[ $gateway->id ] = $gateway;
			}
		}

		return array_filter( (array) apply_filters( 'easyreservations_available_payment_gateways', $_available_gateways ), array(
			$this,
			'filter_valid_gateway_class'
		) );
	}

	/**
	 * Callback for array filter. Returns true if gateway is of correct type.
	 *
	 * @param object $gateway Gateway to check.
	 *
	 * @return bool
	 */
	protected function filter_valid_gateway_class( $gateway ) {
		return $gateway && is_a( $gateway, 'ER_Payment_Gateway' );
	}

	/**
	 * Set the current, active gateway.
	 *
	 * @param array $gateways Available payment gateways.
	 */
	public function set_current_gateway( $gateways ) {
		// Be on the defensive.
		if ( ! is_array( $gateways ) || empty( $gateways ) ) {
			return;
		}

		$current_gateway = false;

		if ( ER()->session ) {
			$current = ER()->session->get( 'chosen_payment_method' );

			if ( $current && isset( $gateways[ $current ] ) ) {
				$current_gateway = $gateways[ $current ];
			}
		}

		if ( ! $current_gateway ) {
			$current_gateway = current( $gateways );
		}

		// Ensure we can make a call to set_current() without triggering an error.
		if ( $current_gateway && is_callable( array( $current_gateway, 'set_current' ) ) ) {
			$current_gateway->set_current();
		}
	}

	/**
	 * Process an order that does require payment.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $payment_method Payment method.
	 */
	public function process_order_payment( $order_id, $payment_method ) {
		$available_gateways = $this->get_available_payment_gateways();

		if ( ! isset( $available_gateways[ $payment_method ] ) ) {
			return;
		}

		// Store Order ID in session so it can be re-used after payment failure.
		ER()->session->set( 'order_awaiting_payment', $order_id );

		// Process Payment.
		$result = $available_gateways[ $payment_method ]->process_payment( $order_id );

		// Redirect to success/confirmation/payment page.
		if ( isset( $result['result'] ) && 'success' === $result['result'] ) {
			$result = apply_filters( 'easyreservations_payment_successful_result', $result, $order_id );

			if ( ! is_easyreservations_ajax() ) {
				wp_redirect( $result['redirect'] );
				exit;
			}

			wp_send_json( $result );
		}
	}

	/**
	 * Redirect to payment page
	 *
	 * @param ER_Order $order
	 *
	 * @return array
	 */
	public function direct_checkout_redirect( $order ) {
		$url = $order->get_checkout_payment_url( false );

		if ( ! is_easyreservations_ajax() ) {
			wp_redirect( $url );
			exit;
		}

		wp_send_json( array(
			'result' => 'success',
			'redirect' => $url,
		) );
	}

	/**
	 * Do refund through gateway
	 *
	 * @param ER_Order        $order
	 * @param ER_Order_Refund $refund
	 *
	 * @return WP_Error|bool
	 */
	public function refund( $order, $refund ) {
		try {
			if ( ! is_a( $order, 'ER_Order' ) ) {
				throw new Exception( __( 'Invalid order.', 'easyReservations' ) );
			}

			$all_gateways   = $this->payment_gateways();
			$payment_method = $order->get_payment_method();
			$gateway        = isset( $all_gateways[ $payment_method ] ) ? $all_gateways[ $payment_method ] : false;

			if ( ! $gateway ) {
				throw new Exception( __( 'The payment gateway for this order does not exist.', 'easyReservations' ) );
			}

			if ( ! $gateway->supports( 'refunds' ) ) {
				throw new Exception( __( 'The payment gateway for this order does not support automatic refunds.', 'easyReservations' ) );
			}

			$result = $gateway->process_refund( $order->get_id(), $refund->get_amount(), $refund->get_reason() );

			if ( ! $result ) {
				throw new Exception( __( 'An error occurred while attempting to create the refund using the payment gateway API.', 'easyReservations' ) );
			}

			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			return true;
		} catch ( Exception $e ) {
			return new WP_Error( 'error', $e->getMessage() );
		}
	}
}