<?php
/**
 * Install and Update easyReservations.
 */

//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Install {

	/**
	 * Array of update files
	 *
	 * @var array
	 */
	private static $db_updates = array(
		'1.4'         => 'updates/easyreservations-update-1.4.php',
		'1.5'         => 'updates/easyreservations-update-1.5.php',
		'1.6'         => 'updates/easyreservations-update-1.6.php',
		'1.7'         => 'updates/easyreservations-update-1.7.php',
		'3.2'         => 'updates/easyreservations-update-3.2.php',
		'4.0'         => 'updates/easyreservations-update-4.0.php',
		'5.0'         => 'updates/easyreservations-update-5.0.php',
		'6.0-alpha.1' => 'updates/easyreservations-update-6.0.alpha.1.php',
		'6.0-alpha.6' => 'updates/easyreservations-update-6.0.alpha.6.php',
	);

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
	}

	/**
	 * Check easyReservations version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) ) {
			$plugins = apply_filters( 'easyreservations_plugins_versions', array(
				'reservations' => ER()->version
			) );

			foreach ( $plugins as $name => $version ) {
				if ( self::needs_install( $name, $version ) ) {
					do_action( 'easyreservations_before_update' );
					self::install( $name );
					do_action( 'easyreservations_updated' );
				}
			}
		}
	}

	/**
	 * Install ER.
	 *
	 * @param string $plugin Plugin to install
	 */
	public static function install( $plugin = 'reservations' ) {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'er_' . $plugin . '_installing' ) ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'er_' . $plugin . '_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		if ( $plugin === 'reservations' ) {
			define( 'RESERVATIONS_INSTALLING', true );

			ER()->define_tables();
			self::create_tables();
			self::verify_base_tables();
			self::create_options();
			self::create_roles();
			self::setup_environment();
			self::create_terms();
			self::create_cron_jobs();
			self::create_files();
			self::maybe_create_pages();
			self::maybe_set_activation_transients();
			self::update_er_version();
			self::maybe_update_db_version();

			if ( self::is_new_install() ) {
				ER_Admin_Notices::add_notice( 'install' );
			}

			do_action( 'easyreservations_flush_rewrite_rules' );
			do_action( 'easyreservations_installed' );
		} else {
			do_action( 'easyreservations_' . $plugin . '_install' );

			self::maybe_update_db_version( $plugin );
		}

		delete_transient( 'er_' . $plugin . '_installing' );
	}

	/**
	 * Check if all the base tables are present.
	 *
	 * @param bool $modify_notice Whether to modify notice based on if all tables are present.
	 * @param bool $execute Whether to execute get_schema queries as well.
	 *
	 * @return array List of querues.
	 */
	public static function verify_base_tables( $modify_notice = true, $execute = false ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( $execute ) {
			self::create_tables();
		}
		$queries        = dbDelta( self::get_schema(), false );
		$missing_tables = array();
		foreach ( $queries as $table_name => $result ) {
			if ( "Created table $table_name" === $result ) {
				$missing_tables[] = $table_name;
			}
		}

		if ( 0 < count( $missing_tables ) ) {
			if ( $modify_notice ) {
				ER_Admin_Notices::add_notice( 'base_tables_missing' );
			}
			update_option( 'reservations_schema_missing_tables', $missing_tables );
		} else {
			if ( $modify_notice ) {
				ER_Admin_Notices::remove_notice( 'base_tables_missing' );
			}
			update_option( 'reservations_schema_version', ER()->db_version );
			delete_option( 'reservations_schema_missing_tables' );
		}

		return $missing_tables;
	}
	/**
	 * Is this a brand new ER install?
	 *
	 * A brand new install has no version yet. Also treat empty installs as 'new'.
	 *
	 * @return boolean
	 */
	private static function is_new_install() {
		$resource_count = array_sum( (array) wp_count_posts( 'easy-rooms' ) );

		return is_null( get_option( 'reservations_db_version', null ) ) || ( 0 === $resource_count && - 1 === er_get_page_id( 'shop' ) );
	}

	/**
	 * Is a DB update needed?
	 *
	 * @param string $plugin Plugin to install
	 *
	 * @return boolean
	 */
	public static function needs_db_update( $plugin = 'reservations' ) {
		$current_db_version = get_option( $plugin . '_db_version', null );

		if ( $plugin !== 'reservations' ) {
			$updates = apply_filters( 'easyreservations_' . $plugin . '_db_updates', array() );
		} else {
			$updates = self::$db_updates;
		}

		$update_versions = array_keys( $updates );
		usort( $update_versions, 'version_compare' );

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, end( $update_versions ), '<' );
	}

	/**
	 * See if we need to set redirect transients for activation or not.
	 */
	private static function maybe_set_activation_transients() {
		if ( self::is_new_install() ) {
			set_transient( '_er_activation_redirect', 1, 30 );
		}
	}
	/**
	 * See if we need to show or run database updates during install.
	 *
	 * @param string $plugin Plugin to install
	 */
	private static function maybe_update_db_version( $plugin = 'reservations' ) {
		if ( self::needs_db_update( $plugin ) ) {
			if ( apply_filters( 'easyreservations_enable_auto_update_db', false ) ) {
				self::update( $plugin );
			} else {
				ER_Admin_Notices::add_notice( 'update_' . $plugin );
			}
		} else {
			if ( $plugin === 'reservations' ) {
				self::update_db_version();
			}
		}
	}

	/**
	 * Update ER version to current.
	 */
	private static function update_er_version() {
		update_option( 'reservations_version', ER()->version );
	}

	/**
	 * Update DB version to current.
	 *
	 * @param string|null $version New easyReservations DB version or null.
	 */
	public static function update_db_version( $version = null ) {
		update_option( 'reservations_db_version', is_null( $version ) ? ER()->version : $version );
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_easyreservations'] ) ) { // WPCS: input var ok.
			check_admin_referer( 'er_db_update', 'er_db_update_nonce' );

			$plugin = sanitize_key( $_GET['do_update_easyreservations'] );

			self::update( $plugin );
		}
	}

	/**
	 * Check if a plugin needs update
	 *
	 * @param string $plugin name
	 * @param string $version current version
	 *
	 * @return bool
	 */
	private static function needs_install( $plugin, $version ) {
		if ( get_option( $plugin . '_version' ) !== $version ) {
			return true;
		}

		return false;
	}

	/**
	 * Execute all updates for a given plugin
	 *
	 * @param string $plugin
	 */
	private static function update( $plugin = 'reservations' ) {
		$db_version = get_option( $plugin . '_db_version' );

		if ( $plugin !== 'reservations' ) {
			$db_updates = apply_filters( 'easyreservations_' . $plugin . '_db_updates', array() );
		} else {
			$db_updates = self::$db_updates;
		}

		foreach ( $db_updates as $version => $update_script ) {
			if ( version_compare( $db_version, $version, '<' ) ) {
				include( $update_script );
				update_option( $plugin . '_db_version', $version );
			}
		}
	}

	/**
	 * Setup ER environment - post types, taxonomies, endpoints.
	 */
	private static function setup_environment() {
		ER()->query->init_query_vars();
		ER()->query->add_endpoints();
		ER_API::add_endpoint();
	}

	/**
	 * Add more cron schedules.
	 *
	 * @param array $schedules List of WP scheduled cron jobs.
	 *
	 * @return array
	 */
	public static function cron_schedules( $schedules ) {
		$schedules['monthly']     = array(
			'interval' => 2635200,
			'display'  => __( 'Monthly', 'easyReservations' ),
		);
		$schedules['fifteendays'] = array(
			'interval' => 1296000,
			'display'  => __( 'Every 15 Days', 'easyReservations' ),
		);

		return $schedules;
	}

	/**
	 * Create cron jobs (clear them first).
	 */
	private static function create_cron_jobs() {
		wp_clear_scheduled_hook( 'easyreservations_scheduled_sales' );
		wp_clear_scheduled_hook( 'easyreservations_cancel_unpaid_orders' );
		wp_clear_scheduled_hook( 'easyreservations_cleanup_sessions' );
		wp_clear_scheduled_hook( 'easyreservations_cleanup_personal_data' );
		wp_clear_scheduled_hook( 'easyreservations_cleanup_logs' );
		wp_clear_scheduled_hook( 'easyreservations_tracker_send_event' );

		$ve = get_option( 'gmt_offset' ) > 0 ? '-' : '+';

		wp_schedule_event( strtotime( '00:00 tomorrow ' . $ve . absint( get_option( 'gmt_offset' ) ) . ' HOURS' ), 'daily', 'easyreservations_scheduled_sales' );

		$held_duration           = get_option( 'reservations_wait_for_payment_minutes', '60' );
		$wait_for_order_duration = get_option( 'reservations_wait_for_ordering_minutes', '60' );

		if ( ! is_numeric( $wait_for_order_duration ) || $wait_for_order_duration === '0' ) {
			$wait_for_order_duration = '60';
		}

		wp_schedule_single_event( time() + ( absint( $wait_for_order_duration ) * 60 ), 'easyreservations_delete_temporary_reservations' );

		if ( '' !== $held_duration ) {
			wp_schedule_single_event( time() + ( absint( $held_duration ) * 60 ), 'easyreservations_cancel_unpaid_orders' );
		}

		wp_schedule_event( time(), 'daily', 'easyreservations_cleanup_personal_data' );
		wp_schedule_event( time() + ( 3 * HOUR_IN_SECONDS ), 'daily', 'easyreservations_cleanup_logs' );
		wp_schedule_event( time() + ( 6 * HOUR_IN_SECONDS ), 'twicedaily', 'easyreservations_cleanup_sessions' );
		wp_schedule_event( time() + 10, apply_filters( 'easyreservations_tracker_event_recurrence', 'daily' ), 'easyreservations_tracker_send_event' );
	}

	/**
	 * Create pages on installation.
	 */
	public static function maybe_create_pages() {
		if ( empty( get_option( 'reservations_db_version' ) ) ) {
			self::create_pages();
		}
	}

	/**
	 * Create pages that the plugin relies on, storing page IDs in variables.
	 */
	public static function create_pages() {
		$pages = apply_filters(
			'easyreservations_create_pages',
			array(
				'shop'      => array(
					'name'    => _x( 'catalog', 'Page slug', 'easyReservations' ),
					'title'   => _x( 'Catalog', 'Page title', 'easyReservations' ),
					'content' => '<!-- wp:shortcode -->[resources]<!-- /wp:shortcode -->',
				),
				'cart'      => array(
					'name'    => _x( 'er-cart', 'Page slug', 'easyReservations' ),
					'title'   => _x( 'Cart', 'Page title', 'easyReservations' ),
					'content' => '<!-- wp:shortcode -->[easy_cart]<!-- /wp:shortcode -->',
				),
				'checkout'  => array(
					'name'    => _x( 'er-checkout', 'Page slug', 'easyReservations' ),
					'title'   => _x( 'Checkout', 'Page title', 'easyReservations' ),
					'content' => '<!-- wp:shortcode -->[easy_checkout]<!-- /wp:shortcode -->',
				),
				'myaccount' => array(
					'name'    => _x( 'er-my-account', 'Page slug', 'easyReservations' ),
					'title'   => _x( 'My account', 'Page title', 'easyReservations' ),
					'content' => '<!-- wp:shortcode -->[easy_my_account]<!-- /wp:shortcode -->',
				),
			)
		);

		foreach ( $pages as $key => $page ) {
			er_create_page( esc_sql( $page['name'] ), 'reservations_' . $key . '_page_id', $page['title'], $page['content'], isset( $page['parent'] ) ? er_get_page_id( $page['parent'] ) : '' );
		}
	}

	/**
	 * Create files/directories.
	 */
	private static function create_files() {
		// Bypass if filesystem is read-only and/or non-standard upload system is used.
		if ( apply_filters( 'easyreservations_install_skip_create_files', false ) ) {
			return;
		}

		$files = array(
			array(
				'base'    => RESERVATIONS_LOG_DIR,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			),
			array(
				'base'    => RESERVATIONS_LOG_DIR,
				'file'    => 'index.html',
				'content' => '',
			),
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
				}
			}
		}
	}

	/**
	 * Create files/directories.
	 */
	private static function create_options() {
		add_option( 'reservations_uninstall', '1', '', 'no' );
		add_option( 'reservations_form', er_form_get_default(), '', 'no' );
		add_option( 'reservations_form_checkout', er_form_get_default( 'checkout' ), '', 'no' );

		add_option( 'reservations_custom_fields', array(
				'id'     => 2,
				'fields' => array(
					1 => array( 'title' => 'Test', 'type' => 'text', 'value' => '', 'unused' => '' ),
				)
			)
		);
	}

	/**
	 * Create easyReservations roles.
	 */
	public static function create_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		add_role(
			'easy_customer',
			__( 'Customer', 'easyReservations' ),
			array(
				'read' => true,
			)
		);

		// Shop manager role.
		add_role(
			'reservation_manager',
			__( 'Reservation Manager', 'easyReservations' ),
			array(
				'level_9'                => true,
				'level_8'                => true,
				'level_7'                => true,
				'level_6'                => true,
				'level_5'                => true,
				'level_4'                => true,
				'level_3'                => true,
				'level_2'                => true,
				'level_1'                => true,
				'level_0'                => true,
				'read'                   => true,
				'read_private_pages'     => true,
				'read_private_posts'     => true,
				'edit_posts'             => true,
				'edit_pages'             => true,
				'edit_published_posts'   => true,
				'edit_published_pages'   => true,
				'edit_private_pages'     => true,
				'edit_private_posts'     => true,
				'edit_others_posts'      => true,
				'edit_others_pages'      => true,
				'publish_posts'          => true,
				'publish_pages'          => true,
				'delete_posts'           => true,
				'delete_pages'           => true,
				'delete_private_pages'   => true,
				'delete_private_posts'   => true,
				'delete_published_pages' => true,
				'delete_published_posts' => true,
				'delete_others_posts'    => true,
				'delete_others_pages'    => true,
				'manage_categories'      => true,
				'manage_links'           => true,
				'moderate_comments'      => true,
				'upload_files'           => true,
				'export'                 => true,
				'import'                 => true,
				'list_users'             => true,
				'edit_theme_options'     => true,
			)
		);

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->add_cap( 'reservation_manager', $cap );
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}

	/**
	 * Remove easyReservations roles.
	 */
	public static function remove_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->remove_cap( 'reservation_manager', $cap );
				$wp_roles->remove_cap( 'administrator', $cap );
			}
		}

		remove_role( 'easy_customer' );
		remove_role( 'reservation_manager' );
	}

	/**
	 * Get capabilities for easyReservations - these are assigned to admin/shop manager during installation or reset.
	 *
	 * @return array
	 */
	public static function get_core_capabilities() {
		$capabilities = array(
			'core' => array(
				'manage_easyreservations',
				'view_easyreservations_reports',
			)
		);

		$capability_types = array( 'easy_order', 'easy_coupon', 'easy_resource' );

		foreach ( $capability_types as $capability_type ) {

			$capabilities[ $capability_type ] = array(
				// Post type.
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms.
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms",
			);
		}

		return $capabilities;
	}

	/**
	 * Add the default terms for ER taxonomies - resource types and order statuses. Modify this at your own risk.
	 */
	public static function create_terms() {
		$taxonomies = array(
			'resource_visibility' => array(
				'exclude-from-search',
				'exclude-from-catalog',
				'featured',
			)
		);

		foreach ( $taxonomies as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				if ( ! get_term_by( 'name', $term, $taxonomy ) ) { // @codingStandardsIgnoreLine.
					wp_insert_term( $term, $taxonomy );
				}
			}
		}

		$default_category = (int) get_option( 'default_resource_cat', 0 );

		if ( ! $default_category || ! term_exists( $default_category, 'resource_cat' ) ) {
			$default_resource_cat_id   = 0;
			$default_resource_cat_slug = sanitize_title( _x( 'Uncategorized', 'Default category slug', 'easyReservations' ) );
			$default_resource_cat      = get_term_by( 'slug', $default_resource_cat_slug, 'resource_cat' ); // @codingStandardsIgnoreLine.

			if ( $default_resource_cat ) {
				$default_resource_cat_id = absint( $default_resource_cat->term_taxonomy_id );
			} else {
				$result = wp_insert_term( _x( 'Uncategorized', 'Default category slug', 'easyReservations' ), 'resource_cat', array( 'slug' => $default_resource_cat_slug ) );

				if ( ! is_wp_error( $result ) && ! empty( $result['term_taxonomy_id'] ) ) {
					$default_resource_cat_id = absint( $result['term_taxonomy_id'] );
				}
			}

			if ( $default_resource_cat_id ) {
				update_option( 'default_resource_cat', $default_resource_cat_id );
			}
		}
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 * WARNING: If you are modifying this method, make sure that its safe to call regardless of the state of database.
	 *
	 * This is called from `install` method and is executed in-sync when ER is installed or updated. This can also be called optionally from `verify_base_tables`.
	 *
	 * Tables:
	 *      receipt_items - Receipt line items are stored in a table to make them easily queryable for reports
	 *      receipt_itemmeta - Receipt line item meta is stored in a table for storing extra data.
	 */
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		/**
		 * Change wp_reservations_sessions schema to use a bigint auto increment field instead of char(32) field as
		 * the primary key as it is not a good practice to use a char(32) field as the primary key of a table and as
		 * there were reports of issues with this table (see https://github.com/woocommerce/woocommerce/issues/20912).
		 *
		 * This query needs to run before dbDelta() as this WP function is not able to handle primary key changes
		 * (see https://github.com/woocommerce/woocommerce/issues/21534 and https://core.trac.wordpress.org/ticket/40357).
		 */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}reservations_sessions'" ) ) {
			if ( ! $wpdb->get_var( "SHOW KEYS FROM {$wpdb->prefix}reservations_sessions WHERE Key_name = 'PRIMARY' AND Column_name = 'session_id'" ) ) {
				$wpdb->query(
					"ALTER TABLE `{$wpdb->prefix}reservations_sessions` DROP PRIMARY KEY, DROP KEY `session_id`, ADD PRIMARY KEY(`session_id`), ADD UNIQUE KEY(`session_key`)"
				);
			}
		}

		dbDelta( self::get_schema() );
	}

	/**
	 * Get Table schema.
	 *
	 * A note on indexes; Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	 * As of WordPress 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 *
	 * Changing indexes may cause duplicate index notices in logs due to https://core.trac.wordpress.org/ticket/34870 but dropping
	 * indexes first causes too much load on some servers/larger DB.
	 *
	 * When adding or removing a table, make sure to update the list of tables in ER_Install::get_tables().
	 *
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		/*
		 * Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
		 * As of WP 4.2, however, they moved to utf8mb4, which uses 4 bytes per character. This means that an index which
		 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
		 */
		$max_index_length = 191;

		$tables = "
