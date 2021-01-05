<?php
/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/checkout/form-address.php.
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
<div class="easyreservations-address-fields">
    <h3><?php esc_html_e( 'Address details', 'easyReservations' ); ?></h3>

	<?php do_action( 'easyreservations_before_checkout_address_form', $checkout ); ?>

    <div class="easyreservations-address-fields__field-wrapper">
		<?php
		$fields = $checkout->get_checkout_fields( 'address' );

		foreach ( $fields as $key => $field ) {
			easyreservations_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
    </div>

	<?php do_action( 'easyreservations_after_checkout_address_form', $checkout ); ?>
</div>

<?php if ( ! is_user_logged_in() && er_is_registration_enabled() ) : ?>
    <div class="easyreservations-account-fields">
		<?php if ( ! er_is_registration_required() ) : ?>

            <p class="form-row form-row-wide create-account">
                <label class="easyreservations-form__label easyreservations-form__label-for-checkbox checkbox">
                    <input class="easyreservations-form__input easyreservations-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'easyreservations_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1"/>
                    <span><?php esc_html_e( 'Create an account?', 'easyReservations' ); ?></span>
                </label>
            </p>

		<?php endif; ?>

		<?php do_action( 'easyreservations_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

            <div class="create-account">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
					<?php easyreservations_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
                <div class="clear"></div>
            </div>

		<?php endif; ?>

		<?php do_action( 'easyreservations_after_checkout_registration_form', $checkout ); ?>
    </div>
<?php endif; ?>
