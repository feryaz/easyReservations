jQuery( function( $ ) {
	/**
	 * Reservations Data Panel
	 */
	var er_meta_boxes_reservation = {
		init: function() {
			jQuery( 'li#toplevel_page_reservations, li#toplevel_page_reservations > a' ).addClass( 'wp-has-current-submenu wp-menu-open' ).removeClass( 'wp-not-current-submenu' );

			$( '#resource' ).on( 'change', this.change_resource );
			$( 'a.remove-from-order' ).on( 'click', this.remove_from_order );
			$( 'a.add-to-order' ).on( 'click', this.add_to_order );

			$( document.body )
				.on( 'er_backbone_modal_loaded', this.backbone.init )
				.on( 'er_backbone_modal_response', this.backbone.response );

			er_meta_boxes_reservation.change_resource();
		},

		change_resource: function( e ) {
			$( '.resource-space' ).css( 'display', 'none' );
			$( '.resource-space select' ).prop( "disabled", true );

			var container = $( '.resource-space.resource-' + $( '#resource' ).val() ).css( 'display', 'block' );

			if ( $( '#resource' ).is( ':enabled' ) ) {
				container.find( 'select' ).prop( "disabled", false );
				container.find( 'select' ).attr( 'name', 'space' )
			}

			if ( e ) {
				container.find( 'select' ).val( 1 );
			}
		},

		add_to_order: function( e ) {
			e.preventDefault();
			$( this ).ERBackboneModal( {
				template: 'er-modal-add-to-order'
			} );
		},

		remove_from_order: function( e ) {
			e.preventDefault();
			if ( window.confirm( easyreservations_admin_meta_boxes.i18n_delete_tax ) ) {
				$( '#easyreservations-reservation-order' ).block();

				var data = {
					reservation_id: $( '#object_id' ).val(),
					order_id: $( this ).attr( 'data-order_id' ),
					action: 'easyreservations_remove_reservation_from_order',
					security: easyreservations_admin_meta_boxes.receipt_item_nonce
				};

				$.ajax( {
					url: easyreservations_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						if ( response.success ) {
							$( '#easyreservations-reservation-order' ).find( '.inside' ).empty();
							$( '#easyreservations-reservation-order' ).find( '.inside' ).append( response.data.html );
						} else {
							window.alert( response.data.error );
						}
						$( '#easyreservations-reservation-order' ).unblock();
					},
					complete: function() {
					}
				} );
			}
			return false;
		},

		backbone: {
			init: function( e, target ) {
				if ( 'er-modal-add-to-order' === target ) {
					$( document.body ).trigger( 'er-enhanced-select-init' );
				}
			},
			response: function( e, target, data ) {
				if ( 'er-modal-add-to-order' === target ) {
					// Build array of data.
					if ( data.order_id ) {
						$( '#easyreservations-reservation-order' ).block();

						var data = {
							reservation_id: $( '#object_id' ).val(),
							order_id: data.order_id,
							reservation: 1,
							action: 'easyreservations_add_reservation_to_order',
							security: easyreservations_admin_meta_boxes.receipt_item_nonce
						};

						$.ajax( {
							url: easyreservations_admin_meta_boxes.ajax_url,
							data: data,
							type: 'POST',
							success: function( response ) {
								if ( response.success ) {
									$( '#easyreservations-reservation-order' ).find( '.inside' ).empty();
									$( '#easyreservations-reservation-order' ).find( '.inside' ).append( response.data.html );
								} else {
									window.alert( response.data.error );
								}
								$( '#easyreservations-reservation-order' ).unblock();
							},
							complete: function() {
							}
						} );
					}
				}
			},
		}
	};

	er_meta_boxes_reservation.init();
} );
