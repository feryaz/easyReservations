<?php
/**
 * Display notices in admin
 *
 * @package easyReservations\Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * ER_Admin_Notices Class.
 */
class ER_Admin_Notices {

	/**
	 * Stores notices.
	 *
	 * @var array
	 */
	private static $notices = array();

	/**
	 * Stores temporary notices.
	 *
	 * @var array
	 */
	private static $temporary_notices = array();

	/**
	 * Has error?
	 *
	 * @var array
	 */
	private static $has_error = false;

	/**
	 * Array of notices - name => callback.
	 *
	 * @var array
	 */
	private static $core_notices = array(
		'install'                          => 'install_notice',
		'update_reservations'              => 'update_notice',
		'update_premium'                   => 'update_premium_notice',
		'template_files'                   => 'template_file_check_notice',
		'no_secure_connection'             => 'secure_connection_notice',
		'wp_php_min_requirements'          => 'wp_php_min_requirements_notice',
		'uploads_directory_is_unprotected' => 'uploads_directory_is_unprotected_notice',
		'base_tables_missing'              => 'base_tables_missing_notice',
	);

	/**
	 * Constructor.
	 */
	public static function init() {
		self::$notices = get_option( 'reservations_admin_notices', array() );

		add_action( 'switch_theme', array( __CLASS__, 'reset_admin_notices' ) );
		add_action( 'easyreservations_installed', array( __CLASS__, 'reset_admin_notices' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'hide_notices' ) );
		add_action( 'shutdown', array( __CLASS__, 'store_notices' ) );

		if ( current_user_can( 'manage_easyreservations' ) ) {
			add_action( 'admin_print_styles', array( __CLASS__, 'add_notices' ) );
		}
	}

	/**
	 * Parses query to create nonces when available.
	 *
	 * @param object $response The WP_REST_Response we're working with.
	 *
	 * @return object $response The prepared WP_REST_Response object.
	 */
	public static function prepare_note_with_nonce( $response ) {
		if ( 'er-update-db-reminder' !== $response->data['name'] || ! isset( $response->data['actions'] ) ) {
			return $response;
		}

		foreach ( $response->data['actions'] as $action_key => $action ) {
			$url_parts = ! empty( $action->query ) ? wp_parse_url( $action->query ) : '';

			if ( ! isset( $url_parts['query'] ) ) {
				continue;
			}

			wp_parse_str( $url_parts['query'], $params );

			if ( array_key_exists( '_nonce_action', $params ) && array_key_exists( '_nonce_name', $params ) ) {
				$org_params = $params;

				// Check to make sure we're acting on the whitelisted nonce actions.
				if ( 'er_db_update' !== $params['_nonce_action'] && 'easyreservations_hide_notices_nonce' !== $params['_nonce_action'] ) {
					continue;
				}

				unset( $org_params['_nonce_action'] );
				unset( $org_params['_nonce_name'] );

				$url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];

				$nonce         = array( $params['_nonce_name'] => wp_create_nonce( $params['_nonce_action'] ) );
				$merged_params = array_merge( $nonce, $org_params );
				$parsed_query  = add_query_arg( $merged_params, $url );

				$response->data['actions'][ $action_key ]->query = $parsed_query;
				$response->data['actions'][ $action_key ]->url   = $parsed_query;
			}
		}

