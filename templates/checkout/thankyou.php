<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/checkout/thankyou.php.
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

<div class="easyreservations-order">

	<?php if ( $order ) : ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

            <p class="easyreservations-notice easyreservations-notice--error easyreservations-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'easyReservations' ); ?></p>

            <p class="easyreservations-notice easyreservations-notice--error easyreservations-thankyou-order-failed-actions">
                <a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>"
                    class="button pay"><?php esc_html_e( 'Pay', 'easyReservations' ) ?></a>
				<?php if ( is_user_logged_in() ) : ?>
                    <a href="<?php echo esc_url( er_get_page_permalink( 'myaccount' ) ); ?>"
                        class="button pay"><?php esc_html_e( 'My account', 'easyReservations' ); ?></a>
				<?php endif; ?>
            </p>

		<?php else : ?>

            <p class="easyreservations-notice easyreservations-notice--success easyreservations-thankyou-order-received"><?php echo apply_filters( 'easyreservations_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'easyReservations' ), $order ); ?></p>

            <ul class="easyreservations-order-overview easyreservations-thankyou-order-details order_details">

                <li class="easyreservations-order-overview__order order">
					<?php esc_html_e( 'Order number:', 'easyReservations' ); ?>
                    <strong><?php echo esc_html( $order->get_order_number() ); ?></strong>
                </li>

                <li class="easyreservations-order-overview__date date">
					<?php esc_html_e( 'Date:', 'easyReservations' ); ?>
                    <strong><?php echo esc_html( er_format_datetime( $order->get_date_created() ) ); ?></strong>
                </li>

				<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_email() ) : ?>
                    <li class="easyreservations-order-overview__email email">
						<?php esc_html_e( 'Email:', 'easyReservations' ); ?>
                        <strong><?php echo sanitize_email( $order->get_email() ); ?></strong>
                    </li>
				<?php endif; ?>

                <li class="easyreservations-order-overview__total total">
					<?php esc_html_e( 'Total:', 'easyReservations' ); ?>
                    <strong><?php echo $order->get_formatted_total(); ?></strong>
                </li>

				<?php if ( $order->get_payment_method_title() ) : ?>
                    <li class="easyreservations-order-overview__payment-method method">
						<?php esc_html_e( 'Payment method:', 'easyReservations' ); ?>
                        <strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
                    </li>
				<?php endif; ?>

            </ul>

		<?php endif; ?>

		<?php do_action( 'easyreservations_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'easyreservations_thankyou', $order->get_id() ); ?>

	<?php else : ?>

        <p class="easyreservations-notice easyreservations-notice--success easyreservations-thankyou-order-received"><?php echo esc_html( apply_filters( 'easyreservations_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'easyReservations' ), null ) ); ?></p>

	<?php endif; ?>

</div>
