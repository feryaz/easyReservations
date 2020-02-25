<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/checkout/submit.php.
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
?>
<?php echo apply_filters( 'easyreservations_order_submit_html', '<button type="submit" class="button alt" id="place_order" value="' . esc_attr( $button_text ) . '" data-value="' . esc_attr( $button_text ) . '">' . esc_html( $button_text ) . '</button>' ); // @codingStandardsIgnoreLine ?>