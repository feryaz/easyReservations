<?php
/**
 * Order Item Details
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/order/order-details-item.php.
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

if ( ! apply_filters( 'easyreservations_order_item_visible', true, $item ) ) {
	return;
}
?>
<tr class="<?php echo esc_attr( apply_filters( 'easyreservations_order_item_class', 'easyreservations-table__line-item order_item', $item, $order ) ); ?>">

    <td class="easyreservations-table__resource-name resource-name">
		<?php
		$is_visible = $resource && $resource->is_visible();
		$title      = $reservation ? $reservation->get_item_label() : $item->get_name();

		echo wp_kses_post( apply_filters( 'easyreservations_order_item_name', $title, $item, $is_visible ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		do_action( 'easyreservations_order_item_meta_start', $item_id, $item, $order, false );

		er_display_meta( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		do_action( 'easyreservations_order_item_meta_end', $item_id, $item, $order, false );
		?>
    </td>

    <td class="easyreservations-table__resource-total resource-total">
		<?php echo $order->get_formatted_item_subtotal( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </td>

</tr>

<?php if ( $show_purchase_note && $purchase_note ) : ?>

    <tr class="easyreservations-table__resource-purchase-note resource-purchase-note">

        <td colspan="2"><?php echo wpautop( do_shortcode( wp_kses_post( $purchase_note ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>

    </tr>

<?php endif; ?>
