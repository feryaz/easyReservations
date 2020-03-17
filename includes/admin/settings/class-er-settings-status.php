<?php
defined( 'ABSPATH' ) || exit;

/**
 * ER_Settings_General.
 */
class ER_Settings_Status extends ER_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'status';
		$this->label = __( 'Status', 'easyReservations' );

		parent::__construct();
	}

	public function get_sections() {
		global $current_section;

		if ( empty( $current_section ) ) {
			$current_section = 'status';
		}

		return apply_filters( 'easyreservations_admin_status_sections', array(
			'status'    => __( 'System status', 'easyReservations' ),
			'changelog' => __( 'Changelog', 'easyReservations' ),
			'logs'      => __( 'Logs', 'easyReservations' ),
		) );
	}

	public function output() {
		global $current_section, $hide_save_button;
		$hide_save_button = 'yes';

		$sections = $this->get_sections();

		$headline = isset( $sections[ $current_section ] ) ? $sections[ $current_section ] : __( 'System status', 'easyReservations' );

		include 'views/html-admin-settings-status.php';
	}
}

return new ER_Settings_Status();
