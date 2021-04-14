<?php
/**
 * Checkout functionality
 *
 * The easyReservations checkout class handles the checkout process, collecting user data and processing the payment.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checkout class.
 */
class ER_Checkout extends ER_Form {

	/**
	 * The single instance of the class.
	 *
	 * @var ER_Checkout|null
	 */
	protected static $instance = null;

	/**
	 * Checkout fields are stored here.
	 *
	 * @var array|null
	 */
	protected $fields = null;

	/**
	 * Caches customer object. @see get_value.
	 *
	 * @var ER_Customer
	 */
	private $logged_in_customer = null;

	/**
	 * Gets the main ER_Checkout Instance.
	 *
	 * @static
	 * @return ER_Checkout Main instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			// Hook in actions once.
			add_action( 'easyreservations_checkout_address', array( self::$instance, 'checkout_form_address' ) );
			add_action( 'easyreservations_checkout_additional', array( self::$instance, 'checkout_form_additional' ) );
			add_action( 'easyreservations_checkout_form', array( self::$instance, 'checkout_form' ) );
			add_action( 'easyreservations_before_order_notes', array( self::$instance, 'checkout_user_template' ) );

			// easyreservations_checkout_init action is ran once when the class is first constructed.
			do_action( 'easyreservations_checkout_init', self::$instance );
		}

		return self::$instance;
	}

	/**
	 * Get an array of checkout fields.
	 *
	 * @param string $fieldset to get.
	 *
	 * @return array
	 */
	public function get_checkout_fields( $fieldset = '' ) {
		if ( ! is_null( $this->fields ) ) {
			return $fieldset ? $this->fields[ $fieldset ] : $this->fields;
		}

		$country           = $this->get_value( 'country' );
		$country           = empty( $country ) ? ER()->countries->get_base_country() : $country;
		$allowed_countries = ER()->countries->get_countries();

		if ( ! array_key_exists( $country, $allowed_countries ) ) {
			$country = current( array_keys( $allowed_countries ) );
		}

		$this->fields = array(
			'address' => ER()->countries->get_address_fields( $country ),
			'account' => array(),
			'order'   => array(
				'order_comments' => array(
					'type'        => 'textarea',
					'class'       => array( 'notes' ),
					'label'       => __( 'Order notes', 'easyReservations' ),
					'placeholder' => esc_attr__(
						'Notes about your order, e.g. special notes for delivery.',
						'easyReservations'
					),
				),
			),
		);

		if ( 'no' === get_option( 'reservations_registration_generate_username' ) ) {
			$this->fields['account']['account_username'] = array(
				'type'        => 'text',
				'label'       => __( 'Account username', 'easyReservations' ),
				'required'    => true,
				'placeholder' => esc_attr__( 'Username', 'easyReservations' ),
			);
		}

		if ( 'no' === get_option( 'reservations_registration_generate_password' ) ) {
			$this->fields['account']['account_password'] = array(
				'type'        => 'password',
				'label'       => __( 'Create account password', 'easyReservations' ),
				'required'    => true,
				'placeholder' => esc_attr__( 'Password', 'easyReservations' ),
			);
		}

		$this->fields = apply_filters( 'easyreservations_checkout_fields', $this->fields );

		foreach ( $this->fields as $field_type => $fields ) {
			// Sort each of the checkout field sections based on priority.
			uasort( $this->fields[ $field_type ], 'er_checkout_fields_uasort_comparison' );

			// Add accessibility labels to fields that have placeholders.
			foreach ( $fields as $single_field_type => $field ) {
				if ( empty( $field['label'] ) && ! empty( $field['placeholder'] ) ) {
					$this->fields[ $field_type ][ $single_field_type ]['label']       = $field['placeholder'];
					$this->fields[ $field_type ][ $single_field_type ]['label_class'] = array( 'screen-reader-text' );
				}
			}
		}

		return $fieldset ? $this->fields[ $fieldset ] : $this->fields;
	}

	/**
	 * When we process the checkout, lets ensure cart items are rechecked to prevent checkout.
	 */
	public function check_cart_items() {
		do_action( 'easyreservations_check_cart_items' );
	}

