<?php
/**
 * Order items HTML for meta box.
 *
 * @package easyReservations/Admin
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;

$line_items_reservation = $object->get_items( 'reservation' );
$line_items_resource    = $object->get_items( 'resource' );
$line_items_fee         = $object->get_items( 'fee' );
$receipt_taxes          = array();

if ( er_tax_enabled() ) {
	$receipt_taxes = $object->get_taxes();
}
?>
<div class="easyreservations_receipt_items_wrapper er-receipt-items-editable">
    <table cellpadding="0" cellspacing="0" class="easyreservations_receipt_items">
        <thead>
        <tr>
            <th class="item sortable" colspan="2" data-sort="string-ins"><?php esc_html_e( 'Item', 'easyReservations' ); ?></th>
			<?php do_action( 'easyreservations_admin_receipt_item_headers', $object ); ?>
            <th class="item_cost sortable" data-sort="float"><?php esc_html_e( 'Cost', 'easyReservations' ); ?></th>
            <th class="line_cost sortable" data-sort="float"><?php esc_html_e( 'Total', 'easyReservations' ); ?></th>
			<?php
			if ( ! empty( $receipt_taxes ) ) :
				foreach ( $receipt_taxes as $tax_id => $tax_item ) :
					$column_label = ! empty( $tax_item->get_name() ) ? $tax_item->get_name() : __( 'Tax', 'easyReservations' );
					?>
                    <th class="line_tax">
						<?php echo esc_attr( $column_label ); ?>
                        <input type="hidden" class="receipt-tax-id" name="receipt_taxes[<?php echo esc_attr( $tax_id ); ?>]" value="<?php echo esc_attr( $tax_item->get_rate_id() ); ?>">
						<?php if ( $object->is_editable() ) : ?>
                            <a class="delete-receipt-tax" href="#" data-rate_id="<?php echo esc_attr( $tax_id ); ?>"></a>
						<?php endif; ?>
                    </th>
				<?php
				endforeach;
			endif;
			?>
            <th class="er-receipt-edit-line-item" width="1%">&nbsp;</th>
        </tr>
        </thead>
        <tbody id="receipt_reservation_line_items">
		<?php
		foreach ( $line_items_reservation as $item_id => $item ) {
			do_action( 'easyreservations_before_receipt_item_reservation_html', $item_id, $item, $object );

			include __DIR__ . '/html-receipt-reservation.php';

			do_action( 'easyreservations_after_receipt_item_reservation_html', $item_id, $item, $object );
		}

		do_action( 'easyreservations_admin_' . $object->get_type() . '_items_after_reservation_line_items', $object->get_id() );
		?>
        </tbody>
        <tbody id="receipt_resource_line_items">
		<?php
		foreach ( $line_items_resource as $item_id => $item ) {
			include __DIR__ . '/html-receipt-resource.php';
		}
		do_action( 'easyreservations_admin_' . $object->get_type() . '_items_after_resources', $object->get_id() );
		?>
        </tbody>
        <tbody id="receipt_fee_line_items">
		<?php
		foreach ( $line_items_fee as $item_id => $item ) {
			include __DIR__ . '/html-receipt-fee.php';
		}
		do_action( 'easyreservations_admin_' . $object->get_type() . '_items_after_fees', $object->get_id() );
		?>
        </tbody>
        <tbody id="receipt_refunds">
		<?php
		$refunds = $object->get_type() === 'easy_order' ? $object->get_refunds() : false;

		if ( $refunds ) {
			foreach ( $refunds as $refund ) {
				include __DIR__ . '/html-order-refund.php';
			}
			do_action( 'easyreservations_admin_' . $object->get_type() . '_items_after_refunds', $object->get_id() );
		}
		?>
        </tbody>
    </table>
</div>
<div class="er-receipt-data-row er-receipt-totals-items er-receipt-items-editable">
	<?php
	$coupons = $object->get_items( 'coupon' );
	if ( $coupons ) :
		?>
        <div class="er-used-coupons">
            <ul class="er_coupon_list">
                <li><strong><?php esc_html_e( 'Coupon(s)', 'easyReservations' ); ?></strong></li>
				<?php
				foreach ( $coupons as $item_id => $item ) :
					$class = $object->is_editable() ? 'code editable' : 'code';
					?>
                    <li class="<?php echo esc_attr( $class ); ?>">
                        <span class="tips" data-tip="<?php echo esc_attr( er_price( $item->get_discount(), true ) ); ?>">
                            <span><?php echo esc_html( $item->get_code() ); ?></span>
                        </span>
						<?php if ( $object->is_editable() ) : ?>
                            <a class="remove-coupon" href="javascript:void(0)" aria-label="Remove" data-code="<?php echo esc_attr( $item->get_code() ); ?>"></a>
						<?php endif; ?>
                    </li>
				<?php endforeach; ?>
            </ul>
        </div>
	<?php endif; ?>
    <table class="er-receipt-totals">
		<?php if ( $object->get_type() === 'easy_order' && 0 < $object->get_total_discount() ) : ?>
            <tr>
                <td class="label"><?php esc_html_e( 'Discount:', 'easyReservations' ); ?></td>
                <td width="1%"></td>
                <td class="total">
					<?php echo er_price( $object->get_total_discount(), true ); // WPCS: XSS ok. ?>
                </td>
            </tr>
		<?php endif; ?>

		<?php do_action( 'easyreservations_admin_' . $object->get_type() . '_totals_after_discount', $object->get_id() ); ?>

		<?php if ( er_tax_enabled() ) : ?>
			<?php foreach ( $object->get_tax_totals() as $code => $tax_total ) : ?>
                <tr>
                    <td class="label"><?php echo esc_html( $tax_total->label ); ?>:</td>
                    <td width="1%"></td>
                    <td class="total">
						<?php
						$refunded = $object->get_type() === 'easy_order' ? $object->get_total_tax_refunded_by_rate_id( $tax_total->rate_id ) : 0;

						if ( $refunded > 0 ) {
							echo '<del>' . wp_strip_all_tags( $tax_total->formatted_amount ) . '</del> <ins>' . er_price( ER_Tax::round( $tax_total->amount, er_get_price_decimals() ) - ER_Tax::round( $refunded, er_get_price_decimals() ), true ) . '</ins>'; // WPCS: XSS ok.
						} else {
							echo wp_kses_post( $tax_total->formatted_amount );
						}
						?>
                    </td>
                </tr>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php do_action( 'easyreservations_admin_' . $object->get_type() . '_totals_after_tax', $object->get_id() ); ?>

        <tr>
            <td class="label"><?php esc_html_e( 'Total', 'easyReservations' ); ?>:</td>
            <td width="1%"></td>
            <td class="total">
			    <?php echo $object->get_formatted_total(); // WPCS: XSS ok. ?>
            </td>
        </tr>

    </table>

    <div class="clear"></div>

	<?php if ( $object->get_type() === 'easy_order' && ! empty( $object->get_paid() ) ) : ?>
        <table class="er-receipt-totals" style="border-top: 1px solid #999; margin-top:12px; padding-top:12px">
            <?php if ( $object->get_paid() > 0 ) : ?>
                <tr >
                    <td class="label"><?php esc_html_e( 'Paid', 'easyReservations' ); ?>:</td>
                    <td width="1%"></td>
                    <td class="total">
                        <?php echo er_price( $object->get_paid(), true ); // WPCS: XSS ok. ?>
                    </td>
                </tr>

                <tr>
                    <td colspan="3">
                        <span class="description">
                        <?php
                            $date_paid = $object->get_date_paid() ? $object->get_date_paid()->date_i18n( get_option( 'date_format' ) ) : __( 'Unknown date', 'easyReservations' );

                            if ( $object->get_payment_method_title() ) {
                                /* translators: 1: payment date. 2: payment method */
                                echo esc_html( sprintf( __( '%1$s via %2$s', 'easyReservations' ), $date_paid, $object->get_payment_method_title() ) );
                            } else {
                                echo esc_html( $date_paid );
                            }
                        ?>
                        </span>
                    </td>
                </tr>

                <tr>
                    <td class="label"><?php esc_html_e( 'Due', 'easyReservations' ); ?>:</td>
                    <td width="1%"></td>
                    <td class="total">
			            <?php echo er_price( $object->get_amount_due(), true ); // WPCS: XSS ok. ?>
                    </td>
                </tr>

            <?php endif; ?>

            <?php do_action( 'easyreservations_admin_' . $object->get_type() . '_totals_after_total', $object->get_id() ); ?>

            <?php if ( $object->get_total_refunded() ) : ?>
                <tr>
                    <td class="label refunded-total"><?php esc_html_e( 'Refunded', 'easyReservations' ); ?>:</td>
                    <td width="1%"></td>
                    <td class="total refunded-total">-<?php echo er_price( $object->get_total_refunded(), true ); // WPCS: XSS ok. ?></td>
                </tr>

                <tr>
                    <td class="label label-highlight"><?php esc_html_e( 'Net Total', 'easyReservations' ); ?>:</td>
                    <td width="1%"></td>
                    <td class="total">
			            <?php echo er_price( $object->get_total() - $object->get_total_refunded(), true ); // WPCS: XSS ok. ?>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if ( $amount_to_pay = $object->get_meta( 'amount_to_pay' ) ) : ?>
                <tr>
                    <td class="label"><?php esc_html_e( 'Deposit to pay', 'easyReservations' ); ?>:</td>
                    <td width="1%"></td>
                    <td class="total">
                        <?php echo er_price( $amount_to_pay, true ); // WPCS: XSS ok. ?>
                    </td>
                </tr>
            <?php endif; ?>

            <?php do_action( 'easyreservations_admin_' . $object->get_type() . '_totals_after_refunded', $object->get_id() ); ?>

        </table>
	<?php endif; ?>

    <div class="clear"></div>
