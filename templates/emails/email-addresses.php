<?php
/**
 * Email Addresses
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/emails/email-addresses.php.
 *
 * HOWEVER, on occasion easyReservations will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package easyReservations/Templates/Emails
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text_align = is_rtl() ? 'right' : 'left';
$address    = $order->get_formatted_address();

?>
<table id="addresses" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top; margin-bottom: 40px; padding:0;" border="0">
    <tr>
        <td style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border:0; padding:0;" valign="top" width="50%">
            <h2><?php esc_html_e( 'Address', 'easyReservations' ); ?></h2>

            <address class="address">
				<?php echo wp_kses_post( $address ? $address : esc_html__( 'N/A', 'easyReservations' ) ); ?>
				<?php if ( $order->get_phone() ) : ?>
                    <br/><?php echo er_make_phone_clickable( $order->get_phone() ); ?>
				<?php endif; ?>
				<?php if ( $order->get_email() ) : ?>
                    <br/><?php echo esc_html( $order->get_email() ); ?>
				<?php endif; ?>
            </address>
        </td>
    </tr>
</table>
