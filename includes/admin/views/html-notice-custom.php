<?php
/**
 * Admin View: Custom Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated easyreservations-message">
    <a class="easyReservations-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'er-hide-notice', $notice ), 'easyreservations_hide_notices_nonce', '_er_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'easyReservations' ); ?></a>
	<?php echo wp_kses_post( wpautop( $notice_html ) ); ?>
</div>
