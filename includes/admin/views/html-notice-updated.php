<?php
/**
 * Admin View: Notice - Updated.
 *
 * @package easyReservations\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated easyreservations-message easyreservations-message--success">
    <a class="easyreservations-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'er-hide-notice', 'update_' . $plugin, remove_query_arg( 'do_update_easyreservations' ) ), 'easyreservations_hide_notices_nonce', '_er_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'easyReservations' ); ?></a>

    <p><?php esc_html_e( 'easyReservations database update complete. Thank you for updating to the latest version!', 'easyReservations' ); ?></p>
</div>
