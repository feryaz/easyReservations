<?php
/**
 * Get all easyReservations screen ids.
 *
 * @return array
 */
function er_get_screen_ids() {
	return apply_filters( 'easyreservations_screen_ids', array(
		'reservations_page_er-settings',
		'reservations_page_reservation-statistics',
		'reservations_page_reservation-availability',
		'reservations_page_reservations',
		'admin_page_resource',
		'admin_page_reservation',
		'easy_reservation',
		'edit-easy_coupon',
		'edit-easy_reservation',
		'edit-easy-rooms',
		'easy-rooms',
		'edit-easy_order',
		'easy_order',
		'easy_coupon',
		'profile',
		'user-edit',
	) );
}

/**
 * Format plugin data, including data on updates, into a standard format.
 *
 * @param string $plugin Plugin directory/file.
 * @param array  $data Plugin data from WP.
 *
 * @return array Formatted data.
 */
function er_admin_format_plugin_data( $plugin, $data, $available_updates ) {
	require_once ABSPATH . 'wp-admin/includes/update.php';

	if ( ! function_exists( 'get_plugin_updates' ) ) {
		return array();
	}

	$version_latest = $data['Version'];

	// Find latest version.
	if ( isset( $available_updates[ $plugin ]->update->new_version ) ) {
		$version_latest = $available_updates[ $plugin ]->update->new_version;
	}

	return array(
		'plugin'            => $plugin,
		'name'              => $data['Name'],
		'version'           => $data['Version'],
		'version_latest'    => $version_latest,
		'url'               => $data['PluginURI'],
		'author_name'       => $data['AuthorName'],
		'author_url'        => esc_url_raw( $data['AuthorURI'] ),
		'network_activated' => $data['Network'],
	);
}

/**
 * Scan the template files.
 *
 * @param string $template_path Path to the template directory.
 *
 * @return array
 */
function er_admin_scan_template_files( $template_path ) {
	$files  = @scandir( $template_path ); // @codingStandardsIgnoreLine.
	$result = array();

	if ( ! empty( $files ) ) {

		foreach ( $files as $key => $value ) {

			if ( ! in_array( $value, array( '.', '..' ), true ) ) {

				if ( is_dir( $template_path . DIRECTORY_SEPARATOR . $value ) ) {
					$sub_files = er_admin_scan_template_files( $template_path . DIRECTORY_SEPARATOR . $value );
					foreach ( $sub_files as $sub_file ) {
						$result[] = $value . DIRECTORY_SEPARATOR . $sub_file;
					}
				} else {
					$result[] = $value;
				}
			}
		}
	}

	return $result;
}

/**
 * Get array of database information. Version, prefix, and table existence.
 *
 * @return array
 */