</div>
<div class="er-receipt-data-row er-receipt-bulk-actions er-receipt-data-row-toggle">
    <p class="add-items">
		<?php if ( $object->is_editable() ) : ?>
            <button type="button" class="button add-line-item"><?php esc_html_e( 'Add item(s)', 'easyReservations' ); ?></button>
			<?php if ( $object->get_type() === 'easy_order' && ( function_exists( 'er_coupons_enabled' ) && er_coupons_enabled() ) ) : ?>
                <button type="button" class="button add-coupon"><?php esc_html_e( 'Apply coupon', 'easyReservations' ); ?></button>
			<?php endif; ?>
		<?php else : ?>
            <span class="description"><?php er_get_help( __( 'To edit this object change the status back to "Pending"', 'easyReservations' ) ); ?><?php esc_html_e( 'This object is no longer editable.', 'easyReservations' ); ?></span>
		<?php endif; ?>
		<?php if ( $object->get_type() === 'easy_order' && ( 0 < $object->get_total() - $object->get_total_refunded() || 0 < absint( $object->get_item_count() - $object->get_item_count_refunded() ) ) ) : ?>
            <button type="button" class="button refund-items"><?php esc_html_e( 'Refund', 'easyReservations' ); ?></button>
		<?php endif; ?>
		<?php
		// Allow adding custom buttons.
		do_action( 'easyreservations_receipt_item_add_action_buttons', $object );
		?>
		<?php if ( $object->is_editable() ) : ?>
            <button type="button" class="button button-primary calculate-action"><?php esc_html_e( 'Recalculate', 'easyReservations' ); ?></button>
		<?php endif; ?>
    </p>
