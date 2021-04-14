<?php
/**
 * easyReservations Status Tracking
 *
 * @package easyReservations\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of easyReservations Orders.
 */
class ER_Status_Tracking {
	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'track_status_view' ), 10 );
	}

	/**
	 * Add Tracks events to the status page.
	 */
	public function track_status_view() {
		if ( isset( $_GET['page'] ) && 'er-status' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {

			$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'status';

			ER_Tracks::record_event(
				'status_view',
				array(
					'tab'       => $tab,
					'tool_used' => isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : null,
				)
			);

			if ( 'status' === $tab ) {
				er_enqueue_js(
					"
					$( 'a.debug-report' ).on( 'click', function() {
						window.erTracks.recordEvent( 'status_view_reports' );
					} );
				"
				);
			}
		}
	}
}
