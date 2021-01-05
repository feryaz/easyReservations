<?php
/**
 * Checkout additional information form
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
<div class="easyreservations-additional-fields">
    <h3><?php esc_html_e( 'Additional information', 'easyReservations' ); ?></h3>

	<?php do_action( 'easyreservations_before_order_notes', $checkout ); ?>

	<?php if ( apply_filters( 'easyreservations_enable_order_notes_field', 'yes' === get_option( 'reservations_enable_order_comments', 'yes' ) ) ) : ?>

        <div class="easyreservations-additional-fields__field-wrapper">
			<?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
				<?php easyreservations_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
			<?php endforeach; ?>
        </div>

	<?php endif; ?>

	<?php do_action( 'easyreservations_after_order_notes', $checkout ); ?>
</div>