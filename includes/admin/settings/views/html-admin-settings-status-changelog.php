<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<table id="changelog" class="widefat" style="margin-top:10px;width:100%">
    <thead>
    <tr>
        <th> <?php esc_html_e( 'Changelog', 'easyReservations' ); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td style="width:100%;line-height: 22px" align="left">
			<?php
                $readme  = file_get_contents( RESERVATIONS_ABSPATH . 'readme.txt' );
                $explode_changelog = explode( '== Changelog ==', $readme );
                $explode_upgrade = explode( '== Upgrade Notice ==', $explode_changelog[1] );

                echo nl2br( substr( $explode_upgrade[0], 2 ) );
			?>
        </td>
    </tr>
    </tbody>
</table>
