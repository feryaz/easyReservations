<?php
/**
 * Checkout Order Receipt Template
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/cart/order-receipt.php.
 *
 * HOWEVER, on occasion easyReservations will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package easyReservations/Templates
 * @version 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<ul class="order_details">
    <li class="order">
		<?php esc_html_e( 'Order number:', 'easyReservations' ); ?>
        <strong><?php echo esc_html( $order->get_order_number() ); ?></strong>
    </li>
    <li class="date">
		<?php esc_html_e( 'Date:', 'easyReservations' ); ?>
        <strong><?php echo esc_html( er_format_datetime( $order->get_date_created() ) ); ?></strong>
    </li>
    <li class="total">
		<?php esc_html_e( 'Total:', 'easyReservations' ); ?>
        <strong><?php echo wp_kses_post( $order->get_formatted_total() ); ?></strong>
    </li>
	<?php if ( $order->get_payment_method_title() ) : ?>
        <li class="method">
			<?php esc_html_e( 'Payment method:', 'easyReservations' ); ?>
            <strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
        </li>
	<?php endif; ?>
</ul>

<?php do_action( 'easyreservations_receipt_' . $order->get_payment_method(), $order->get_id() ); ?>

<div class="clear"></div>