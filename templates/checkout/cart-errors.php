<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/checkout/cart-errors.php.
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
?>

<p><?php esc_html_e( 'There are some issues with the items in your cart. Please go back to the cart page and resolve these issues before checking out.', 'easyReservations' ); ?></p>

<?php do_action( 'easyreservations_cart_has_errors' ); ?>

<p>
    <a class="button er-backward" href="<?php echo esc_url( er_get_cart_url() ); ?>"><?php esc_html_e( 'Return to cart', 'easyReservations' ); ?></a>
</p>
