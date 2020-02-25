<?php
/**
 * Displayed when no resources are found matching the current query
 *
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/loop/no-resource-found.php.
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
<p class="easyreservations-info"><?php esc_html_e( 'No resources were found matching your selection.', 'easyReservations' ); ?></p>
