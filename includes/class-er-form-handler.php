<?php

defined( 'ABSPATH' ) || exit;

/**
 * ER_Form_Handler class.
 */
class ER_Form_Handler {

	/**
	 * The single instance of the class.
	 *
	 * @var ER_Resources|null
	 */
	protected static $instance = null;

	/**
	 * Return instance of ER_Form_Handler
	 *
	 * @return ER_Form_Handler
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * ER_Form_Handler constructor.
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( __CLASS__, 'update_cart_action' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'reservation_and_checkout_action' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'process_login' ), 20 );
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
		if ( ! ER()->is_request( 'ajax' ) && isset( $_POST['easy_form_id'] ) && ( isset( $_POST['email'], $_POST['first_name'] ) || isset( $_POST['email'], $_POST['first_name'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
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
		$do_checkout = isset( $_POST['email'], $_POST['first_name'] );
		$submit      = isset( $_POST['submit'] ) || ! is_easyreservations_ajax();
		$done        = false;

		if ( isset( $_POST['arrival'], $_POST['departure'] ) ) {
			//Reservation
			$done = ER()->reservation_form()->process_reservation( $submit );
			if ( $done && ! $do_checkout && ! $submit ) {
				wp_send_json( array(
					'result' => 'success',
					'price'  => $done->get_formatted_total()
				) );

				exit;
			}
		}

		if ( ( ! $done || isset( $_POST['direct_checkout'] ) ) && isset( $_POST['easy_form_hash'] ) ) {
			//check for custom fields in own form
			$errors  = new WP_Error();
			$customs = ER()->checkout()->get_form_data_custom( $errors, false, 'checkout' );

			if ( $customs && ! $errors->has_errors() ) {
				foreach ( $customs as $custom ) {
					ER()->cart->add_custom_to_cart( $custom );
				}

				$done = $do_checkout ? false : $customs;
			}
		}

		//Either a reservation or a custom fields have been added to cart but we won't checkout now
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
						'result'   => 'success',
						'messages' => $messages
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
			$checkout = ER()->checkout()->process_checkout( $submit );

			if ( $checkout ) {
				if ( $done ) {
					ER()->cart->calculate_totals( $done );

					ob_start();

					do_action( 'easyreservations_checkout_order_review' );

					$order_review = ob_get_clean();

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
}