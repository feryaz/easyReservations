<?php
defined( 'ABSPATH' ) || exit;

global $wpdb;

wp_localize_script(
	'er-admin-system-status',
	'easyreservations_admin_system_status',
	array(
		'delete_log_confirmation' => esc_js( __( 'Are you sure you want to delete this log?', 'easyReservations' ) ),
		'run_tool_confirmation' => esc_js( __( 'Are you sure you want to run this tool?', 'easyReservations' ) ),
	)
);

wp_enqueue_script( 'er-admin-system-status' );

$wp_memory_limit = er_let_to_num( WP_MEMORY_LIMIT );
if ( function_exists( 'memory_get_usage' ) ) {
	$wp_memory_limit = max( $wp_memory_limit, er_let_to_num( @ini_get( 'memory_limit' ) ) );
}

// Figure out cURL version, if installed.
$curl_version = '';
if ( function_exists( 'curl_version' ) ) {
	$curl_version = curl_version();
	$curl_version = $curl_version['version'] . ', ' . $curl_version['ssl_version'];
} elseif ( extension_loaded( 'curl' ) ) {
	$curl_version = __( 'cURL installed but unable to retrieve version.', 'easyReservations' );
}

$database = er_admin_get_server_database_info();

$database_version = er_get_server_database_version();

$default_timezone = date_default_timezone_get();

// Test POST requests.
$post_response_code = get_transient( 'easyreservations_test_remote_post' );

if ( false === $post_response_code || is_wp_error( $post_response_code ) ) {
	$response = wp_safe_remote_post(
		'https://www.paypal.com/cgi-bin/webscr',
		array(
			'timeout'     => 10,
			'user-agent'  => 'easyReservations/' . ER()->version,
			'httpversion' => '1.1',
			'body'        => array(
				'cmd' => '_notify-validate',
			),
		)
	);
	if ( ! is_wp_error( $response ) ) {
		$post_response_code = $response['response']['code'];
	}
	set_transient( 'easyreservations_test_remote_post', $post_response_code, HOUR_IN_SECONDS );
}

$post_response_successful = ! is_wp_error( $post_response_code ) && $post_response_code >= 200 && $post_response_code < 300;

// Test GET requests.
$get_response_code = get_transient( 'easyreservations_test_remote_get' );

if ( false === $get_response_code || is_wp_error( $get_response_code ) ) {
	$response = wp_safe_remote_get( 'https://woocommerce.com/wc-api/product-key-api?request=ping&network=' . ( is_multisite() ? '1' : '0' ) );
	if ( ! is_wp_error( $response ) ) {
		$get_response_code = $response['response']['code'];
	}
	set_transient( 'easyreservations_test_remote_get', $get_response_code, HOUR_IN_SECONDS );
}

$get_response_successful = ! is_wp_error( $get_response_code ) && $get_response_code >= 200 && $get_response_code < 300;

$check_page = er_get_page_permalink( 'checkout' );

$secure_connection = 'https' === substr( $check_page, 0, 5 );
$hide_errors       = ! ( defined( 'WP_DEBUG' ) && defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG && WP_DEBUG_DISPLAY ) || 0 === intval( ini_get( 'display_errors' ) );

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$active_plugins        = (array) get_option( 'active_plugins', array() );
$active_plugins_data   = array();
$inactive_plugins_data = array();
$available_updates     = get_plugin_updates();

if ( is_multisite() ) {
	$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
	$active_plugins            = array_merge( $active_plugins, $network_activated_plugins );
}

if ( function_exists( 'get_plugin_data' ) ) {
	foreach ( $active_plugins as $plugin ) {
		$data                  = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
		$active_plugins_data[] = er_admin_format_plugin_data( $plugin, $data, $available_updates );
	}
}

if ( function_exists( 'get_plugins' ) ) {
	$plugins = get_plugins();

	foreach ( $plugins as $plugin => $data ) {
		if ( in_array( $plugin, $active_plugins, true ) ) {
			continue;
		}
		$inactive_plugins_data[] = er_admin_format_plugin_data( $plugin, $data, $available_updates );
	}
}

$dropins        = get_dropins();
$dropin_plugins = array(
	'dropins'    => array(),
	'mu_plugins' => array(),
);
foreach ( $dropins as $key => $dropin ) {
	$plugins['dropins'][] = array(
		'plugin' => $key,
		'name'   => $dropin['Name'],
	);
}

$mu_plugins = get_mu_plugins();
foreach ( $mu_plugins as $plugin => $mu_plugin ) {
	$dropin_plugins['mu_plugins'][] = array(
		'plugin'      => $plugin,
		'name'        => $mu_plugin['Name'],
		'version'     => $mu_plugin['Version'],
		'url'         => $mu_plugin['PluginURI'],
		'author_name' => $mu_plugin['AuthorName'],
		'author_url'  => esc_url_raw( $mu_plugin['AuthorURI'] ),
	);
}

$check_pages = array(
	_x( 'Catalog', 'Page setting', 'easyReservations' )                 => array(
		'option'    => 'reservations_shop_page_id',
		'shortcode' => '',
	),
	_x( 'Cart', 'Page setting', 'easyReservations' )                 => array(
		'option'    => 'reservations_cart_page_id',
		'shortcode' => '[' . apply_filters( 'easyreservations_cart_shortcode_tag', 'easy_cart' ) . ']',
	),
	_x( 'Checkout', 'Page setting', 'easyReservations' )             => array(
		'option'    => 'reservations_checkout_page_id',
		'shortcode' => '[' . apply_filters( 'easyreservations_checkout_shortcode_tag', 'easy_checkout' ) . ']',
	),
	_x( 'My account', 'Page setting', 'easyReservations' )           => array(
		'option'    => 'reservations_myaccount_page_id',
		'shortcode' => '[' . apply_filters( 'easyreservations_my_account_shortcode_tag', 'easy_my_account' ) . ']',
	),
	_x( 'Terms and conditions', 'Page setting', 'easyReservations' ) => array(
		'option'    => 'reservations_terms_page_id',
		'shortcode' => '',
	),
);

