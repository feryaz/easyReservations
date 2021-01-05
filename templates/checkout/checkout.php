<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/checkout/checkout.php.
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

do_action( 'easyreservations_before_checkout_form' );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! er_is_registration_enabled() && er_is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'easyreservations_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'easyReservations' ) ) );

	return;
}

?>
<form name="checkout" method="post" class="checkout easyreservations-checkout" rel="js-easy-form"
    action="<?php echo esc_url( er_get_checkout_url() ); ?>" enctype="multipart/form-data">

    <div class="easy-ui easyreservations-checkout-form">
		<?php do_action( 'easyreservations_checkout_form' ); ?>
    </div>

	<?php do_action( 'easyreservations_checkout_before_order_review_heading' ); ?>

    <h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'easyReservations' ); ?></h3>

	<?php do_action( 'easyreservations_checkout_before_order_review' ); ?>

    <div id="order_review" class="easyreservations-checkout-review-order">
		<?php do_action( 'easyreservations_checkout_order_review' ); ?>
    </div>

	<?php do_action( 'easyreservations_checkout_after_order_review' ); ?>

    <div id="order_submit">

		<?php do_action( 'easyreservations_checkout_before_order_submit' ); ?>

        <div class="form-row place-order">

			<?php do_action( 'easyreservations_checkout_order_submit' ); ?>

        </div>

		<?php do_action( 'easyreservations_checkout_after_order_submit' ); ?>

    </div>

	<?php do_action( 'easyreservations_checkout_after' ); ?>

    <input type="hidden" name="easy_form_id" value="checkout">
	<?php wp_nonce_field( 'easyreservations-process-checkout', 'easyreservations-process-checkout-nonce' ); ?>
	<?php wp_nonce_field( 'easyreservations-form', 'easy-form-nonce' ); ?>
</form>