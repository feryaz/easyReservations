<?php
/**
 * easyReservations Settings Tracking
 *
 * @package easyReservations\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of easyReservations Settings.
 */
class ER_Settings_Tracking {
	/**
	 * Whitelisted easyReservations settings to potentially track updates for.
	 *
	 * @var array
	 */
	protected $whitelist = array();

	/**
	 * easyReservations settings that have been updated (and will be tracked).
	 *
	 * @var array
	 */
	protected $updated_options = array();

	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'easyreservations_settings_page_init', array( $this, 'track_settings_page_view' ) );
		add_action( 'easyreservations_update_option', array( $this, 'add_option_to_whitelist' ) );
		add_action( 'easyreservations_update_options', array( $this, 'send_settings_change_event' ) );
	}

	/**
	 * Add a easyReservations option name to our whitelist and attach
	 * the `update_option` hook. Rather than inspecting every updated
	 * option and pattern matching for "easyreservations", just build a dynamic
	 * whitelist for easyReservations options that might get updated.
	 *
	 * See `easyreservations_update_option` hook.
	 *
	 * @param array $option easyReservations option (config) that might get updated.
	 */
	public function add_option_to_whitelist( $option ) {
		$this->whitelist[] = $option['id'];

		// Delay attaching this action since it could get fired a lot.
		if ( false === has_action( 'update_option', array( $this, 'track_setting_change' ) ) ) {
			add_action( 'update_option', array( $this, 'track_setting_change' ), 10, 3 );
		}
	}

	/**
	 * Add easyReservations option to a list of updated options.
	 *
	 * @param string $option_name Option being updated.
	 * @param mixed  $old_value Old value of option.
	 * @param mixed  $new_value New value of option.
	 */
	public function track_setting_change( $option_name, $old_value, $new_value ) {
		// Make sure this is a easyReservations option.
		if ( ! in_array( $option_name, $this->whitelist, true ) ) {
			return;
		}

		// Check to make sure the new value is truly different.
		// `easyreservations_price_num_decimals` tends to trigger this
		// because form values aren't coerced (e.g. '2' vs. 2).
		if (
			is_scalar( $old_value ) &&
			is_scalar( $new_value ) &&
			(string) $old_value === (string) $new_value
		) {
			return;
		}

		$this->updated_options[] = $option_name;
	}

	/**
	 * Send a Tracks event for easyReservations options that changed values.
	 */
	public function send_settings_change_event() {
		global $current_tab;

		if ( empty( $this->updated_options ) ) {
			return;
		}

		$properties = array(
			'settings' => implode( ',', $this->updated_options ),
		);

		if ( isset( $current_tab ) ) {
			$properties['tab'] = $current_tab;
		}

		ER_Tracks::record_event( 'settings_change', $properties );
	}

	/**
	 * Send a Tracks event for easyReservations settings page views.
	 */
	public function track_settings_page_view() {
		global $current_tab, $current_section;

		$properties = array(
			'tab'     => $current_tab,
			'section' => empty( $current_section ) ? null : $current_section,
		);

		ER_Tracks::record_event( 'settings_view', $properties );
	}
}
