<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<table id="custom_fields_table" class="widefat table" style="width:100%">
    <thead>
    <tr>
        <th><?php esc_html_e( 'ID', 'easyReservations' ); ?></th>
        <th><?php esc_html_e( 'Title', 'easyReservations' ); ?></th>
        <th><?php esc_html_e( 'Type', 'easyReservations' ); ?></th>
        <th><?php esc_html_e( 'Value', 'easyReservations' ); ?></th>
        <th></th>
    </tr>
    </thead>
    <tbody>
	<?php if ( $custom_fields && ! empty( $custom_fields ) ): ?>
		<?php foreach ( $custom_fields as $key => $custom_field ): ?>
            <tr>
                <td><?php echo esc_html( $key ); ?></td>
                <td><?php echo esc_html( $custom_field['title'] ); ?></td>
                <td><?php echo esc_html( ucfirst( $custom_field['type'] ) ); ?></td>
                <td>
					<?php if ( $custom_field['type'] === 'select' || $custom_field['type'] === 'radio' ): ?>
                        <ul class="options">
							<?php foreach ( $custom_field['options'] as $opt_id => $option ): ?>
                                <li class="<?php if ( isset( $option['checked'] ) ) {
									echo 'selectedoption';
								} ?>">
									<?php echo esc_html( $option['value'] ); ?>
									<?php if ( isset( $option['price'] ) ) {
										echo er_price( $option['price'], true );
									} ?>
									<?php if ( isset( $option['clauses'] ) ): ?>
                                        (<?php echo esc_html( count( $option['clauses'] ) . _n( 'condition', 'conditions', count( $option['clauses'] ), 'easyReservations' ) ); ?>)
									<?php endif; ?>

                                </li>
							<?php endforeach; ?>
                        </ul>
					<?php
                    elseif ( isset( $custom_field['value'] ) ):
						echo esc_html( $custom_field['value'] );
					endif;
					?>
                </td>
                <td style="width:60px">
                    <a href="javascript:custom_edit(<?php echo esc_attr( $key ); ?>);" class="dashicons dashicons-edit"></a>
                    <a href="<?php echo esc_url( wp_nonce_url( 'admin.php?page=er-settings&tab=custom&delete-custom=' . $key, 'easy-delete-custom' ) ); ?>" class="dashicons dashicons-trash"></a>
                </td>
            </tr>
		<?php endforeach; ?>
	<?php else: ?>
        <tr>
            <td colspan="5"><?php esc_html_e( 'No custom fields defined', 'easyReservations' ); ?></td>
        </tr>
	<?php endif; ?>
    </tbody>
</table>
<table id="custom_field_add" class="widefat easy-ui-container" style="width:100%">
    <thead>
    <tr>
        <th>
			<?php esc_html_e( 'Custom field', 'easyReservations' ); ?>
        </th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td class="content">
			<?php esc_html_e( 'With custom fields you can add your own fields to the form. The data gathered can be used throughout the whole system like in emails and invoices.', 'easyReservations' ); ?>
        </td>
    </tr>
    <tr>
        <td>
            <label class="in-hierarchy"><?php esc_html_e( 'Title', 'easyReservations' ); ?></label>
            <input type="text" name="custom_name" id="custom_name">
        </td>
    </tr>
    <tr>
        <td>
            <label class="in-hierarchy"><?php esc_html_e( 'Price', 'easyReservations' ); ?></label>
            <label class="wrapper"><input id="custom_price_field" name="custom_price_field" type="checkbox">
				<?php esc_html_e( 'Field has influence on price', 'easyReservations' ); ?></label>
        </td>
    </tr>
    <tr id="custom_type_tr">
        <td>
            <label class="in-hierarchy"><?php esc_html_e( 'Type', 'easyReservations' ); ?></label>
            <select name="custom_field_type" id="custom_field_type"></select>
        </td>
    </tr>
    <tr>
        <td colspan="2" id="custom_field_extras">
        </td>
    </tr>
    </tbody>
</table>
<div style="margin-top:10px">
    <button type="submit" name="save" value="<?php esc_html_e( 'Submit', 'easyReservations' ); ?>" class="button-primary"><?php esc_html_e( 'Submit', 'easyReservations' ); ?></button>
	<?php wp_nonce_field( 'easyreservations-settings' ); ?>
</div>
