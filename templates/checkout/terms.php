<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/checkout/terms.php.
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

if ( apply_filters( 'easyreservations_checkout_show_terms', true ) && function_exists( 'er_terms_and_conditions_checkbox_enabled' ) ) {
	do_action( 'easyreservations_checkout_before_terms_and_conditions' );

	?>
    <div class="easyreservations-terms-and-conditions-wrapper">
		<?php
		/**
		 * Terms and conditions hook used to inject content.
		 *
		 * @hooked er_checkout_privacy_policy_text() Shows custom privacy policy text. Priority 20.
		 * @hooked er_terms_and_conditions_page_content() Shows t&c page content. Priority 30.
		 */
		do_action( 'easyreservations_checkout_terms_and_conditions' );
		?>

		<?php if ( er_terms_and_conditions_checkbox_enabled() ) : ?>
            <p class="form-row validate-required">
                <label class="easyreservations-form__label easyreservations-form__label-for-checkbox checkbox">
                    <input type="checkbox" class="easyreservations-form__input easyreservations-form__input-checkbox input-checkbox" name="terms" <?php checked( apply_filters( 'easyreservations_terms_is_checked_default', isset( $_POST['terms'] ) ), true ); // WPCS: input var ok, csrf ok. ?> id="terms"/>
                    <span class="easyreservations-terms-and-conditions-checkbox-text"><?php er_terms_and_conditions_checkbox_text(); ?></span>&nbsp;<span class="required">*</span>
                </label>
                <input type="hidden" name="terms-field" value="1"/>
            </p>
		<?php endif; ?>
    </div>
	<?php

	do_action( 'easyreservations_checkout_after_terms_and_conditions' );
}
