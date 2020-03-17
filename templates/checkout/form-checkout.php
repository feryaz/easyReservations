<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/checkout/form-checkout.php.
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<?php if ( $checkout->get_checkout_fields() ) : ?>

	<?php do_action( 'easyreservations_checkout_before_customer_details' ); ?>

    <div class="col2-set" id="customer_details">
        <div class="col-1">
			<?php do_action( 'easyreservations_checkout_address' ); ?>
        </div>

        <div class="col-2">
			<?php do_action( 'easyreservations_checkout_additional' ); ?>
        </div>
    </div>

	<?php do_action( 'easyreservations_checkout_after_customer_details' ); ?>

<?php endif; ?>