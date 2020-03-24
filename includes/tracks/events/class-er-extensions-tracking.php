<?php
/**
 * easyReservations Extensions Tracking
 *
 * @package easyReservations\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of the easyReservations Extensions page.
 */
class ER_Extensions_Tracking {
	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'load-easyreservations_page_er-addons', array( $this, 'track_extensions_page' ) );
		add_action( 'easyreservations_helper_connect_start', array( $this, 'track_helper_connection_start' ) );
		add_action( 'easyreservations_helper_denied', array( $this, 'track_helper_connection_cancelled' ) );
		add_action( 'easyreservations_helper_connected', array( $this, 'track_helper_connection_complete' ) );
		add_action( 'easyreservations_helper_disconnected', array( $this, 'track_helper_disconnected' ) );
		add_action( 'easyreservations_helper_subscriptions_refresh', array( $this, 'track_helper_subscriptions_refresh' ) );
	}

	/**
	 * Send a Tracks event when an Extensions page is viewed.
	 */
	public function track_extensions_page() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$event      = 'extensions_view';
		$properties = array(
			'section' => empty( $_REQUEST['section'] ) ? '_featured' : er_clean( wp_unslash( $_REQUEST['section'] ) ),
		);

		if ( ! empty( $_REQUEST['search'] ) ) {
			$event                     = 'extensions_view_search';
			$properties['search_term'] = er_clean( wp_unslash( $_REQUEST['search'] ) );
		}
		// phpcs:enable

		ER_Tracks::record_event( $event, $properties );
	}

	/**
	 * Send a Tracks even when a Helper connection process is initiated.
	 */
	public function track_helper_connection_start() {
		ER_Tracks::record_event( 'extensions_subscriptions_connect' );
	}

	/**
	 * Send a Tracks even when a Helper connection process is cancelled.
	 */
	public function track_helper_connection_cancelled() {
		ER_Tracks::record_event( 'extensions_subscriptions_cancelled' );
	}

	/**
	 * Send a Tracks even when a Helper connection process completed successfully.
	 */
	public function track_helper_connection_complete() {
		ER_Tracks::record_event( 'extensions_subscriptions_connected' );
	}

	/**
	 * Send a Tracks even when a Helper has been disconnected.
	 */
	public function track_helper_disconnected() {
		ER_Tracks::record_event( 'extensions_subscriptions_disconnect' );
	}

	/**
	 * Send a Tracks even when Helper subscriptions are refreshed.
	 */
	public function track_helper_subscriptions_refresh() {
		ER_Tracks::record_event( 'extensions_subscriptions_update' );
	}
}
