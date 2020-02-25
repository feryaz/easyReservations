<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/form/price.php.
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
<?php do_action( 'easyreservations_form_before_price' ); ?>
<div class="easy-price">
    <?php esc_html_e('Total price', 'easyReservations'); ?>
    <div class="easy-price-display"></div>
</div>
<?php do_action( 'easyreservations_form_after_price' ); ?>
