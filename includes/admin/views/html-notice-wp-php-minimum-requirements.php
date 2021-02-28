<?php
/**
 * Admin View: Notice - PHP & WP minimum requirements.
 *
 * @package easyReservations\Admin\Notices
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="message" class="updated easyreservations-message">
    <a class="easyreservations-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'er-hide-notice', RESERVATIONS_PHP_MIN_REQUIREMENTS_NOTICE ), 'easyreservations_hide_notices_nonce', '_er_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'easyReservations' ); ?></a>

    <p>
		<?php
		echo wp_kses_post(
			sprintf(
				$msg . '<p><a href="%s" class="button button-primary">' . __( 'Learn how to upgrade', 'easyReservations' ) . '</a></p>',
				add_query_arg(
					array(
						'utm_source'   => 'wpphpupdatebanner',
						'utm_medium'   => 'product',
						'utm_campaign' => 'woocommerceplugin',
						'utm_content'  => 'docs',
					),
					'https://easyreservations.org/documentation/update-php-and-wordpress/'
				)
			)
		);
		?>
    </p>
</div>