$pages = array();
foreach ( $check_pages as $page_name => $values ) {
	$page_id            = get_option( $values['option'] );
	$page_set           = false;
	$page_exists        = false;
	$page_visible       = false;
	$shortcode_present  = false;
	$shortcode_required = false;

	// Page checks.
	if ( $page_id ) {
		$page_set = true;
	}
	if ( get_post( $page_id ) ) {
		$page_exists = true;
	}
	if ( 'publish' === get_post_status( $page_id ) ) {
		$page_visible = true;
	}

	// Shortcode checks.
	if ( $values['shortcode'] && get_post( $page_id ) ) {
		$shortcode_required = true;
		$page               = get_post( $page_id );
		if ( strstr( $page->post_content, $values['shortcode'] ) ) {
			$shortcode_present = true;
		}
	}

	// Wrap up our findings into an output array.
	$pages[] = array(
		'page_name'          => $page_name,
		'page_id'            => $page_id,
		'page_set'           => $page_set,
		'page_exists'        => $page_exists,
		'page_visible'       => $page_visible,
		'shortcode'          => $values['shortcode'],
		'shortcode_required' => $shortcode_required,
		'shortcode_present'  => $shortcode_present,
	);
}

$active_theme = wp_get_theme();

// Get parent theme info if this theme is a child theme, otherwise
// pass empty info in the response.
if ( is_child_theme() ) {
	$parent_theme      = wp_get_theme( $active_theme->template );
	$parent_theme_info = array(
		'parent_name'           => $parent_theme->name,
		'parent_version'        => $parent_theme->version,
		'parent_version_latest' => er_admin_get_latest_theme_version( $parent_theme ),
		'parent_author_url'     => $parent_theme->{'Author URI'},
	);
} else {
	$parent_theme_info = array(
		'parent_name'           => '',
		'parent_version'        => '',
		'parent_version_latest' => '',
		'parent_author_url'     => '',
	);
}

/**
 * Scan the theme directory for all ER templates to see if our theme
 * overrides any of them.
 */
$override_files     = array();
$outdated_templates = false;
$scan_files         = er_admin_scan_template_files( ER()->plugin_path() . '/templates/' );
foreach ( $scan_files as $file ) {
	$located = apply_filters( 'er_get_template', $file, $file, array(), ER()->template_path(), ER()->plugin_path() . '/templates/' );

	if ( file_exists( $located ) ) {
		$theme_file = $located;
	} elseif ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
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

	if ( ! empty( $theme_file ) ) {

		$core_version  = er_admin_get_file_version( ER()->plugin_path() . '/templates/' . $file );
		$theme_version = er_admin_get_file_version( $theme_file );
		if ( $core_version && ( empty( $theme_version ) || version_compare( $theme_version, $core_version, '<' ) ) ) {
			if ( ! $outdated_templates ) {
				$outdated_templates = true;
			}
		}
		$override_files[] = array(
			'file'         => str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file ),
			'version'      => $theme_version,
			'core_version' => $core_version,
		);
	}
}

$active_theme_info = array(
	'name'                         => $active_theme->name,
	'version'                      => $active_theme->version,
	'version_latest'               => er_admin_get_latest_theme_version( $active_theme ),
	'author_url'                   => esc_url_raw( $active_theme->{'Author URI'} ),
	'is_child_theme'               => is_child_theme(),
	'has_easyreservations_support' => current_theme_supports( 'easyreservations' ),
	'has_outdated_templates'       => $outdated_templates,
	'overrides'                    => $override_files,
);
?>
<div class="updated easyreservations-message inline">
    <p>
		<?php esc_html_e( 'Please copy and paste this information in your ticket when contacting support:', 'easyReservations' ); ?>
    </p>
    <p class="submit">
        <a href="#" class="button-primary debug-report"><?php esc_html_e( 'Get system report', 'easyReservations' ); ?></a>
        <a class="button-secondary docs" href="https://easyreservations.org/documentation/understanding-the-easyreservations-system-status-report/" target="_blank">
			<?php esc_html_e( 'Understanding the status report', 'easyReservations' ); ?>
        </a>
    </p>
    <div id="debug-report">
        <textarea readonly="readonly"></textarea>
        <p class="submit">
            <button id="copy-for-support" class="button-primary" href="#" data-tip="<?php esc_attr_e( 'Copied!', 'easyReservations' ); ?>">
				<?php esc_html_e( 'Copy for support', 'easyReservations' ); ?>
            </button>
        </p>
        <p class="copy-error hidden">
			<?php esc_html_e( 'Copying to clipboard failed. Please press Ctrl/Cmd+C to copy.', 'easyReservations' ); ?>
        </p>
    </div>
