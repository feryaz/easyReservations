<?php
/**
 * Customer new account email
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/emails/plain/customer-new-account.php.
 *
 * HOWEVER, on occasion easyReservations will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package easyReservations/Templates/Emails/Plain
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'easyReservations' ), esc_html( $user_login ) ) . "\n\n";
/* translators: %1$s: Site title, %2$s: Username, %3$s: My account link */
echo sprintf( esc_html__( 'Thanks for creating an account on %1$s. Your username is %2$s. You can access your account area to view orders, change your password, and more at: %3$s', 'easyReservations' ), esc_html( $blogname ), '<strong>' . esc_html( $user_login ) . '</strong>', esc_html( er_get_page_permalink( 'myaccount' ) ) ) . "\n\n";

if ( 'yes' === get_option( 'reservations_registration_generate_password' ) && $password_generated ) {
	/* translators: %s: Auto generated password */
	echo sprintf( esc_html__( 'Your password has been automatically generated: %s.', 'easyReservations' ), esc_html( $user_pass ) ) . "\n\n";
}

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'easyreservations_email_footer_text', get_option( 'reservations_email_footer_text' ) ) );
