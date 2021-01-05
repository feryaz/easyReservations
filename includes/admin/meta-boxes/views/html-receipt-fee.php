<?php
/**
 * Shows an order item fee
 *
 * @var object $item The item being displayed
 * @var int    $item_id The id of the item being displayed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<tr class="fee <?php echo ( ! empty( $class ) ) ? esc_attr( $class ) : ''; ?>" data-receipt_item_id="<?php echo esc_attr( $item_id ); ?>">
    <td class="thumb">
        <div></div>
    </td>

    <td class="name">
        <div class="view">
			<?php echo esc_html( $item->get_name() ? $item->get_name() : __( 'Fee', 'easyReservations' ) ); ?>
        </div>
        <div class="edit" style="display: none;">
            <input type="text" placeholder="<?php esc_attr_e( 'Fee name', 'easyReservations' ); ?>" name="receipt_item_name[<?php echo absint( $item_id ); ?>]" value="<?php echo ( $item->get_name() ) ? esc_attr( $item->get_name() ) : ''; ?>"/>
            <input type="hidden" class="receipt_item_id" name="receipt_item_id[]" value="<?php echo esc_attr( $item_id ); ?>"/>
        </div>
		<?php do_action( 'easyreservations_after_receipt_fee_item_name', $item_id, $item, null ); ?>
    </td>

	<?php do_action( 'easyreservations_admin_receipt_item_values', null, $item, absint( $item_id ) ); ?>

    <td class="item_cost" width="1%">&nbsp;</td>

    <td class="line_cost" width="1%">
        <div class="view">
			<?php
			echo er_price( $item->get_total(), true );

			if ( $item->get_subtotal() !== $item->get_total() ) {
				/* translators: %s: discount amount */
				echo '<span class="er-receipt-item-discount">' . sprintf( esc_html__( '%s discount', 'easyReservations' ), er_price( er_format_decimal( $item->get_subtotal() - $item->get_total(), '' ), true ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			if ( $object->get_type() === 'easy_order' && $refunded = $object->get_total_refunded_for_item( $item_id, 'fee' ) ) {
				echo '<small class="refunded">-' . er_price( $refunded, true ) . '</small>';
			}
			?>
        </div>
        <div class="edit" style="display: none;">
            <div class="split-input">
                <div class="input">
                    <label><?php esc_attr_e( 'Before discount', 'easyReservations' ); ?></label>
                    <input type="text" name="line_subtotal[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( er_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( er_format_localized_price( $item->get_subtotal() ) ); ?>" class="line_subtotal er_input_price" data-subtotal="<?php echo esc_attr( er_format_localized_price( $item->get_subtotal() ) ); ?>"/>
                </div>
                <div class="input">
                    <label><?php esc_attr_e( 'Total', 'easyReservations' ); ?></label>
                    <input type="text" name="line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( er_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( er_format_localized_price( $item->get_total() ) ); ?>" class="line_total er_input_price" data-tip="<?php esc_attr_e( 'After pre-tax discounts.', 'easyReservations' ); ?>" data-total="<?php echo esc_attr( er_format_localized_price( $item->get_total() ) ); ?>"/>
                </div>
            </div>
        </div>
        <div class="refund" style="display: none;">
            <input type="text" name="refund_line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( er_format_localized_price( 0 ) ); ?>" class="refund_line_total er_input_price"/>
        </div>
    </td>

	<?php
	if ( ( $tax_data = $item->get_taxes() ) && er_tax_enabled() ) {
		foreach ( $receipt_taxes as $tax_item ) {
			$tax_item_id       = $tax_item->get_rate_id();
			$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
			$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';
			?>
            <td class="line_tax" width="1%">
                <div class="view">
					<?php
					echo ( '' !== $tax_item_total ) ? er_price( er_round_tax_total( $tax_item_total ), true ) : '&ndash;';

					if ( $object->get_type() === 'easy_order' && $refunded = $object->get_tax_refunded_for_item( $item_id, $tax_item_id, 'fee' ) ) {
						echo '<small class="refunded">-' . er_price( $refunded, true ) . '</small>';
					}
					?>
                </div>
                <div class="edit" style="display: none;">
                    <div class="split-input">
                        <div class="input">
                            <label><?php esc_attr_e( 'Before discount', 'easyReservations' ); ?></label>
                            <input type="text" name="line_subtotal_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo esc_attr( er_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( er_format_localized_price( $tax_item_subtotal ) ); ?>" class="line_subtotal_tax er_input_price" data-subtotal_tax="<?php echo esc_attr( er_format_localized_price( $tax_item_subtotal ) ); ?>" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>"/>
                        </div>
                        <div class="input">
                            <label><?php esc_attr_e( 'Total', 'easyReservations' ); ?></label>
                            <input type="text" name="line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo esc_attr( er_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( er_format_localized_price( $tax_item_total ) ); ?>" class="line_tax er_input_price" data-total_tax="<?php echo esc_attr( er_format_localized_price( $tax_item_total ) ); ?>" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>"/>
                        </div>
                    </div>
                </div>
                <div class="refund" style="display: none;">
                    <input type="text" name="refund_line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo esc_attr( er_format_localized_price( 0 ) ); ?>" class="refund_line_tax er_input_price" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>"/>
                </div>
            </td>
			<?php
		}
	}
	?>
    <td class="er-receipt-edit-line-item">
		<?php if ( $object->is_editable() ) : ?>
            <div class="er-receipt-edit-line-item-actions">
                <a class="edit-receipt-item tips" href="#" data-tip="<?php esc_attr_e( 'Edit item', 'easyReservations' ); ?>"></a><a class="delete-receipt-item tips" href="#" data-tip="<?php esc_attr_e( 'Delete item', 'easyReservations' ); ?>"></a>
            </div>
		<?php endif; ?>
    </td>
</tr>
