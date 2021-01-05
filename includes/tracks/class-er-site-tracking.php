<?php
/**
 * Nosara Tracks for easyReservations
 *
 * @package easyReservations\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of easyReservations.
 */
class ER_Site_Tracking {
	/**
	 * Check if tracking is enabled.
	 *
	 * @return bool
	 */
	public static function is_tracking_enabled() {
		/**
		 * Don't track users if a filter has been applied to turn it off.
		 */
		if ( ! apply_filters( 'easyreservations_apply_user_tracking', true ) ) {
			return false;
		}

		// Check if tracking is actively being opted into.
		$is_obw_opting_in = isset( $_POST['er_tracker_checkbox'] ) && 'yes' === sanitize_text_field( $_POST['er_tracker_checkbox'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput

		/**
		 * Don't track users who haven't opted-in to tracking or aren't in
		 * the process of opting-in.
		 */
		if ( 'yes' !== get_option( 'reservations_allow_tracking' ) && ! $is_obw_opting_in ) {
			return false;
		}

		if ( ! class_exists( 'ER_Tracks' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register scripts required to record events from javascript.
	 */
	public static function register_scripts() {
		wp_register_script( 'easy-tracks', 'https://stats.wp.com/w.js', array( 'wp-hooks' ), gmdate( 'YW' ), false );
	}

	/**
	 * Add scripts required to record events from javascript.
	 */
	public static function enqueue_scripts() {
		// Add w.js to the page.
		wp_enqueue_script( 'easy-tracks' );
	}

	/**
	 * Adds the tracking function to the admin footer.
	 */
	public static function add_tracking_function() {
		?>
		<!-- easyReservations Tracks -->
		<script type="text/javascript">
			window.erTracks = window.erTracks || {};
			window.erTracks.isEnabled = <?php echo self::is_tracking_enabled() ? 'true' : 'false'; ?>;
			window.erTracks.recordEvent = function( name, properties ) {
				var eventName = '<?php echo esc_attr( ER_Tracks::PREFIX ); ?>' + name;
				var eventProperties = properties || {};
				eventProperties.url = '<?php echo esc_html( home_url() ); ?>';
				eventProperties.products_count = '<?php echo intval( ER_Tracks::get_products_count() ); ?>';
				if ( window.wp && window.wp.hooks && window.wp.hooks.applyFilters ) {
					eventProperties = window.wp.hooks.applyFilters( 'easyreservations_tracks_client_event_properties', eventProperties, eventName );
					delete ( eventProperties._ui );
					delete ( eventProperties._ut );
				}
				window._tkq = window._tkq || [];
				window._tkq.push( [ 'recordEvent', eventName, eventProperties ] );
			}
		</script>
		<?php
	}

	/**
	 * Adds a function to load tracking scripts and enable them client-side on the fly.
	 * Note that this function does not update `woocommerce_allow_tracking` in the database
	 * and will not persist enabled tracking across page loads.
	 */
	public static function add_enable_tracking_function() {
		global $wp_scripts;

		if ( ! isset( $wp_scripts->registered['er-tracks'] ) ) {
			return;
		}

		$er_tracks_script = $wp_scripts->registered['er-tracks']->src;

		?>
		<script type="text/javascript">
			window.erTracks.enable = function( callback = null ) {
				window.erTracks.isEnabled = true;

				var scriptUrl = '<?php echo esc_url( $er_tracks_script ); ?>';
				var existingScript = document.querySelector( `script[src="${scriptUrl}"]` );
				if ( existingScript ) {
					return;
				}

				var script = document.createElement( 'script' );
				script.src = scriptUrl;
				document.body.append( script );

				// Callback after scripts have loaded.
				script.onload = function() {
					if ( 'function' === typeof callback ) {
						callback( true );
					}
				}

				// Callback triggered if the script fails to load.
				script.onerror = function() {
					if ( 'function' === typeof callback ) {
						callback( false );
					}
				}
			}		</script>
		<?php
	}

	/**
	 * Init tracking.
	 */
	public static function init() {

		// Define window.wcTracks.recordEvent in case it is enabled client-side.
		self::register_scripts();
		add_filter( 'admin_footer', array( __CLASS__, 'add_tracking_function' ), 24 );

		if ( ! self::is_tracking_enabled() ) {
			add_filter( 'admin_footer', array( __CLASS__, 'add_enable_tracking_function' ), 24 );

			return;
		}

		self::enqueue_scripts();

		include_once RESERVATIONS_ABSPATH . 'includes/tracks/events/class-er-admin-setup-wizard-tracking.php';
		include_once RESERVATIONS_ABSPATH . 'includes/tracks/events/class-er-extensions-tracking.php';
		include_once RESERVATIONS_ABSPATH . 'includes/tracks/events/class-er-importer-tracking.php';
		include_once RESERVATIONS_ABSPATH . 'includes/tracks/events/class-er-products-tracking.php';
		include_once RESERVATIONS_ABSPATH . 'includes/tracks/events/class-er-orders-tracking.php';
		include_once RESERVATIONS_ABSPATH . 'includes/tracks/events/class-er-order-tracking.php';
		include_once RESERVATIONS_ABSPATH . 'includes/tracks/events/class-er-settings-tracking.php';
		include_once RESERVATIONS_ABSPATH . 'includes/tracks/events/class-er-status-tracking.php';
		include_once RESERVATIONS_ABSPATH . 'includes/tracks/events/class-er-coupons-tracking.php';

		$tracking_classes = array(
			'ER_Admin_Setup_Wizard_Tracking',
			'ER_Extensions_Tracking',
			'ER_Importer_Tracking',
			'ER_Products_Tracking',
			'ER_Orders_Tracking',
			'ER_Order_Tracking',
			'ER_Settings_Tracking',
			'ER_Status_Tracking',
			'ER_Coupons_Tracking',
		);

		foreach ( $tracking_classes as $tracking_class ) {
			$tracker_instance    = new $tracking_class();
			$tracker_init_method = array( $tracker_instance, 'init' );

			if ( is_callable( $tracker_init_method ) ) {
				call_user_func( $tracker_init_method );
			}
		}
	}
}
