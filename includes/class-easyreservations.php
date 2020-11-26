<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class easyReservations {

	/**
	 * The single instance of the class.
	 *
	 * @var easyReservations
	 */
	protected static $_instance = null;

	/**
	 * easyReservations version.
	 *
	 * @var string
	 */
	public $version = '6.0-alpha.15';

	/**
	 * easyReservations Schema version.
	 *
	 * @var string
	 */
	public $db_version = '600';

	/**
	 * Session instance.
	 *
	 * @var ER_Session|ER_Session_Handler
	 */
	public $session = null;

	/**
	 * Query instance.
	 *
	 * @var ER_Query
	 */
	public $query = null;

	/**
	 * Countries instance.
	 *
	 * @var ER_Countries
	 */
	public $countries = null;

	/**
	 * Structured data instance.
	 *
	 * @var ER_Structured_Data
	 */
	public $structured_data = null;

	/**
	 * Cart instance.
	 *
	 * @var ER_Cart
	 */
	public $cart = null;

	/**
	 * Customer instance.
	 *
	 * @var ER_Customer
	 */
	public $customer = null;

	/**
	 * Initialize and make sure only one instance is made
	 *
	 * @return easyReservations
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'easyReservations' ), '6.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Unserializ ing instances of this class is forbidden.', 'easyReservations' ), '6.0' );
	}

	/**
	 * easyReservations constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->define_tables();
		$this->includes();

		if ( get_option( 'reservations_db_version' ) == $this->version ) {
			$this->init_hooks();
		} else {
			register_activation_hook( RESERVATIONS_PLUGIN_FILE, array( 'ER_Install', 'install' ) );
		}
	}

	/**
	 * Init easyReservations when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_easyreservations_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Load class instances.
		$this->countries       = new ER_Countries();
		$this->structured_data = new ER_Structured_Data();

		load_plugin_textdomain( 'easyReservations', false, basename( dirname( RESERVATIONS_PLUGIN_FILE ) ) . '/i18n/languages' );

		// Classes/actions loaded for the frontend and for ajax requests.
		if ( $this->is_request( 'frontend' ) ) {
			er_load_cart();
		}

		do_action( 'easyreservations_init' );
	}

	/**
	 * When WP has loaded all plugins, trigger the `easyreservations_loaded` hook.
	 *
	 * This ensures `easyreservations_loaded` is called only after all other plugins
	 * are loaded, to avoid issues caused by plugin directory naming changing
	 * the load order. See #21524 for details.
	 */
	public function on_plugins_loaded() {
		do_action( 'easyreservations_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		register_activation_hook( RESERVATIONS_PLUGIN_FILE, array( 'ER_Install', 'install' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );

		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), - 1 );
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'ER_Shortcodes', 'init' ) );
		add_action( 'init', array( 'ER_Emails', 'init_transactional_emails' ) );
		add_action( 'init', array( $this, 'add_image_sizes' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 0 );
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( $error && in_array( $error['type'], array(
				E_ERROR,
				E_PARSE,
				E_COMPILE_ERROR,
				E_USER_ERROR,
				E_RECOVERABLE_ERROR
			), true ) ) {
			$logger = er_get_logger();
			$logger->critical(
			/* translators: 1: error message 2: file name and path 3: line number */
				sprintf( __( '%1$s in %2$s on line %3$s', 'easyResevations' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
				array(
					'source' => 'fatal-errors',
				)
			);
			do_action( 'easyreservations_shutdown_error', $error );
		}
	}

	/**
	 * Define ER Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		define( 'RESERVATIONS_VERSION', $this->version );
		define( 'RESERVATIONS_LOG_DIR', $upload_dir['basedir'] . '/easyreservations-logs/' );
		define( 'RESERVATIONS_SESSION_CACHE_GROUP', 'er_session_id' );
		define( 'RESERVATIONS_ABSPATH', dirname( RESERVATIONS_PLUGIN_FILE ) . '/' );
		define( 'RESERVATIONS_URL', WP_PLUGIN_URL . '/easyreservations/' );
		define( 'RESERVATIONS_TEMPLATE_DEBUG_MODE', false );
		define( 'RESERVATIONS_MIN_PHP_VERSION', '5.6.20' );
		define( 'RESERVATIONS_MIN_WP_VERSION', '4.9' );
		define( 'RESERVATIONS_PHP_MIN_REQUIREMENTS_NOTICE', 'wp_php_min_requirements_' . RESERVATIONS_MIN_PHP_VERSION . '_' . RESERVATIONS_MIN_WP_VERSION );
	}

	/**
	 * Register custom tables within $wpdb object.
	 */
	public function define_tables() {
		global $wpdb;

		// List of tables without prefixes.
		$tables = array(
			'receipt_itemmeta' => 'receipt_itemmeta',
		);

		foreach ( $tables as $name => $table ) {
			$wpdb->$name    = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @param string $type admin, ajax, cron or frontend.
	 *
	 * @return bool
	 */
	public function is_request( $type ) {
		switch ( $type ) {
			case 'admin' :
				return is_admin() || defined( 'EASY_API' );
			case 'ajax' :
				return defined( 'DOING_AJAX' );
			case 'cron' :
				return defined( 'DOING_CRON' );
			case 'frontend' :
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			default:
				return false;
		}
	}

	/**
	 * Is user request?
	 *
	 * @return bool
	 */
	public function is_user_request() {
		return ER()->is_request( 'frontend' ) && ! defined( 'RESERVATIONS_ADMIN_REQUEST' );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-autoloader.php' );

		/**
		 * Interfaces.
		 */
		include_once( RESERVATIONS_ABSPATH . 'includes/interfaces/class-er-abstract-order-data-store-interface.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/interfaces/class-er-customer-data-store-interface.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/interfaces/class-er-object-data-store-interface.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/interfaces/class-er-order-data-store-interface.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/interfaces/class-er-receipt-item-data-store-interface.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/interfaces/class-er-receipt-item-type-data-store-interface.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/interfaces/class-er-order-refund-data-store-interface.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/interfaces/class-er-reservation-data-store-interface.php' );

		/**
		 * Core traits.
		 */
		include_once( RESERVATIONS_ABSPATH . 'includes/traits/trait-er-item-totals.php' );

		/**
		 * Abstract classes.
		 */
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-custom.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-custom-options.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-data.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-form.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-log-levels.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-privacy.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-receipt.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-receipt-item.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-receipt-item-line.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-object-query.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-order.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-session.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-settings-api.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/abstracts/abstract-er-status.php' );

		/**
		 * Core classes.
		 */
		//include_once( RESERVATIONS_ABSPATH . 'includes/widgets/class-er-widget-form.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/er-core-functions.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-ajax.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-autoloader.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-background-emailer.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-checkout.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-comments.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-customer.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-custom-data.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-datetime.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-emails.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-https.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-install.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-logger.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-order.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-order-refund.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-order-query.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-payment.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-post-data.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-privacy.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-query.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-receipt-item-custom.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-receipt-item-fee.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-receipt-item-reservation.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-receipt-item-resource.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-receipt-item-tax.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-reservation.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-reservation-form.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-reservation-manager.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-resource.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-resource-availability.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-resources.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-shortcodes.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-structured-data.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-tax.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-validation.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/customizer/class-er-shop-customizer.php' );

		/**
		 * Data stores - used to store and retrieve CRUD object data from the database.
		 */
		include_once( RESERVATIONS_ABSPATH . 'includes/class-er-data-store.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/class-er-data-store-wp.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/abstract-er-receipt-item-type-data-store.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/abstract-er-receipt-data-store.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/class-er-receipt-item-custom-data-store.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/class-er-receipt-item-fee-data-store.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/class-er-receipt-item-reservation-data-store.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/class-er-receipt-item-resource-data-store.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/class-er-receipt-item-tax-data-store.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/class-er-customer-data-store.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/class-er-customer-data-store-session.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/abstract-er-order-data-store-cpt.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/class-er-order-data-store-cpt.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/class-er-order-refund-data-store-cpt.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/data-stores/class-er-reservation-data-store.php' );

		/**
		 * Custom fields.
		 */
		include_once( RESERVATIONS_ABSPATH . 'includes/custom-fields/class-er-custom-area.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/custom-fields/class-er-custom-check.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/custom-fields/class-er-custom-number.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/custom-fields/class-er-custom-radio.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/custom-fields/class-er-custom-select.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/custom-fields/class-er-custom-slider.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/custom-fields/class-er-custom-text.php' );

		if ( $this->is_request( 'admin' ) ) {
			include_once( RESERVATIONS_ABSPATH . 'includes/admin/class-er-admin.php' );
		}

		if ( $this->is_request( 'frontend' ) ) {
			include_once( RESERVATIONS_ABSPATH . 'includes/er-notice-functions.php' );

			include_once( RESERVATIONS_ABSPATH . 'includes/class-er-frontend.php' );
			include_once( RESERVATIONS_ABSPATH . 'includes/class-er-session-handler.php' );
			include_once( RESERVATIONS_ABSPATH . 'includes/class-er-template-loader.php' );
			include_once( RESERVATIONS_ABSPATH . 'includes/class-er-form-handler.php' );
		}

		if ( $this->is_request( 'cron' ) && 'yes' === get_option( 'reservations_allow_tracking', 'no' ) ) {
			//include_once( RESERVATIONS_ABSPATH . 'includes/class-er-tracker.php' );
		}

		$this->add_theme_support();
		$this->query = new ER_Query();
		$this->api   = new ER_API();
		$this->api->init();
	}

	/**
	 * Function used to Init easyReservations Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		include_once RESERVATIONS_ABSPATH . 'includes/er-template-functions.php';
	}

	/**
	 * Register scripts and styles
	 */
	public function register_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( file_exists( RESERVATIONS_URL . 'assets/css/custom/datepicker.css' ) ) {
			$form1 = 'custom/datepicker.css';
		} else {
			$form1 = 'datepicker' . $suffix . '.css';
		}

		wp_register_style( 'select2', RESERVATIONS_URL . 'assets/css/select2' . $suffix . '.css', array(), '4.0.3' );
		wp_register_style( 'easy-ui', RESERVATIONS_URL . 'assets/css/ui' . $suffix . '.css', array( 'select2', 'dashicons' ), RESERVATIONS_VERSION );
		wp_register_style( 'er-datepicker', RESERVATIONS_URL . 'assets/css/' . $form1, array(), RESERVATIONS_VERSION );

		wp_register_script( 'jquery-blockui', ER()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'select2', ER()->plugin_url() . '/assets/js/select2/select2.full' . $suffix . '.js', array( 'jquery' ), '4.0.3' );
		wp_register_script( 'selectWoo', ER()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.6' );
		wp_register_script( 'easy-ui', RESERVATIONS_URL . 'assets/js/ui' . $suffix . '.js', array( 'jquery-ui-slider', 'jquery-touch-punch', 'selectWoo' ), RESERVATIONS_VERSION );

		wp_register_script( 'er-datepicker', ER()->plugin_url() . '/assets/js/er-datepicker' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), RESERVATIONS_VERSION );
		wp_localize_script( 'er-datepicker', 'er_date_picker_params', array(
			'select'              => __( 'Select', 'easyReservations' ),
			'wait'                => __( 'Wait', 'easyReservations' ),
			'is_frontend_request' => ER()->is_request( 'frontend' ) ? 'yes' : 'no',
			'start_of_week'       => get_option( 'start_of_week' ),
			'earliest_possible'   => er_earliest_arrival(),
			'date_format'         => er_date_format(),
			'day_names'           => er_date_get_label( 0, 0, false, true ),
			'day_names_short'     => er_date_get_label( 0, 3, false, true ),
			'day_names_min'       => er_date_get_label( 0, 2, false, true ),
			'month_names'         => er_date_get_label( 1, 0, false, true ),
			'month_names_short'   => er_date_get_label( 1, 3, false, true ),
		) );

		wp_register_script( 'er-both', RESERVATIONS_URL . 'assets/js/both.js', RESERVATIONS_VERSION );
		wp_localize_script( 'er-both', 'er_both_params', array(
			'date_format' => er_date_format(),
			'time_format' => er_time_format(),
			'use_time'    => er_use_time(),
			'time'        => er_get_time(),
			'offset'      => date( "Z" ),
			'resources'   => er_list_pluck( ER()->resources()->get(), 'get_data' ),
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'plugin_url'  => WP_PLUGIN_URL
		) );

		wp_enqueue_style( 'easy-ui' );
		wp_enqueue_script( 'er-both' );
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/easyReservations/easyReservations-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/easyReservations-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', determine_locale(), 'easyReservations' );

		unload_textdomain( 'easyReservations' );
		load_textdomain( 'easyReservations', WP_LANG_DIR . '/easyReservations/easyReservations-' . $locale . '.mo' );
		load_plugin_textdomain( 'easyReservations', false, plugin_basename( dirname( RESERVATIONS_PLUGIN_FILE ) ) . '/i18n/languages' );
	}

	/**
	 * Ensure theme and server variable compatibility and setup image sizes.
	 */
	public function setup_environment() {
		$this->add_thumbnail_support();
	}

	/**
	 * Ensure post thumbnail support is turned on.
	 */
	private function add_thumbnail_support() {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails' );
		}
		add_post_type_support( 'easy-rooms', 'thumbnail' );
	}

	/**
	 * Add ER Image sizes to WP.
	 *
	 * Image sizes can be registered via themes using add_theme_support for easyReservations
	 * and defining an array of args. If these are not defined, we will use defaults. This is
	 * handled in er_get_image_size function.
	 *
	 * easyreservations_thumbnail - Used in resource listings. We assume these work for a 3 column grid layout.
	 * easyreservations_single - Used on single resource pages for the main image.
	 */
	public function add_image_sizes() {
		$thumbnail         = er_get_image_size( 'thumbnail' );
		$single            = er_get_image_size( 'single' );
		$gallery_thumbnail = er_get_image_size( 'gallery_thumbnail' );

		add_image_size( 'easyreservations_thumbnail', $thumbnail['width'], $thumbnail['height'], $thumbnail['crop'] );
		add_image_size( 'easyreservations_single', $single['width'], $single['height'], $single['crop'] );
		add_image_size( 'easyreservations_gallery_thumbnail', $gallery_thumbnail['width'], $gallery_thumbnail['height'], $gallery_thumbnail['crop'] );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', RESERVATIONS_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( RESERVATIONS_PLUGIN_FILE ) );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'easyreservations_template_path', 'easyreservations/' );
	}

	/**
	 * Return the ER API URL for a given request.
	 *
	 * @param string    $request Requested endpoint.
	 * @param bool|null $ssl If should use SSL, null if should auto detect. Default: null.
	 *
	 * @return string
	 */
	public function api_request_url( $request, $ssl = null ) {
		if ( is_null( $ssl ) ) {
			$scheme = wp_parse_url( home_url(), PHP_URL_SCHEME );
		} elseif ( $ssl ) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}

		if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {
			$api_request_url = trailingslashit( home_url( '/index.php/er-api/' . $request, $scheme ) );
		} elseif ( get_option( 'permalink_structure' ) ) {
			$api_request_url = trailingslashit( home_url( '/er-api/' . $request, $scheme ) );
		} else {
			$api_request_url = add_query_arg( 'er-api', $request, trailingslashit( home_url( '', $scheme ) ) );
		}

		return esc_url_raw( apply_filters( 'easyreservations_api_request_url', $api_request_url, $request, $ssl ) );
	}

	/**
	 * Initialize the customer and cart objects and setup customer saving on shutdown.
	 *
	 * @return void
	 */
	public function initialize_cart() {
		if ( is_null( $this->customer ) || ! $this->customer instanceof ER_Customer ) {
			$this->customer = new ER_Customer( get_current_user_id(), true );

			// Customer should be saved during shutdown.
			add_action( 'shutdown', array( $this->customer, 'save' ), 10 );
		}
		if ( is_null( $this->cart ) || ! $this->cart instanceof ER_Cart ) {
			$this->cart = new ER_Cart();
		}
	}

	/**
	 * Initialize the session class.
	 *
	 * @return void
	 */
	public function initialize_session() {
		// Session class, handles session data for users - can be overwritten if custom handler is needed.
		$session_class = apply_filters( 'easyreservations_session_handler', 'ER_Session_Handler' );
		if ( is_null( $this->session ) || ! $this->session instanceof $session_class ) {
			$this->session = new $session_class();
			$this->session->init();
		}
	}

	/**
	 * Adds theme support for default themes
	 */
	public function add_theme_support() {
		$template         = get_template();
		$supported_themes = apply_filters( 'easyreservations_supported_themes', array(
			'twentyten'       => array(
				'thumbnail_image_width' => 200,
				'single_image_width'    => 300,
			),
			'twentyeleven'    => array(
				'thumbnail_image_width' => 150,
				'single_image_width'    => 300,
			),
			'twentytwelve'    => array(
				'thumbnail_image_width' => 200,
				'single_image_width'    => 300,
			),
			'twentythirteen'  => array(
				'thumbnail_image_width' => 200,
				'single_image_width'    => 300,
			),
			'twentyfourteen'  => array(
				'thumbnail_image_width' => 150,
				'single_image_width'    => 300,
			),
			'twentyfifteen'   => array(
				'thumbnail_image_width' => 200,
				'single_image_width'    => 350,
			),
			'twentysixteen'   => array(
				'thumbnail_image_width' => 250,
				'single_image_width'    => 400,
			),
			'twentyseventeen' => array(
				'thumbnail_image_width' => 250,
				'single_image_width'    => 350,
			),
			'twentynineteen'  => array(
				'thumbnail_image_width' => 300,
				'single_image_width'    => 450,
			)
		) );

		if ( isset( $supported_themes[ $template ] ) ) {
			add_theme_support( 'er-resource-gallery-zoom' );
			add_theme_support( 'er-resource-gallery-lightbox' );
			add_theme_support( 'er-resource-gallery-slider' );

			add_theme_support( 'easyreservations', $supported_themes[ $template ] );
		}
	}

	/**
	 * @return ER_Resources
	 */
	public function resources() {
		return ER_Resources::instance();
	}

	/**
	 * @return ER_Reservation_Manager
	 */
	public function reservation_manager() {
		return ER_Reservation_Manager::instance();
	}

	/**
	 * Get Checkout Class.
	 *
	 * @return ER_Checkout
	 */
	public function checkout() {
		return ER_Checkout::instance();
	}

	/**
	 * Get Reservation Form Class.
	 *
	 * @return ER_Reservation_Form
	 */
	public function reservation_form() {
		return ER_Reservation_Form::instance();
	}

	/**
	 * Email Class.
	 *
	 * @return ER_Emails
	 */
	public function mailer() {
		return ER_Emails::instance();
	}

	/**
	 * Get gateways class.
	 *
	 * @return ER_Payment
	 */
	public function payment_gateways() {
		return ER_Payment::instance();
	}
}