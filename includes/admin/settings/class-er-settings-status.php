<?php
defined( 'ABSPATH' ) || exit;

/**
 * ER_Settings_General.
 */
class ER_Settings_Status extends ER_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'status';
		$this->label = __( 'Status', 'easyReservations' );

		if ( isset( $_GET['action'] ) ) {
			check_admin_referer( 'debug_action' );
			$result = $this->execute_tool( sanitize_key( $_GET['action'] ) );

			if( $result['success'] ){
				ER_Admin_Notices::add_temporary_success( $result['message'] );
			} else {
				ER_Admin_Notices::add_temporary_error( $result['message'] );
			}
		}

		parent::__construct();
	}

	public function get_sections() {
		global $current_section;

		if ( empty( $current_section ) ) {
			$current_section = 'status';
		}

		return apply_filters( 'easyreservations_admin_status_sections', array(
			'status'    => __( 'System status', 'easyReservations' ),
			'tools' => __( 'Tools', 'easyReservations' ),
			'changelog' => __( 'Changelog', 'easyReservations' ),
			'logs'      => __( 'Logs', 'easyReservations' ),
		) );

	}

	public static function get_tools(){
		$tools = array(
			'clear_transients'                   => array(
				'name'   => __( 'easyReservations transients', 'easyReservations' ),
				'button' => __( 'Clear transients', 'easyReservations' ),
				'desc'   => __( 'This tool will clear the resource/shop transients cache.', 'easyReservations' ),
			),
			'clear_expired_transients'           => array(
				'name'   => __( 'Expired transients', 'easyReservations' ),
				'button' => __( 'Clear transients', 'easyReservations' ),
				'desc'   => __( 'This tool will clear ALL expired transients from WordPress.', 'easyReservations' ),
			),
			'recount_terms'                      => array(
				'name'   => __( 'Term counts', 'easyReservations' ),
				'button' => __( 'Recount terms', 'easyReservations' ),
				'desc'   => __( 'This tool will recount resource terms - useful when changing your settings in a way which hides resources from the catalog.', 'easyReservations' ),
			),
			'reset_roles'                        => array(
				'name'   => __( 'Capabilities', 'easyReservations' ),
				'button' => __( 'Reset capabilities', 'easyReservations' ),
				'desc'   => __( 'This tool will reset the admin, customer and reservation_manager roles to default. Use this if your users cannot access all of the WooCommerce admin pages.', 'easyReservations' ),
			),
			'clear_sessions'                     => array(
				'name'   => __( 'Clear customer sessions', 'easyReservations' ),
				'button' => __( 'Clear', 'easyReservations' ),
				'desc'   => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'easyReservations' ),
					__( 'This tool will delete all customer session data from the database, including current carts and saved carts in the database.', 'easyReservations' )
				),
			),
			'clear_template_cache'               => array(
				'name'   => __( 'Clear template cache', 'easyReservations' ),
				'button' => __( 'Clear', 'easyReservations' ),
				'desc'   => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'easyReservations' ),
					__( 'This tool will empty the template cache.', 'easyReservations' )
				),
			),
			'install_pages'                      => array(
				'name'   => __( 'Create default easyReservations pages', 'easyReservations' ),
				'button' => __( 'Create pages', 'easyReservations' ),
				'desc'   => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'easyReservations' ),
					__( 'This tool will install all the missing easyReservations pages. Pages already defined and set up will not be replaced.', 'easyReservations' )
				),
			),
			'delete_taxes'                       => array(
				'name'   => __( 'Delete easyReservations tax rates', 'easyReservations' ),
				'button' => __( 'Delete tax rates', 'easyReservations' ),
				'desc'   => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'easyReservations' ),
					__( 'This option will delete ALL of your tax rates, use with caution. This action cannot be reversed.', 'easyReservations' )
				),
			),
		);
		if ( method_exists( 'ER_Install', 'verify_base_tables' ) ) {
			$tools['verify_db_tables'] = array(
				'name'   => __( 'Verify base database tables', 'easyReservations' ),
				'button' => __( 'Verify database', 'easyReservations' ),
				'desc'   => sprintf(
					__( 'Verify if all base database tables are present.', 'easyReservations' )
				),
			);
		}

		return apply_filters( 'easyreservations_debug_tools', $tools );
	}
	/**
	 * Actually executes a tool.
	 *
	 * @param string $tool Tool.
	 *
	 * @return array
	 */
	public function execute_tool( $tool ) {
		global $wpdb;
		$ran = true;
		switch ( $tool ) {
			case 'clear_transients':
				er_delete_easy_order_transients();
				delete_transient( 'er_count_comments' );

				$message = __( 'Resource transients cleared', 'easyReservations' );
				break;

			case 'clear_expired_transients':
				/* translators: %d: amount of expired transients */
				$message = sprintf( __( '%d transients rows cleared', 'easyReservations' ), er_delete_expired_transients() );
				break;

			case 'reset_roles':
				// Remove then re-add caps and roles.
				ER_Install::remove_roles();
				ER_Install::create_roles();
				$message = __( 'Roles successfully reset', 'easyReservations' );
				break;

			case 'recount_terms':
				$resource_cats = get_terms(
					'resource_cat',
					array(
						'hide_empty' => false,
						'fields'     => 'id=>parent',
					)
				);
				_er_term_recount( $resource_cats, get_taxonomy( 'resource_cat' ), true, false );
				$resource_tags = get_terms(
					'resource_tag',
					array(
						'hide_empty' => false,
						'fields'     => 'id=>parent',
					)
				);
				_er_term_recount( $resource_tags, get_taxonomy( 'resource_tag' ), true, false );
				$message = __( 'Terms successfully recounted', 'easyReservations' );
				break;

			case 'clear_sessions':
				$wpdb->query( "TRUNCATE {$wpdb->prefix}reservations_sessions" );
				$result = absint( $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key='_easyreservations_persistent_cart_" . get_current_blog_id() . "';" ) ); // WPCS: unprepared SQL ok.
				wp_cache_flush();
				/* translators: %d: amount of sessions */
				$message = sprintf( __( 'Deleted all active sessions, and %d saved carts.', 'easyReservations' ), absint( $result ) );
				break;

			case 'install_pages':
				ER_Install::create_pages();
				$message = __( 'All missing easyReservations pages successfully installed', 'easyReservations' );
				break;

			case 'delete_taxes':
				delete_option( 'reservations_tax_rates' );
				$message = __( 'Tax rates successfully deleted', 'easyReservations' );
				break;

			case 'clear_template_cache':
				er_clear_template_cache();
				$message = __( 'Template cache cleared.', 'easyReservations' );

				break;

			case 'verify_db_tables':
				// Try to manually create table again.
				$missing_tables = ER_Install::verify_base_tables( true, true );
				if ( 0 === count( $missing_tables ) ) {
					$message = __( 'Database verified successfully.', 'easyReservations' );
				} else {
					$message = __( 'Verifying database... One or more tables are still missing: ', 'easyReservations' );
					$message .= implode( ', ', $missing_tables );
					$ran     = false;
				}
				break;

			default:
				$tools = $this->get_tools();
				if ( isset( $tools[ $tool ]['callback'] ) ) {
					$callback = $tools[ $tool ]['callback'];
					$return   = call_user_func( $callback );
					if ( is_string( $return ) ) {
						$message = $return;
					} elseif ( false === $return ) {
						$callback_string = is_array( $callback ) ? get_class( $callback[0] ) . '::' . $callback[1] : $callback;
						$ran             = false;
						/* translators: %s: callback string */
						$message = sprintf( __( 'There was an error calling %s', 'easyReservations' ), $callback_string );
					} else {
						$message = __( 'Tool ran.', 'easyReservations' );
					}
				} else {
					$ran     = false;
					$message = __( 'There was an error calling this tool. There is no callback present.', 'easyReservations' );
				}
				break;
		}

		return array(
			'success' => $ran,
			'message' => $message,
		);
	}

	public function output() {
		global $current_section, $hide_save_button;
		$hide_save_button = 'yes';

		$sections = $this->get_sections();

		$headline = isset( $sections[ $current_section ] ) ? $sections[ $current_section ] : __( 'System status', 'easyReservations' );

		include 'views/html-admin-settings-status.php';
	}
}

return new ER_Settings_Status();
