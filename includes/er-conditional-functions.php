<?php
defined( 'ABSPATH' ) || exit;

/**
 * If is shop or resource page
 *
 * @return bool
 */
function is_easyreservations() {
	return apply_filters( 'is_easyreservations', is_easyreservations_shop() || is_easyreservations_resource() );
}

/**
 * Is_shop - Returns true when viewing the resource type archive (shop).
 *
 * @return bool
 */
function is_easyreservations_shop() {
	return ( is_post_type_archive( 'easy-rooms' ) || is_page( er_get_page_id( 'shop' ) ) );
}

/**
 * is_easyreservations_resource_category - Returns true when viewing a resource category.
 *
 * @param string $term (default: '') The term slug your checking for. Leave blank to return true on any.
 *
 * @return bool
 */
function is_easyreservations_resource_category( $term = '' ) {
	return is_tax( 'easy_resource_cat', $term );
}

/**
 * is_easyreservations_resource_tag - Returns true when viewing a resource tag.
 *
 * @param string $term (default: '') The term slug your checking for. Leave blank to return true on any.
 *
 * @return bool
 */
function is_easyreservations_resource_tag( $term = '' ) {
	return is_tax( 'easy_resource_tag', $term );
}

/**
 * is_easyreservations_resource - Returns true when viewing a single resource.
 *
 * @return bool
 */
function is_easyreservations_resource() {
	return is_singular( array( 'easy-rooms' ) );
}

/**
 * is_easyreservations_ajax - Returns true when the page is loaded via ajax.
 *
 * @return bool
 */
function is_easyreservations_ajax() {
	return function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' );
}

/**
 * is_easyreservations_order_received_page - Returns true when viewing the order received page.
 *
 * @return bool
 */
function is_easyreservations_order_received_page() {
	global $wp;

	$page_id = er_get_page_id( 'checkout' );

	return apply_filters( 'easyreservations_is_order_received_page', ( $page_id && is_page( $page_id ) && isset( $wp->query_vars['order-received'] ) ) );
}

/**
 * Is_add_payment_method_page - Returns true when viewing the add payment method page.
 *
 * @return bool
 */
function is_easyreservations_add_payment_method_page() {
	global $wp;

	$page_id = er_get_page_id( 'myaccount' );

	return ( $page_id && is_page( $page_id ) && ( isset( $wp->query_vars['payment-methods'] ) || isset( $wp->query_vars['add-payment-method'] ) ) );
}

/**
 * is_easyreservations_password_page - Returns true when viewing the lost password page.
 *
 * @return bool
 */
function is_easyreservations_lost_password_page() {
	global $wp;

	$page_id = er_get_page_id( 'myaccount' );

	return ( $page_id && is_page( $page_id ) && isset( $wp->query_vars['lost-password'] ) );
}

/**
 * Is_cart - Returns true when viewing the cart page.
 *
 * @return bool
 */
function is_easyreservations_cart() {
	$page_id = er_get_page_id( 'cart' );

	return ( $page_id && is_page( $page_id ) ) || defined( 'EASYRESERVATIONS_CART' ) || er_post_content_has_shortcode( 'easy_cart' );
}

/**
 * Is_checkout - Returns true when viewing the checkout page.
 *
 * @return bool
 */
function is_easyreservations_checkout() {
	$page_id = er_get_page_id( 'checkout' );

	return ( $page_id && is_page( $page_id ) ) || er_post_content_has_shortcode( 'easy_checkout' ) || apply_filters( 'easyreservations_is_checkout', false ) || defined( 'RESERVATIONS_CHECKOUT' );
}

/**
 * Is_search - Returns true when viewing the checkout page.
 *
 * @return bool
 */
function is_easyreservations_search() {
	return (er_post_content_has_shortcode( 'easy_search' ) || apply_filters( 'easyreservations_is_search', false ) );
}

/**
 * Is_account_page - Returns true when viewing an account page.
 *
 * @return bool
 */
function is_easyreservations_account_page() {
	$page_id = er_get_page_id( 'myaccount' );

	return ( $page_id && is_page( $page_id ) ) || er_post_content_has_shortcode( 'easy_my_account' ) || apply_filters( 'easyreservations_is_account_page', false );
}

/**
 * Is_checkout_pay - Returns true when viewing the checkout's pay page.
 *
 * @return bool
 */
function is_easyreservations_checkout_pay_page() {
	global $wp;

	return is_easyreservations_checkout() && ! empty( $wp->query_vars['order-payment'] );
}

/**
 * Is_er_endpoint_url - Check if an endpoint is showing.
 *
 * @param string|false $endpoint Whether endpoint.
 *
 * @return bool
 */
function is_er_endpoint_url( $endpoint = false ) {
	global $wp;

	$er_endpoints = ER()->query->get_query_vars();

	if ( false !== $endpoint ) {
		if ( ! isset( $er_endpoints[ $endpoint ] ) ) {
			return false;
		} else {
			$endpoint_var = $er_endpoints[ $endpoint ];
		}

		return isset( $wp->query_vars[ $endpoint_var ] );
	} else {
		foreach ( $er_endpoints as $key => $value ) {
			if ( isset( $wp->query_vars[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}
}

/**
 * Simple check for validating a URL, it must start with http:// or https://.
 * and pass FILTER_VALIDATE_URL validation.
 *
 * @param string $url to check.
 *
 * @return bool
 */
function er_is_valid_url( $url ) {

	// Must start with http:// or https://.
	if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) ) {
		return false;
	}

	// Must pass validation.
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return false;
	}

	return true;
}

/**
 * Check if the home URL is https. If it is, we don't need to do things such as 'force ssl'.
 *
 * @return bool
 */
function er_site_is_https() {
	return false !== strstr( get_option( 'home' ), 'https:' );
}

/**
 * Check if the checkout is configured for https. Look at options, WP HTTPS plugin, or the permalink itself.
 *
 * @return bool
 */
function er_checkout_is_https() {
	return er_site_is_https() || 'yes' === get_option( 'reservations_force_ssl_checkout' ) || class_exists( 'WordPressHTTPS' ) || strstr( er_get_page_permalink( 'checkout' ), 'https:' );
}

/**
 * Checks whether the content passed contains a specific short code.
 *
 * @param string $tag Shortcode tag to check.
 *
 * @return bool
 */
function er_post_content_has_shortcode( $tag = '' ) {
	global $post;

	return is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
}
