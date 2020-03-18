/* global er_orders_params */

jQuery( function( $ ) {
	/**
	 * EROrdersTable class.
	 */
	const EROrdersTable = function() {
		$( document )
			.on( 'click', '.post-type-easy_order .wp-list-table tbody td', this.onRowClick )
			.on( 'click', '.order-preview:not(.disabled)', this.onPreview );
	};

	/**
	 * Click a row.
	 *
	 * @param {Event} e
	 */
	EROrdersTable.prototype.onRowClick = function( e ) {
		if ( $( e.target ).filter( 'a, a *, .no-link, .no-link *, button, button *' ).length ) {
			return true;
		}

		if ( window.getSelection && window.getSelection().toString().length ) {
			return true;
		}

		const href = $( this ).closest( 'tr' ).find( 'a.order-view' ).attr( 'href' );

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
	 * Preview an order.
	 */
	EROrdersTable.prototype.onPreview = function() {
		const previewButton = $( this ),
			orderId = previewButton.data( 'order-id' );

		if ( previewButton.data( 'order-data' ) ) {
			$( this ).ERBackboneModal( {
				template: 'er-modal-view-order',
				variable: previewButton.data( 'order-data' ),
			} );
		} else {
			previewButton.addClass( 'disabled' );

			$.ajax( {
				url: er_orders_params.ajax_url,
				data: {
					order_id: orderId,
					action: 'easyreservations_get_order_details',
					security: er_orders_params.preview_nonce,
				},
				type: 'GET',
				success: function( response ) {
					$( '.order-preview' ).removeClass( 'disabled' );

					if ( response.success ) {
						previewButton.data( 'order-data', response.data );

						$( this ).ERBackboneModal( {
							template: 'er-modal-view-order',
							variable: response.data,
						} );
					}
				},
			} );
		}

		return false;
	};

	/**
	 * Init EROrdersTable.
	 */
	new EROrdersTable();
} );
