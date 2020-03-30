<?php
/**
 * This template can be overridden by copying it to yourtheme/easyreservations/form/header.php.
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

$form_class = 'easy-ui easy-ui-container border';

if ( $atts['inline'] ) {
	$form_class = 'easy-ui inline';
}

?>
<form id="easyreservations-form-<?php echo esc_attr( $form_hash ); ?>" rel="js-easy-form">
	<?php wp_nonce_field( 'easyreservations-form', 'easy-form-nonce' ); ?>
	<?php wp_nonce_field( 'easyreservations-process-reservation', 'easyreservations-process-reservation-nonce' ); ?>
    <input type="hidden" name="easy_form_id" value="<?php echo esc_attr( $form_id ); ?>">
    <input type="hidden" name="easy_form_hash" value="<?php echo esc_attr( $form_hash ); ?>">
    <input type="hidden" name="redirect" value="<?php echo esc_url( $atts['redirect'] ); ?>">
    <input type="hidden" name="direct_checkout" value="<?php echo esc_attr( $atts['direct_checkout'] ); ?>">
    <div class="easy-ui easy-form <?php echo esc_attr( $form_class ); ?>">