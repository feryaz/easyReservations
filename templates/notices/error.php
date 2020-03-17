<?php
/**
 * Show error messages
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/notices/error.php.
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

if ( ! $notices ) {
	return;
}

?>
<ul class="easyreservations-error" role="alert">
	<?php foreach ( $notices as $notice ) : ?>
        <li<?php echo er_get_notice_data_attr( $notice ); ?>>
			<?php echo er_kses_notice( $notice['notice'] ); ?>
        </li>
	<?php endforeach; ?>
</ul>