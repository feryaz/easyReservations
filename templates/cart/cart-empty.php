<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/cart/cart-empty.php.
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

do_action( 'easyreservations_cart_is_empty' );

?>
<p class="cart-empty easyreservations-info">
	<?php echo wp_kses_post( apply_filters( 'easyreservations_empty_cart_message', __( 'Your cart is currently empty.', 'easyReservations' ) ) ); ?>
</p>