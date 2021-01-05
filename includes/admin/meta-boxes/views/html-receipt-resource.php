<?php
/**
 * Shows an reservation item
 */

defined( 'ABSPATH' ) || exit;

$resource_id   = $item->get_resource_id();
$resource      = ER()->resources()->get( $resource_id );
$resource_link = $resource ? admin_url( 'admin.php?page=resource&resource=' . $resource_id . '&action=edit' ) : '';
$thumbnail     = $resource ? apply_filters( 'easyreservations_admin_order_item_thumbnail', $resource->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '';
$row_class     = apply_filters( 'easyreservations_admin_html_order_item_class', ! empty( $class ) ? $class : '', $item, $object );
?>
<tr class="item <?php echo esc_attr( $row_class ); ?>" data-receipt_item_id="<?php echo esc_attr( $item_id ); ?>">
    <td class="thumb">
		<?php echo '<div class="er-receipt-item-thumbnail">' . wp_kses_post( $thumbnail ) . '</div>'; ?>
    </td>
    <td class="name" data-sort-value="<?php echo esc_attr( $item->get_name() ); ?>">
		<?php
		echo '<a href="' . esc_url( $resource_link ) . '" class="er-receipt-item-name">' . wp_kses_post( $item->get_name() ) . '</a>';
		?>
        <input type="hidden" class="receipt_item_id" name="receipt_item_id[]" value="<?php echo esc_attr( $item_id ); ?>"/>

		<?php do_action( 'easyreservations_before_order_itemmeta', $item_id, $item, $resource ); ?>
		<?php require 'html-receipt-item-meta.php'; ?>
		<?php do_action( 'easyreservations_after_order_itemmeta', $item_id, $item, $resource ); ?>
    </td>

	<?php do_action( 'easyreservations_admin_order_item_values', $resource, $item, absint( $item_id ) ); ?>

    <td class="item_cost" width="1%" data-sort-value="<?php echo esc_attr( $object->get_item_subtotal( $item, false, true ) ); ?>">
        <div class="view">
			<?php
			echo er_price( $object->get_item_total( $item, false, true ), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
        </div>
    </td>
    <td class="line_cost" width="1%" data-sort-value="<?php echo esc_attr( $item->get_total() ); ?>">
        <div class="view">
			<?php
			echo er_price( $item->get_total(), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( $item->get_subtotal() !== $item->get_total() ) {
				/* translators: %s: discount amount */
				echo '<span class="er-receipt-item-discount">' . sprintf( esc_html__( '%s discount', 'easyReservations' ), er_price( er_format_decimal( $item->get_subtotal() - $item->get_total(), '' ), true ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
	$tax_data = er_tax_enabled() ? $item->get_taxes() : false;

	if ( $tax_data ) {
		foreach ( $receipt_taxes as $tax_item ) {
			$tax_item_id       = $tax_item->get_rate_id();
			$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
			$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';
			?>
            <td class="line_tax" width="1%">
                <div class="view">
					<?php
					if ( '' !== $tax_item_total ) {
						echo er_price( er_round_tax_total( $tax_item_total ), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						echo '&ndash;';
					}

					$refunded = $object->get_type() === 'easy_order' ? $object->get_tax_refunded_for_item( $item_id, $tax_item_id ) : false;

					if ( $refunded ) {
						echo '<small class="refunded">-' . er_price( $refunded, true ) . '</small>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
    <td class="er-receipt-edit-line-item" width="1%">
        <div class="er-receipt-edit-line-item-actions">
			<?php if ( $object->is_editable() ) : ?>
                <a class="edit-receipt-item tips" href="#" data-tip="<?php esc_attr_e( 'Edit item', 'easyReservations' ); ?>"></a><a class="recalculate-receipt-item tips" href="#" data-item-id="<?php echo esc_attr( $item_id ); ?>" data-tip="<?php esc_attr_e( 'Recalculate', 'easyReservations' ); ?>"></a>
			<?php endif; ?>
        </div>
    </td>
</tr>