function er_admin_get_server_database_info() {
	global $wpdb;

	$tables        = array();
	$database_size = array();

	// It is not possible to get the database name from some classes that replace wpdb (e.g., HyperDB)
	// and that is why this if condition is needed.
	if ( defined( 'DB_NAME' ) ) {
		$database_table_information = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					    table_name AS 'name',
						engine AS 'engine',
					    round( ( data_length / 1024 / 1024 ), 2 ) 'data',
					    round( ( index_length / 1024 / 1024 ), 2 ) 'index'
					FROM information_schema.TABLES
					WHERE table_schema = %s
					ORDER BY name ASC;",
				DB_NAME
			)
		);

		// ER Core tables to check existence of.
		$core_tables = apply_filters(
			'easyreservations_database_tables',
			array(
				'reservations',
				'reservationmeta',
				'reservations_sessions',
			)
		);

		/**
		 * Adding the prefix to the tables array, for backwards compatibility.
		 *
		 * If we changed the tables above to include the prefix, then any filters against that table could break.
		 */
		foreach ( $core_tables as $key => $value ) {
			$core_tables[ $key ] = $wpdb->prefix . $value;
		}

		/**
		 * Organize easyReservations and non-easyReservations tables separately for display purposes later.
		 *
		 * To ensure we include all ER tables, even if they do not exist, pre-populate the ER array with all the tables.
		 */
		$tables = array(
			'easyreservations' => array_fill_keys( $core_tables, false ),
			'other'            => array(),
		);

		$database_size = array(
			'data'  => 0,
			'index' => 0,
		);

		$site_tables_prefix = $wpdb->get_blog_prefix( get_current_blog_id() );
		$global_tables      = $wpdb->tables( 'global', true );
		foreach ( $database_table_information as $table ) {
			// Only include tables matching the prefix of the current site, this is to prevent displaying all tables on a MS install not relating to the current.
			if ( is_multisite() && 0 !== strpos( $table->name, $site_tables_prefix ) && ! in_array( $table->name, $global_tables, true ) ) {
				continue;
			}
			$table_type = in_array( $table->name, $core_tables ) ? 'easyreservations' : 'other';

			$tables[ $table_type ][ $table->name ] = array(
				'data'   => $table->data,
				'index'  => $table->index,
				'engine' => $table->engine,
			);

			$database_size['data']  += $table->data;
			$database_size['index'] += $table->index;
		}
	}

	// Return all database info. Described by JSON Schema.
	return array(
		'er_database_version' => get_option( 'reservations_db_version' ),
		'database_prefix'     => $wpdb->prefix,
		'database_tables'     => $tables,
		'database_size'       => $database_size,
	);
}

/**
 * Retrieve metadata from a file. Based on WP Core's get_file_data function.
 *
 * @param string $file Path to the file.
 *
 * @return string
 */
function er_admin_get_file_version( $file ) {

	// Avoid notices if file does not exist.
	if ( ! file_exists( $file ) ) {
		return '';
	}

	// We don't need to write to the file, so just open for reading.
	$fp = fopen( $file, 'r' ); // @codingStandardsIgnoreLine.

	// Pull only the first 8kiB of the file in.
	$file_data = fread( $fp, 8192 ); // @codingStandardsIgnoreLine.

	// PHP will close file handle, but we are good citizens.
	fclose( $fp ); // @codingStandardsIgnoreLine.

	// Make sure we catch CR-only line endings.
	$file_data = str_replace( "\r", "\n", $file_data );
	$version   = '';

	if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( '@version', '/' ) . '(.*)$/mi', $file_data, $match ) && $match[1] ) {
		$version = _cleanup_header_comment( $match[1] );
	}

	return $version;
}

/**
 * Get latest version of a theme by slug.
 *
 * @param object $theme WP_Theme object.
 *
 * @return string Version number if found.
 */
function er_admin_get_latest_theme_version( $theme ) {
	include_once ABSPATH . 'wp-admin/includes/theme.php';

	$api = themes_api(
		'theme_information',
		array(
			'slug'   => $theme->get_stylesheet(),
			'fields' => array(
				'sections' => false,
				'tags'     => false,
			),
		)
	);

	$update_theme_version = 0;

	// Check .org for updates.
	if ( is_object( $api ) && ! is_wp_error( $api ) ) {
		$update_theme_version = $api->version;
	} elseif ( strstr( $theme->{'Author URI'}, 'feryaz' ) ) { // Check easyReservations Theme Version.
		$theme_dir          = substr( strtolower( str_replace( ' ', '', $theme->Name ) ), 0, 45 ); // @codingStandardsIgnoreLine.
		$theme_version_data = get_transient( $theme_dir . '_version_data' );

		if ( false === $theme_version_data ) {
			$theme_changelog = wp_safe_remote_get( 'http://dzv365zjfbd8v.cloudfront.net/changelogs/' . $theme_dir . '/changelog.txt' );
			$cl_lines        = explode( "\n", wp_remote_retrieve_body( $theme_changelog ) );
			if ( ! empty( $cl_lines ) ) {
				foreach ( $cl_lines as $line_num => $cl_line ) {
					if ( preg_match( '/^[0-9]/', $cl_line ) ) {
						$theme_date         = str_replace( '.', '-', trim( substr( $cl_line, 0, strpos( $cl_line, '-' ) ) ) );
						$theme_version      = preg_replace( '~[^0-9,.]~', '', stristr( $cl_line, 'version' ) );
						$theme_update       = trim( str_replace( '*', '', $cl_lines[ $line_num + 1 ] ) );
						$theme_version_data = array(
							'date'      => $theme_date,
							'version'   => $theme_version,
							'update'    => $theme_update,
							'changelog' => $theme_changelog,
						);
						set_transient( $theme_dir . '_version_data', $theme_version_data, DAY_IN_SECONDS );
						break;
					}
				}
			}
		}

		if ( ! empty( $theme_version_data['version'] ) ) {
			$update_theme_version = $theme_version_data['version'];
		}
	}

	return $update_theme_version;
}

