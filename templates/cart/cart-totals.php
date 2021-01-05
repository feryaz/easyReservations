<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/cart/cart-totals.php.
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
<div class="cart_totals">

	<?php do_action( 'easyreservations_before_cart_totals' ); ?>

    <h2><?php esc_html_e( 'Cart totals', 'easyReservations' ); ?></h2>

    <table cellspacing="0" class="shop_table shop_table_responsive">

        <tr class="cart-subtotal">
            <th><?php esc_html_e( 'Subtotal', 'easyReservations' ); ?></th>
            <td data-title="<?php esc_attr_e( 'Subtotal', 'easyReservations' ); ?>"><?php er_cart_totals_subtotal_html(); ?></td>
        </tr>

		<?php do_action( 'easyreservations_cart_totals_after_order_subtotal' ); ?>

		<?php foreach ( ER()->cart->get_fees() as $fee ) : ?>
            <tr class="fee">
                <th><?php echo esc_html( $fee->get_name() ); ?></th>
                <td data-title="<?php echo esc_attr( $fee->get_name() ); ?>"><?php er_cart_totals_fee_html( $fee ); ?></td>
            </tr>
		<?php endforeach; ?>

		<?php if ( er_tax_enabled() && ! ER()->cart->display_prices_including_tax() ) :
			if ( 'itemized' === get_option( 'reservations_tax_total_display' ) ) : ?>
				<?php foreach ( ER()->cart->get_tax_totals() as $code => $tax ) : ?>
                    <tr class="tax-rate tax-rate-<?php echo sanitize_title( $code ); ?>">
                        <th><?php echo esc_html( $tax->label ); ?></th>
                        <td data-title="<?php echo esc_attr( $tax->label ); ?>"><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
                    </tr>
				<?php endforeach; ?>
			<?php else : ?>
                <tr class="tax-total">
                    <th><?php echo esc_html( ER()->countries->tax_or_vat() ); ?></th>
                    <td data-title="<?php echo esc_attr( ER()->countries->tax_or_vat() ); ?>"><?php er_cart_totals_taxes_total_html(); ?></td>
                </tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'easyreservations_cart_totals_before_order_total' ); ?>

        <tr class="order-total">
            <th><?php esc_html_e( 'Total', 'easyReservations' ); ?></th>
            <td data-title="<?php esc_attr_e( 'Total', 'easyReservations' ); ?>"><?php er_cart_totals_order_total_html(); ?></td>
        </tr>

		<?php do_action( 'easyreservations_cart_totals_after_order_total' ); ?>

    </table>

    <div class="er-proceed-to-checkout">
		<?php do_action( 'easyreservations_proceed_to_checkout' ); ?>
    </div>

	<?php do_action( 'easyreservations_after_cart_totals' ); ?>

</div>
