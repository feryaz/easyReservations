<?php
/**
 * easyReservations Tracker
 *
 * The easyReservations tracker class adds functionality to track easyReservations usage based on if the customer opted in.
 * No personal information is tracked, only general easyReservations settings, general product, order and user counts and admin email for discount code.
 *
 * @class ER_Tracker
 * @since 2.3.0
 * @package easyReservations/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * easyReservations Tracker Class
 */
class ER_Tracker {

	/**
	 * URL to the WooThemes Tracker API endpoint.
	 *
	 * @var string
	 */
	private static $api_url = 'https://tracking.easyreservations.org/v1/';

	/**
	 * Hook into cron event.
	 */
	public static function init() {
		add_action( 'easyreservations_tracker_send_event', array( __CLASS__, 'send_tracking_data' ) );
	}

	/**
	 * Decide whether to send tracking data or not.
	 *
	 * @param boolean $override Should override?.
	 */
	public static function send_tracking_data( $override = false ) {
		// Don't trigger this on AJAX Requests.
		if ( defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( ! apply_filters( 'easyreservations_tracker_send_override', $override ) ) {
			// Send a maximum of once per week by default.
			$last_send = self::get_last_send_time();
			if ( $last_send && $last_send > apply_filters( 'easyreservations_tracker_last_send_interval', strtotime( '-1 week' ) ) ) {
				return;
			}
		} else {
			// Make sure there is at least a 1 hour delay between override sends, we don't want duplicate calls due to double clicking links.
			$last_send = self::get_last_send_time();
			if ( $last_send && $last_send > strtotime( '-1 hours' ) ) {
				return;
			}
		}

		// Update time first before sending to ensure it is set.
		update_option( 'reservations_tracker_last_send', time() );

		$params = self::get_tracking_data();
		wp_safe_remote_post(
			self::$api_url,
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => false,
				'headers'     => array( 'user-agent' => 'easyReservationsTracker/' . md5( esc_url_raw( home_url( '/' ) ) ) . ';' ),
				'body'        => wp_json_encode( $params ),
				'cookies'     => array(),
			)
		);
	}

	/**
	 * Get the last time tracking data was sent.
	 *
	 * @return int|bool
	 */
	private static function get_last_send_time() {
		return apply_filters( 'easyreservations_tracker_last_send_time', get_option( 'reservations_tracker_last_send', false ) );
	}

	/**
	 * Test whether this site is a staging site according to the Jetpack criteria.
	 *
	 * With Jetpack 8.1+, Jetpack::is_staging_site has been deprecated.
	 * \Automattic\Jetpack\Status::is_staging_site is the replacement.
	 * However, there are version of JP where \Automattic\Jetpack\Status exists, but does *not* contain is_staging_site method,
	 * so with those, code still needs to use the previous check as a fallback.
	 *
	 * @return bool
	 */
	private static function is_jetpack_staging_site() {
		if ( class_exists( '\Automattic\Jetpack\Status' ) ) {
			// Preferred way of checking with Jetpack 8.1+.
			$jp_status = new \Automattic\Jetpack\Status();
			if ( is_callable( array( $jp_status, 'is_staging_site' ) ) ) {
				return $jp_status->is_staging_site();
			}
		}

		return ( class_exists( 'Jetpack' ) && is_callable( 'Jetpack::is_staging_site' ) && Jetpack::is_staging_site() );
	}

