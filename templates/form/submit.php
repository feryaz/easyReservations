<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/form/submit.php.
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
<?php do_action( 'easyreservations_before_add_to_cart_button' ); ?>
    <button type="submit" name="add-to-cart" class="button alt add-to-cart-button"><?php echo esc_html( $button_text ); ?></button>
<?php do_action( 'easyreservations_after_add_to_cart_button' ); ?>