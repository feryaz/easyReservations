<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=resource&resource=' . $resource->get_id() ) ); ?>" id="set_spaces_names" name="set_spaces_names">
    <table class="widefat" style="margin-top:10px;width: 100%">
        <thead>
        <tr>
            <th colspan="2"><?php esc_html_e( 'Resource spaces titles', 'easyReservations' ); ?></th>
        </tr>
        </thead>
        <tbody>
		<?php for ( $i = 0; $i < $resource->get_quantity(); $i ++ ): ?>
            <tr>
                <td> #<?php echo esc_html( $i + 1 ); ?></td>
                <td style="text-align:right;width:70%">
                    <input type="text" name="resource_spaces[]" value="<?php echo esc_attr( isset( $spaces_names[ $i ] ) && ! empty( $spaces_names[ $i ] ) ? $spaces_names[ $i ] : $i + 1 ); ?>" style="width:99%">
                </td>
            </tr>
		<?php endfor; ?>
        </tbody>
    </table>
    <input class="button-primary" type="submit" value="<?php esc_attr_e( 'Save changes', 'easyreservations' ); ?>" style="margin-top: 8px">
</form>