		return $response;
	}

	/**
	 * Store notices to DB
	 */
	public static function store_notices() {
		update_option( 'reservations_admin_notices', self::get_notices() );
	}

	/**
	 * Get notices
	 *
	 * @return array
	 */
	public static function get_notices() {
		return self::$notices;
	}

	/**
	 * Get temporary notices
	 *
	 * @return array
	 */
	public static function get_temporary_notices() {
		return self::$temporary_notices;
	}

	/**
	 * Has temporary errors?
	 *
	 * @return array
	 */
	public static function has_errors() {
		return self::$has_error;
	}

	/**
	 * Remove all notices.
	 */
	public static function remove_all_notices() {
		self::$notices           = array();
		self::$temporary_notices = array();
	}

	/**
	 * Reset notices for themes when switched or a new version of ER is installed.
	 */
	public static function reset_admin_notices() {
		if ( ! self::is_ssl() ) {
			self::add_notice( 'no_secure_connection' );
		}
		if ( ! self::is_uploads_directory_protected() ) {
			self::add_notice( 'uploads_directory_is_unprotected' );
		}
		self::add_notice( 'template_files' );
		self::add_min_version_notice();
	}

	/**
	 * Add temporary error message
	 *
	 * @param string $message Error message
	 */
	public static function add_temporary_error( $message ) {
		self::$has_error = true;
		self::add( 'error', $message );
	}

	/**
	 * Add temporary notice message
	 *
	 * @param string $message Warning message
	 */
	public static function add_temporary_notice( $message ) {
		self::add( 'notice', $message );
	}

	/**
	 * Add temporary notice success message
	 *
	 * @param string $message Message
	 */
	public static function add_temporary_success( $message ) {
		self::add( 'updated', $message );
	}

	/**
	 * Add admin message
	 *
	 * @param string $type updated, notice, error
	 * @param string $message
	 */
	public static function add( $type, $message ) {
		self::$temporary_notices[] = array(
			'type'    => $type,
			'message' => $message
		);
	}

	/**
	 * Show a notice.
	 *
	 * @param string $name Notice name.
	 * @param bool   $force_save Force saving inside this method instead of at the 'shutdown'.
	 */
	public static function add_notice( $name, $force_save = false ) {
		self::$notices = array_unique( array_merge( self::get_notices(), array( $name ) ) );

		if ( $force_save ) {
			// Adding early save to prevent more race conditions with notices.
			self::store_notices();
		}
	}

	/**
	 * Remove a notice from being displayed.
	 *
	 * @param string $name Notice name.
	 * @param bool   $force_save Force saving inside this method instead of at the 'shutdown'.
	 */
	public static function remove_notice( $name, $force_save = false ) {
		self::$notices = array_diff( self::get_notices(), array( $name ) );
		delete_option( 'reservations_admin_notice_' . $name );

		if ( $force_save ) {
			// Adding early save to prevent more race conditions with notices.
			self::store_notices();
		}
	}

	/**
	 * See if a notice is being shown.
	 *
	 * @param string $name Notice name.
	 *
	 * @return boolean
	 */
	public static function has_notice( $name ) {
		return in_array( $name, self::get_notices(), true );
	}

	/**
	 * Hide a notice if the GET variable is set.
	 */
	public static function hide_notices() {
		if ( isset( $_GET['er-hide-notice'] ) && isset( $_GET['_er_notice_nonce'] ) ) { // WPCS: input var ok, CSRF ok.
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_er_notice_nonce'] ) ), 'easyreservations_hide_notices_nonce' ) ) { // WPCS: input var ok, CSRF ok.
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'easyReservations' ) );
			}

			if ( ! current_user_can( 'manage_easyreservations' ) ) {
				wp_die( esc_html__( 'You don&#8217;t have permission to do this.', 'easyReservations' ) );
			}

			$hide_notice = sanitize_text_field( wp_unslash( $_GET['er-hide-notice'] ) ); // WPCS: input var ok, CSRF ok.

			self::remove_notice( $hide_notice );

			update_user_meta( get_current_user_id(), 'dismissed_' . $hide_notice . '_notice', true );

			do_action( 'easyreservations_hide_' . $hide_notice . '_notice' );
		}
	}

	/**
	 * Add notices + styles if needed.
	 */
	public static function add_notices() {
		$screen          = get_current_screen();
		$screen_id       = $screen ? $screen->id : '';
		$show_on_screens = array(
			'dashboard',
			'plugins',
		);

		// Notices should only show on easyReservations screens, the main dashboard, and on the plugins screen.
		if ( ! in_array( $screen_id, er_get_screen_ids(), true ) && ! in_array( $screen_id, $show_on_screens, true ) ) {
			return;
		}

		add_action( 'admin_notices', array( __CLASS__, 'output_temporary_notices' ) );

		$notices = self::get_notices();

		if ( empty( $notices ) ) {
			return;
		}

		wp_enqueue_style( 'easyreservations-activation', plugins_url( '/assets/css/activation.css', RESERVATIONS_PLUGIN_FILE ), array(), RESERVATIONS_VERSION );

		// Add RTL support.
		//wp_style_add_data( 'easyreservations-activation', 'rtl', 'replace' );

		foreach ( $notices as $notice ) {
			if ( ! empty( self::$core_notices[ $notice ] ) && apply_filters( 'easyreservations_show_admin_notice', true, $notice ) ) {
				add_action( 'admin_notices', array( __CLASS__, self::$core_notices[ $notice ] ) );
			} else {
				add_action( 'admin_notices', array( __CLASS__, 'output_custom_notices' ) );
			}
		}
	}

	/**
	 * Add a custom notice.
	 *
	 * @param string $name Notice name.
	 * @param string $notice_html Notice HTML.
	 */
	public static function add_custom_notice( $name, $notice_html ) {
		self::add_notice( $name );
		update_option( 'reservations_admin_notice_' . $name, wp_kses_post( $notice_html ) );
	}

	/**
	 * Output any stored custom notices.
	 */
	public static function output_custom_notices() {
		$notices = self::get_notices();

		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice ) {
				if ( empty( self::$core_notices[ $notice ] ) ) {
					$notice_html = get_option( 'reservations_admin_notice_' . $notice );

					if ( $notice_html ) {
						include dirname( __FILE__ ) . '/views/html-notice-custom.php';
					}
				}
			}
		}
	}

	/**
	 * Output any stored custom notices.
	 *
	 * @param string $type type of note to output
	 */
	public static function output_temporary_notices( $type = 'all' ) {
		$notices = self::get_temporary_notices();

		if ( ! empty( $notices ) ) {
			foreach ( $notices as $message ) {
				if ( empty( $type ) || $type == 'all' || $type == $message['type'] ) {
					echo '<div class="easy-message ' . esc_attr( $message['type'] ) . ' inline"><p>' . wp_kses_post( $message['message'] ) . '</p></div>';
				}
			}
		}
	}

	/**
	 * If we need to update the database, include a message with the DB update button.
	 */
	public static function update_notice() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( in_array( $screen_id, er_get_screen_ids(), true ) ) {
			return;
		}

		$plugin = 'reservations';

		if ( ER_Install::needs_db_update() ) {
			include dirname( __FILE__ ) . '/views/html-notice-update.php';
		} else {
			ER_Install::update_db_version();
			include dirname( __FILE__ ) . '/views/html-notice-updated.php';
		}
	}

	/**
	 * If we need to update, include a message with the update button.
	 */
	public static function update_premium_notice() {
		$plugin = 'premium';

		if ( ER_Install::needs_db_update( 'premium' ) ) {
			include dirname( __FILE__ ) . '/views/html-notice-update.php';
		} else {
			include dirname( __FILE__ ) . '/views/html-notice-updated.php';
		}
	}

	/**
	 * If we have just installed, show a message with the install pages button.
	 */
	public static function install_notice() {
		include dirname( __FILE__ ) . '/views/html-notice-install.php';
	}

	/**
	 * Show a notice highlighting bad template files.
	 */
	public static function template_file_check_notice() {
		$core_templates = er_admin_scan_template_files( ER()->plugin_path() . '/templates' );
		$outdated       = false;

		foreach ( $core_templates as $file ) {

			$theme_file = false;
			if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . $file;
			} elseif ( file_exists( get_stylesheet_directory() . '/' . ER()->template_path() . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . ER()->template_path() . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
				$theme_file = get_template_directory() . '/' . $file;
			} elseif ( file_exists( get_template_directory() . '/' . ER()->template_path() . $file ) ) {
				$theme_file = get_template_directory() . '/' . ER()->template_path() . $file;
			}

			if ( false !== $theme_file ) {
				$core_version  = er_admin_get_file_version( ER()->plugin_path() . '/templates/' . $file );
				$theme_version = er_admin_get_file_version( $theme_file );

				if ( $core_version && $theme_version && version_compare( $theme_version, $core_version, '<' ) ) {
					$outdated = true;
					break;
				}
			}
		}

		if ( $outdated ) {
			include dirname( __FILE__ ) . '/views/html-notice-template-check.php';
		} else {
			self::remove_notice( 'template_files' );
		}
	}

	/**
	 * Notice about secure connection.
	 */
	public static function secure_connection_notice() {
		if ( self::is_ssl() || get_user_meta( get_current_user_id(), 'dismissed_no_secure_connection_notice', true ) ) {
			return;
		}

		include dirname( __FILE__ ) . '/views/html-notice-secure-connection.php';
	}

	/**
	 * Add notice about minimum PHP and WordPress requirement.
	 */
	public static function add_min_version_notice() {
		if ( version_compare( phpversion(), RESERVATIONS_MIN_PHP_VERSION, '<' ) || version_compare( get_bloginfo( 'version' ), RESERVATIONS_MIN_WP_VERSION, '<' ) ) {
			self::add_notice( 'wp_php_min_requirements' );
		}
	}

	/**
	 * Notice about WordPress and PHP minimum requirements.
	 *
	 * @return void
	 */
	public static function wp_php_min_requirements_notice() {
		if ( apply_filters( 'easyreservations_hide_php_wp_nag', get_user_meta( get_current_user_id(), 'dismissed_wp_php_min_requirements_notice', true ) ) ) {
			self::remove_notice( 'wp_php_min_requirements' );

			return;
		}

		$old_php = version_compare( phpversion(), RESERVATIONS_MIN_PHP_VERSION, '<' );
		$old_wp  = version_compare( get_bloginfo( 'version' ), RESERVATIONS_MIN_WP_VERSION, '<' );

		// Both PHP and WordPress up to date version => no notice.
		if ( ! $old_php && ! $old_wp ) {
			return;
		}

		if ( $old_php && $old_wp ) {
			$msg = sprintf(
			/* translators: 1: Minimum PHP version 2: Minimum WordPress version */
				__( 'Update required: easyReservations will soon require PHP version %1$s and WordPress version %2$s or newer.', 'easyReservations' ),
				RESERVATIONS_MIN_PHP_VERSION,
				RESERVATIONS_MIN_WP_VERSION
			);
		} elseif ( $old_php ) {
			$msg = sprintf(
			/* translators: %s: Minimum PHP version */
				__( 'Update required: easyReservations will soon require PHP version %s or newer.', 'easyReservations' ),
				RESERVATIONS_MIN_PHP_VERSION
			);
		} elseif ( $old_wp ) {
			$msg = sprintf(
			/* translators: %s: Minimum WordPress version */
				__( 'Update required: easyReservations will soon require WordPress version %s or newer.', 'easyReservations' ),
				RESERVATIONS_MIN_WP_VERSION
			);
		}

		include dirname( __FILE__ ) . '/views/html-notice-wp-php-minimum-requirements.php';
	}

	/**
	 * Notice about uploads directory begin unprotected.
	 *
	 */
	public static function uploads_directory_is_unprotected_notice() {
		if ( get_user_meta( get_current_user_id(), 'dismissed_uploads_directory_is_unprotected_notice', true ) || self::is_uploads_directory_protected() ) {
			self::remove_notice( 'uploads_directory_is_unprotected' );

			return;
		}

		include dirname( __FILE__ ) . '/views/html-notice-uploads-directory-is-unprotected.php';
	}

	/**
	 * Notice about base tables missing.
	 */
	public static function base_tables_missing_notice() {
		$notice_dismissed = apply_filters(
			'easyreservations_hide_base_tables_missing_nag',
			get_user_meta( get_current_user_id(), 'dismissed_base_tables_missing_notice', true )
		);
		if ( $notice_dismissed ) {
			self::remove_notice( 'base_tables_missing' );
		}

		include dirname( __FILE__ ) . '/views/html-notice-base-table-missing.php';
	}

	/**
	 * Determine if the store is running SSL.
	 *
	 * @return bool Flag SSL enabled.
	 */
	protected static function is_ssl() {
		$shop_page = er_get_page_permalink( 'shop' );

		return ( is_ssl() && 'https' === substr( $shop_page, 0, 5 ) );
	}

	/**
	 * Wrapper for is_plugin_active.
	 *
	 * @param string $plugin Plugin to check.
	 *
	 * @return boolean
	 */
	protected static function is_plugin_active( $plugin ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin );
	}

	/**
	 * Check if uploads directory is protected.
	 *
	 * @return bool
	 * @since 4.2.0
	 */
	protected static function is_uploads_directory_protected() {
		$cache_key = '_easyreservations_upload_directory_status';
		$status    = get_transient( $cache_key );

		// Check for cache.
		if ( false !== $status ) {
			return 'protected' === $status;
		}

		// Get only data from the uploads directory.
		$uploads = wp_get_upload_dir();

		// Check for the "uploads/easyreservations_uploads" directory.
		$response         = wp_safe_remote_get(
			esc_url_raw( $uploads['baseurl'] . '/easyreservations_uploads/' ),
			array(
				'redirection' => 0,
			)
		);
		$response_code    = intval( wp_remote_retrieve_response_code( $response ) );
		$response_content = wp_remote_retrieve_body( $response );

		// Check if returns 200 with empty content in case can open an index.html file,
		// and check for non-200 codes in case the directory is protected.
		$is_protected = ( 200 === $response_code && empty( $response_content ) ) || ( 200 !== $response_code );
		set_transient( $cache_key, $is_protected ? 'protected' : 'unprotected', 1 * DAY_IN_SECONDS );

		return $is_protected;
	}
}

ER_Admin_Notices::init();
