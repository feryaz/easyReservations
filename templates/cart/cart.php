<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/cart/cart.php.
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

do_action( 'easyreservations_before_cart' ); ?>

<form class="easyreservations-cart-form" action="<?php echo esc_url( er_get_cart_url() ); ?>" method="post">
	<?php do_action( 'easyreservations_before_cart_table' ); ?>

    <table class="shop_table shop_table_responsive cart easyreservations-cart-form__contents" cellspacing="0">
        <thead>
        <tr>
            <th class="remove">&nbsp;</th>
            <th class="resource-thumbnail">&nbsp;</th>
            <th class="name"><?php esc_html_e( 'Item', 'easyReservations' ); ?></th>
            <th class="amount"><?php esc_html_e( 'Subtotal', 'easyReservations' ); ?></th>
        </tr>
        </thead>
        <tbody>
		<?php do_action( 'easyreservations_before_cart_contents' ); ?>

		<?php

		foreach ( ER()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$resource    = false;
			$reservation = false;

			if ( is_integer( $cart_item ) ) {
				$receipt_item = ER()->cart->get_order()->find_reservation( $cart_item );
			} else {
				$receipt_item = ER()->cart->get_order()->find_custom( $cart_item );
			}

			if ( ! $receipt_item ) {
				continue;
			}

			if ( method_exists( $receipt_item, 'get_resource' ) ) {
				$resource = $receipt_item->get_resource();
			}

			if ( method_exists( $receipt_item, 'get_reservation_id' ) ) {
				$reservation = ER()->reservation_manager()->get( $receipt_item->get_reservation_id() );
			}

			?>
            <tr class="easyreservations-cart-form__cart-item <?php echo esc_attr( $receipt_item->get_type() ); ?>">

                <td class="entry-remove">
					<?php

					// @codingStandardsIgnoreLine
					echo apply_filters( 'easyreservations_cart_item_remove_link', sprintf(
						'<a href="%s" class="remove" aria-label="%s" data-cart_item_key="%s">&times;</a>',
						esc_url( er_get_cart_remove_url( $cart_item_key ) ),
						__( 'Remove this item', 'easyreservations' ),
						esc_attr( $cart_item_key )
					), $cart_item_key );
					?>
                </td>

                <td class="entry-thumbnail">
					<?php
					if ( $resource ) {
						$thumbnail = apply_filters( 'easyreservations_cart_item_thumbnail', $resource->get_image(), $receipt_item );

						echo $thumbnail; // PHPCS: XSS ok.
					}
					?>
                </td>

                <td class="entry-name" data-title="<?php esc_attr_e( 'Title', 'easyReservations' ); ?>">
					<?php
					echo wp_kses_post( apply_filters( 'easyreservations_cart_item_name', $reservation ? $reservation->get_item_label() : $receipt_item->get_name(), $receipt_item ) . '&nbsp;' );

					do_action( 'easyreservations_after_cart_item_name', $receipt_item );
					?>
                </td>

                <td class="amount" data-title="<?php esc_attr_e( 'Subtotal', 'easyReservations' ); ?>">
					<?php
					echo apply_filters( 'easyreservations_cart_item_price', er_get_display_price( $receipt_item ), $receipt_item ); // PHPCS: XSS ok.
					?>
                </td>
            </tr>
			<?php
		}
		?>

		<?php do_action( 'easyreservations_cart_contents' ); ?>

		<?php if ( ( function_exists( 'er_coupons_enabled' ) && er_coupons_enabled() ) || has_action( 'easyreservations_cart_actions' ) ) { ?>
            <tr>
                <td colspan="4" class="actions">

                    <div class="coupon">
						<?php wp_nonce_field( 'easyreservations-coupon', 'easyreservations-coupon-nonce' ); ?>

                        <label for="coupon_code"><?php esc_html_e( 'Coupon:', 'easyReservations' ); ?></label>
                        <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'easyReservations' ); ?>"/>
                        <button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'easyReservations' ); ?>">
							<?php esc_attr_e( 'Apply coupon', 'easyReservations' ); ?>
                        </button>
						<?php do_action( 'easyreservations_cart_coupon' ); ?>
                    </div>

					<?php do_action( 'easyreservations_cart_actions' ); ?>

                </td>
            </tr>
		<?php } ?>

		<?php wp_nonce_field( 'easyreservations-cart', 'easyreservations-cart-nonce' ); ?>

		<?php do_action( 'easyreservations_after_cart_contents' ); ?>
        </tbody>
    </table>
	<?php do_action( 'easyreservations_after_cart_table' ); ?>
</form>

<div class="cart-totals">
	<?php do_action( 'easyreservations_cart_totals' ); ?>
</div>

<?php do_action( 'easyreservations_after_cart' ); ?>
