<?php
/**
 * Admin View: Notice - Secure connection.
 *
 * @package easyReservations\Admin\Notices
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated easyreservations-message">
    <a class="easyreservations-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'er-hide-notice', 'no_secure_connection' ), 'easyreservations_hide_notices_nonce', '_er_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'easyReservations' ); ?></a>

    <p>
		<?php
		echo wp_kses_post( sprintf(
		/* translators: %s: documentation URL */
			__( 'Your store does not appear to be using a secure connection. We highly recommend serving your entire website over an HTTPS connection to help keep customer data secure. <a href="%s">Learn more here.</a>', 'easyReservations' ),
			'https://easyreservations.org/documentation/ssl-and-https/'
		) );
		?>
    </p>
</div>
