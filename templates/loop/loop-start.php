<?php
/**
 * Resources Loop Start
 *
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/loop/loop-start.php.
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
<ul class="resources columns-<?php echo esc_attr( er_get_loop_prop( 'columns' ) ); ?>">
