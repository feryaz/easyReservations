<?php
/**
 * Order details
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/order/order-details.php.
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

$order = er_get_order( $order_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited

if ( ! $order ) {
	return;
}

$order_items           = $order->get_reservations();
$show_purchase_note    = $order->has_status( apply_filters( 'easyreservations_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
$show_customer_details = is_user_logged_in() && $order->get_user_id() === get_current_user_id();

?>
    <section class="easyreservations-order-details">
		<?php do_action( 'easyreservations_order_details_before_order_table', $order ); ?>

        <h2 class="easyreservations-order-details__title"><?php esc_html_e( 'Order details', 'easyReservations' ); ?></h2>

        <table class="easyreservations-table easyreservations-table--order-details shop_table order_details">

            <thead>
            <tr>
                <th class="easyreservations-table__resource-name resource-name"><?php esc_html_e( 'Item', 'easyReservations' ); ?></th>
                <th class="easyreservations-table__resource-table resource-total"><?php esc_html_e( 'Total', 'easyReservations' ); ?></th>
            </tr>
            </thead>

            <tbody>
			<?php
			do_action( 'easyreservations_order_details_before_order_table_items', $order );

			foreach ( $order_items as $item_id => $item ) {
				$resource    = $item->get_resource();
				$reservation = ER()->reservation_manager()->get( $item->get_reservation_id() );

				er_get_template(
					'order/order-details-item.php',
					array(
						'order'              => $order,
						'item_id'            => $item_id,
						'item'               => $item,
						'show_purchase_note' => $show_purchase_note,
						'purchase_note'      => $resource ? $resource->get_purchase_note() : '',
						'resource'           => $resource,
						'reservation'        => $reservation,
					)
				);
			}

			do_action( 'easyreservations_order_details_after_order_table_items', $order );
			?>
            </tbody>

            <tfoot>
			<?php foreach ( $order->get_order_item_totals() as $key => $total ): ?>
                <tr>
                    <th scope="row"><?php echo esc_html( $total['label'] ); ?></th>
                    <td><?php echo ( 'payment_method' === $key ) ? esc_html( $total['value'] ) : wp_kses_post( $total['value'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                </tr>
			<?php endforeach; ?>
			<?php if ( $order->get_customer_note() ) : ?>
                <tr>
                    <th><?php esc_html_e( 'Note:', 'easyReservations' ); ?></th>
                    <td><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
                </tr>
			<?php endif; ?>
            </tfoot>
        </table>

		<?php do_action( 'easyreservations_order_details_after_order_table', $order ); ?>
    </section>

<?php
if ( $show_customer_details ) {
	er_get_template( 'order/order-details-customer.php', array( 'order' => $order ) );
}
