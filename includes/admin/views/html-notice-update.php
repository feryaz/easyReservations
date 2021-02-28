<?php
/**
 * Admin View: Notice - Update
 *
 * @package easyReservations\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$update_url = wp_nonce_url(
	add_query_arg( 'do_update_easyreservations', $plugin, admin_url( 'plugins.php' ) ),
	'er_db_update',
	'er_db_update_nonce'
);

?>
<div id="message" class="updated easyreservations-message">
	<?php if ( $plugin === 'reservations' ): ?>
        <p>
            <strong>Welcome to the easyReservations 6.0 alpha!</strong>
        </p>
        Please be aware that this update is not finished and many old features are not implemented yet.<br>
        This update requires many irreversible database changes, so
        <strong>backup your database</strong> before continuing.<br><br>

        <a href="http://easyreservations.org/easyreservations-6-0-alpha/" target="_blank">Read more</a>
        <br>
	<?php else: ?>
        <p>
            <strong><?php esc_html_e( 'easyReservations database update required', 'easyReservations' ); ?></strong>
        </p>
        <p>
			<?php printf( esc_html__( '%s has been updated! To keep things running smoothly, we have to update your database to the newest version.', 'easyReservations' ), $plugin === 'reservations' ? 'easyReservations' : 'Premium' ); ?>
        </p>
	<?php endif; ?>

    <p class="submit">
        <a href="<?php echo esc_url( $update_url ); ?>" class="er-update-now button-primary">
			<?php esc_html_e( 'Update easyReservations Database', 'easyReservations' ); ?>
        </a>
        <a href="https://easyreservations.org/documentation/how-to-update-easyreservations/" class="button-secondary">
		    <?php esc_html_e( 'Learn more about updates', 'easyReservations' ); ?>
        </a>
    </p>
</div>