</div>
<div class="er-receipt-data-row er-receipt-add-item er-receipt-data-row-toggle" style="display:none;">
	<?php if ( $object->get_type() === 'easy_order' ) : ?>
        <button type="button" class="button add-receipt-reservation"><?php esc_html_e( 'Add reservation', 'easyReservations' ); ?></button>
	<?php endif; ?>
    <button type="button" class="button add-receipt-fee"><?php esc_html_e( 'Add fee', 'easyReservations' ); ?></button>
	<?php if ( er_tax_enabled() ) : ?>
        <button type="button" class="button add-receipt-tax"><?php esc_html_e( 'Add tax', 'easyReservations' ); ?></button>
	<?php endif; ?>
	<?php
	// Allow adding custom buttons.
	do_action( 'easyreservations_receipt_item_add_line_buttons', $object );
	?>
    <button type="button" class="button cancel-action"><?php esc_html_e( 'Cancel', 'easyReservations' ); ?></button>
    <button type="button" class="button button-primary save-action"><?php esc_html_e( 'Save', 'easyReservations' ); ?></button>
</div>
<?php if ( $object->get_type() === 'easy_order' && ( 0 < $object->get_total() - $object->get_total_refunded() || 0 < absint( $object->get_item_count() - $object->get_item_count_refunded() ) ) ) : ?>
    <div class="er-receipt-data-row er-receipt-refund-items er-receipt-data-row-toggle" style="display: none;">
        <table class="er-order-totals">
            <tr>
                <td class="label">
                    <label for="restock_refunded_items"><?php esc_html_e( 'Cancel reservations', 'easyReservations' ); ?>:</label>
                </td>
                <td class="total">
                    <input type="checkbox" id="restock_refunded_items" name="restock_refunded_items" <?php checked( apply_filters( 'easyreservations_restock_refunded_items', true ) ); ?> />
                </td>
            </tr>
            <tr>
                <td class="label"><?php esc_html_e( 'Amount already refunded', 'easyReservations' ); ?>:</td>
                <td class="total">-<?php echo er_price( $object->get_total_refunded(), true ); // WPCS: XSS ok. ?></td>
            </tr>
            <tr>
                <td class="label"><?php esc_html_e( 'Total available to refund', 'easyReservations' ); ?>:</td>
                <td class="total"><?php echo er_price( $object->get_total() - $object->get_total_refunded(), true ); // WPCS: XSS ok. ?></td>
            </tr>
            <tr>
                <td class="label">
                    <label for="refund_amount">
						<?php er_get_help( __( 'Refund the line items above. This will show the total amount to be refunded', 'easyReservations' ) ); ?>
						<?php esc_html_e( 'Refund amount', 'easyReservations' ); ?>:
                    </label>
                </td>
                <td class="total">
                    <input type="text" id="refund_amount" name="refund_amount" class="er_input_price"
						<?php
						if ( er_tax_enabled() ) {
							// If taxes are enabled, using this refund amount can cause issues due to taxes not being refunded also.
							// The refunds should be added to the line items, not the receipt as a whole.
							echo 'readonly';
						}
						?>
                    />
                    <div class="clear"></div>
                </td>
            </tr>
            <tr>
                <td class="label">
                    <label for="refund_reason">
						<?php er_get_help( __( 'Note: the refund reason will be visible by the customer.', 'easyReservations' ) ); ?>
						<?php esc_html_e( 'Reason for refund (optional):', 'easyReservations' ); ?>
                    </label>
                </td>
                <td class="total">
                    <input type="text" id="refund_reason" name="refund_reason"/>
                    <div class="clear"></div>
                </td>
            </tr>
        </table>
        <div class="clear"></div>
        <div class="refund-actions">
			<?php

			$payment_gateway = er_get_payment_gateway_by_order( $object );
			$refund_amount   = '<span class="er-order-refund-amount">' . er_price( 0, true ) . '</span>';
			$gateway_name    = false !== $payment_gateway ? ( ! empty( $payment_gateway->method_title ) ? $payment_gateway->method_title : $payment_gateway->get_title() ) : __( 'Payment gateway', 'easyReservations' );

			if ( false !== $payment_gateway && $payment_gateway->can_refund_order( $object ) ) {
				/* translators: refund amount, gateway name */
				echo '<button type="button" class="button button-primary do-api-refund">' . sprintf( esc_html__( 'Refund %1$s via %2$s', 'easyReservations' ), wp_kses_post( $refund_amount ), esc_html( $gateway_name ) ) . '</button>';
			}
			?>
			<?php /* translators: refund amount  */ ?>
            <button type="button" class="button button-primary do-manual-refund tips" data-tip="<?php esc_attr_e( 'You will need to manually issue a refund through your payment gateway after using this.', 'easyReservations' ); ?>"><?php printf( esc_html__( 'Refund %s manually', 'easyReservations' ), wp_kses_post( $refund_amount ) ); ?></button>
            <button type="button" class="button cancel-action"><?php esc_html_e( 'Cancel', 'easyReservations' ); ?></button>
            <input type="hidden" id="refunded_amount" name="refunded_amount" value="<?php echo esc_attr( $object->get_total_refunded() ); ?>"/>
            <div class="clear"></div>
        </div>
    </div>