	/**
	 * Output the billing form.
	 */
	public function checkout_form() {
		er_get_template( 'checkout/form-checkout.php', array( 'checkout' => $this ) );
	}

	/**
	 * Output the billing form.
	 */
	public function checkout_form_address() {
		er_get_template( 'checkout/form-address.php', array( 'checkout' => $this ) );
	}

	/**
	 * Output the billing form.
	 */
	public function checkout_form_additional() {
		er_get_template( 'checkout/form-additional.php', array( 'checkout' => $this ) );
	}

	/*
	 * Output checkout form
	 */
	public function checkout_user_template() {
		$this->enqueue();

		echo $this->generate( 'checkout', 0 );
	}

	/**
	 * Create an order. Error codes:
	 *      520 - Cannot insert order into the database.
	 *      521 - Cannot get order after creation.
	 *      522 - Cannot update order.
	 *      525 - Cannot create line item.
	 *      526 - Cannot create fee item.
	 *      528 - Cannot create tax item.
	 *      529 - Cannot create coupon item.
	 *
	 * @param array $data Posted data.
	 * @param array $customs Custom data.
	 *
	 * @return int|WP_ERROR
	 * @throws Exception When checkout validation fails.
	 */
	public function create_order( $data, $customs ) {
		// Give plugins the opportunity to create an order themselves.
		$order_id = apply_filters( 'easyreservations_create_order', null, $this );
		if ( $order_id ) {
			return $order_id;
		}

		try {
			$order_id  = absint( ER()->session->get( 'order_awaiting_payment' ) );
			$cart_hash = ER()->cart->get_cart_hash();
			$order     = $order_id ? er_get_order( $order_id ) : null;

			/**
			 * If there is an order pending payment, we can resume it here so
			 * long as it has not changed. If the order has changed, i.e.
			 * different items or cost, create a new order. We use a hash to
			 * detect changes which is based on cart items + order total.
			 */
			if ( $order && $order->has_cart_hash( $cart_hash ) && $order->has_status( array( 'pending', 'failed' ) ) ) {
				// Action for 3rd parties.
				do_action( 'easyreservations_resume_order', $order_id );
			} else {
				$order = new ER_Order( 0 );
			}

			$order->hold_applied_coupons( $data['email'] );
			$order->set_order_key( er_generate_order_key() );
			$order->set_props( $data );
			$order->set_created_via( 'checkout' );
			$order->set_cart_hash( $cart_hash );
			$order_vat_exempt = ER()->customer->get_is_vat_exempt() ? 'yes' : 'no';
			$order->add_meta_data( 'is_vat_exempt', $order_vat_exempt );
			$order->set_customer_id( apply_filters( 'easyreservations_checkout_customer_id', get_current_user_id() ) );
			$order->set_customer_ip_address( er_get_ip_address() );
			$order->set_customer_user_agent( er_get_user_agent() );
			$order->set_customer_note( isset( $data['order_comments'] ) ? $data['order_comments'] : '' );
			$order->set_locale( determine_locale() );
			$order->set_prices_include_tax( er_prices_include_tax() );

			if ( isset( $data['payment_method'] ) ) {
				$available_gateways = ER()->payment_gateways()->get_available_payment_gateways();
				$order->set_payment_method( isset( $available_gateways[ $data['payment_method'] ] ) ? $available_gateways[ $data['payment_method'] ] : $data['payment_method'] );
			}

			try {
				$this->create_order_line_items( $order, ER()->cart );
			} catch ( Exception $e ) {
				return new WP_Error( 'checkout', $e->getMessage() );
			}

			if ( ! empty( $customs ) ) {
				foreach ( $customs as $custom ) {
					$order->add_custom( $custom );
				}
			}

			$order->calculate_taxes( false );
			$order->calculate_totals( false );

			do_action( 'easyreservations_create_order_before_coupons', $order );

			if ( ! empty( ER()->cart->get_applied_coupons() ) ) {
				foreach ( ER()->cart->get_applied_coupons() as $coupon_code ) {
					$error = apply_filters( 'easyreservations_add_order_coupon', $coupon_code, $order, false );

					if ( is_wp_error( $error ) ) {
						return $error;
					}
				}
			}

			$order->save();
			/**
			 * Action hook fired after an order is created used to add custom meta to the order.
			 */
			do_action( 'easyreservations_checkout_update_order_meta', $order_id, $data );

			$order->finalize();

			return $order->get_id();
		} catch ( Exception $e ) {
			if ( $order && $order instanceof ER_Order ) {
				$order->get_data_store()->release_held_coupons( $order );
				do_action( 'easyreservations_checkout_order_exception', $order );
			}

			return new WP_Error( 'checkout-error', $e->getMessage() );
		}
	}

