<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Admin_Settings {
	/**
	 * Setting pages.
	 *
	 * @var array
	 */
	private static $settings = array();

	/**
	 * Include the settings page classes.
	 */
	public static function get_settings_pages() {
		if ( empty( self::$settings ) ) {
			$settings = array();

			include_once dirname( __FILE__ ) . '/settings/class-er-settings-page.php';

			$settings[] = include 'settings/class-er-settings-general.php';
			$settings[] = include 'settings/class-er-settings-form.php';
			$settings[] = include 'settings/class-er-settings-custom.php';
			$settings[] = include 'settings/class-er-settings-tax.php';
			$settings[] = include 'settings/class-er-settings-emails.php';

			$settings = apply_filters( 'easyreservations_get_settings_pages', $settings );

			$settings[] = include 'settings/class-er-settings-accounts.php';
			$settings[] = include 'settings/class-er-settings-advanced.php';
			$settings[] = include 'settings/class-er-settings-status.php';

			self::$settings = $settings;
		}

		return self::$settings;
	}

	/**
	 * Output settings page
	 */
	public static function output() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		do_action( 'easyreservations_settings_start' );

		if ( ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_script( 'easyreservations_settings', ER()->plugin_url() . '/assets/js/admin/settings' . $suffix . '.js', array(
			'jquery',
			'wp-util',
			'jquery-ui-sortable',
			'iris',
		), ER()->version, true );

		$current_tab = 'general';
		if ( isset( $_GET['tab'] ) ) {
			$current_tab = sanitize_key( $_GET['tab'] );
		}

		self::get_settings_pages();

		do_action( 'easyreservations_settings_start' );

		$tabs = apply_filters( 'easyreservations_settings_tabs_array', array() );

		include dirname( __FILE__ ) . '/views/html-admin-settings.php';

		switch ( $current_tab ) {
			case 'general':
				break;
			case 'form':
				break;
			case 'custom':
				break;
			case 'about':
				include 'views/html-admin-settings-about.php';
				break;
			default:
				do_action( 'er_set_add', $current_tab );
				break;
		}
	}

	/**
	 * Save the settings.
	 */
	public static function save( $current_tab ) {

		check_admin_referer( 'easyreservations-settings' );

		// Trigger actions.
		do_action( 'easyreservations_settings_save_' . $current_tab );
		do_action( 'easyreservations_update_options_' . $current_tab );
		do_action( 'easyreservations_update_options' );

		ER_Admin_Notices::add_temporary_success( __( 'Your settings have been saved.', 'easyReservations' ) );

		// Clear any unwanted data and flush rules.
		update_option( 'reservations_queue_flush_rewrite_rules', 'yes' );
		ER()->query->init_query_vars();
		ER()->query->add_endpoints();

		do_action( 'easyreservations_settings_saved' );
	}

	/**
	 * Get a setting from the settings API.
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	public static function get_option( $option_name, $default = '' ) {
		// Array value.
		if ( strstr( $option_name, '[' ) ) {

			parse_str( $option_name, $option_array );

			// Option name is first key.
			$option_name = current( array_keys( $option_array ) );

			// Get value.
			$option_values = get_option( $option_name, '' );

			$key = key( $option_array[ $option_name ] );

			if ( isset( $option_values[ $key ] ) ) {
				$option_value = $option_values[ $key ];
			} else {
				$option_value = null;
			}
		} else {
			// Single value.
			$option_value = get_option( $option_name, null );
		}

		if ( is_array( $option_value ) ) {
			$option_value = wp_unslash( $option_value );
		} elseif ( ! is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		return ( null === $option_value ) ? $default : $option_value;
	}

	/**
	 * Save admin fields.
	 *
	 * Loops through the easyReservations options array and outputs each field.
	 *
	 * @param array $options Options array to output.
	 * @param array $data Optional. Data to use for saving. Defaults to $_POST.
	 *
	 * @return bool
	 */
	public static function save_fields( $options, $data = null ) {
		if ( is_null( $data ) ) {
			$data = $_POST; // WPCS: input var okay, CSRF ok.
		}
		if ( empty( $data ) ) {
			return false;
		}

		// Options to update will be stored here and saved later.
		$update_options   = array();
		$autoload_options = array();

		// Loop options and get values to save.
		foreach ( $options as $option ) {
			if ( ! isset( $option['id'] ) || ! isset( $option['type'] ) ) {
				continue;
			}

			// Get posted value.
			if ( strstr( $option['id'], '[' ) ) {
				parse_str( $option['id'], $option_name_array );
				$option_name  = current( array_keys( $option_name_array ) );
				$setting_name = key( $option_name_array[ $option_name ] );
				$raw_value    = isset( $data[ $option_name ][ $setting_name ] ) ? wp_unslash( $data[ $option_name ][ $setting_name ] ) : null;
			} else {
				$option_name  = $option['id'];
				$setting_name = '';
				$raw_value    = isset( $data[ $option['id'] ] ) ? wp_unslash( $data[ $option['id'] ] ) : null;
			}

			// Format the value based on option type.
			switch ( $option['type'] ) {
				case 'checkbox':
					$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
					break;
				case 'textarea':
					$value = wp_kses_post( trim( $raw_value ) );
					break;
				case 'multiselect':
				case 'multi_select_countries':
					$value = array_filter( array_map( 'er_clean', (array) $raw_value ) );
					break;
				case 'image_width':
					$value = array();
					if ( isset( $raw_value['width'] ) ) {
						$value['width']  = er_clean( $raw_value['width'] );
						$value['height'] = er_clean( $raw_value['height'] );
						$value['crop']   = isset( $raw_value['crop'] ) ? 1 : 0;
					} else {
						$value['width']  = $option['default']['width'];
						$value['height'] = $option['default']['height'];
						$value['crop']   = $option['default']['crop'];
					}
					break;
				case 'select':
					$allowed_values = empty( $option['options'] ) ? array() : array_map( 'strval', array_keys( $option['options'] ) );
					if ( empty( $option['default'] ) && empty( $allowed_values ) ) {
						$value = null;
						break;
					}
					$default = ( empty( $option['default'] ) ? $allowed_values[0] : $option['default'] );
					$value   = in_array( $raw_value, $allowed_values, true ) ? $raw_value : $default;
					break;
				case 'relative_date_selector':
					$value = er_parse_relative_date_option( $raw_value );
					break;
				default:
					$value = er_clean( $raw_value );
					break;
			}

			/**
			 * Sanitize the value of an option.
			 */
			$value = apply_filters( 'easyreservations_admin_settings_sanitize_option', $value, $option, $raw_value );

			/**
			 * Sanitize the value of an option by option name.
			 */
			$value = apply_filters( "easyreservations_admin_settings_sanitize_option_$option_name", $value, $option, $raw_value );

			if ( is_null( $value ) ) {
				continue;
			}

			// Check if option is an array and handle that differently to single values.
			if ( $option_name && $setting_name ) {
				if ( ! isset( $update_options[ $option_name ] ) ) {
					$update_options[ $option_name ] = get_option( $option_name, array() );
				}
				if ( ! is_array( $update_options[ $option_name ] ) ) {
					$update_options[ $option_name ] = array();
				}
				$update_options[ $option_name ][ $setting_name ] = $value;
			} else {
				$update_options[ $option_name ] = $value;
			}

			$autoload_options[ $option_name ] = isset( $option['autoload'] ) ? (bool) $option['autoload'] : true;
			/**
			 * Fire an action before saved.
			 */
			do_action( 'easyreservations_update_option', $option );
		}

		// Save all options in our array.
		foreach ( $update_options as $name => $value ) {
			update_option( $name, $value, $autoload_options[ $name ] ? 'yes' : 'no' );
		}

		return true;
	}
}

return new ER_Admin_Settings();