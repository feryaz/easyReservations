<?php
/**
 * ER_Cache_Helper class.
 */

defined( 'ABSPATH' ) || exit;

/**
 * ER_Cache_Helper.
 */
class ER_Cache_Helper {

	/**
	 * Transients to delete on shutdown.
	 *
	 * @var array Array of transient keys.
	 */
	private static $delete_transients = array();

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_filter( 'nocache_headers', array( __CLASS__, 'additional_nocache_headers' ), 10 );
		add_action( 'shutdown', array( __CLASS__, 'delete_transients_on_shutdown' ), 10 );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'delete_version_transients', array( __CLASS__, 'delete_version_transients' ), 10 );
		add_action( 'wp', array( __CLASS__, 'prevent_caching' ) );
		add_action( 'clean_term_cache', array( __CLASS__, 'clean_term_cache' ), 10, 2 );
		add_action( 'edit_terms', array( __CLASS__, 'clean_term_cache' ), 10, 2 );
	}

	/**
	 * Set additional nocache headers.
	 *
	 * @param array $headers Header names and field values.
	 * @since 3.6.0
	 */
	public static function additional_nocache_headers( $headers ) {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		/**
		 * Allow plugins to enable nocache headers. Enabled for Google weblight.
		 *
		 * @see   https://support.google.com/webmasters/answer/1061943?hl=en
		 * @param bool $enable_nocache_headers Flag indicating whether to add nocache headers. Default: false.
		 */
		if ( false !== strpos( $agent, 'googleweblight' ) || apply_filters( 'easyreservations_enable_nocache_headers', false ) ) {
			// no-transform: Opt-out of Google weblight. https://support.google.com/webmasters/answer/6211428?hl=en.
			$headers['Cache-Control'] = 'no-transform, no-cache, no-store, must-revalidate';
		}
		return $headers;
	}

	/**
	 * Add a transient to delete on shutdown.
	 *
	 * @since 3.6.0
	 * @param string|array $keys Transient key or keys.
	 */
	public static function queue_delete_transient( $keys ) {
		self::$delete_transients = array_unique( array_merge( is_array( $keys ) ? $keys : array( $keys ), self::$delete_transients ) );
	}

	/**
	 * Transients that don't need to be cleaned right away can be deleted on shutdown to avoid repetition.
	 *
	 * @since 3.6.0
	 */
	public static function delete_transients_on_shutdown() {
		if ( self::$delete_transients ) {
			foreach ( self::$delete_transients as $key ) {
				delete_transient( $key );
			}
			self::$delete_transients = array();
		}
	}

	/**
	 * Used to clear layered nav counts based on passed attribute names.
	 *
	 * @since 3.6.0
	 * @param array $attribute_keys Attribute keys.
	 */
	public static function invalidate_attribute_count( $attribute_keys ) {
		if ( $attribute_keys ) {
			foreach ( $attribute_keys as $attribute_key ) {
				self::queue_delete_transient( 'er_layered_nav_counts_' . $attribute_key );
			}
		}
	}

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @param  string $group Group of cache to get.
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		// Get cache key - uses cache key er_orders_cache_prefix to invalidate when needed.
		$prefix = wp_cache_get( 'er_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = microtime();
			wp_cache_set( 'er_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'er_cache_' . $prefix . '_';
	}

	/**
	 * Invalidate cache group.
	 *
	 * @param string $group Group of cache to clear.
	 */
	public static function invalidate_cache_group( $group ) {
		wp_cache_set( 'er_' . $group . '_cache_prefix', microtime(), $group );
	}

	/**
	 * Prevent caching on certain pages
	 */
	public static function prevent_caching() {
		if ( ! is_blog_installed() ) {
			return;
		}
		$page_ids = array_filter( array( er_get_page_id( 'cart' ), er_get_page_id( 'checkout' ), er_get_page_id( 'myaccount' ) ) );

		if ( is_page( $page_ids ) ) {
			self::set_nocache_constants();
			nocache_headers();
		}
	}

	/**
	 * Get transient version.
	 *
	 * When using transients with unpredictable names, e.g. those containing an md5
	 * hash in the name, we need a way to invalidate them all at once.
	 *
	 * When using default WP transients we're able to do this with a DB query to
	 * delete transients manually.
	 *
	 * With external cache however, this isn't possible. Instead, this function is used
	 * to append a unique string (based on time()) to each transient. When transients
	 * are invalidated, the transient version will increment and data will be regenerated.
	 *
	 * Raised in issue https://github.com/woocommerce/woocommerce/issues/5777.
	 * Adapted from ideas in http://tollmanz.com/invalidation-schemes/.
	 *
	 * @param  string  $group   Name for the group of transients we need to invalidate.
	 * @param  boolean $refresh true to force a new version.
	 * @return string transient version based on time(), 10 digits.
	 */
	public static function get_transient_version( $group, $refresh = false ) {
		$transient_name  = $group . '-transient-version';
		$transient_value = get_transient( $transient_name );

		if ( false === $transient_value || true === $refresh ) {
			$transient_value = (string) time();

			set_transient( $transient_name, $transient_value );
		}

		return $transient_value;
	}

	/**
	 * Set constants to prevent caching by some plugins.
	 *
	 * @param  mixed $return Value to return. Previously hooked into a filter.
	 * @return mixed
	 */
	public static function set_nocache_constants( $return = true ) {
		er_maybe_define_constant( 'DONOTCACHEPAGE', true );
		er_maybe_define_constant( 'DONOTCACHEOBJECT', true );
		er_maybe_define_constant( 'DONOTCACHEDB', true );
		return $return;
	}

	/**
	 * Notices function.
	 */
	public static function notices() {
		if ( ! function_exists( 'w3tc_pgcache_flush' ) || ! function_exists( 'w3_instance' ) ) {
			return;
		}

		$config   = w3_instance( 'W3_Config' );
		$enabled  = $config->get_integer( 'dbcache.enabled' );
		$settings = array_map( 'trim', $config->get_array( 'dbcache.reject.sql' ) );

		if ( $enabled && ! in_array( '_er_session_', $settings, true ) ) {
			?>
			<div class="error">
				<p>
				<?php
				/* translators: 1: key 2: URL */
				echo wp_kses_post( sprintf( __( 'In order for <strong>database caching</strong> to work with easyReservations you must add %1$s to the "Ignored Query Strings" option in <a href="%2$s">W3 Total Cache settings</a>.', 'easyReservations' ), '<code>_er_session_</code>', esc_url( admin_url( 'admin.php?page=w3tc_dbcache' ) ) ) );
				?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Clean term caches added by easyReservations.
	 *
	 * @since 3.3.4
	 * @param array|int $ids Array of ids or single ID to clear cache for.
	 * @param string    $taxonomy Taxonomy name.
	 */
	public static function clean_term_cache( $ids, $taxonomy ) {
		if ( 'resource_cat' === $taxonomy ) {
			$ids = is_array( $ids ) ? $ids : array( $ids );

			$clear_ids = array( 0 );

			foreach ( $ids as $id ) {
				$clear_ids[] = $id;
				$clear_ids   = array_merge( $clear_ids, get_ancestors( $id, 'resource_cat', 'taxonomy' ) );
			}

			$clear_ids = array_unique( $clear_ids );

			foreach ( $clear_ids as $id ) {
				wp_cache_delete( 'resource-category-hierarchy-' . $id, 'resource_cat' );
			}
		}
	}

	/**
	 * When the transient version increases, this is used to remove all past transients to avoid filling the DB.
	 *
	 * Note; this only works on transients appended with the transient version, and when object caching is not being used.
	 *
	 * @deprecated 3.6.0 Adjusted transient usage to include versions within the transient values, making this cleanup obsolete.
	 * @since  2.3.10
	 * @param string $version Version of the transient to remove.
	 */
	public static function delete_version_transients( $version = '' ) {
		if ( ! wp_using_ext_object_cache() && ! empty( $version ) ) {
			global $wpdb;

			$limit = apply_filters( 'easyreservations_delete_version_transients_limit', 1000 );

			if ( ! $limit ) {
				return;
			}

			$affected = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT %d;", '\_transient\_%' . $version, $limit ) ); // WPCS: cache ok, db call ok.

			// If affected rows is equal to limit, there are more rows to delete. Delete in 30 secs.
			if ( $affected === $limit ) {
				wp_schedule_single_event( time() + 30, 'delete_version_transients', array( $version ) );
			}
		}
	}
}

ER_Cache_Helper::init();
