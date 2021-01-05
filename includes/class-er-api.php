<?php
/**
 * ER-API endpoint handler.
 *
 * @package easyReservations/API
 */

defined( 'ABSPATH' ) || exit;

class ER_API {
	/**
	 * Init the API by setting up action and filter hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );
		//add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'handle_api_requests' ), 0 );
	}

	/**
	 * ER API for payment gateway IPNs, etc.
	 */
	public static function add_endpoint() {
		add_rewrite_endpoint( 'er-api', EP_ALL );
	}

	/**
	 * API request - Trigger any API requests.
	 */
	public function handle_api_requests() {
		global $wp;

		if ( ! empty( $_GET['er-api'] ) ) { // WPCS: input var okay, CSRF ok.
			$wp->query_vars['er-api'] = sanitize_key( wp_unslash( $_GET['er-api'] ) ); // WPCS: input var okay, CSRF ok.
		}

		// er-api endpoint requests.
		if ( ! empty( $wp->query_vars['er-api'] ) ) {
			// Buffer, we won't want any output here.
			ob_start();

			// No cache headers.
			nocache_headers();

			// Clean the API request.
			$api_request = strtolower( er_clean( $wp->query_vars['er-api'] ) );

			// Make sure gateways are available for request.
			ER()->payment_gateways();

			// Trigger generic action before request hook.
			do_action( 'easyreservations_api_request', $api_request );

			// Is there actually something hooked into this API request? If not trigger 400 - Bad request.
			status_header( has_action( 'easyreservations_api_' . $api_request ) ? 200 : 400 );

			// Trigger an action which plugins can hook into to fulfill the request.
			do_action( 'easyreservations_api_' . $api_request );

			// Done, clear buffer and exit.
			ob_end_clean();

			die( '-1' );
		}
	}
}