	/**
	 * Get all the tracking data.
	 *
	 * @return array
	 */
	private static function get_tracking_data() {
		$data = array();

		// General site info.
		$data['url']   = home_url();
		$data['email'] = apply_filters( 'easyreservations_tracker_admin_email', get_option( 'admin_email' ) );
		$data['theme'] = self::get_theme_info();

		// WordPress Info.
		$data['wp'] = self::get_wordpress_info();

		// Server Info.
		$data['server'] = self::get_server_info();

		// Plugin info.
		$all_plugins              = self::get_all_plugins();
		$data['active_plugins']   = $all_plugins['active_plugins'];
		$data['inactive_plugins'] = $all_plugins['inactive_plugins'];

		// Jetpack & easyReservations Connect.

		$data['jetpack_version']    = Constants::is_defined( 'JETPACK__VERSION' ) ? Constants::get_constant( 'JETPACK__VERSION' ) : 'none';
		$data['jetpack_connected']  = ( class_exists( 'Jetpack' ) && is_callable( 'Jetpack::is_active' ) && Jetpack::is_active() ) ? 'yes' : 'no';
		$data['jetpack_is_staging'] = self::is_jetpack_staging_site() ? 'yes' : 'no';

		// Store count info.
		$data['users']      = self::get_user_counts();
		$data['resources']   = self::get_product_counts();
		$data['orders']     = self::get_orders();
		$data['categories'] = self::get_category_counts();

		// Payment gateway info.
		$data['gateways'] = self::get_active_payment_gateways();

		// Get all easyReservations options info.
		$data['settings'] = self::get_all_easyreservations_options_values();

		// Template overrides.
		$data['template_overrides'] = self::get_all_template_overrides();

		// Template overrides.
		$data['admin_user_agents'] = self::get_admin_user_agents();

		return apply_filters( 'easyreservations_tracker_data', $data );
	}

	/**
	 * Get the current theme info, theme name and version.
	 *
	 * @return array
	 */
	public static function get_theme_info() {
		$theme_data        = wp_get_theme();
		$theme_child_theme = er_bool_to_string( is_child_theme() );
		$theme_er_support  = er_bool_to_string( current_theme_supports( 'easyreservations' ) );

		return array(
			'name'        => $theme_data->Name, // @phpcs:ignore
			'version'     => $theme_data->Version, // @phpcs:ignore
			'child_theme' => $theme_child_theme,
			'er_support'  => $theme_er_support,
		);
	}

	/**
	 * Get WordPress related data.
	 *
	 * @return array
	 */
	private static function get_wordpress_info() {
		$wp_data = array();

		$memory = er_let_to_num( WP_MEMORY_LIMIT );

		if ( function_exists( 'memory_get_usage' ) ) {
			$system_memory = er_let_to_num( @ini_get( 'memory_limit' ) );
			$memory        = max( $memory, $system_memory );
		}

		$wp_data['memory_limit'] = size_format( $memory );
		$wp_data['debug_mode']   = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Yes' : 'No';
		$wp_data['locale']       = get_locale();
		$wp_data['version']      = get_bloginfo( 'version' );
		$wp_data['multisite']    = is_multisite() ? 'Yes' : 'No';

		return $wp_data;
	}

	/**
	 * Get server related info.
	 *
	 * @return array
	 */
	private static function get_server_info() {
		$server_data = array();

		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$server_data['software'] = $_SERVER['SERVER_SOFTWARE']; // @phpcs:ignore
		}

		if ( function_exists( 'phpversion' ) ) {
			$server_data['php_version'] = phpversion();
		}

		if ( function_exists( 'ini_get' ) ) {
			$server_data['php_post_max_size']  = size_format( er_let_to_num( ini_get( 'post_max_size' ) ) );
			$server_data['php_time_limt']      = ini_get( 'max_execution_time' );
			$server_data['php_max_input_vars'] = ini_get( 'max_input_vars' );
			$server_data['php_suhosin']        = extension_loaded( 'suhosin' ) ? 'Yes' : 'No';
		}

		$database_version             = er_get_server_database_version();
		$server_data['mysql_version'] = $database_version['number'];

		$server_data['php_max_upload_size']  = size_format( wp_max_upload_size() );
		$server_data['php_default_timezone'] = date_default_timezone_get();
		$server_data['php_soap']             = class_exists( 'SoapClient' ) ? 'Yes' : 'No';
		$server_data['php_fsockopen']        = function_exists( 'fsockopen' ) ? 'Yes' : 'No';
		$server_data['php_curl']             = function_exists( 'curl_init' ) ? 'Yes' : 'No';

