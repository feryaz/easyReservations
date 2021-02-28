<?php
/**
 * Admin View: Notice - Template Check
 *
 * @package easyReservations/Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$theme = wp_get_theme();
?>
<div id="message" class="updated easyreservations-message">
    <a class="easyreservations-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'er-hide-notice', 'template_files' ), 'easyreservations_hide_notices_nonce', '_er_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'easyReservations' ); ?></a>

    <p>
		<?php /* translators: %s: theme name */ ?>
		<?php printf( __( '<strong>Your theme (%s) contains outdated copies of some easyReservations template files.</strong> These files may need updating to ensure they are compatible with the current version of easyReservations. Suggestions to fix this:', 'easyReservations' ), esc_html( $theme['Name'] ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
    <ol>
        <li><?php esc_html_e( 'Update your theme to the latest version. If no update is available contact your theme author asking about compatibility with the current easyReservations version.', 'easyReservations' ); ?></li>
        <li><?php esc_html_e( 'If you copied over a template file to change something, then you will need to copy the new version of the template and apply your changes again.', 'easyReservations' ); ?></li>
    </ol>
    </p>
    <p class="submit">
        <a class="button-primary" href="https://easyreservation.org/documentation/template-structure/" target="_blank"><?php esc_html_e( 'Learn more about templates', 'easyReservations' ); ?></a>
        <a class="button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=er-settings&tab=status' ) ); ?>" target="_blank"><?php esc_html_e( 'View affected templates', 'easyReservations' ); ?></a>
    </p>
</div>
