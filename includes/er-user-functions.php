<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checks if a user has a certain capability.
 *
 * @param array $allcaps All capabilities.
 * @param array $caps Capabilities.
 * @param array $args Arguments.
 *
 * @return array The filtered array of all capabilities.
 */
function er_customer_has_capability( $allcaps, $caps, $args ) {
	if ( isset( $caps[0] ) ) {
		switch ( $caps[0] ) {
			case 'easy_view_order':
				$user_id = intval( $args[1] );
				$order   = er_get_order( $args[2] );

				if ( $order && $user_id === $order->get_user_id() ) {
					$allcaps['easy_view_order'] = true;
				}
				break;
			case 'easy_pay_for_order':
				$user_id  = intval( $args[1] );
				$order_id = isset( $args[2] ) ? $args[2] : null;

				// When no order ID, we assume it's a new order
				// and thus, customer can pay for it.
				if ( ! $order_id ) {
					$allcaps['easy_pay_for_order'] = true;
					break;
				}

				$order = er_get_order( $order_id );

				if ( $order && ( $user_id === $order->get_user_id() || ! $order->get_user_id() ) ) {
					$allcaps['easy_pay_for_order'] = true;
				}
				break;
			case 'easy_cancel_order':
				$user_id = intval( $args[1] );
				$order   = er_get_order( $args[2] );

				if ( $order && $user_id === $order->get_user_id() ) {
					$allcaps['easy_cancel_order'] = true;
				}
				break;
		}
	}

	return $allcaps;
}

add_filter( 'user_has_cap', 'er_customer_has_capability', 10, 3 );

/**
 * Create a new customer.
 *
 * @param string $email Customer email.
 * @param string $username Customer username.
 * @param string $password Customer password.
 * @param array  $args List of arguments to pass to `wp_insert_user()`.
 *
 * @return int|WP_Error Returns WP_Error on failure, Int (user ID) on success.
 */
function er_create_new_customer( $email, $username = '', $password = '', $args = array() ) {
	if ( empty( $email ) || ! is_email( $email ) ) {
		return new WP_Error( 'registration-error-invalid-email', __( 'Please provide a valid email address.', 'easyReservations' ) );
	}

	if ( email_exists( $email ) ) {
		return new WP_Error( 'registration-error-email-exists', apply_filters( 'easyreservations_registration_error_email_exists', __( 'An account is already registered with your email address. <a href="#" class="showlogin">Please log in</a>.', 'easyReservations' ), $email ) );
	}

	if ( 'yes' === get_option( 'reservations_registration_generate_username', 'yes' ) && empty( $username ) ) {
		$username = er_create_new_customer_username( $email, $args );
	}

	$username = sanitize_user( $username );

	if ( empty( $username ) || ! validate_username( $username ) ) {
		return new WP_Error( 'registration-error-invalid-username', __( 'Please enter a valid account username.', 'easyReservations' ) );
	}

	if ( username_exists( $username ) ) {
		return new WP_Error( 'registration-error-username-exists', __( 'An account is already registered with that username. Please choose another.', 'easyReservations' ) );
	}

	// Handle password creation.
	$password_generated = false;
	if ( 'yes' === get_option( 'reservations_registration_generate_password' ) && empty( $password ) ) {
		$password           = wp_generate_password();
		$password_generated = true;
	}

	if ( empty( $password ) ) {
		return new WP_Error( 'registration-error-missing-password', __( 'Please enter an account password.', 'easyReservations' ) );
	}

	// Use WP_Error to handle registration errors.
	$errors = new WP_Error();

	do_action( 'easyreservations_register_post', $username, $email, $errors );

	$errors = apply_filters( 'easyreservations_registration_errors', $errors, $username, $email );

	if ( $errors->get_error_code() ) {
		return $errors;
	}

	$new_customer_data = apply_filters(
		'easyreservations_new_customer_data',
		array_merge(
			$args,
			array(
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => $email,
				'role'       => 'easy_customer',
			)
		)
	);

	$customer_id = wp_insert_user( $new_customer_data );

	if ( is_wp_error( $customer_id ) ) {
		return $customer_id;
	}

	do_action( 'easyreservations_created_customer', $customer_id, $new_customer_data, $password_generated );

	return $customer_id;
}

/**
 * Create a unique username for a new customer.
 *
 * @param string $email New customer email address.
 * @param array  $new_user_args Array of new user args, maybe including first and last names.
 * @param string $suffix Append string to username to make it unique.
 *
 * @return string Generated username.
 */
