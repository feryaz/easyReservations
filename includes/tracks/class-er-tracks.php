<?php
/**
 * PHP Tracks Client
 *
 * @package easyReservations\Tracks
 */

/**
 * ER_Tracks class.
 */
class ER_Tracks {

	/**
	 * Tracks event name prefix.
	 */
	const PREFIX = 'eradmin_';

	/**
	 * Get total product counts.
	 *
	 * @return int Number of products.
	 */
	public static function get_products_count() {
		$product_counts = ER_Tracker::get_product_counts();

		return $product_counts['total'];
	}

	/**
	 * Gather blog related properties.
	 *
	 * @param int $user_id User id.
	 *
	 * @return array Blog details.
	 */
	public static function get_blog_details( $user_id ) {
		$blog_details = get_transient( 'er_tracks_blog_details' );
		if ( false === $blog_details ) {
			$blog_details = array(
				'url'            => home_url(),
				'blog_lang'      => get_user_locale( $user_id ),
				'blog_id'        => class_exists( 'Jetpack_Options' ) ? Jetpack_Options::get_option( 'id' ) : null,
				'products_count' => self::get_products_count(),
				'er_version'     => ER()->version,
			);
			set_transient( 'er_tracks_blog_details', $blog_details, DAY_IN_SECONDS );
		}

		return $blog_details;
	}

	/**
	 * Gather details from the request to the server.
	 *
	 * @return array Server details.
	 */
	public static function get_server_details() {
		$data = array();

		$data['_via_ua'] = isset( $_SERVER['HTTP_USER_AGENT'] ) ? er_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$data['_via_ip'] = isset( $_SERVER['REMOTE_ADDR'] ) ? er_clean( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$data['_lg']     = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? er_clean( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '';
		$data['_dr']     = isset( $_SERVER['HTTP_REFERER'] ) ? er_clean( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		$uri         = isset( $_SERVER['REQUEST_URI'] ) ? er_clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$host        = isset( $_SERVER['HTTP_HOST'] ) ? er_clean( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$data['_dl'] = isset( $_SERVER['REQUEST_SCHEME'] ) ? er_clean( wp_unslash( $_SERVER['REQUEST_SCHEME'] ) ) . '://' . $host . $uri : '';

		return $data;
	}

	/**
	 * Record an event in Tracks - this is the preferred way to record events from PHP.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $properties Custom properties to send with the event.
	 *
	 * @return bool|WP_Error True for success or WP_Error if the event pixel could not be fired.
	 */
	public static function record_event( $event_name, $properties = array() ) {
		/**
		 * Don't track users who don't have tracking enabled.
		 */
		if ( ! ER_Site_Tracking::is_tracking_enabled() ) {
			return false;
		}

		$user = wp_get_current_user();

		// We don't want to track user events during unit tests/CI runs.
		if ( $user instanceof WP_User && 'wptests_capabilities' === $user->cap_key ) {
			return false;
		}

		$prefixed_event_name = self::PREFIX . $event_name;

		$data = array(
			'_en' => $prefixed_event_name,
			'_ts' => ER_Tracks_Client::build_timestamp(),
		);

		$server_details = self::get_server_details();
		$identity       = ER_Tracks_Client::get_identity( $user->ID );
		$blog_details   = self::get_blog_details( $user->ID );

		// Allow event props to be filtered to enable adding site-wide props.
		$filtered_properties = apply_filters( 'easyreservations_tracks_event_properties', $properties, $prefixed_event_name );

		// Delete _ui and _ut protected properties.
		unset( $filtered_properties['_ui'] );
		unset( $filtered_properties['_ut'] );

		$event_obj = new ER_Tracks_Event( array_merge( $data, $server_details, $identity, $blog_details, $filtered_properties ) );

		if ( is_wp_error( $event_obj->error ) ) {
			return $event_obj->error;
		}

		return $event_obj->record();
	}
}
