<?php
/**
 * Mini-cart
 *
 * Contains the markup for the mini-cart, used by the cart widget.
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/cart/mini-cart.php.
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

do_action( 'easyreservations_before_mini_cart' ); ?>

<?php if ( ! ER()->cart->is_empty() ) : ?>

	<ul class="easyreservations-mini-cart cart_list resource_list_widget <?php echo esc_attr( $args['list_class'] ); ?>">
		<?php
		do_action( 'easyreservations_before_mini_cart_contents' );

		foreach ( ER()->cart->get_cart() as $cart_item_key => $cart_item ) {
			//Integer cart items are reservations
			if ( is_integer( $cart_item ) ) {
				$receipt_item = ER()->cart->get_order()->find_reservation( $cart_item );
			} else {
				$receipt_item = ER()->cart->get_order()->find_custom( $cart_item );
			}

			if ( ! $receipt_item ) {
				continue;
			}

			$resource = false;

			if ( method_exists( $receipt_item, 'get_resource' ) ) {
				$resource = $receipt_item->get_resource();
			}

            ?>
            <li class="easyreservations-mini-cart-item <?php echo esc_attr( apply_filters( 'easyreservations_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>">
                <?php
                echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'easyreservations_cart_item_remove_link',
                    sprintf(
                        '<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-cart_item_key="%s">&times;</a>',
                        esc_url( er_get_cart_remove_url( $cart_item_key ) ),
                        __( 'Remove this item', 'easyreservations' ),
                        esc_attr( $cart_item_key )
                    ),
                    $cart_item_key
                );
                ?>
                <?php if ( $resource ) : ?>
	                <?php echo apply_filters( 'easyreservations_cart_item_thumbnail', $resource->get_image(), $receipt_item ); // PHPCS: XSS ok ?>
		            <a href="<?php echo esc_url( $resource->get_permalink() ); ?>">
		              <?php echo wp_kses_post( apply_filters( 'easyreservations_cart_item_name', $receipt_item->get_name(), $receipt_item ) . '&nbsp;' ); ?>
		            </a>
                <?php else: ?>
                    <div>
	                    <?php echo wp_kses_post( apply_filters( 'easyreservations_cart_item_name', $receipt_item->get_name(), $receipt_item ) . '&nbsp;' ); ?>
                    </div>
                <?php endif; ?>
                <?php echo apply_filters( 'easyreservations_widget_cart_item_price', '<span class="price">' . er_get_display_price( $receipt_item ) . '</span>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </li>
            <?php
		}

		do_action( 'easyreservations_mini_cart_contents' );
		?>
	</ul>

	<p class="easyreservations-mini-cart__total total">
		<?php
		/**
		 * Hook: easyreservations_widget_shopping_cart_total.
		 *
		 * @hooked easyreservations_widget_shopping_cart_subtotal - 10
		 */
		do_action( 'easyreservations_widget_shopping_cart_total' );
		?>
	</p>

	<?php do_action( 'easyreservations_widget_shopping_cart_before_buttons' ); ?>

	<p class="easyreservations-mini-cart__buttons buttons"><?php do_action( 'easyreservations_widget_shopping_cart_buttons' ); ?></p>

	<?php do_action( 'easyreservations_widget_shopping_cart_after_buttons' ); ?>

<?php else : ?>

	<p class="easyreservations-mini-cart__empty-message"><?php esc_html_e( 'No items in the cart.', 'easyReservations' ); ?></p>

<?php endif; ?>

<?php do_action( 'easyreservations_after_mini_cart' ); ?>
