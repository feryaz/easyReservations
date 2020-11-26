<?php
/**
 * Admin View: Page - Status
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap easy-ui">
    <h1 class="screen-reader-text"><?php echo esc_html( $headline ); ?></h1>
	<?php
	switch ( $current_section ) {
		case 'changelog':
			include 'html-admin-settings-status-changelog.php';
			break;
		case 'logs':
			include 'html-admin-settings-status-logs.php';
			break;
		case 'tools':
            $tools = $this->get_tools();
			include 'html-admin-settings-status-tools.php';
			break;
		default:
			if ( array_key_exists( $current_section, $sections ) && has_action( 'easyreservations_admin_status_content_' . $current_section ) ) {
				do_action( 'easyreservations_admin_status_content_' . $current_section );
			} else {
				include 'html-admin-settings-status-report.php';
			}
			break;
	}
	?>
</div>
