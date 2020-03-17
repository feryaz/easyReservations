<?php
/**
 * Order Customer Details
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/order/order-details-customer.php.
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

defined( 'ABSPATH' ) || exit;
?>
<section class="easyreservations-customer-details">

    <h2 class="easyreservations-column__title"><?php esc_html_e( 'Address', 'easyReservations' ); ?></h2>

    <address>
		<?php echo wp_kses_post( $order->get_formatted_address( esc_html__( 'N/A', 'easyReservations' ) ) ); ?>

		<?php if ( $order->get_phone() ) : ?>
            <p class="easyreservations-customer-details--phone"><?php echo esc_html( $order->get_phone() ); ?></p>
		<?php endif; ?>

		<?php if ( $order->get_email() ) : ?>
            <p class="easyreservations-customer-details--email"><?php echo esc_html( $order->get_email() ); ?></p>
		<?php endif; ?>
    </address>

	<?php do_action( 'easyreservations_order_details_after_customer_details', $order ); ?>

</section>