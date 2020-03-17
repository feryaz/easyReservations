/* global er_reservations_params */
jQuery( function( $ ) {

	if ( typeof er_reservations_params === 'undefined' ) {
		return false;
	}

	/**
	 * ERReservationsTable class.
	 */
	var ERReservationsTable = function() {
		$( document )
			.on( 'click', '.post-type-easy_reservation .wp-list-table tbody td', this.onRowClick )
			.on( 'click', '.reservation-preview:not(.disabled)', this.onPreview );
	};

	/**
	 * Click a row.
	 */
	ERReservationsTable.prototype.onRowClick = function( e ) {
		if ( $( e.target ).filter( 'a, a *, .no-link, .no-link *, button, button *' ).length ) {
			return true;
		}

		if ( window.getSelection && window.getSelection().toString().length ) {
			return true;
		}

		var $row = $( this ).closest( 'tr' ),
			href = $row.find( 'a.order-view' ).attr( 'href' );

		if ( href && href.length ) {
			e.preventDefault();

			if ( e.metaKey || e.ctrlKey ) {
				window.open( href, '_blank' );
			} else {
				window.location = href;
			}
		}
	};

	/**
	 * Preview an reservation.
	 */
	ERReservationsTable.prototype.onPreview = function() {
		var $previewButton  = $( this ),
			$reservation_id = $previewButton.data( 'reservation-id' );

		if ( $previewButton.data( 'reservation-data' ) ) {
			$( this ).ERBackboneModal( {
				template: 'er-modal-view-reservation',
				variable: $previewButton.data( 'reservation-data' )
			} );
		} else {
			$previewButton.addClass( 'disabled' );

			$.ajax( {
				url:     er_reservations_params.ajax_url,
				data:    {
					reservation_id: $reservation_id,
					action:         'easyreservations_get_reservation_details',
					security:       er_reservations_params.preview_nonce
				},
				type:    'GET',
				success: function( response ) {
					$( '.reservation-preview' ).removeClass( 'disabled' );

					if ( response.success ) {
						$previewButton.data( 'reservation-data', response.data );

						$( this ).ERBackboneModal( {
							template: 'er-modal-view-reservation',
							variable: response.data
						} );
					}
				}
			} );
		}
		return false;
	};

	/**
	 * Init EROrdersTable.
	 */
	new ERReservationsTable();

	$( document ).ready( function() {
		$( 'input[name="delete_all"]' ).remove();
	} );
} );