		return $server_data;
	}

	/**
	 * Get all plugins grouped into activated or not.
	 *
	 * @return array
	 */
	private static function get_all_plugins() {
		// Ensure get_plugins function is loaded.
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins             = get_plugins();
		$active_plugins_keys = get_option( 'active_plugins', array() );
		$active_plugins      = array();

		foreach ( $plugins as $k => $v ) {
			// Take care of formatting the data how we want it.
			$formatted         = array();
			$formatted['name'] = strip_tags( $v['Name'] );
			if ( isset( $v['Version'] ) ) {
				$formatted['version'] = strip_tags( $v['Version'] );
			}
			if ( isset( $v['Author'] ) ) {
				$formatted['author'] = strip_tags( $v['Author'] );
			}
			if ( isset( $v['Network'] ) ) {
				$formatted['network'] = strip_tags( $v['Network'] );
			}
			if ( isset( $v['PluginURI'] ) ) {
				$formatted['plugin_uri'] = strip_tags( $v['PluginURI'] );
			}
			if ( in_array( $k, $active_plugins_keys ) ) {
				// Remove active plugins from list so we can show active and inactive separately.
				unset( $plugins[ $k ] );
				$active_plugins[ $k ] = $formatted;
			} else {
				$plugins[ $k ] = $formatted;
			}
		}

		return array(
			'active_plugins'   => $active_plugins,
			'inactive_plugins' => $plugins,
		);
	}

	/**
	 * Get user totals based on user role.
	 *
	 * @return array
	 */
	private static function get_user_counts() {
		$user_count          = array();
		$user_count_data     = count_users();
		$user_count['total'] = $user_count_data['total_users'];

		// Get user count based on user role.
		foreach ( $user_count_data['avail_roles'] as $role => $count ) {
			$user_count[ $role ] = $count;
		}

		return $user_count;
	}

	/**
	 * Get product totals based on product type.
	 *
	 * @return array
	 */
	public static function get_product_counts() {
		$product_count          = array();
		$product_count_data     = wp_count_posts( 'easy-rooms' );
		$product_count['total'] = $product_count_data->publish;

		return $product_count;
	}

	/**
	 * Get order counts
	 *
	 * @return array
	 */
	private static function get_order_counts() {
		$order_count      = array();
		$order_count_data = wp_count_posts( 'easy_order' );

		foreach ( ER_Order_Status::get_statuses() as $status_slug => $status_name ) {
			$order_count[ $status_slug ] = $order_count_data->{ $status_slug };
		}
		return $order_count;
	}

	/**
	 * Combine all order data.
	 *
	 * @return array
	 */
	private static function get_orders() {
		$order_dates  = self::get_order_dates();
		$order_counts = self::get_order_counts();
		$order_totals = self::get_order_totals();

		return array_merge( $order_dates, $order_counts, $order_totals );
	}

	/**
	 * Get the number of product categories.
	 *
	 * @return int
	 */
	private static function get_category_counts() {
		return wp_count_terms( 'resource_cat' );
	}

	/**
	 * Get a list of all active payment gateways.
	 *
	 * @return array
	 */
	private static function get_active_payment_gateways() {
		$active_gateways = array();
		$gateways        = ER()->payment_gateways()->payment_gateways();
		foreach ( $gateways as $id => $gateway ) {
			if ( isset( $gateway->enabled ) && 'yes' === $gateway->enabled ) {
				$active_gateways[ $id ] = array(
					'title'    => $gateway->title,
					'supports' => $gateway->supports,
				);
			}
		}

		return $active_gateways;
	}

	/**
	 * Get all options starting with easyreservations_ prefix.
	 *
	 * @return array
	 */
	private static function get_all_easyreservations_options_values() {
		return array(
			'version'                               => ER()->version,
			'currency'                              => er_get_currency(),
			'base_location'                         => ER()->countries->get_base_country(),
			'use_time'                              => get_option( 'reservations_use_time' ),
			'calc_taxes'                            => get_option( 'reservations_enable_taxes' ),
			'coupons_enabled'                       => get_option( 'reservations_enable_coupons' ),
			'guest_checkout'                        => get_option( 'reservations_enable_guest_checkout' ),
			'secure_checkout'                       => get_option( 'reservations_force_ssl_checkout' ),
			'enable_signup_and_login_from_checkout' => get_option( 'reservations_enable_signup_and_login_from_checkout' ),
			'enable_myaccount_registration'         => get_option( 'reservations_enable_myaccount_registration' ),
			'registration_generate_username'        => get_option( 'reservations_registration_generate_username' ),
			'registration_generate_password'        => get_option( 'reservations_registration_generate_password' ),
		);
	}

	/**
	 * Look for any template override and return filenames.
	 *
	 * @return array
	 */
	private static function get_all_template_overrides() {
		$override_data  = array();
		$template_paths = apply_filters( 'easyreservations_template_overrides_scan_paths', array( 'easyReservations' => ER()->plugin_path() . '/templates/' ) );
		$scanned_files  = array();

		foreach ( $template_paths as $plugin_name => $template_path ) {
			$scanned_files[ $plugin_name ] = er_admin_scan_template_files( $template_path );
		}

		foreach ( $scanned_files as $plugin_name => $files ) {
			foreach ( $files as $file ) {
				if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
					$theme_file = get_stylesheet_directory() . '/' . $file;
				} elseif ( file_exists( get_stylesheet_directory() . '/' . ER()->template_path() . $file ) ) {
					$theme_file = get_stylesheet_directory() . '/' . ER()->template_path() . $file;
				} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
					$theme_file = get_template_directory() . '/' . $file;
				} elseif ( file_exists( get_template_directory() . '/' . ER()->template_path() . $file ) ) {
					$theme_file = get_template_directory() . '/' . ER()->template_path() . $file;
				} else {
					$theme_file = false;
				}

				if ( false !== $theme_file ) {
					$override_data[] = basename( $theme_file );
				}
			}
		}
		return $override_data;
	}

	/**
	 * When an admin user logs in, there user agent is tracked in user meta and collected here.
	 *
	 * @return array
	 */
	private static function get_admin_user_agents() {
		return array_filter( (array) get_option( 'reservations_tracker_ua', array() ) );
	}

	/**
	 * Get order totals
	 *
	 * @return array
	 */
	public static function get_order_totals() {
		global $wpdb;

		$gross_total = $wpdb->get_var(
			"
			SELECT
				SUM( order_meta.meta_value ) AS 'gross_total'
			FROM {$wpdb->prefix}posts AS orders
			LEFT JOIN {$wpdb->prefix}postmeta AS order_meta ON order_meta.post_id = orders.ID
			WHERE order_meta.meta_key =  '_order_total'
				AND orders.post_status in ( 'completed', 'refunded' )
			GROUP BY order_meta.meta_key
		"
		);

		if ( is_null( $gross_total ) ) {
			$gross_total = 0;
		}

		$processing_gross_total = $wpdb->get_var(
			"
			SELECT
				SUM( order_meta.meta_value ) AS 'gross_total'
			FROM {$wpdb->prefix}posts AS orders
			LEFT JOIN {$wpdb->prefix}postmeta AS order_meta ON order_meta.post_id = orders.ID
			WHERE order_meta.meta_key =  '_order_total'
				AND orders.post_status = 'processing'
			GROUP BY order_meta.meta_key
		"
		);

		if ( is_null( $processing_gross_total ) ) {
			$processing_gross_total = 0;
		}

		return array(
			'gross'            => $gross_total,
			'processing_gross' => $processing_gross_total,
		);
	}

	/**
	 * Get last order date
	 *
	 * @return array
	 */
	private static function get_order_dates() {
		global $wpdb;

		$min_max = $wpdb->get_row(
			"
			SELECT
				MIN( post_date_gmt ) as 'first', MAX( post_date_gmt ) as 'last'
			FROM {$wpdb->prefix}posts
			WHERE post_type = 'easy_order'
			AND post_status = 'completed'
		",
			ARRAY_A
		);

		if ( is_null( $min_max ) ) {
			$min_max = array(
				'first' => '-',
				'last'  => '-',
			);
		}

		$processing_min_max = $wpdb->get_row(
			"
			SELECT
				MIN( post_date_gmt ) as 'processing_first', MAX( post_date_gmt ) as 'processing_last'
			FROM {$wpdb->prefix}posts
			WHERE post_type = 'easy_order'
			AND post_status = 'processing'
		",
			ARRAY_A
		);

		if ( is_null( $processing_min_max ) ) {
			$processing_min_max = array(
				'processing_first' => '-',
				'processing_last'  => '-',
			);
		}

		return array_merge( $min_max, $processing_min_max );
	}
}

ER_Tracker::init();
