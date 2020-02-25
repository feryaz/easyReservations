<?php
/**
 * Email Order Items
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/emails/email-order-items.php.
 *
 * HOWEVER, on occasion easyReservations will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package easyReservations/Templates/Emails
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

$text_align  = is_rtl() ? 'right' : 'left';
$margin_side = is_rtl() ? 'left' : 'right';

foreach ( $items as $item_id => $item ) :
    $reservation    = false;
    $resource       = false;
    $sku            = '';
    $purchase_note  = '';
    $image          = '';

    if ( ! apply_filters( 'easyreservations_order_item_visible', true, $item ) ) {
		continue;
	}

    if ( method_exists( $item, 'get_resource_id' ) ) {
        $resource = $item->get_resource();
    }

    if ( method_exists( $item, 'get_reservation_id' ) ) {
        $reservation = $item->get_reservation();
    }

    if ( is_object( $resource ) ) {
		$sku           = $resource->get_sku();
		$purchase_note = $resource->get_purchase_note();
		$image         = $resource->get_image( $image_size );
	}

	?>
	<tr class="<?php echo esc_attr( apply_filters( 'easyreservations_order_item_class', 'receipt_item', $item, $order ) ); ?>">
		<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
		<?php

		// Show title/image etc.
		if ( $show_image ) {
			echo wp_kses_post( apply_filters( 'easyreservations_order_item_thumbnail', $image, $item ) );
		}

		// Resource name.
		echo wp_kses_post( apply_filters( 'easyreservations_order_item_name', $reservation ? $reservation->get_item_label() : $item->get_name(), $item, false ) );

		// SKU.
		if ( $show_sku && $sku ) {
			echo wp_kses_post( ' (#' . $sku . ')' );
		}

        // allow other plugins to add additional resource information here.
        do_action( 'easyreservations_order_item_meta_start', $item_id, $item, $order, $plain_text );

        er_display_meta(
			$item->get_formatted_meta_data(),
			array(
				'label_before' => '<strong class="er-item-meta-label" style="float: ' . esc_attr( $text_align ) . '; margin-' . esc_attr( $margin_side ) . ': .25em; clear: both">',
			)
		);

		// allow other plugins to add additional resource information here.
		do_action( 'easyreservations_order_item_meta_end', $item_id, $item, $order, $plain_text );

		?>
		</td>
		<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
			<?php echo wp_kses_post( $order->get_formatted_item_subtotal( $item ) ); ?>
		</td>
	</tr>
	<?php

	if ( $show_purchase_note && $purchase_note ) {
		?>
		<tr>
			<td colspan="3" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
				<?php
				echo wp_kses_post( wpautop( do_shortcode( $purchase_note ) ) );
				?>
			</td>
		</tr>
		<?php
	}
	?>

<?php endforeach; ?>
