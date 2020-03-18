/* global display_availability_filter, er_admin_availability_params */

/**
 * Used by easyReservations/includes/admin/er-admin-availability.php
 */
( function( $, data ) {
	$( document ).ready( function() {
		$( 'table.widefat thead:first-of-type, table.widefat tbody:first-of-type, .easy-navigation, a.dashicons-no' ).remove();
		$( '.wrap h1:first-of-type' ).html( er_admin_availability_params.i18n_headline );
		$( '.wrap p:first-of-type' ).html( er_admin_availability_params.i18n_description );
		$( 'th.tmiddle' ).removeClass( 'tmiddle' );
		display_availability_filter();
	} );
}( jQuery, er_admin_availability_params ) );
