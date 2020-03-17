<?php
/**
 * Login form
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/global/form-login.php.
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
	exit; // Exit if accessed directly.
}

if ( is_user_logged_in() ) {
	return;
}

?>
<form class="easyreservations-form easyreservations-form-login login" method="post" <?php echo ( $hidden ) ? 'style="display:none;"' : ''; ?>>

	<?php do_action( 'easyreservations_login_form_start' ); ?>

	<?php echo ( $message ) ? wpautop( wptexturize( $message ) ) : ''; // @codingStandardsIgnoreLine ?>

    <p class="form-row form-row-first">
        <label for="username"><?php esc_html_e( 'Username or email', 'easyReservations' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text" name="username" id="username" autocomplete="username"/>
    </p>
    <p class="form-row form-row-last">
        <label for="password"><?php esc_html_e( 'Password', 'easyReservations' ); ?>&nbsp;<span class="required">*</span></label>
        <input class="input-text" type="password" name="password" id="password" autocomplete="current-password"/>
    </p>
    <div class="clear"></div>

	<?php do_action( 'easyreservations_login_form' ); ?>

    <p class="form-row">
        <label class="easyreservations-form__label easyreservations-form__label-for-checkbox easyreservations-form-login__rememberme">
            <input class="easyreservations-form__input easyreservations-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever"/>
            <span><?php esc_html_e( 'Remember me', 'easyReservations' ); ?></span>
        </label>
		<?php wp_nonce_field( 'easyreservations-login', 'easyreservations-login-nonce' ); ?>
        <input type="hidden" name="redirect" value="<?php echo esc_url( $redirect ) ?>"/>
        <button type="submit" class="easyreservations-button button easyreservations-form-login__submit" name="login" value="<?php esc_attr_e( 'Login', 'easyReservations' ); ?>"><?php esc_html_e( 'Login', 'easyReservations' ); ?></button>
    </p>
    <p class="lost_password">
        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'easyReservations' ); ?></a>
    </p>

    <div class="clear"></div>

	<?php do_action( 'easyreservations_login_form_end' ); ?>

</form>
