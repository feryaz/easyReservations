<?php

defined( 'ABSPATH' ) || exit;

/**
 * ER_Form_Handler class.
 */
class ER_Form_Handler {

	/**
	 * ER_Form_Handler constructor.
	 */
	public static function init() {
		add_action( 'easyreservations_process_reservation_and_checkout', array( __CLASS__, 'process_reservation_and_checkout' ), 20 );

		add_action( 'wp_loaded', array( __CLASS__, 'update_cart_action' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'reservation_and_checkout_action' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'process_login' ), 20 );

		// May need $wp global to access query vars.
		add_action( 'wp', array( __CLASS__, 'pay_action' ), 20 );
		add_action( 'wp', array( __CLASS__, 'add_payment_method_action' ), 20 );
		add_action( 'wp', array( __CLASS__, 'delete_payment_method_action' ), 20 );
		add_action( 'wp', array( __CLASS__, 'set_default_payment_method_action' ), 20 );
	}

	/**
	 * Remove from cart/update.
	 */
	public static function update_cart_action() {
		if ( ! ( isset( $_REQUEST['apply_coupon'] ) || isset( $_REQUEST['remove_coupon'] ) || isset( $_REQUEST['remove_item'] ) || isset( $_REQUEST['undo_item'] ) || isset( $_REQUEST['proceed'] ) ) ) {
			return;
		}

		nocache_headers();

		$nonce_value        = er_get_var( $_REQUEST['easyreservations-cart-nonce'], er_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.
		$coupon_nonce_value = er_get_var( $_REQUEST['easyreservations-coupon-nonce'], er_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( ! empty( $_POST['apply_coupon'] ) && ! empty( $_POST['coupon_code'] ) && wp_verify_nonce( $coupon_nonce_value, 'easyreservations-coupon' ) ) {
			//Apply coupon
			ER()->cart->apply_coupon( er_coupon_format_code( wp_unslash( $_POST['coupon_code'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		} elseif ( isset( $_GET['remove_coupon'] ) && wp_verify_nonce( $coupon_nonce_value, 'easyreservations-coupon' ) ) {
			//Remove coupon
			ER()->cart->remove_coupon( er_coupon_format_code( urldecode( wp_unslash( $_GET['remove_coupon'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		} elseif ( ! empty( $_GET['remove_item'] ) && wp_verify_nonce( $nonce_value, 'easyreservations-cart' ) ) {
			$cart_item_key = sanitize_text_field( wp_unslash( $_GET['remove_item'] ) );
			$cart_item     = ER()->cart->get_cart_item( $cart_item_key );

			if ( $cart_item ) {
				$name = false;

				if ( is_integer( $cart_item ) ) {
					$reservation = ER()->reservation_manager()->get( absint( $cart_item ) );

					if ( $reservation ) {
						$name = $reservation->get_name();
					}
				} elseif ( isset( $cart_item['name'] ) ) {
					$name = sanitize_text_field( $cart_item['name'] );
				}

				ER()->cart->remove_cart_item( $cart_item_key );

				/* translators: %s: Item name. */
				$item_removed_title = apply_filters( 'easyreservations_cart_item_removed_name', $name ? sprintf( '&ldquo;%s&rdquo;', $name ) : __( 'Item', 'easyReservations' ), $cart_item );

				$removed_notice = sprintf( __( '%s removed.', 'easyReservations' ), $item_removed_title );
				$removed_notice .= ' <a href="' . esc_url( er_get_cart_undo_url( $cart_item_key ) ) . '" class="restore-item">' . __( 'Undo?', 'easyReservations' ) . '</a>';

				er_add_notice( $removed_notice );
			}

			$referer = wp_get_referer() ? remove_query_arg( array(
				'remove_item',
				'add-to-cart',
				'added-to-cart',
				'_wpnonce'
			), add_query_arg( 'removed_item', '1', wp_get_referer() ) ) : er_get_cart_url();
			wp_safe_redirect( $referer );
			exit;
		} elseif ( ! empty( $_GET['undo_item'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $nonce_value, 'easyreservations-cart' ) ) {
			// Undo Cart Item.
			$cart_item_key = sanitize_text_field( wp_unslash( $_GET['undo_item'] ) );

			ER()->cart->restore_cart_item( $cart_item_key );

			$referer = wp_get_referer() ? remove_query_arg( array(
				'undo_item',
				'_wpnonce'
			), wp_get_referer() ) : er_get_cart_url();
			wp_safe_redirect( $referer );
			exit;
		}

		// Update Cart - checks apply_coupon too because they are in the same form.
		if ( ( ! empty( $_POST['apply_coupon'] ) || ! empty( $_POST['proceed'] ) ) && wp_verify_nonce( $nonce_value, 'easyreservations-cart' ) ) {
			ER()->cart->calculate_totals();

			if ( ! empty( $_POST['proceed'] ) ) {
				wp_safe_redirect( er_get_checkout_url() );
				exit;
			} else {
				$referer = remove_query_arg( array(
					'remove_coupon',
					'add-to-cart'
				), ( wp_get_referer() ? wp_get_referer() : er_get_cart_url() ) );

				wp_safe_redirect( $referer );
				exit;
			}
		}
	}

	/**
	 * Process the checkout form.
	 */
	public static function reservation_and_checkout_action() {
		if ( ! ER()->is_request( 'ajax' ) && isset( $_POST['easy_form_id'] ) && ( isset( $_POST['email'], $_POST['easyreservations-process-checkout-nonce'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			nocache_headers();

			if ( ER()->cart->is_empty() ) {
				wp_safe_redirect( er_get_cart_url() );
				exit;
			}

			self::process_reservation_and_checkout();
		}
	}

	/**
	 * Process reservation form and checkout submit as they happen at the same time
	 */
	public static function process_reservation_and_checkout() {
		$do_checkout = isset( $_POST['email'], $_POST['easyreservations-process-checkout-nonce'] );
		$submit      = isset( $_POST['submit'] ) || ! is_easyreservations_ajax();
		$done        = false;

		if ( isset( $_POST['arrival'], $_POST['departure'] ) ) {
			//Reservation
			$done = ER()->reservation_form()->process_reservation( $submit );

			if ( $done && ! $do_checkout && ! $submit ) {
				wp_send_json( array(
					'result'          => 'success',
					'label'           => $done->get_name(),
					'price'           => $done->get_total(),
					'price_formatted' => $done->get_formatted_total()
				) );

				exit;
			}
		}

		$cart_items_added = array();
		if ( ! $done && isset( $_POST['easy_form_hash'] ) ) {
			//check for custom fields in own form
			$errors  = new WP_Error();
			$customs = ER()->checkout()->get_form_data_custom( $errors, false, 'checkout' );

			if ( $customs && ! $errors->has_errors() ) {
				foreach ( $customs as $custom ) {
					$cart_items_added[] = ER()->cart->add_custom_to_cart( $custom );
				}

				$done = $do_checkout ? false : $customs;
			}
		}

		//Either a reservation or custom field(s) have been added to cart but we won't checkout now
		if ( $done && ! $do_checkout ) {
			$url = esc_url_raw( $_POST['redirect'] );

			if ( is_easyreservations_ajax() ) {
				if ( 'yes' === get_option( 'reservations_cart_redirect_after_add' ) ) {
					er_add_to_cart_message( $done );

					wp_send_json( array(
						'result'   => 'success',
						'redirect' => $url,
					) );

					exit;
				} else {
					$messages = er_add_to_cart_message( $done, true );

					wp_send_json( array(
						'result'        => 'success',
						'added_to_cart' => true,
						'messages'      => $messages,
					) );

					exit;
				}
			} else {
				er_add_to_cart_message( $done );

				if ( 'yes' === get_option( 'reservations_cart_redirect_after_add' ) ) {
					wp_safe_redirect( $url );
					exit;
				}
			}
		}

		//Checkout
		if ( $do_checkout ) {
			$checkout = $submit ? ER()->checkout()->process_checkout( $submit, $cart_items_added ) : true;

			if ( $checkout ) {
				if ( $done ) {
					ER()->cart->calculate_totals( $done );

					ob_start();

					do_action( 'easyreservations_checkout_order_review' );

					$order_review = ob_get_clean();

					if ( isset( $_POST['direct_checkout'] ) && $_POST['direct_checkout'] === '1' ) {
						ER()->cart->reset_totals();
					}

					wp_send_json( array(
						'result'       => 'success',
						'order_review' => $order_review,
					) );
				} else {
					wp_send_json( array(
						'result' => 'success',
					) );

					exit;
				}
			}
		}
	}

	/**
	 * Process the login form.
	 *
	 * @throws Exception On login error.
	 */
	public static function process_login() {
		// The global form-login.php template used `_wpnonce` in template versions < 3.3.0.
		$nonce_value = er_get_var( $_REQUEST['easyreservations-login-nonce'], er_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( isset( $_POST['login'], $_POST['username'], $_POST['password'] ) && wp_verify_nonce( $nonce_value, 'easyreservations-login' ) ) {
			try {
				$creds = array(
					'user_login'    => trim( wp_unslash( $_POST['username'] ) ),
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					'user_password' => $_POST['password'],
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
					'remember'      => isset( $_POST['rememberme'] ),
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				);

				$validation_error = new WP_Error();
				$validation_error = apply_filters( 'easyreservations_process_login_errors', $validation_error, $creds['user_login'], $creds['user_password'] );

				if ( $validation_error->get_error_code() ) {
					throw new Exception( '<strong>' . __( 'Error:', 'easyreservations' ) . '</strong> ' . $validation_error->get_error_message() );
				}

				if ( empty( $creds['user_login'] ) ) {
					throw new Exception( '<strong>' . __( 'Error:', 'easyreservations' ) . '</strong> ' . __( 'Username is required.', 'easyreservations' ) );
				}

				// On multisite, ensure user exists on current site, if not add them before allowing login.
				if ( is_multisite() ) {
					$user_data = get_user_by( is_email( $creds['user_login'] ) ? 'email' : 'login', $creds['user_login'] );

					if ( $user_data && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
						add_user_to_blog( get_current_blog_id(), $user_data->ID, 'easy_customer' );
					}
				}

				// Perform the login.
				$user = wp_signon( apply_filters( 'easyreservations_login_credentials', $creds ), is_ssl() );

				if ( is_wp_error( $user ) ) {
					throw new Exception( $user->get_error_message() );
				} else {
					if ( ! empty( $_POST['redirect'] ) ) {
						$redirect = wp_unslash( $_POST['redirect'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					} elseif ( er_get_raw_referer() ) {
						$redirect = er_get_raw_referer();
					} else {
						$redirect = er_get_page_permalink( 'myaccount' );
					}

					wp_redirect( wp_validate_redirect( apply_filters( 'easyreservations_login_redirect', remove_query_arg( 'er_error', $redirect ), $user ), er_get_page_permalink( 'myaccount' ) ) ); // phpcs:ignore
					exit;
				}
			} catch ( Exception $e ) {
				er_add_notice( apply_filters( 'login_errors', $e->getMessage() ), 'error' );
				do_action( 'easyreservations_login_failed' );
			}
		}
	}

	/**
	 * Process the pay form.
	 *
	 * @throws Exception On payment error.
	 */
	public static function pay_action() {
		global $wp;

		if ( isset( $_POST['easyreservations_pay'], $_GET['key'] ) ) {
			nocache_headers();

			$nonce_value = er_get_var( $_REQUEST['easyreservations-process-checkout-nonce'], er_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

			if ( ! wp_verify_nonce( $nonce_value, 'easyreservations-process-checkout' ) ) {
				return;
			}

			ob_start();

			// Pay for existing order.
			$order_key = wp_unslash( $_GET['key'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$order_id  = absint( $wp->query_vars['order-payment'] );
			$order     = er_get_order( $order_id );

			if ( $order_id === $order->get_id() && hash_equals( $order->get_order_key(), $order_key ) && $order->needs_payment() ) {

				do_action( 'easyreservations_before_pay_action', $order );

				ER()->customer->set_props(
					array(
						'country'  => $order->get_country() ? $order->get_country() : null,
						'state'    => $order->get_state() ? $order->get_state() : null,
						'postcode' => $order->get_postcode() ? $order->get_postcode() : null,
						'city'     => $order->get_city() ? $order->get_city() : null,
					)
				);

				ER()->customer->save();

				if ( ! empty( $_POST['terms-field'] ) && empty( $_POST['terms'] ) ) {
					er_add_notice( __( 'Please read and accept the terms and conditions to proceed with your order.', 'easyReservations' ), 'error' );

					return;
				}

				// Update payment method.
				if ( $order->needs_payment() ) {
					try {
						$payment_method_id = isset( $_POST['payment_method'] ) ? er_clean( wp_unslash( $_POST['payment_method'] ) ) : false;

						if ( ! $payment_method_id ) {
							throw new Exception( __( 'Invalid payment method.', 'easyReservations' ) );
						}

						$available_gateways = ER()->payment_gateways()->get_available_payment_gateways();
						$payment_method     = isset( $available_gateways[ $payment_method_id ] ) ? $available_gateways[ $payment_method_id ] : false;

						if ( ! $payment_method ) {
							throw new Exception( __( 'Invalid payment method.', 'easyReservations' ) );
						}

						$order->set_payment_method( $payment_method );
						$order->save();

						$payment_method->validate_fields();

						if ( 0 === er_notice_count( 'error' ) ) {

							$result = $payment_method->process_payment( $order_id );

							// Redirect to success/confirmation/payment page.
							if ( isset( $result['result'] ) && 'success' === $result['result'] ) {
								$result = apply_filters( 'easyreservations_payment_successful_result', $result, $order_id );

								wp_redirect( $result['redirect'] ); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
								exit;
							}
						}
					} catch ( Exception $e ) {
						er_add_notice( $e->getMessage(), 'error' );
					}
				} else {
					// No payment was required for order.
					$order->payment_complete();
					wp_safe_redirect( $order->get_checkout_order_received_url() );
					exit;
				}

				do_action( 'easyreservations_after_pay_action', $order );
			}
		}
	}

	/**
	 * Process the add payment method form.
	 */
	public static function add_payment_method_action() {
		if ( isset( $_POST['easyreservations_add_payment_method'], $_POST['payment_method'] ) ) {
			nocache_headers();

			$nonce_value = er_get_var( $_REQUEST['easyreservations-add-payment-method-nonce'], er_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

			if ( ! wp_verify_nonce( $nonce_value, 'easyreservations-add-payment-method' ) ) {
				return;
			}

			if ( ! apply_filters( 'easyreservations_add_payment_method_form_is_valid', true ) ) {
				return;
			}

			// Test rate limit.
			$current_user_id = get_current_user_id();
			$rate_limit_id   = 'add_payment_method_' . $current_user_id;
			$delay           = (int) apply_filters( 'easyreservations_payment_gateway_add_payment_method_delay', 20 );

			if ( ER_Rate_Limiter::retried_too_soon( $rate_limit_id ) ) {
				er_add_notice(
					sprintf(
					/* translators: %d number of seconds */
						_n(
							'You cannot add a new payment method so soon after the previous one. Please wait for %d second.',
							'You cannot add a new payment method so soon after the previous one. Please wait for %d seconds.',
							$delay,
							'easyReservations'
						),
						$delay
					),
					'error'
				);

				return;
			}

			ER_Rate_Limiter::set_rate_limit( $rate_limit_id, $delay );

			ob_start();

			$payment_method_id  = er_clean( wp_unslash( $_POST['payment_method'] ) );
			$available_gateways = ER()->payment_gateways()->get_available_payment_gateways();

			if ( isset( $available_gateways[ $payment_method_id ] ) ) {
				$gateway = $available_gateways[ $payment_method_id ];

				if ( ! $gateway->supports( 'add_payment_method' ) && ! $gateway->supports( 'tokenization' ) ) {
					er_add_notice( __( 'Invalid payment gateway.', 'easyReservations' ), 'error' );

					return;
				}

				$gateway->validate_fields();

				if ( er_notice_count( 'error' ) > 0 ) {
					return;
				}

				$result = $gateway->add_payment_method();

				if ( 'success' === $result['result'] ) {
					er_add_notice( __( 'Payment method successfully added.', 'easyReservations' ) );
				}

				if ( 'failure' === $result['result'] ) {
					er_add_notice( __( 'Unable to add payment method to your account.', 'easyReservations' ), 'error' );
				}

				if ( ! empty( $result['redirect'] ) ) {
					wp_redirect( $result['redirect'] ); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit();
				}
			}
		}
	}

	/**
	 * Process the delete payment method form.
	 */
	public static function delete_payment_method_action() {
		global $wp;

		if ( isset( $wp->query_vars['delete-payment-method'] ) ) {
			nocache_headers();

			$token_id = absint( $wp->query_vars['delete-payment-method'] );
			$token    = ER_Payment_Tokens::get( $token_id );

			if ( is_null( $token ) || get_current_user_id() !== $token->get_user_id() || ! isset( $_REQUEST['_wpnonce'] ) || false === wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'delete-payment-method-' . $token_id ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				er_add_notice( __( 'Invalid payment method.', 'easyReservations' ), 'error' );
			} else {
				ER_Payment_Tokens::delete( $token_id );
				er_add_notice( __( 'Payment method deleted.', 'easyReservations' ) );
			}

			wp_safe_redirect( er_get_account_endpoint_url( 'payment-methods' ) );
			exit();
		}
	}

	/**
	 * Process the delete payment method form.
	 */
	public static function set_default_payment_method_action() {
		global $wp;

		if ( isset( $wp->query_vars['set-default-payment-method'] ) ) {
			nocache_headers();

			$token_id = absint( $wp->query_vars['set-default-payment-method'] );
			$token    = ER_Payment_Tokens::get( $token_id );

			if ( is_null( $token ) || get_current_user_id() !== $token->get_user_id() || ! isset( $_REQUEST['_wpnonce'] ) || false === wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'set-default-payment-method-' . $token_id ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				er_add_notice( __( 'Invalid payment method.', 'easyReservations' ), 'error' );
			} else {
				ER_Payment_Tokens::set_users_default( $token->get_user_id(), intval( $token_id ) );
				er_add_notice( __( 'This payment method was successfully set as your default.', 'easyReservations' ) );
			}

			wp_safe_redirect( er_get_account_endpoint_url( 'payment-methods' ) );
			exit();
		}
	}
}

ER_Form_Handler::init();
