<?php
/**
 * Admin View: Page - Status Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<form method="post">
	<?php settings_fields( 'easyreservations_status_settings_fields' ); ?>
    <table class="er_status_table er_status_table--tools widefat" cellspacing="0">
        <tbody class="tools">
		<?php foreach ( $tools as $action => $tool ) : ?>
            <tr class="<?php echo sanitize_html_class( $action ); ?>">
                <th>
                    <strong class="name"><?php echo esc_html( $tool['name'] ); ?></strong>
                    <p class="description"><?php echo wp_kses_post( $tool['desc'] ); ?></p>
                </th>
                <td class="run-tool">
                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=er-settings&tab=status&section=tools&action=' . $action ), 'debug_action' ); ?>" class="button button-large <?php echo esc_attr( $action ); ?>"><?php echo esc_html( $tool['button'] ); ?></a>
                </td>
            </tr>
		<?php endforeach; ?>
        </tbody>
    </table>
</form>
