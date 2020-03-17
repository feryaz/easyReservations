<?php
/**
 * Show options for ordering
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/loop/orderby.php.
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
<form class="easyreservations-ordering" method="get">
    <select name="orderby" class="orderby" aria-label="<?php esc_attr_e( 'Catalog order', 'easyReservations' ); ?>">
		<?php foreach ( $catalog_orderby_options as $id => $name ) : ?>
            <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $orderby, $id ); ?>><?php echo esc_html( $name ); ?></option>
		<?php endforeach; ?>
    </select>
    <input type="hidden" name="paged" value="1"/>
	<?php //wc_query_string_form_fields( null, array( 'orderby', 'submit', 'paged', 'product-page' ) ); ?>
</form>
