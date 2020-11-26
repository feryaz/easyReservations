<?php
/**
 * Admin View: Notice - Base table missing.
 *
 * @package easyReservations\Admin
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="updated easyreservations-message">
	<a class="easyreservations-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'er-hide-notice', 'base_tables_missing' ), 'easyreservations_hide_notices_nonce', '_er_notice_nonce' ) ); ?>">
		<?php esc_html_e( 'Dismiss', 'easyReservations' ); ?>
	</a>

	<p>
		<strong><?php esc_html_e( 'Database tables missing', 'easyReservations' ); ?></strong>
	</p>
	<p>
		<?php
		$verify_db_tool_available = array_key_exists( 'verify_db_tables', ER_Settings_Status::get_tools() );
		$missing_tables           = get_option( 'reservations_schema_missing_tables' );
		if ( $verify_db_tool_available ) {
			echo wp_kses_post(
				sprintf(
				/* translators: %1%s: Missing tables (seperated by ",") %2$s: Link to check again */
					__( 'One or more tables required for easyReservations to function are missing, some features may not work as expected. Missing tables: %1$s. <a href="%2$s">Check again.</a>', 'easyReservations' ),
					esc_html( implode( ', ', $missing_tables ) ),
					wp_nonce_url( admin_url( 'admin.php?page=er-settings&tab=status&section=tools&action=verify_db_tables' ), 'debug_action' )
				)
			);
		} else {
			echo wp_kses_post(
				sprintf(
				/* translators: %1%s: Missing tables (seperated by ",") */
					__( 'One or more tables required for easyReservations to function are missing, some features may not work as expected. Missing tables: %1$s.', 'easyReservations' ),
					esc_html( implode( ', ', $missing_tables ) )
				)
			);
		}
		?>
	</p>
</div>
