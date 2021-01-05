<?php
/**
 * Admin View: Page - Status Logs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logs = ER_Logger::get_log_files();

if ( ! empty( $_REQUEST['log_file'] ) && isset( $logs[ sanitize_title( wp_unslash( $_REQUEST['log_file'] ) ) ] ) ) { // WPCS: input var ok, CSRF ok.
	$viewed_log = $logs[ sanitize_title( wp_unslash( $_REQUEST['log_file'] ) ) ]; // WPCS: input var ok, CSRF ok.
} elseif ( ! empty( $logs ) ) {
	$viewed_log = current( $logs );
}

$handle = ! empty( $viewed_log ) ? substr( $viewed_log, 0, strlen( $viewed_log ) > 48 ? strlen( $viewed_log ) - 48 : strlen( $viewed_log ) - 4 ) : '';

if ( ! empty( $_REQUEST['handle'] ) ) { // WPCS: input var ok, CSRF ok.
	if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'remove_log' ) ) { // WPCS: input var ok, sanitization ok.
		wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'easyReservations' ) );
	}

	if ( ! empty( $_REQUEST['handle'] ) ) {  // WPCS: input var ok.
		$file = realpath( trailingslashit( RESERVATIONS_LOG_DIR ) . wp_unslash( $_REQUEST['handle'] ) );
		if ( 0 === stripos( $file, realpath( trailingslashit( RESERVATIONS_LOG_DIR ) ) ) && is_file( $file ) && is_writable( $file ) ) { // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_is_writable
			$this->close( $file ); // Close first to be certain no processes keep it alive after it is unlinked.
			$removed = unlink( $file ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_unlink
		}
	}

	wp_safe_redirect( admin_url( 'admin.php?page=er-settings&tab=status&section=logs' ) );
}

?>
<?php if ( $logs ) : ?>
    <div id="log-viewer-select">
        <div class="alignleft">
            <h2>
				<?php echo esc_html( $viewed_log ); ?>
				<?php if ( ! empty( $viewed_log ) ) : ?>
                    <a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'handle' => sanitize_title( $viewed_log ) ), admin_url( 'admin.php?page=reservation-settings&tab=status&section=logs' ) ), 'remove_log' ) ); ?>" class="button"><?php esc_html_e( 'Delete log', 'easyReservations' ); ?></a>
				<?php endif; ?>
            </h2>
        </div>
        <div class="alignright">
            <form action="<?php echo esc_url( admin_url( 'admin.php?page=er-settings&tab=status&section=logs' ) ); ?>" method="post">
                <select name="log_file">
					<?php foreach ( $logs as $log_key => $log_file ) : ?>
						<?php
						$timestamp = filemtime( RESERVATIONS_LOG_DIR . $log_file );
						/* translators: 1: last access date 2: last access time */
						$date = sprintf( __( '%1$s at %2$s', 'easyReservations' ), date_i18n( er_date_format(), $timestamp ), date_i18n( er_time_format(), $timestamp ) );
						?>
                        <option value="<?php echo esc_attr( $log_key ); ?>" <?php selected( sanitize_title( $viewed_log ), $log_key ); ?>><?php echo esc_html( $log_file ); ?> (<?php echo esc_html( $date ); ?>)</option>
					<?php endforeach; ?>
                </select>
                <button type="submit" class="button" value="<?php esc_attr_e( 'View', 'easyReservations' ); ?>"><?php esc_html_e( 'View', 'easyReservations' ); ?></button>
            </form>
        </div>
        <div class="clear"></div>
    </div>
    <div id="log-viewer">
        <pre><?php echo esc_html( file_get_contents( RESERVATIONS_LOG_DIR . $viewed_log ) ); ?></pre>
    </div>
<?php else : ?>
    <div class="updated easyreservations-message inline">
        <p><?php esc_html_e( 'There are currently no logs to view.', 'easyReservations' ); ?></p></div>
<?php endif; ?>