CREATE TABLE {$wpdb->prefix}reservations (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    order_id bigint(20) unsigned NOT NULL,
    arrival DATETIME NOT NULL,
    departure DATETIME NOT NULL,
    status varchar(10) NOT NULL,
    resource bigint(20) NOT NULL,
    space int(10) NOT NULL,
    adults int(10) NOT NULL,
    children int(10) NOT NULL,
  PRIMARY KEY (id)
) $collate;
CREATE TABLE {$wpdb->prefix}reservationmeta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  reservation_id bigint(20) unsigned NOT NULL default '0',
  meta_key varchar(255) default NULL,
  meta_value longtext,
  PRIMARY KEY  (meta_id),
  KEY reservation_id (reservation_id),
  KEY meta_key (meta_key($max_index_length))
) $collate;
CREATE TABLE {$wpdb->prefix}reservations_sessions (
  session_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_key char(32) NOT NULL,
  session_value longtext NOT NULL,
  session_expiry BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY  (session_id),
  UNIQUE KEY session_key (session_key)
) $collate;
CREATE TABLE {$wpdb->prefix}receipt_items (
  receipt_item_id BIGINT UNSIGNED NOT NULL auto_increment,
  receipt_item_name TEXT NOT NULL,
  receipt_item_type varchar(200) NOT NULL DEFAULT '',
  receipt_object_type varchar(200) NOT NULL DEFAULT '',
  receipt_object_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY  (receipt_item_id),
  KEY order_id (receipt_object_id)
) $collate;
CREATE TABLE {$wpdb->prefix}receipt_itemmeta (
  meta_id BIGINT UNSIGNED NOT NULL auto_increment,
  receipt_item_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY receipt_item_id (receipt_item_id),
  KEY meta_key (meta_key(32))
) $collate;";

		return $tables;
	}

	/**
	 * Return a list of easyReservations tables. Used to make sure all ER tables are dropped when uninstalling the plugin
	 * in a single site or multi site environment.
	 *
	 * @return array ER tables.
	 */
	public static function get_tables() {
		global $wpdb;

		$tables = array(
			"{$wpdb->prefix}reservations",
			"{$wpdb->prefix}reservation_meta",
			"{$wpdb->prefix}reservations_sessions",
			"{$wpdb->prefix}receipt_items",
			"{$wpdb->prefix}receipt_itemmeta",
			"{$wpdb->prefix}reservations_payment_tokenmeta",
			"{$wpdb->prefix}reservations_payment_tokens",
		);

		/**
		 * Filter the list of known easyReservations tables.
		 *
		 * If easyReservations plugins need to add new tables, they can inject them here.
		 *
		 * @param array $tables An array of easyReservations-specific database table names.
		 */
		$tables = apply_filters( 'easyreservations_install_get_tables', $tables );

		return $tables;
	}

	/**
	 * Drop easyReservations tables.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = self::get_tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 *
	 * @param array $tables List of tables that will be deleted by WP.
	 *
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		return array_merge( $tables, self::get_tables() );
	}
}

ER_Install::init();
