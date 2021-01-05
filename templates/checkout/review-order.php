<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/checkout/review-order.php.
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
<table class="shop_table easyreservations-checkout-review-order-table">
    <thead>
    <tr>
        <th class="name"><?php esc_html_e( 'Item', 'easyReservations' ); ?></th>
        <th class="amount"><?php esc_html_e( 'Subtotal', 'easyReservations' ); ?></th>
    </tr>
    </thead>
    <tbody>
	<?php
	do_action( 'easyreservations_review_order_before_cart_contents' );

	foreach ( ER()->cart->get_order()->get_items() as $receipt_item ) {
		?>
        <tr class="<?php echo esc_attr( apply_filters( 'easyreservations_cart_item_class', 'cart_item', $receipt_item ) ); ?>">
            <td class="name">
				<?php echo apply_filters( 'easyreservations_cart_item_name', $receipt_item->get_name(), $receipt_item ) . '&nbsp;'; ?>
				<?php //echo wc_get_formatted_cart_item_data( $cart_item ); ?>
            </td>
            <td class="amount">
				<?php echo er_price( apply_filters( 'easyreservations_cart_item_subtotal', $receipt_item->get_subtotal(), $receipt_item ), true ); ?>
            </td>
        </tr>
		<?php
	}

	do_action( 'easyreservations_review_order_after_cart_contents' );
	?>
    </tbody>
    <tfoot>

    <tr class="cart-subtotal">
        <th><?php esc_html_e( 'Subtotal', 'easyReservations' ); ?></th>
        <td class="amount"><?php er_cart_totals_subtotal_html(); ?></td>
    </tr>

	<?php do_action( 'easyreservations_review_order_after_subtotal', ER()->cart ); ?>

	<?php foreach ( ER()->cart->get_fees() as $fee ) : ?>
        <tr class="fee">
            <th><?php echo esc_html( $fee->get_name() ); ?></th>
            <td><?php er_cart_totals_fee_html( $fee ); ?></td>
        </tr>
	<?php endforeach; ?>

	<?php if ( er_tax_enabled() && ! ER()->cart->display_prices_including_tax() ) : ?>
		<?php if ( 'itemized' === get_option( 'reservations_tax_total_display' ) ) : ?>
			<?php foreach ( ER()->cart->get_tax_totals() as $code => $tax ) : ?>
                <tr class="tax-rate tax-rate-<?php echo sanitize_title( $code ); ?>">
                    <th><?php echo esc_html( $tax->label ); ?></th>
                    <td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
                </tr>
			<?php endforeach; ?>
		<?php else : ?>
            <tr class="tax-total">
                <th><?php echo esc_html( ER()->countries->tax_or_vat() ); ?></th>
                <td class="amount"><?php er_cart_totals_taxes_total_html(); ?></td>
            </tr>
		<?php endif; ?>
	<?php endif; ?>

	<?php do_action( 'easyreservations_review_order_before_order_total' ); ?>

    <tr class="order-total">
        <th><?php esc_html_e( 'Total', 'easyReservations' ); ?></th>
        <td class="amount"><?php er_cart_totals_order_total_html(); ?></td>
    </tr>

	<?php do_action( 'easyreservations_review_order_after_order_total' ); ?>

    </tfoot>
</table>