	/**
	 * Add line items to the order.
	 *
	 * @param ER_Order $order Order instance.
	 * @param ER_Cart  $cart Cart instance.
	 */
	public function create_order_line_items( &$order, $cart ) {
		foreach ( $cart->get_cart_contents() as $cart_item_key => $values ) {
			if ( is_numeric( $values ) ) {
				// Add item to order and save.
				$order->add_reservation( absint( $values ) );
			} else {
				$order->add_custom( $values );
			}
		}
	}

	/**
	 * See if a fieldset should be skipped.
	 *
	 * @param string $fieldset_key Fieldset key.
	 * @param array  $data Posted data.
	 *
	 * @return bool
	 */
	protected function maybe_skip_fieldset( $fieldset_key, $data ) {
		if ( 'account' === $fieldset_key && ( is_user_logged_in() || ( ! er_is_registration_required() && empty( $data['createaccount'] ) ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get posted data from the checkout form.
	 *
	 * @return array of data.
	 */
	public function get_posted_data() {
		$skipped = array();
		$data    = array(
			'terms'                                   => (int) isset( $_POST['terms'] ),
			'createaccount'                           => (int) er_is_registration_enabled() ? ! empty( $_POST['createaccount'] ) : false,
			'direct_checkout'                         => isset( $_POST['direct_checkout'] ) ? $_POST['direct_checkout'] === "1" : false,
			'payment_method'                          => isset( $_POST['payment_method'] ) ? er_clean( wp_unslash( $_POST['payment_method'] ) ) : '',
			'easyreservations_checkout_update_totals' => isset( $_POST['easyreservations_checkout_update_totals'] ),
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		foreach ( $this->get_checkout_fields() as $fieldset_key => $fieldset ) {
			if ( $this->maybe_skip_fieldset( $fieldset_key, $data ) ) {
				$skipped[] = $fieldset_key;
				continue;
			}

			foreach ( $fieldset as $key => $field ) {
				$type = sanitize_title( isset( $field['type'] ) ? $field['type'] : 'text' );

				switch ( $type ) {
					case 'checkbox':
						$value = isset( $_POST[ $key ] ) ? 1 : ''; // WPCS: input var ok, CSRF ok.
						break;
					case 'multiselect':
						$value = isset( $_POST[ $key ] ) ? implode( ', ', er_clean( wp_unslash( $_POST[ $key ] ) ) ) : ''; // WPCS: input var ok, CSRF ok.
						break;
					case 'textarea':
						$value = isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : ''; // WPCS: input var ok, CSRF ok.
						break;
					case 'password':
						$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // WPCS: input var ok, CSRF ok, sanitization ok.
						break;
					default:
						$value = isset( $_POST[ $key ] ) ? er_clean( wp_unslash( $_POST[ $key ] ) ) : ''; // WPCS: input var ok, CSRF ok.
						break;
				}

				$data[ $key ] = apply_filters( 'easyreservations_process_checkout_' . $type . '_field', apply_filters( 'easyreservations_process_checkout_field_' . $key, $value ) );
			}
		}

		return apply_filters( 'easyreservations_checkout_posted_data', $data );
	}

	/**
	 * Validates the posted checkout data based on field properties.
	 *
	 * @param array    $data An array of posted data.
	 * @param WP_Error $errors Validation error.
	 */
	protected function validate_posted_data( &$data, &$errors ) {
		foreach ( $this->get_checkout_fields() as $fieldset_key => $fieldset ) {
			$validate_fieldset = true;
			if ( $this->maybe_skip_fieldset( $fieldset_key, $data ) ) {
				$validate_fieldset = false;
			}

			foreach ( $fieldset as $key => $field ) {
				if ( ! isset( $data[ $key ] ) ) {
					continue;
				}
				$required    = ! empty( $field['required'] );
				$format      = array_filter( isset( $field['validate'] ) ? (array) $field['validate'] : array() );
				$field_label = isset( $field['label'] ) ? $field['label'] : '';

				if ( $validate_fieldset &&
				     ( isset( $field['type'] ) && 'country' === $field['type'] ) &&
				     ! ER()->countries->country_exists( $data[ $key ] ) ) {
					/* translators: ISO 3166-1 alpha-2 country code */
					$errors->add( $key . '_validation', sprintf( __( "'%s' is not a valid country code.", 'easyReservations' ), $data[ $key ] ) );
				}

				if ( in_array( 'postcode', $format, true ) ) {
					$country      = isset( $data[ $fieldset_key . '_country' ] ) ? $data[ $fieldset_key . '_country' ] : ER()->customer->get_address_country();
					$data[ $key ] = er_format_postcode( $data[ $key ], $country );

					if ( $validate_fieldset && '' !== $data[ $key ] && ! ER_Validation::is_postcode( $data[ $key ], $country ) ) {
						switch ( $country ) {
							case 'IE':
								/* translators: %1$s: field name, %2$s finder.eircode.ie URL */
								$postcode_validation_notice = sprintf( __( '%1$s is not valid. You can look up the correct Eircode <a target="_blank" href="%2$s">here</a>.', 'easyReservations' ), '<strong>' . esc_html( $field_label ) . '</strong>', 'https://finder.eircode.ie' );
								break;
							default:
								/* translators: %s: field name */
								$postcode_validation_notice = sprintf( __( '%s is not a valid postcode / ZIP.', 'easyReservations' ), '<strong>' . esc_html( $data[ $key ] ) . '</strong>' );
						}
						$errors->add( $key, apply_filters( 'easyreservations_checkout_postcode_validation_notice', $postcode_validation_notice, $country, $data[ $key ] ), array( 'id' => $key ) );
					}
				}

				if ( in_array( 'phone', $format, true ) ) {
					if ( $validate_fieldset && '' !== $data[ $key ] && ! ER_Validation::is_phone( $data[ $key ] ) ) {
						/* translators: %s: phone number */
						$errors->add( $key, sprintf( __( '%s is not a valid phone number.', 'easyReservations' ), '<strong>' . esc_html( $data[ $key ] ) . '</strong>' ), array( 'id' => $key ) );
					}
				}

				if ( in_array( 'email', $format, true ) && '' !== $data[ $key ] ) {
					$email_is_valid = is_email( $data[ $key ] );
					$data[ $key ]   = sanitize_email( $data[ $key ] );

					if ( $validate_fieldset && ! $email_is_valid ) {
						/* translators: %s: email address */
						$errors->add( $key, sprintf( __( '%s is not a valid email address.', 'easyReservations' ), '<strong>' . esc_html( $data[ $key ] ) . '</strong>' ), array( 'id' => $key ) );
						continue;
					}
				}

				if ( '' !== $data[ $key ] && in_array( 'state', $format, true ) ) {
					$country      = isset( $data[ $fieldset_key . '_country' ] ) ? $data[ $fieldset_key . '_country' ] : ER()->customer->get_address_country();
					$valid_states = ER()->countries->get_states( $country );

					if ( ! empty( $valid_states ) && is_array( $valid_states ) && count( $valid_states ) > 0 ) {
						$valid_state_values = array_map( 'er_strtoupper', array_flip( array_map( 'er_strtoupper', $valid_states ) ) );
						$data[ $key ]       = er_strtoupper( $data[ $key ] );

						if ( isset( $valid_state_values[ $data[ $key ] ] ) ) {
							// With this part we consider state value to be valid as well, convert it to the state key for the valid_states check below.
							$data[ $key ] = $valid_state_values[ $data[ $key ] ];
						}

						if ( $validate_fieldset && ! in_array( $data[ $key ], $valid_state_values, true ) ) {
							/* translators: 1: state field 2: valid states */
							$errors->add( $key, sprintf( __( '%1$s is not valid. Please enter one of the following: %2$s', 'easyReservations' ), '<strong>' . esc_html( $field_label ) . '</strong>', implode( ', ', $valid_states ), array( 'id' => $key ) ) );
						}
					}
				}

				if ( $validate_fieldset && $required && '' === $data[ $key ] ) {
					/* translators: %s: field name */
					$errors->add( $key, apply_filters( 'easyreservations_checkout_required_field_notice', sprintf( __( '%s is a required field.', 'easyReservations' ), '<strong>' . esc_html( $field_label ) . '</strong>' ), $field_label ), array( 'id' => $key ) );
				}
			}
		}
	}

	/**
	 * Validates that the checkout has enough info to proceed.
	 *
	 * @param array    $data An array of posted data.
	 * @param WP_Error $errors Validation errors.
	 */
	protected function validate_checkout( &$data, &$errors ) {
		$this->validate_posted_data( $data, $errors );
		$this->check_cart_items();

		if ( empty( $data['easyreservations_checkout_update_totals'] ) && empty( $data['terms'] ) && ! empty( $_POST['terms-field'] ) ) { // WPCS: input var ok, CSRF ok.
			$errors->add( 'terms', __( 'Please read and accept the terms and conditions to proceed with your order.', 'easyReservations' ) );
		}

		if ( !$data['direct_checkout'] && ER()->cart->needs_payment() ) {
			$available_gateways = ER()->payment_gateways()->get_available_payment_gateways();

			if ( ! isset( $available_gateways[ $data['payment_method'] ] ) ) {
				$errors->add( 'payment', __( 'Invalid payment method.', 'easyReservations' ) );
			} else {
				$available_gateways[ $data['payment_method'] ]->validate_fields();
			}
		}

		do_action( 'easyreservations_after_checkout_validation', $data, $errors );
	}

	/**
	 * Set address field for customer.
	 *
	 * @param string $field String to update.
	 * @param string $key Field key.
	 * @param array  $data Array of data to get the value from.
	 */
	protected function set_customer_address_fields( $field, $key, $data ) {
		$value = null;
		if ( isset( $data[ $field ] ) && is_callable( array( ER()->customer, "set_address_{$field}" ) ) ) {
			ER()->customer->{"set_address_{$field}"}( $data[ $field ] );
		}
	}

	/**
	 * Update customer and session data from the posted checkout data.
	 *
	 * @param array $data Posted data.
	 */
	protected function update_session( $data ) {
		$address_fields = array(
			'first_name',
			'last_name',
			'company',
			'email',
			'phone',
			'address_1',
			'address_2',
			'city',
			'postcode',
			'state',
			'country',
		);

		array_walk( $address_fields, array( $this, 'set_customer_address_fields' ), $data );
		ER()->customer->save();

		ER()->session->set( 'chosen_payment_method', $data['payment_method'] );

		// Update cart totals now we have customer address.
		ER()->cart->calculate_totals();
	}

	/**
	 * Process an order that doesn't require payment.
	 *
	 * @param int $order_id Order ID.
	 */
	protected function process_order_without_payment( $order_id ) {
		$order = er_get_order( $order_id );
		$url   = apply_filters( 'easyreservations_checkout_no_payment_needed_redirect', $order->get_checkout_order_received_url(), $order );

		if ( ER()->session ) {
			ER()->session->set( 'order_awaiting_payment', false );
		}

		er_empty_cart();

		$order->update_status( ER_Order_Status::ONHOLD );

		if ( ! is_easyreservations_ajax() ) {
			wp_safe_redirect( $url );
			exit;
		}

		wp_send_json(
			array(
				'result'   => 'success',
				'redirect' => $url,
			)
		);

		exit;
	}

	/**
	 * Create a new customer account if needed.
	 *
	 * @param array $data Posted data.
	 * @param array $customs Custom data.
	 *
	 * @throws Exception When not able to create customer.
	 */
	protected function process_customer( $data, $customs ) {
		$customer_id = apply_filters( 'easyreservations_checkout_customer_id', get_current_user_id() );

		if ( ! is_user_logged_in() && ( er_is_registration_required() || ! empty( $data['createaccount'] ) ) ) {
			$username    = ! empty( $data['account_username'] ) ? $data['account_username'] : '';
			$password    = ! empty( $data['account_password'] ) ? $data['account_password'] : '';
			$customer_id = er_create_new_customer(
				$data['email'],
				$username,
				$password,
				array(
					'first_name' => ! empty( $data['first_name'] ) ? $data['first_name'] : '',
					'last_name'  => ! empty( $data['last_name'] ) ? $data['last_name'] : '',
				)
			);

			if ( is_wp_error( $customer_id ) ) {
				throw new Exception( $customer_id->get_error_message() );
			}

			er_set_customer_auth_cookie( $customer_id );

			// As we are now logged in, checkout will need to refresh to show logged in data.
			ER()->session->set( 'reload_checkout', true );

			// Also, recalculate cart totals to reveal any role-based discounts that were unavailable before registering.
			ER()->cart->calculate_totals();
		}

		// On multisite, ensure user exists on current site, if not add them before allowing login.
		if ( $customer_id && is_multisite() && is_user_logged_in() && ! is_user_member_of_blog() ) {
			add_user_to_blog( get_current_blog_id(), $customer_id, 'easy_customer' );
		}

		// Add customer info from other fields.
		if ( $customer_id && apply_filters( 'easyreservations_checkout_update_customer_data', true, $this ) ) {
			$customer = new ER_Customer( $customer_id );

			if ( ! empty( $data['first_name'] ) && '' === $customer->get_first_name() ) {
				$customer->set_first_name( $data['first_name'] );
			}

			if ( ! empty( $data['last_name'] ) && '' === $customer->get_last_name() ) {
				$customer->set_last_name( $data['first_name'] );
			}

			// If the display name is an email, update to the user's full name.
			if ( is_email( $customer->get_display_name() ) ) {
				$customer->set_display_name( $customer->get_first_name() . ' ' . $customer->get_last_name() );
			}

			foreach ( $data as $key => $value ) {
				// Use setters where available.
				if ( is_callable( array( $customer, "set_address_{$key}" ) ) ) {
					$customer->{"set_address_{$key}"}( $value );
				} elseif ( is_callable( array( $customer, "set_{$key}" ) ) ) {
					$customer->{"set_{$key}"}( $value );
				}
			}

			//Save checkout custom data
			if ( $customs && ! empty( $customs ) ) {
				foreach ( $customs as $custom ) {
					$customer->add_meta_data( $custom['custom_id'], $custom['custom_value'], true );
				}
			}

			/**
			 * Action hook to adjust customer before save.
			 */
			do_action( 'easyreservations_checkout_update_customer', $customer, $data );

			$customer->save();
		}

		do_action( 'easyreservations_checkout_update_user_meta', $customer_id, $data );
	}

	/**
	 * Process the checkout after the confirm order button is pressed.
	 *
	 * @param bool $submit
	 * @param array $cart_items_added Items that got
	 */
	public function process_checkout( $submit = false, $cart_items_added = array() ) {
		try {
			$nonce_value = er_get_var( $_REQUEST['easyreservations-process-checkout-nonce'] ); // @codingStandardsIgnoreLine.

			if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, 'easyreservations-process-checkout' ) ) {
				ER()->session->set( 'refresh_totals', true );
				throw new Exception( __( 'We were unable to process your order, please try again.', 'easyReservations' ) );
			}

			er_maybe_define_constant( 'RESERVATIONS_CHECKOUT', true );
			er_set_time_limit( 0 );

			do_action( 'easyreservations_before_checkout_process' );

			if ( $submit && ER()->cart->is_empty() ) {
				/* translators: %s: shop cart url */
				throw new Exception( sprintf( __( 'Sorry, your session has expired. <a href="%s" class="er-backward">Return to shop</a>', 'easyReservations' ), esc_url( er_get_page_permalink( 'shop' ) ) ) );
			}

			do_action( 'easyreservations_checkout_process' );

			$errors      = new WP_Error();
			$posted_data = $this->get_posted_data();

			// Update session for customer and totals.
			$this->update_session( $posted_data );

			// Validate posted data and cart items before proceeding.
			$this->validate_checkout( $posted_data, $errors );

			$customs = $this->get_form_data_custom( $errors, false, 'checkout' );

			foreach ( $errors->errors as $code => $messages ) {
				$data = $errors->get_error_data( $code );
				foreach ( $messages as $message ) {
					er_add_notice( $message, 'error', $data );
				}
			}

			if ( 0 === er_notice_count( 'error' ) ) {
				if ( ! $submit ) {
					return true;
				}

				$this->process_customer( $posted_data, $customs );
				$order_id = $this->create_order( $posted_data, $customs );

				if ( is_wp_error( $order_id ) ) {
					throw new Exception( $order_id->get_error_message() );
				}

				$order = er_get_order( $order_id );

				if ( ! $order ) {
					throw new Exception( __( 'Unable to create order.', 'easyReservations' ) );
				}

				do_action( 'easyreservations_checkout_order_processed', $order, $posted_data );

				if ( ER()->cart->needs_payment() ) {
					if ( $posted_data['direct_checkout'] ) {
						ER()->payment_gateways()->direct_checkout_redirect( $order );
					} else {
						ER()->payment_gateways()->process_order_payment( $order_id, $posted_data['payment_method'] );
					}
				} else {
					$this->process_order_without_payment( $order_id );
				}
			} else {
				ER()->cart->remove_cart_items( $cart_items_added );
			}
		} catch ( Exception $e ) {
			er_add_notice( $e->getMessage(), 'error' );
			ER()->cart->remove_cart_items( $cart_items_added );
		}

		$this->send_ajax_failure_response();
		exit;
	}

	/**
	 * Gets the value either from POST, or from the customer object. Sets the default values in checkout fields.
	 *
	 * @param string $input Name of the input we want to grab data for. e.g. billing_country.
	 *
	 * @return string The default value.
	 */
	public function get_value( $input ) {
		// If the form was posted, get the posted value. This will only tend to happen when JavaScript is disabled client side.
		if ( ! empty( $_POST[ $input ] ) ) { // WPCS: input var ok, CSRF OK.
			return er_clean( wp_unslash( $_POST[ $input ] ) ); // WPCS: input var ok, CSRF OK.
		}

		// Allow 3rd parties to short circuit the logic and return their own default value.
		$value = apply_filters( 'easyreservations_checkout_get_value', null, $input );

		if ( ! is_null( $value ) ) {
			return $value;
		}

		/**
		 * For logged in customers, pull data from their account rather than the session which may contain incomplete data.
		 */
		$customer_object = false;

		if ( is_user_logged_in() ) {
			// Load customer object, but keep it cached to avoid reloading it multiple times.
			if ( is_null( $this->logged_in_customer ) ) {
				$this->logged_in_customer = new ER_Customer( get_current_user_id(), true );
			}
			$customer_object = $this->logged_in_customer;
		}

		if ( ! $customer_object ) {
			$customer_object = ER()->customer;
		}

		if ( is_callable( array( $customer_object, "get_address_$input" ) ) ) {
			$value = $customer_object->{"get_address_$input"}();
		} elseif ( is_callable( array( $customer_object, "get_$input" ) ) ) {
			$value = $customer_object->{"get_$input"}();
		} elseif ( $customer_object->meta_exists( $input ) ) {
			$value = $customer_object->get_meta( $input, true );
		}

		if ( '' === $value ) {
			$value = null;
		}

		return apply_filters( 'default_checkout_' . $input, $value, $input );
	}
}