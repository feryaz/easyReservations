<?php
/**
 * Admin View: Notice - Uploads directory is unprotected.
 *
 * @package easyReservations\Admin\Notices
 */

defined( 'ABSPATH' ) || exit;

$uploads = wp_get_upload_dir();

?>
<div id="message" class="error easyreservations-message">
	<a class="easyreservations-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'er-hide-notice', 'uploads_directory_is_public' ), 'easyreservations_hide_notices_nonce', '_wc_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'easyReservations' ); ?></a>

	<p>
	<?php
		echo wp_kses_post(
			sprintf(
				/* translators: 1: uploads directory URL 2: documentation URL */
				__( 'Your store\'s uploads directory is <a href="%1$s">browsable via the web</a>. We strongly recommend <a href="%2$s">configuring your web server to prevent directory indexing</a>.', 'easyReservations' ),
				esc_url( $uploads['baseurl'] . '/easyreservations_uploads' ),
				'https://easyreservations.org/documentation/protecting-your-uploads-directory/'
			)
		);
		?>
	</p>
</div>