function er_create_new_customer_username( $email, $new_user_args = array(), $suffix = '' ) {
	$username_parts = array();

	if ( isset( $new_user_args['first_name'] ) ) {
		$username_parts[] = sanitize_user( $new_user_args['first_name'], true );
	}

	if ( isset( $new_user_args['last_name'] ) ) {
		$username_parts[] = sanitize_user( $new_user_args['last_name'], true );
	}

	// Remove empty parts.
	$username_parts = array_filter( $username_parts );

	// If there are no parts, e.g. name had unicode chars, or was not provided, fallback to email.
	if ( empty( $username_parts ) ) {
		$email_parts    = explode( '@', $email );
		$email_username = $email_parts[0];

		// Exclude common prefixes.
		if ( in_array(
			$email_username,
			array(
				'sales',
				'hello',
				'mail',
				'contact',
				'info',
			),
			true
		) ) {
			// Get the domain part.
			$email_username = $email_parts[1];
		}

		$username_parts[] = sanitize_user( $email_username, true );
	}

	$username = er_strtolower( implode( '.', $username_parts ) );

	if ( $suffix ) {
		$username .= $suffix;
	}

	/**
	 * WordPress 4.4 - filters the list of blacklisted usernames.
	 *
	 * @param array $usernames Array of blacklisted usernames.
	 */
	$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );

	// Stop illegal logins and generate a new random username.
	if ( in_array( strtolower( $username ), array_map( 'strtolower', $illegal_logins ), true ) ) {
		$new_args = array();

		/**
		 * Filter generated customer username.
		 *
		 * @param string $username Generated username.
		 * @param string $email New customer email address.
		 * @param array  $new_user_args Array of new user args, maybe including first and last names.
		 * @param string $suffix Append string to username to make it unique.
		 */
		$new_args['first_name'] = apply_filters(
			'easyreservations_generated_customer_username',
			'er_user_' . zeroise( wp_rand( 0, 9999 ), 4 ),
			$email,
			$new_user_args,
			$suffix
		);

		return er_create_new_customer_username( $email, $new_args, $suffix );
	}

	if ( username_exists( $username ) ) {
		// Generate something unique to append to the username in case of a conflict with another user.
		$suffix = '-' . zeroise( wp_rand( 0, 9999 ), 4 );

		return er_create_new_customer_username( $email, $new_user_args, $suffix );
	}

	/**
	 * Filter new customer username.
	 *
	 * @param string $username Customer username.
	 * @param string $email New customer email address.
	 * @param array  $new_user_args Array of new user args, maybe including first and last names.
	 * @param string $suffix Append string to username to make it unique.
	 */
	return apply_filters( 'easyreservations_new_customer_username', $username, $email, $new_user_args, $suffix );
}

/**
 * Login a customer (set auth cookie and set global user object).
 *
 * @param int $customer_id Customer ID.
 */
function er_set_customer_auth_cookie( $customer_id ) {
	wp_set_current_user( $customer_id );
	wp_set_auth_cookie( $customer_id, true );

	// Update session.
	ER()->session->init_session_cookie();
}

/**
 * Get logout endpoint.
 *
 * @param string $redirect Redirect URL.
 *
 * @return string
 */
function er_logout_url( $redirect = '' ) {
	$redirect = $redirect ? $redirect : er_get_page_permalink( 'myaccount' );

	if ( get_option( 'reservations_logout_endpoint' ) ) {
		return wp_nonce_url( er_get_endpoint_url( 'customer-logout', '', $redirect ), 'customer-logout' );
	}

	return wp_logout_url( $redirect );
}

/**
 * Get account endpoint URL.
 *
 * @param string $endpoint Endpoint.
 *
 * @return string
 */
function er_get_account_endpoint_url( $endpoint ) {
	if ( 'dashboard' === $endpoint ) {
		return er_get_page_permalink( 'myaccount' );
	}

	if ( 'customer-logout' === $endpoint ) {
		return er_logout_url();
	}

	return er_get_endpoint_url( $endpoint, '', er_get_page_permalink( 'myaccount' ) );
}

/**
 * Update when the user was last active.
 */
function er_current_user_is_active() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	er_update_user_last_active( get_current_user_id() );
}

add_action( 'wp', 'er_current_user_is_active', 10 );

/**
 * Set the user last active timestamp to now.
 *
 * @param int $user_id User ID to mark active.
 */
function er_update_user_last_active( $user_id ) {
	if ( ! $user_id ) {
		return;
	}
	update_user_meta( $user_id, 'er_last_active', (string) strtotime( date( 'Y-m-d', time() ) ) );
}

/**
 * Hooks into the `profile_update` hook to set the user last updated timestamp.
 *
 * @param int   $user_id The user that was updated.
 * @param array $old The profile fields pre-change.
 */
function er_update_profile_last_update_time( $user_id, $old ) {
	er_set_user_last_update_time( $user_id );
}

add_action( 'profile_update', 'er_update_profile_last_update_time', 10, 2 );

/**
 * Hooks into the update user meta function to set the user last updated timestamp.
 *
 * @param int    $meta_id ID of the meta object that was changed.
 * @param int    $user_id The user that was updated.
 * @param string $meta_key Name of the meta key that was changed.
 * @param string $_meta_value Value of the meta that was changed.
 */
function er_meta_update_last_update_time( $meta_id, $user_id, $meta_key, $_meta_value ) {
	$keys_to_track = apply_filters( 'easyreservations_user_last_update_fields', array( 'first_name', 'last_name' ) );

	$update_time = in_array( $meta_key, $keys_to_track, true ) ? true : false;
	$update_time = 'address_' === substr( $meta_key, 0, 8 ) ? true : $update_time;

	if ( $update_time ) {
		er_set_user_last_update_time( $user_id );
	}
}

add_action( 'update_user_meta', 'er_meta_update_last_update_time', 10, 4 );

/**
 * Sets a user's "last update" time to the current timestamp.
 *
 * @param int $user_id The user to set a timestamp for.
 */
function er_set_user_last_update_time( $user_id ) {
	update_user_meta( $user_id, 'er_last_update', gmdate( 'U' ) );
}

/**
 * Get customer saved payment methods list.
 *
 * @param int $customer_id Customer ID.
 *
 * @return array
 */
function er_get_customer_saved_methods_list( $customer_id ) {
	return apply_filters( 'easyreservations_saved_payment_methods_list', array(), $customer_id );
}
