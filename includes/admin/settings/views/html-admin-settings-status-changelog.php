<?php
if ( !defined( 'ABSPATH' ) ) {
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
            <?php include( RESERVATIONS_ABSPATH . 'changelog.html' ); ?>
        </td>
    </tr>
    </tbody>
</table>
