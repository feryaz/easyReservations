<?php

/**
 * Retrieve page ids - used for myaccount, edit_address, shop, cart, checkout, pay, view_order, terms. returns -1 if no page is found.
 *
 * @param string $page Page slug.
 *
 * @return int
 */
function er_get_page_id( $page ) {
	$page = apply_filters( 'easyreservations_get_' . $page . '_page_id', get_option( 'reservations_' . $page . '_page_id' ) );

	return $page ? absint( $page ) : - 1;
}

/**
 * Retrieve page permalink.
 *
 * @param string      $page page slug.
 * @param string|bool $fallback Fallback URL if page is not set. Defaults to home URL.
 *
 * @return string
 */
function er_get_page_permalink( $page, $fallback = null ) {
	$page_id   = er_get_page_id( $page );
	$permalink = 0 < $page_id ? get_permalink( $page_id ) : '';

	if ( ! $permalink ) {
		$permalink = is_null( $fallback ) ? get_home_url() : $fallback;
	}

	return apply_filters( 'easyreservations_get_' . $page . '_page_permalink', $permalink );
}

/**
 * Get endpoint URL.
 *
 * Gets the URL for an endpoint, which varies depending on permalink settings.
 *
 * @param string $endpoint Endpoint slug.
 * @param string $value Query param value.
 * @param string $permalink Permalink.
 *
 * @return string
 */
function er_get_endpoint_url( $endpoint, $value = '', $permalink = '' ) {
	if ( ! $permalink ) {
		$permalink = get_permalink();
	}

	// Map endpoint to options.
	$query_vars = ER()->query->get_query_vars();
	$endpoint   = ! empty( $query_vars[ $endpoint ] ) ? $query_vars[ $endpoint ] : $endpoint;

	if ( get_option( 'permalink_structure' ) ) {
		if ( strstr( $permalink, '?' ) ) {
			$query_string = '?' . wp_parse_url( $permalink, PHP_URL_QUERY );
			$permalink    = current( explode( '?', $permalink ) );
		} else {
			$query_string = '';
		}

		$url = trailingslashit( $permalink );

		if ( $value ) {
			$url .= trailingslashit( $endpoint ) . user_trailingslashit( $value );
		} else {
			$url .= user_trailingslashit( $endpoint );
		}

		$url .= $query_string;
	} else {
		$url = add_query_arg( $endpoint, $value, $permalink );
	}

	return apply_filters( 'easyreservations_get_endpoint_url', $url, $endpoint, $value, $permalink );
}

/**
 * Gets the url to the cart page.
 *
 * @return string Url to cart page
 */
function er_get_cart_url() {
	return apply_filters( 'easyreservations_get_cart_url', er_get_page_permalink( 'cart' ) );
}

/**
 * Gets the url to the checkout page.
 *
 * @return string Url to checkout page
 */
function er_get_checkout_url() {
	$checkout_url = er_get_page_permalink( 'checkout' );
	if ( $checkout_url ) {
		// Force SSL if needed.
		if ( is_ssl() || 'yes' === get_option( 'reservations_force_ssl_checkout' ) ) {
			$checkout_url = str_replace( 'http:', 'https:', $checkout_url );
		}
	}

	return apply_filters( 'easyreservations_get_checkout_url', $checkout_url );
}

/**
 * Gets the url to remove an item from the cart.
 *
 * @param string $cart_item_key contains the id of the cart item.
 *
 * @return string url to page
 */
function er_get_cart_remove_url( $cart_item_key ) {
	$cart_page_url = er_get_cart_url();

	return apply_filters( 'easyreservations_get_remove_url', $cart_page_url ? wp_nonce_url( add_query_arg( 'remove_item', $cart_item_key, $cart_page_url ), 'easyreservations-cart' ) : '' );
}

/**
 * Gets the url to re-add an item into the cart.
 *
 * @param string $cart_item_key Cart item key to undo.
 *
 * @return string url to page
 */
function er_get_cart_undo_url( $cart_item_key ) {
	$cart_page_url = er_get_cart_url();

	$query_args = array(
		'undo_item' => $cart_item_key,
	);

	return apply_filters( 'easyreservations_get_undo_url', $cart_page_url ? wp_nonce_url( add_query_arg( $query_args, $cart_page_url ), 'easyreservations-cart' ) : '', $cart_item_key );
}