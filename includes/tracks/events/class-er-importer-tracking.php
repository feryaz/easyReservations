<?php
/**
 * easyReservations Import Tracking
 *
 * @package easyReservations\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of easyReservations Imports.
 */
class ER_Importer_Tracking {
	/**
	 * Init tracking.
	 */
	public function init() {
		//TODO update action name when we implement importer
		add_action( 'resource_page_resource_importer', array( $this, 'track_resource_importer' ) );
	}

	/**
	 * Route resource importer action to the right callback.
	 *
	 * @return void
	 */
	public function track_resource_importer() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['step'] ) ) {
			return;
		}

		if ( 'import' === $_REQUEST['step'] ) {
			return $this->track_resource_importer_start();
		}

		if ( 'done' === $_REQUEST['step'] ) {
			return $this->track_resource_importer_complete();
		}
		// phpcs:enable
	}

	/**
	 * Send a Tracks event when the resource importer is started.
	 *
	 * @return void
	 */
	public function track_resource_importer_start() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['file'] ) || ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		$properties = array(
			'update_existing' => isset( $_REQUEST['update_existing'] ) ? (bool) $_REQUEST['update_existing'] : false,
			'delimiter'       => empty( $_REQUEST['delimiter'] ) ? ',' : er_clean( wp_unslash( $_REQUEST['delimiter'] ) ),
		);
		// phpcs:enable

		ER_Tracks::record_event( 'resource_import_start', $properties );
	}

	/**
	 * Send a Tracks event when the resource importer has finished.
	 *
	 * @return void
	 */
	public function track_resource_importer_complete() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['nonce'] ) ) {
			return;
		}

		$properties = array(
			'imported' => isset( $_GET['resources-imported'] ) ? absint( $_GET['resources-imported'] ) : 0,
			'updated'  => isset( $_GET['resources-updated'] ) ? absint( $_GET['resources-updated'] ) : 0,
			'failed'   => isset( $_GET['resources-failed'] ) ? absint( $_GET['resources-failed'] ) : 0,
			'skipped'  => isset( $_GET['resources-skipped'] ) ? absint( $_GET['resources-skipped'] ) : 0,
		);
		// phpcs:enable

		ER_Tracks::record_event( 'resource_import_complete', $properties );
	}
}