</div>
<table class="er_status_table widefat" cellspacing="0" id="status">
    <thead>
    <tr>
        <th colspan="3" data-export-label="WordPress Environment">
            <h2><?php esc_html_e( 'WordPress environment', 'easyReservations' ); ?></h2></th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td data-export-label="WordPress address (URL)"><?php esc_html_e( 'WordPress address (URL)', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The root URL of your site.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( get_option( 'siteurl' ) ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Site address (URL)"><?php esc_html_e( 'Site address (URL)', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The homepage URL of your site.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( get_option( 'home' ) ); ?></td>
    </tr>
    <tr>
        <td data-export-label="ER Version"><?php esc_html_e( 'easyReservations version', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The version of easyReservations installed on your site.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( RESERVATIONS_VERSION ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Log Directory Writable"><?php esc_html_e( 'Log directory writable', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Several functions can write logs which makes debugging problems easier. The directory must be writable for this to happen.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( (bool) @fopen( RESERVATIONS_LOG_DIR . 'test-log.log', 'a' ) ) {
				echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> <code class="private">' . esc_html( 1 ) . '</code></mark> ';
			} else {
				/* Translators: %1$s: Log directory, %2$s: Log directory constant */
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'To allow logging, make %1$s writable or define a custom %2$s.', 'easyReservations' ), '<code>' . esc_html( RESERVATIONS_LOG_DIR ) . '</code>', '<code>RESERVATIONS_LOG_DIR</code>' ) . '</mark>';
			}
			?>
        </td>
    </tr>
    <tr>
        <td data-export-label="WP Version"><?php esc_html_e( 'WordPress version', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The version of WordPress installed on your site.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			$latest_version  = get_transient( 'easyreservations_system_status_wp_version_check' );
			$current_version = get_bloginfo( 'version' );

			if ( false === $latest_version ) {
				$version_check = wp_remote_get( 'https://api.wordpress.org/core/version-check/1.7/' );
				$api_response  = json_decode( wp_remote_retrieve_body( $version_check ), true );

				if ( $api_response && isset( $api_response['offers'], $api_response['offers'][0], $api_response['offers'][0]['version'] ) ) {
					$latest_version = $api_response['offers'][0]['version'];
				} else {
					$latest_version = $current_version;
				}
				set_transient( 'easyreservations_system_status_wp_version_check', $latest_version, DAY_IN_SECONDS );
			}

			if ( version_compare( $current_version, $latest_version, '<' ) ) {
				/* Translators: %1$s: Current version, %2$s: New version */
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - There is a newer version of WordPress available (%2$s)', 'easyReservations' ), esc_html( $current_version ), esc_html( $latest_version ) ) . '</mark>';
			} else {
				echo '<mark class="yes">' . esc_html( $current_version ) . '</mark>';
			}
			?>
        </td>
    </tr>
    <tr>
        <td data-export-label="WP Multisite"><?php esc_html_e( 'WordPress multisite', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Whether or not you have WordPress Multisite enabled.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo ( is_multisite() ) ? '<span class="dashicons dashicons-yes"></span>' : '&ndash;'; ?></td>
    </tr>
    <tr>
        <td data-export-label="WP Memory Limit"><?php esc_html_e( 'WordPress memory limit', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The maximum amount of memory (RAM) that your site can use at one time.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( $wp_memory_limit < 67108864 ) {
				/* Translators: %1$s: Memory limit, %2$s: Docs link. */
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - We recommend setting memory to at least 64MB. See: %2$s', 'easyReservations' ), esc_html( size_format( $wp_memory_limit ) ), '<a href="https://wordpress.org/support/article/editing-wp-config-php/#increasing-memory-allocated-to-php" target="_blank">' . esc_html__( 'Increasing memory allocated to PHP', 'easyReservations' ) . '</a>' ) . '</mark>';
			} else {
				echo '<mark class="yes">' . esc_html( size_format( $wp_memory_limit ) ) . '</mark>';
			}
			?>
        </td>
    </tr>
    <tr>
        <td data-export-label="WP Debug Mode"><?php esc_html_e( 'WordPress debug mode', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Displays whether or not WordPress is in Debug Mode.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) : ?>
                <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
			<?php else : ?>
                <mark class="no">&ndash;</mark>
			<?php endif; ?>
        </td>
    </tr>
    <tr>
        <td data-export-label="WP Cron"><?php esc_html_e( 'WordPress cron', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Displays whether or not WP Cron Jobs are enabled.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php if ( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) : ?>
                <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
			<?php else : ?>
                <mark class="no">&ndash;</mark>
			<?php endif; ?>
        </td>
    </tr>
    <tr>
        <td data-export-label="Language"><?php esc_html_e( 'Language', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The current language used by WordPress. Default = English', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( get_locale() ); ?></td>
    </tr>
    <tr>
        <td data-export-label="External object cache"><?php esc_html_e( 'External object cache', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Displays whether or not WordPress is using an external object cache.', 'easyReservations' ) ); ?></td>
        <td>
			<?php if ( wp_using_ext_object_cache() ) : ?>
                <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
			<?php else : ?>
                <mark class="no">&ndash;</mark>
			<?php endif; ?>
        </td>
    </tr>
    </tbody>
</table>
<table class="er_status_table widefat" cellspacing="0">
    <thead>
    <tr>
        <th colspan="3" data-export-label="Server Environment">
            <h2><?php esc_html_e( 'Server environment', 'easyReservations' ); ?></h2></th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td data-export-label="Server Info"><?php esc_html_e( 'Server info', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Information about the web server that is currently hosting your site.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( isset( $_SERVER['SERVER_SOFTWARE'] ) ? er_clean( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '' ); ?></td>
    </tr>
    <tr>
        <td data-export-label="PHP Version"><?php esc_html_e( 'PHP version', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The version of PHP installed on your hosting server.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( version_compare( phpversion(), '7.2', '>=' ) ) {
				echo '<mark class="yes">' . esc_html( phpversion() ) . '</mark>';
			} else {
				$update_link = ' <a href="https://easyreservations.org/documentation/how-to-update-your-php-version/" target="_blank">' . esc_html__( 'How to update your PHP version', 'easyReservations' ) . '</a>';
				$class       = 'error';

				if ( version_compare( phpversion(), '5.4', '<' ) ) {
					$notice = '<span class="dashicons dashicons-warning"></span> ' . __( 'easyReservations will run under this version of PHP, however, some features are not compatible. Support for this version will be dropped in the next major release. We recommend using PHP version 7.2 or above for greater performance and security.', 'easyReservations' ) . $update_link;
				} elseif ( version_compare( phpversion(), '5.6', '<' ) ) {
					$notice = '<span class="dashicons dashicons-warning"></span> ' . __( 'easyReservations will run under this version of PHP, however, it has reached end of life. We recommend using PHP version 7.2 or above for greater performance and security.', 'easyReservations' ) . $update_link;
				} elseif ( version_compare( phpversion(), '7.2', '<' ) ) {
					$notice = __( 'We recommend using PHP version 7.2 or above for greater performance and security.', 'easyReservations' ) . $update_link;
					$class  = 'recommendation';
				}

				echo '<mark class="' . esc_attr( $class ) . '">' . esc_html( phpversion() ) . ' - ' . wp_kses_post( $notice ) . '</mark>';
			}
			?>
        </td>
    </tr>
	<?php if ( function_exists( 'ini_get' ) ) : ?>
        <tr>
            <td data-export-label="PHP Post Max Size"><?php esc_html_e( 'PHP post max size', 'easyReservations' ); ?>:</td>
            <td class="help"><?php er_print_help( esc_html__( 'The largest filesize that can be contained in one post.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
            <td><?php echo esc_html( size_format( er_let_to_num( ini_get( 'post_max_size' ) ) ) ); ?></td>
        </tr>
        <tr>
            <td data-export-label="PHP Time Limit"><?php esc_html_e( 'PHP time limit', 'easyReservations' ); ?>:</td>
            <td class="help"><?php er_print_help( esc_html__( 'The amount of time (in seconds) that your site will spend on a single operation before timing out (to avoid server lockups)', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
            <td><?php echo esc_html( ini_get( 'max_execution_time' ) ); ?></td>
        </tr>
        <tr>
            <td data-export-label="PHP Max Input Vars"><?php esc_html_e( 'PHP max input vars', 'easyReservations' ); ?>:</td>
            <td class="help"><?php er_print_help( esc_html__( 'The maximum number of variables your server can use for a single function to avoid overloads.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
            <td><?php echo esc_html( ini_get( 'max_input_vars' ) ); ?></td>
        </tr>
        <tr>
            <td data-export-label="cURL Version"><?php esc_html_e( 'cURL version', 'easyReservations' ); ?>:</td>
            <td class="help"><?php er_print_help( esc_html__( 'The version of cURL installed on your server.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
            <td><?php echo esc_html( $curl_version ); ?></td>
        </tr>
        <tr>
            <td data-export-label="SUHOSIN Installed"><?php esc_html_e( 'SUHOSIN installed', 'easyReservations' ); ?>:</td>
            <td class="help"><?php er_print_help( esc_html__( 'Suhosin is an advanced protection system for PHP installations. It was designed to protect your servers on the one hand against a number of well known problems in PHP applications and on the other hand against potential unknown vulnerabilities within these applications or the PHP core itself. If enabled on your server, Suhosin may need to be configured to increase its data submission limits.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
            <td><?php echo extension_loaded( 'suhosin' ) ? '<span class="dashicons dashicons-yes"></span>' : '&ndash;'; ?></td>
        </tr>
	<?php endif; ?>

	<?php

	if ( $database_version['number'] ) :
		?>
        <tr>
            <td data-export-label="MySQL Version"><?php esc_html_e( 'MySQL version', 'easyReservations' ); ?>:</td>
            <td class="help"><?php er_print_help( esc_html__( 'The version of MySQL installed on your hosting server.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
            <td>
				<?php
				if ( version_compare( $database_version['number'], '5.6', '<' ) && ! strstr( $database_version['string'], 'MariaDB' ) ) {
					/* Translators: %1$s: MySQL version, %2$s: Recommended MySQL version. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - We recommend a minimum MySQL version of 5.6. See: %2$s', 'easyReservations' ), esc_html( $database_version['string'] ), '<a href="https://wordpress.org/about/requirements/" target="_blank">' . esc_html__( 'WordPress requirements', 'easyReservations' ) . '</a>' ) . '</mark>';
				} else {
					echo '<mark class="yes">' . esc_html( $database_version['string'] ) . '</mark>';
				}
				?>
            </td>
        </tr>
	<?php endif; ?>
    <tr>
        <td data-export-label="Max Upload Size"><?php esc_html_e( 'Max upload size', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The largest filesize that can be uploaded to your WordPress installation.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( size_format( wp_max_upload_size() ) ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Default Timezone is UTC"><?php esc_html_e( 'Default timezone is UTC', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The default timezone for your server.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( 'UTC' !== $default_timezone ) {
				/* Translators: %s: default timezone.. */
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Default timezone is %s - it should be UTC', 'easyReservations' ), esc_html( $default_timezone ) ) . '</mark>';
			} else {
				echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
			}
			?>
        </td>
    </tr>
    <tr>
        <td data-export-label="fsockopen/cURL"><?php esc_html_e( 'fsockopen/cURL', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Payment gateways can use cURL to communicate with remote servers to authorize payments, other plugins may also use it when communicating with remote services.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ) ) {
				echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
			} else {
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Your server does not have fsockopen or cURL enabled - PayPal IPN and other scripts which communicate with other servers will not work. Contact your hosting provider.', 'easyReservations' ) . '</mark>';
			}
			?>
        </td>
    </tr>
    <tr>
        <td data-export-label="SoapClient"><?php esc_html_e( 'SoapClient', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Some webservices like shipping use SOAP to get information from remote servers, for example, live shipping quotes from FedEx require SOAP to be installed.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( class_exists( 'SoapClient' ) ) {
				echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
			} else {
				/* Translators: %s classname and link. */
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Your server does not have the %s class enabled - some gateway plugins which use SOAP may not work as expected.', 'easyReservations' ), '<a href="https://php.net/manual/en/class.soapclient.php">SoapClient</a>' ) . '</mark>';
			}
			?>
        </td>
    </tr>
    <tr>
        <td data-export-label="DOMDocument"><?php esc_html_e( 'DOMDocument', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'HTML/Multipart emails use DOMDocument to generate inline CSS in templates.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( class_exists( 'DOMDocument' ) ) {
				echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
			} else {
				/* Translators: %s: classname and link. */
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Your server does not have the %s class enabled - HTML/Multipart emails, and also some extensions, will not work without DOMDocument.', 'easyReservations' ), '<a href="https://php.net/manual/en/class.domdocument.php">DOMDocument</a>' ) . '</mark>';
			}
			?>
        </td>
    </tr>
    <tr>
        <td data-export-label="GZip"><?php esc_html_e( 'GZip', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'GZip (gzopen) is used to open the GEOIP database from MaxMind.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( is_callable( 'gzopen' ) ) {
				echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
			} else {
				/* Translators: %s: classname and link. */
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Your server does not support the %s function - this is required to use the GeoIP database from MaxMind.', 'easyReservations' ), '<a href="https://php.net/manual/en/zlib.installation.php">gzopen</a>' ) . '</mark>';
			}
			?>
        </td>
    </tr>
    <tr>
        <td data-export-label="Multibyte String"><?php esc_html_e( 'Multibyte string', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Multibyte String (mbstring) is used to convert character encoding, like for emails or converting characters to lowercase.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( extension_loaded( 'mbstring' ) ) {
				echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
			} else {
				/* Translators: %s: classname and link. */
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Your server does not support the %s functions - this is required for better character encoding. Some fallbacks will be used instead for it.', 'easyReservations' ), '<a href="https://php.net/manual/en/mbstring.installation.php">mbstring</a>' ) . '</mark>';
			}
			?>
        </td>
    </tr>
    <tr>
        <td data-export-label="Remote Post"><?php esc_html_e( 'Remote post', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'PayPal uses this method of communicating when sending back transaction information.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( $post_response_successful ) {
				echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
			} else {
				/* Translators: %s: function name. */
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%s failed. Contact your hosting provider.', 'easyReservations' ), 'wp_remote_post()' ) . ' ' . esc_html( is_wp_error( $post_response_code ) ? $post_response_code->get_error_message() : $post_response_code ) . '</mark>';
			}
			?>
        </td>
    </tr>
    <tr>
        <td data-export-label="Remote Get"><?php esc_html_e( 'Remote get', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'May use this method of communication when checking for plugin updates.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( $get_response_successful ) {
				echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
			} else {
				/* Translators: %s: function name. */
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%s failed. Contact your hosting provider.', 'easyReservations' ), 'wp_remote_get()' ) . ' ' . esc_html( is_wp_error( $get_response_code ) ? $get_response_code->get_error_message() : $get_response_code ) . '</mark>';
			}
			?>
        </td>
    </tr>
    </tbody>
</table>
<table class="er_status_table widefat" cellspacing="0">
    <thead>
    <tr>
        <th colspan="3" data-export-label="Database"><h2><?php esc_html_e( 'Database', 'easyReservations' ); ?></h2>
        </th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td data-export-label="ER Database Version"><?php esc_html_e( 'easyReservations database version', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The version of easyReservations that the database is formatted for. This should be the same as your easyReservations version.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( $database['er_database_version'] ); ?></td>
    </tr>
    <tr>
        <td data-export-label="ER Database Prefix"><?php esc_html_e( 'Database prefix', 'easyReservations' ); ?></td>
        <td class="help">&nbsp;</td>
        <td>
			<?php
			if ( strlen( $database['database_prefix'] ) > 20 ) {
				/* Translators: %1$s: Database prefix, %2$s: Docs link. */
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - We recommend using a prefix with less than 20 characters. See: %2$s', 'easyReservations' ), esc_html( $database['database_prefix'] ), '<a href="https://easyreservations.org/documentation/how-to-update-your-database-table-prefix/" target="_blank">' . esc_html__( 'How to update your database table prefix', 'easyReservations' ) . '</a>' ) . '</mark>';
			} else {
				echo '<mark class="yes">' . esc_html( $database['database_prefix'] ) . '</mark>';
			}
			?>
        </td>
    </tr>

	<?php if ( ! empty( $database['database_size'] ) && ! empty( $database['database_tables'] ) ) : ?>
        <tr>
            <td><?php esc_html_e( 'Total Database Size', 'easyReservations' ); ?></td>
            <td class="help">&nbsp;</td>
            <td><?php printf( '%.2fMB', esc_html( $database['database_size']['data'] + $database['database_size']['index'] ) ); ?></td>
        </tr>

        <tr>
            <td><?php esc_html_e( 'Database Data Size', 'easyReservations' ); ?></td>
            <td class="help">&nbsp;</td>
            <td><?php printf( '%.2fMB', esc_html( $database['database_size']['data'] ) ); ?></td>
        </tr>

        <tr>
            <td><?php esc_html_e( 'Database Index Size', 'easyReservations' ); ?></td>
            <td class="help">&nbsp;</td>
            <td><?php printf( '%.2fMB', esc_html( $database['database_size']['index'] ) ); ?></td>
        </tr>

		<?php foreach ( $database['database_tables']['easyreservations'] as $table => $table_data ) { ?>
            <tr>
                <td><?php echo esc_html( $table ); ?></td>
                <td class="help">&nbsp;</td>
                <td>
					<?php
					if ( ! $table_data ) {
						echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Table does not exist', 'easyReservations' ) . '</mark>';
					} else {
						/* Translators: %1$f: Table size, %2$f: Index size, %3$s Engine. */
						printf( esc_html__( 'Data: %1$.2fMB + Index: %2$.2fMB + Engine %3$s', 'easyReservations' ), esc_html( er_format_decimal( $table_data['data'], 2 ) ), esc_html( er_format_decimal( $table_data['index'], 2 ) ), esc_html( $table_data['engine'] ) );
					}
					?>
                </td>
            </tr>
		<?php } ?>

		<?php foreach ( $database['database_tables']['other'] as $table => $table_data ) { ?>
            <tr>
                <td><?php echo esc_html( $table ); ?></td>
                <td class="help">&nbsp;</td>
                <td>
					<?php
					/* Translators: %1$f: Table size, %2$f: Index size, %3$s Engine. */
					printf( esc_html__( 'Data: %1$.2fMB + Index: %2$.2fMB + Engine %3$s', 'easyReservations' ), esc_html( er_format_decimal( $table_data['data'], 2 ) ), esc_html( er_format_decimal( $table_data['index'], 2 ) ), esc_html( $table_data['engine'] ) );
					?>
                </td>
            </tr>
		<?php } ?>
	<?php else : ?>
        <tr>
            <td><?php esc_html_e( 'Database information:', 'easyReservations' ); ?></td>
            <td class="help">&nbsp;</td>
            <td>
				<?php
				esc_html_e(
					'Unable to retrieve database information. Usually, this is not a problem, and it only means that your install is using a class that replaces the WordPress database class (e.g., HyperDB) and easyReservations is unable to get database information.',
					'easyReservations'
				);
				?>
            </td>
        </tr>
	<?php endif; ?>
    </tbody>
</table>
<table class="er_status_table widefat" cellspacing="0">
    <thead>
    <tr>
        <th colspan="3" data-export-label="Security"><h2><?php esc_html_e( 'Security', 'easyReservations' ); ?></h2>
        </th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td data-export-label="Secure connection (HTTPS)"><?php esc_html_e( 'Secure connection (HTTPS)', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Is the connection to your store secure?', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php if ( $secure_connection ) : ?>
                <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
			<?php else : ?>
                <mark class="error"><span class="dashicons dashicons-warning"></span>
					<?php
					/* Translators: %s: docs link. */
					echo wp_kses_post( sprintf( __( 'Your store is not using HTTPS. <a href="%s" target="_blank">Learn more about HTTPS and SSL Certificates</a>.', 'easyReservations' ), 'https://easyreservations.org/documentation/ssl-and-https/' ) );
					?>
                </mark>
			<?php endif; ?>
        </td>
    </tr>
    <tr>
        <td data-export-label="Hide errors from visitors"><?php esc_html_e( 'Hide errors from visitors', 'easyReservations' ); ?></td>
        <td class="help"><?php er_print_help( esc_html__( 'Error messages can contain sensitive information about your store environment. These should be hidden from untrusted visitors.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php if ( $hide_errors ) : ?>
                <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
			<?php else : ?>
                <mark class="error">
                    <span class="dashicons dashicons-warning"></span><?php esc_html_e( 'Error messages should not be shown to visitors.', 'easyReservations' ); ?>
                </mark>
			<?php endif; ?>
        </td>
    </tr>
    </tbody>
</table>
<table class="er_status_table widefat" cellspacing="0">
    <thead>
    <tr>
        <th colspan="3" data-export-label="Active Plugins (<?php echo count( $active_plugins_data ); ?>)">
            <h2><?php esc_html_e( 'Active plugins', 'easyReservations' ); ?> (<?php echo count( $active_plugins_data ); ?>)</h2>
        </th>
    </tr>
    </thead>
    <tbody>
	<?php
	foreach ( $active_plugins_data as $plugin ) {
		if ( ! empty( $plugin['name'] ) ) {
			$dirname = dirname( $plugin['plugin'] );

			// Link the plugin name to the plugin url if available.
			$plugin_name = esc_html( $plugin['name'] );
			if ( ! empty( $plugin['url'] ) ) {
				$plugin_name = '<a href="' . esc_url( $plugin['url'] ) . '" aria-label="' . esc_attr__( 'Visit plugin homepage', 'easyReservations' ) . '" target="_blank">' . $plugin_name . '</a>';
			}

			$version_string = '';
			$network_string = '';
			if ( strstr( $plugin['url'], 'easyreservations.org' ) ) {
				if ( ! empty( $plugin['version_latest'] ) && version_compare( $plugin['version_latest'], $plugin['version'], '>' ) ) {
					/* translators: %s: plugin latest version */
					$version_string = ' &ndash; <strong style="color:#f00;">' . sprintf( esc_html__( '%s is available', 'easyReservations' ), $plugin['version_latest'] ) . '</strong>';
				}

				if ( false !== $plugin['network_activated'] ) {
					$network_string = ' &ndash; <strong style="color:#000;">' . esc_html__( 'Network enabled', 'easyReservations' ) . '</strong>';
				}
			}
			?>
            <tr>
                <td><?php echo wp_kses_post( $plugin_name ); ?></td>
                <td class="help">&nbsp;</td>
                <td>
					<?php
					/* translators: %s: plugin author */
					printf( esc_html__( 'by %s', 'easyReservations' ), esc_html( $plugin['author_name'] ) );
					echo ' &ndash; ' . esc_html( $plugin['version'] ) . $version_string . $network_string; // WPCS: XSS ok.
					?>
                </td>
            </tr>
			<?php
		}
	}
	?>
    </tbody>
</table>
<table class="er_status_table widefat" cellspacing="0">
    <thead>
    <tr>
        <th colspan="3" data-export-label="Inactive Plugins (<?php echo count( $inactive_plugins_data ); ?>)">
            <h2><?php esc_html_e( 'Inactive plugins', 'easyReservations' ); ?> (<?php echo count( $inactive_plugins_data ); ?>)</h2>
        </th>
    </tr>
    </thead>
    <tbody>
	<?php
	foreach ( $inactive_plugins_data as $plugin ) {
		if ( ! empty( $plugin['name'] ) ) {
			$dirname = dirname( $plugin['plugin'] );

			// Link the plugin name to the plugin url if available.
			$plugin_name = esc_html( $plugin['name'] );
			if ( ! empty( $plugin['url'] ) ) {
				$plugin_name = '<a href="' . esc_url( $plugin['url'] ) . '" aria-label="' . esc_attr__( 'Visit plugin homepage', 'easyReservations' ) . '" target="_blank">' . $plugin_name . '</a>';
			}

			$version_string = '';
			$network_string = '';
			if ( strstr( $plugin['url'], 'easyreservations.org' ) ) {
				if ( ! empty( $plugin['version_latest'] ) && version_compare( $plugin['version_latest'], $plugin['version'], '>' ) ) {
					/* translators: %s: plugin latest version */
					$version_string = ' &ndash; <strong style="color:#f00;">' . sprintf( esc_html__( '%s is available', 'easyReservations' ), $plugin['version_latest'] ) . '</strong>';
				}

				if ( false !== $plugin['network_activated'] ) {
					$network_string = ' &ndash; <strong style="color:#000;">' . esc_html__( 'Network enabled', 'easyReservations' ) . '</strong>';
				}
			}
			?>
            <tr>
                <td><?php echo wp_kses_post( $plugin_name ); ?></td>
                <td class="help">&nbsp;</td>
                <td>
					<?php
					/* translators: %s: plugin author */
					printf( esc_html__( 'by %s', 'easyReservations' ), esc_html( $plugin['author_name'] ) );
					echo ' &ndash; ' . esc_html( $plugin['version'] ) . $version_string . $network_string; // WPCS: XSS ok.
					?>
                </td>
            </tr>
			<?php
		}
	}
	?>
    </tbody>
</table>
<?php
if ( 0 < count( $dropin_plugins['dropins'] ) ) :
	?>
    <table class="er_status_table widefat" cellspacing="0">
        <thead>
        <tr>
            <th colspan="3" data-export-label="Dropin Plugins (<?php echo count( $dropin_plugins['dropins'] ); ?>)">
                <h2><?php esc_html_e( 'Dropin Plugins', 'easyReservations' ); ?> (<?php echo count( $dropin_plugins['dropins'] ); ?>)</h2>
            </th>
        </tr>
        </thead>
        <tbody>
		<?php
		foreach ( $dropin_plugins['dropins'] as $dropin ) {
			?>
            <tr>
                <td><?php echo wp_kses_post( $dropin['plugin'] ); ?></td>
                <td class="help">&nbsp;</td>
                <td><?php echo wp_kses_post( $dropin['name'] ); ?>
            </tr>
			<?php
		}
		?>
        </tbody>
    </table>
<?php
endif;
if ( 0 < count( $dropin_plugins['mu_plugins'] ) ) :
	?>
    <table class="er_status_table widefat" cellspacing="0">
        <thead>
        <tr>
            <th colspan="3" data-export-label="Must Use Plugins (<?php echo count( $dropin_plugins['mu_plugins'] ); ?>)">
                <h2><?php esc_html_e( 'Must Use Plugins', 'easyReservations' ); ?> (<?php echo count( $dropin_plugins['mu_plugins'] ); ?>)</h2>
            </th>
        </tr>
        </thead>
        <tbody>
		<?php
		foreach ( $dropin_plugins['mu_plugins'] as $mu_plugin ) {
			$plugin_name = esc_html( $mu_plugin['name'] );
			if ( ! empty( $mu_plugin['url'] ) ) {
				$plugin_name = '<a href="' . esc_url( $mu_plugin['url'] ) . '" aria-label="' . esc_attr__( 'Visit plugin homepage', 'easyReservations' ) . '" target="_blank">' . $plugin_name . '</a>';
			}
			?>
            <tr>
                <td><?php echo wp_kses_post( $plugin_name ); ?></td>
                <td class="help">&nbsp;</td>
                <td>
					<?php
					/* translators: %s: plugin author */
					printf( esc_html__( 'by %s', 'easyReservations' ), esc_html( $mu_plugin['author_name'] ) );
					echo ' &ndash; ' . esc_html( $mu_plugin['version'] );
					?>
            </tr>
			<?php
		}
		?>
        </tbody>
    </table>
<?php endif; ?>
<table class="er_status_table widefat" cellspacing="0">
    <thead>
    <tr>
        <th colspan="3" data-export-label="Settings"><h2><?php esc_html_e( 'Settings', 'easyReservations' ); ?></h2>
        </th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td data-export-label="Force SSL"><?php esc_html_e( 'Force SSL', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Does your site force a SSL Certificate for transactions?', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo 'yes' === get_option( 'reservations_force_ssl_checkout' ) ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
    </tr>
    <tr>
        <td data-export-label="Date format"><?php esc_html_e( 'Date format', 'easyReservations' ); ?></td>
        <td class="help"><?php er_print_help( esc_html__( 'Selected date format.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( er_date_format() ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Time format"><?php esc_html_e( 'Time format', 'easyReservations' ); ?></td>
        <td class="help"><?php er_print_help( esc_html__( 'Selected time format.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( er_time_format() ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Time"><?php esc_html_e( 'Time', 'easyReservations' ); ?></td>
        <td class="help"><?php er_print_help( esc_html__( 'Wether to display time.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( er_use_time() ? 'Yes' : 'No' ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Taxes enabled"><?php esc_html_e( 'Taxes enabled', 'easyReservations' ); ?></td>
        <td class="help"><?php er_print_help( esc_html__( 'Wether taxes are enabled.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( er_tax_enabled() ? 'Yes' : 'No' ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Prices include Tax"><?php esc_html_e( 'Prices including tax', 'easyReservations' ); ?></td>
        <td class="help"><?php er_print_help( esc_html__( 'Wether prices are entered with tax.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( er_prices_include_tax() ? 'Yes' : 'No' ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Currency"><?php esc_html_e( 'Currency', 'easyReservations' ); ?></td>
        <td class="help"><?php er_print_help( esc_html__( 'What currency prices are listed at in the catalog and which currency gateways will take payments in.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( strtoupper( er_get_currency() ) ); ?> (<?php echo esc_html( er_get_currency_symbol() ); ?>)</td>
    </tr>
    <tr>
        <td data-export-label="Currency Position"><?php esc_html_e( 'Currency position', 'easyReservations' ); ?></td>
        <td class="help"><?php er_print_help( esc_html__( 'The position of the currency symbol.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( er_get_price_currency_pos() ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Thousand Separator"><?php esc_html_e( 'Thousand separator', 'easyReservations' ); ?></td>
        <td class="help"><?php er_print_help( esc_html__( 'The thousand separator of displayed prices.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( er_get_price_thousand_separator() ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Decimal Separator"><?php esc_html_e( 'Decimal separator', 'easyReservations' ); ?></td>
        <td class="help"><?php er_print_help( esc_html__( 'The decimal separator of displayed prices.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( er_get_price_decimal_separator() ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Number of Decimals"><?php esc_html_e( 'Number of decimals', 'easyReservations' ); ?></td>
        <td class="help"><?php er_print_help( esc_html__( 'The number of decimal points shown in displayed prices.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( er_get_price_decimals() ); ?></td>
    </tr>
    </tbody>
</table>
<table class="er_status_table widefat" cellspacing="0">
    <thead>
    <tr>
        <th colspan="3" data-export-label="ER Pages">
            <h2><?php esc_html_e( 'easyReservations pages', 'easyReservations' ); ?></h2>
        </th>
    </tr>
    </thead>
    <tbody>
	<?php
	$alt = 1;
	foreach ( $pages as $_page ) {
		$found_error = false;

		if ( $_page['page_id'] ) {
			/* Translators: %s: page name. */
			$page_name = '<a href="' . get_edit_post_link( $_page['page_id'] ) . '" aria-label="' . sprintf( esc_html__( 'Edit %s page', 'easyReservations' ), esc_html( $_page['page_name'] ) ) . '">' . esc_html( $_page['page_name'] ) . '</a>';
		} else {
			$page_name = esc_html( $_page['page_name'] );
		}

		echo '<tr><td data-export-label="' . esc_attr( $page_name ) . '">' . wp_kses_post( $page_name ) . ':</td>';
		/* Translators: %s: page name. */
		echo '<td class="help">' . er_get_help( sprintf( esc_html__( 'The URL of your %s page (along with the Page ID).', 'easyReservations' ), $page_name ) ) . '</td><td>';

		// Page ID check.
		if ( ! $_page['page_set'] ) {
			echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Page not set', 'easyReservations' ) . '</mark>';
			$found_error = true;
		} elseif ( ! $_page['page_exists'] ) {
			echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Page ID is set, but the page does not exist', 'easyReservations' ) . '</mark>';
			$found_error = true;
		} elseif ( ! $_page['page_visible'] ) {
			/* Translators: %s: docs link. */
			echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . wp_kses_post( sprintf( __( 'Page visibility should be <a href="%s" target="_blank">public</a>', 'easyReservations' ), 'https://wordpress.org/support/article/content-visibility/' ) ) . '</mark>';
			$found_error = true;
		} else {
			// Shortcode check.
			if ( $_page['shortcode_required'] ) {
				if ( ! $_page['shortcode_present'] ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Page does not contain the shortcode.', 'easyReservations' ), esc_html( $_page['shortcode'] ) ) . '</mark>';
					$found_error = true;
				}
			}
		}

		if ( ! $found_error ) {
			echo '<mark class="yes">#' . absint( $_page['page_id'] ) . ' - ' . esc_html( str_replace( home_url(), '', get_permalink( $_page['page_id'] ) ) ) . '</mark>';
		}

		echo '</td></tr>';
	}
	?>
    </tbody>
</table>
<table class="er_status_table widefat" cellspacing="0">
    <thead>
    <tr>
        <th colspan="3" data-export-label="Theme"><h2><?php esc_html_e( 'Theme', 'easyReservations' ); ?></h2></th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td data-export-label="Name"><?php esc_html_e( 'Name', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The name of the current active theme.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( $active_theme_info['name'] ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Version"><?php esc_html_e( 'Version', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The installed version of the current active theme.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			echo esc_html( $active_theme_info['version'] );
			if ( version_compare( $active_theme_info['version'], $active_theme_info['version_latest'], '<' ) ) {
				/* translators: %s: theme latest version */
				echo ' &ndash; <strong style="color:red;">' . sprintf( esc_html__( '%s is available', 'easyReservations' ), esc_html( $active_theme_info['version_latest'] ) ) . '</strong>';
			}
			?>
        </td>
    </tr>
    <tr>
        <td data-export-label="Author URL"><?php esc_html_e( 'Author URL', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'The theme developers URL.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td><?php echo esc_html( $active_theme_info['author_url'] ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Child Theme"><?php esc_html_e( 'Child theme', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Displays whether or not the current theme is a child theme.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( $active_theme_info['is_child_theme'] ) {
				echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
			} else {
				/* Translators: %s docs link. */
				echo '<span class="dashicons dashicons-no-alt"></span> &ndash; ' . wp_kses_post( sprintf( __( 'If you are modifying easyReservations on a parent theme that you did not build personally we recommend using a child theme. See: <a href="%s" target="_blank">How to create a child theme</a>', 'easyReservations' ), 'https://developer.wordpress.org/themes/advanced-topics/child-themes/' ) );
			}
			?>
        </td>
    </tr>
	<?php if ( $active_theme_info['is_child_theme'] ) : ?>
        <tr>
            <td data-export-label="Parent Theme Name"><?php esc_html_e( 'Parent theme name', 'easyReservations' ); ?>:</td>
            <td class="help"><?php er_print_help( esc_html__( 'The name of the parent theme.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
            <td><?php echo esc_html( $active_theme_info['parent_name'] ); ?></td>
        </tr>
        <tr>
            <td data-export-label="Parent Theme Version"><?php esc_html_e( 'Parent theme version', 'easyReservations' ); ?>:</td>
            <td class="help"><?php er_print_help( esc_html__( 'The installed version of the parent theme.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
            <td>
				<?php
				echo esc_html( $active_theme_info['parent_version'] );
				if ( version_compare( $active_theme_info['parent_version'], $active_theme_info['parent_version_latest'], '<' ) ) {
					/* translators: %s: parent theme latest version */
					echo ' &ndash; <strong style="color:red;">' . sprintf( esc_html__( '%s is available', 'easyReservations' ), esc_html( $active_theme_info['parent_version_latest'] ) ) . '</strong>';
				}
				?>
            </td>
        </tr>
        <tr>
            <td data-export-label="Parent Theme Author URL"><?php esc_html_e( 'Parent theme author URL', 'easyReservations' ); ?>:</td>
            <td class="help"><?php er_print_help( esc_html__( 'The parent theme developers URL.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
            <td><?php echo esc_html( $active_theme_info['parent_author_url'] ); ?></td>
        </tr>
	<?php endif ?>
    <tr>
        <td data-export-label="easyReservations Support"><?php esc_html_e( 'easyReservations support', 'easyReservations' ); ?>:</td>
        <td class="help"><?php er_print_help( esc_html__( 'Displays whether or not the current active theme declares easyReservations support.', 'easyReservations' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
        <td>
			<?php
			if ( ! $active_theme_info['has_easyreservations_support'] ) {
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Not declared', 'easyReservations' ) . '</mark>';
			} else {
				echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
			}
			?>
        </td>
    </tr>
    </tbody>
</table>
<table class="er_status_table widefat" cellspacing="0">
    <thead>
    <tr>
        <th colspan="3" data-export-label="Templates">
            <h2><?php esc_html_e( 'Templates', 'easyReservations' ); ?><?php er_print_help( esc_html__( 'This section shows any files that are overriding the default easyReservations template pages.', 'easyReservations' ) ); ?></h2>
        </th>
    </tr>
    </thead>
    <tbody>
	<?php if ( ! empty( $active_theme_info['overrides'] ) ) : ?>
        <tr>
            <td data-export-label="Overrides"><?php esc_html_e( 'Overrides', 'easyReservations' ); ?></td>
            <td class="help">&nbsp;</td>
            <td>
				<?php
				$total_overrides = count( $active_theme_info['overrides'] );
				for ( $i = 0; $i < $total_overrides; $i ++ ) {
					$override = $active_theme_info['overrides'][ $i ];
					if ( $override['core_version'] && ( empty( $override['version'] ) || version_compare( $override['version'], $override['core_version'], '<' ) ) ) {
						$current_version = $override['version'] ? $override['version'] : '-';
						printf(
						/* Translators: %1$s: Template name, %2$s: Template version, %3$s: Core version. */
							esc_html__( '%1$s version %2$s is out of date. The core version is %3$s', 'easyReservations' ),
							'<code>' . esc_html( $override['file'] ) . '</code>',
							'<strong style="color:red">' . esc_html( $current_version ) . '</strong>',
							esc_html( $override['core_version'] )
						);
					} else {
						echo esc_html( $override['file'] );
					}
					if ( ( count( $active_theme_info['overrides'] ) - 1 ) !== $i ) {
						echo ', ';
					}
					echo '<br />';
				}
				?>
            </td>
        </tr>
	<?php else : ?>
        <tr>
            <td data-export-label="Overrides"><?php esc_html_e( 'Overrides', 'easyReservations' ); ?>:</td>
            <td class="help">&nbsp;</td>
            <td>&ndash;</td>
        </tr>
	<?php endif; ?>

	<?php if ( true === $active_theme_info['has_outdated_templates'] ) : ?>
        <tr>
            <td data-export-label="Outdated Templates"><?php esc_html_e( 'Outdated templates', 'easyReservations' ); ?>:</td>
            <td class="help">&nbsp;</td>
            <td>
                <mark class="error">
                    <span class="dashicons dashicons-warning"></span>
                </mark>
                <a href="https://easyreservations.org/documentation/fixing-outdated-easyreservations-templates/" target="_blank">
					<?php esc_html_e( 'Learn how to update', 'easyReservations' ); ?>
                </a>
            </td>
        </tr>
	<?php endif; ?>
    </tbody>
</table>

<?php do_action( 'easyreservations_system_status_report' ); ?>