/**
 * Create a page and store the ID in an option.
 *
 * @param mixed  $slug Slug for the new page.
 * @param string $option Option name to store the page's ID.
 * @param string $page_title (default: '') Title for the new page.
 * @param string $page_content (default: '') Content for the new page.
 * @param int    $post_parent (default: 0) Parent for the new page.
 *
 * @return int page ID.
 */
function er_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
	global $wpdb;

	$option_value = get_option( $option );

	if ( $option_value > 0 ) {
		$page_object = get_post( $option_value );

		if ( $page_object && 'page' === $page_object->post_type && ! in_array( $page_object->post_status, array(
				'pending',
				'trash',
				'future',
				'auto-draft'
			), true ) ) {
			// Valid page is already in place.
			return $page_object->ID;
		}
	}

	if ( strlen( $page_content ) > 0 ) {
		// Search for an existing page with the specified page content (typically a shortcode).
		$shortcode        = str_replace( array(
			'<!-- wp:shortcode -->',
			'<!-- /wp:shortcode -->'
		), '', $page_content );
		$valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", "%{$shortcode}%" ) );
	} else {
		// Search for an existing page with the specified page slug.
		$valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' )  AND post_name = %s LIMIT 1;", $slug ) );
	}

	$valid_page_found = apply_filters( 'easyreservations_create_page_id', $valid_page_found, $slug, $page_content );

	if ( $valid_page_found ) {
		if ( $option ) {
			update_option( $option, $valid_page_found );
		}

		return $valid_page_found;
	}

	// Search for a matching valid trashed page.
	if ( strlen( $page_content ) > 0 ) {
		// Search for an existing page with the specified page content (typically a shortcode).
		$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
	} else {
		// Search for an existing page with the specified page slug.
		$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_name = %s LIMIT 1;", $slug ) );
	}

	if ( $trashed_page_found ) {
		$page_id   = $trashed_page_found;
		$page_data = array(
			'ID'          => $page_id,
			'post_status' => 'publish',
		);
		wp_update_post( $page_data );
	} else {
		$page_data = array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => $slug,
			'post_title'     => $page_title,
			'post_content'   => $page_content,
			'post_parent'    => $post_parent,
			'comment_status' => 'closed',
		);
		$page_id   = wp_insert_post( $page_data );
	}

	if ( $option ) {
		update_option( $option, $page_id );
	}

	return $page_id;
}

/**
 * Get HTML for some action buttons. Used in list tables.
 *
 * @param array $actions Actions to output.
 *
 * @return string
 */
function er_render_action_buttons( $actions ) {
	$actions_html = '';

	foreach ( $actions as $action ) {
		if ( isset( $action['group'] ) ) {
			$actions_html .= '<div class="er-action-button-group"><label>' . $action['group'] . '</label> <span class="er-action-button-group__items">' . er_render_action_buttons( $action['actions'] ) . '</span></div>';
		} elseif ( isset( $action['action'], $action['url'], $action['name'] ) ) {
			$actions_html .= sprintf( '<a class="button er-action-button er-action-button-%1$s %1$s" href="%2$s" aria-label="%3$s" title="%3$s">%4$s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( isset( $action['title'] ) ? $action['title'] : $action['name'] ), esc_html( $action['name'] ) );
		}
	}

	return $actions_html;
}