<?php endif; ?>

<?php if ( ! empty( $line_items_reservation ) ) {
	er_reservation_preview_template();
} ?>

<script type="text/template" id="tmpl-er-modal-add-products">
    <div class="er-backbone-modal">
        <div class="er-backbone-modal-content">
            <section class="er-backbone-modal-main" role="main">
                <header class="er-backbone-modal-header">
                    <h1><?php esc_html_e( 'Add products', 'easyReservations' ); ?></h1>
                    <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                        <span class="screen-reader-text">Close modal panel</span>
                    </button>
                </header>
                <article>
                    <form action="" method="post">
                        <table class="widefat">
                            <thead>
                            <tr>
                                <th><?php esc_html_e( 'Resource', 'easyReservations' ); ?></th>
                                <th><?php esc_html_e( 'Quantity', 'easyReservations' ); ?></th>
                            </tr>
                            </thead>
							<?php
							$row = '
									<td><select class="er-product-search" name="item_id" data-allow_clear="true" data-display_stock="true" data-placeholder="' . esc_attr__( 'Search for a product&hellip;', 'easyReservations' ) . '"></select></td>
									<td><input type="number" step="1" min="0" max="9999" autocomplete="off" name="item_qty" placeholder="1" size="4" class="quantity" /></td>';
							?>
                            <tbody data-row="<?php echo esc_attr( $row ); ?>">
                            <tr>
								<?php echo $row; // WPCS: XSS ok. ?>
                            </tr>
                            </tbody>
                        </table>
                    </form>
                </article>
                <footer>
                    <div class="inner">
                        <button id="btn-ok" class="button button-primary button-large"><?php esc_html_e( 'Add', 'easyReservations' ); ?></button>
                    </div>
                </footer>
            </section>
        </div>
    </div>
    <div class="er-backbone-modal-backdrop modal-close"></div>
