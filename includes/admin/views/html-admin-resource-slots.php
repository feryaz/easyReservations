<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>
<script>var slots = new Array();</script>
<h2><?php esc_html_e( 'Slots', 'easyReservations' ); ?></h2>
<p>
    Slots are predefined time ranges between which your guests can choose.
    As arrival and departure are set most requirements do not apply to slots.<br>
    The prices are for the whole duration of the slot.
    They are only selectable in the new [date] form field for now.
    <a onclick="jQuery('.paste-slot-input').toggleClass('hidden');" class="dashicons dashicons-upload tips" style="float:right;" data-tip="<?php echo sprintf( __( 'Paste %s', 'easyReservations' ), __( 'slot', 'easyReservations' ) ); ?>"></a>
    <input type="text" placeholder="Paste here" style="float:right" class="paste-slot-input hidden">
</p>
<table class="widefat" style="width: 100%">
    <thead>
    <tr>
        <th><?php esc_html_e( 'Name', 'easyReservations' ); ?></th>
        <th style=""><?php esc_html_e( 'Active', 'easyReservations' ); ?></th>
        <th style="text-align: center"><?php esc_html_e( 'Duration', 'easyReservations' ); ?></th>
        <th style="text-align: center"><?php esc_html_e( 'Repeat', 'easyReservations' ); ?></th>
        <th style="text-align: right"><?php esc_html_e( 'Price', 'easyReservations' ); ?></th>
        <th></th>
    </tr>
    </thead>
    <tbody id="sortable">
	<?php if ( $slots && ! empty( $slots ) ): ?>
		<?php foreach ( $slots as $key => $slot ): ?>
        <script>
			slots[<?php echo $key; ?>] = new Object();
			slots[<?php echo $key; ?>] = <?php echo wp_json_encode( $slot ); ?>;
        </script>
        <tr>
            <td><?php echo esc_html( $slot['name'] ); ?></td>
            <td>
                <code><?php echo esc_html( $slot['from_str'] ); ?></code> -
                <code><?php echo esc_html( $slot['to_str'] ); ?></code>
            </td>
            <td style="text-align: center">
				<?php echo esc_html( human_time_diff( $slot['from'] * 60, $slot['duration'] * DAY_IN_SECONDS + $slot['to'] * 60 ) ); ?>
                <br>
            </td>
            <td style="text-align: center">
				<?php echo isset( $slot['repeat'] ) ? esc_attr( $slot['repeat'] ) : 0; ?><br>
            </td>
            <td style="text-align: right">
				<?php echo er_price( $slot['base-price'], true ); ?><br>
				<?php echo er_price( $slot['children-price'], true ); ?>
            </td>
            <td style="text-align: right">
                <a class="dashicons dashicons-edit slot-edit tips" data-slot="<?php echo esc_attr( $key ); ?>"
                    data-tip="<?php echo sprintf( esc_attr__( 'Edit %s', 'easyReservations' ), esc_attr__( 'slot', 'easyReservations' ) ); ?>"> </a>
                <a class="dashicons dashicons-admin-page tips" data-slot="<?php echo esc_attr( $key ); ?>"
                    data-tip="<?php echo sprintf( esc_attr__( 'Copy %s', 'easyReservations' ), esc_attr__( 'slot', 'easyReservations' ) ); ?>"> </a>
                <a
                    href="<?php echo esc_url( wp_nonce_url( 'admin.php?page=resource&resource=' . $resource->get_id() . '&delete_slot=' . $key, 'easy-resource-delete-slot' ) ); ?>#slots"
                    class="dashicons dashicons-trash tips" data-tip="<?php echo sprintf( esc_attr__( 'Delete %s', 'easyReservations' ), esc_attr__( 'slot', 'easyReservations' ) ); ?>"> </a>
            </td>
        </tr>
	<?php endforeach; ?>
	<?php else: ?>
        <tr>
            <td colspan="5">
				<?php esc_html_e( 'No slots defined', 'easyReservations' ); ?>
            </td>
        </tr>
	<?php endif; ?>
    </tbody>
</table>