</script>

<script type="text/template" id="tmpl-er-modal-add-tax">
    <div class="er-backbone-modal">
        <div class="er-backbone-modal-content">
            <section class="er-backbone-modal-main" role="main">
                <header class="er-backbone-modal-header">
                    <h1><?php esc_html_e( 'Add tax', 'easyReservations' ); ?></h1>
                    <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                        <span class="screen-reader-text">Close modal panel</span>
                    </button>
                </header>
                <article>
                    <form action="" method="post">
                        <table class="widefat">
                            <thead>
                            <tr>
                                <th>&nbsp;</th>
                                <th><?php esc_html_e( 'Rate name', 'easyReservations' ); ?></th>
                                <th><?php esc_html_e( 'Applies', 'easyReservations' ); ?></th>
                                <th><?php esc_html_e( 'Rate %', 'easyReservations' ); ?></th>
                            </tr>
                            </thead>
							<?php
							//$rates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}easyreservations_tax_rates ORDER BY tax_rate_name LIMIT 100" );
							$rates = ER_Tax::get_rates();
							foreach ( $rates as $rate ) {
								$applies = $rate['apply'];

								if ( is_integer( $applies ) ) {
									$resource = ER()->resources()->get( $applies );
									$applies  = $resource->get_title();
								} else {
									$applies = ucfirst( $applies );
								}

								echo '
									<tr>
										<td style="text-align:center"><input type="radio" id="add_order_tax_' . absint( $rate['id'] ) . '" name="add_order_tax" value="' . absint( $rate['id'] ) . '" /></td>
										<td><label for="add_order_tax_' . absint( $rate['id'] ) . '">' . esc_html( $rate['title'] ) . '</label></td>
										<td><label for="add_order_tax_' . absint( $rate['id'] ) . '">' . esc_html( $applies ) . '</label></td>
										<td>' . esc_html( $rate['flat'] ? $rate['rate'] : $rate['rate'] . '%' ) . '</td>
									</tr>
								'; // WPCS: XSS ok.
							}
							?>
                        </table>
                    </form>
                </article>
                <footer>
                    <div class="inner">
                        <button id="btn-ok" class="button button-primary button-large"><?php esc_html_e( 'Add', 'easyReservations' ); ?></button>
                    </div>
                </footer>
            </section>
        </div>
    </div>
    <div class="er-backbone-modal-backdrop modal-close"></div>
</script>
