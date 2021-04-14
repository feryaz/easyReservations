/*global easyreservations_admin_meta_boxes, er_admin_params, accounting, er_admin_meta_boxes_order_params, erSetClipboard, erClearClipboard */
jQuery( function( $ ) {
	/**
	 * Custom Data Panel
	 */
	const erMetaBoxesCustom = {

		init: function() {
			$( 'a.edit_custom' ).on( 'click', this.edit_custom_data );
			$( 'a.add_custom' ).on( 'click', this.display_modal );

			$( document.body )
				.on( 'er_backbone_modal_loaded', this.backbone.init )
				.on( 'er_backbone_modal_response', this.backbone.response );
			erMetaBoxesCustom.init_redraw();
		},

		init_redraw: function() {
			$( 'a.delete-custom' ).on( 'click', this.delete_custom );
		},

		edit_custom_data: function( e ) {
			e.preventDefault();

			const $this = $( this ),
				$wrapper = $this.closest( '.order_data_column' ),
				$editCustom = $wrapper.find( 'div.edit_custom_data' ),
				$custom = $wrapper.find( 'div.custom_data' );

			$custom.hide();
			$this.parent().find( 'a:not(.delete_custom)' ).toggle();

			$editCustom.show();
		},

		display_modal: function() {
			$( this ).ERBackboneModal( {
				template: 'er-modal-add-custom',
			} );

			return false;
		},

		register: function() {
		},

		delete_custom: function( e ) {
			const answer = window.confirm( easyreservations_admin_meta_boxes.remove_item_notice );

			if ( answer ) {
				e.preventDefault();
				$( '.custom_data_container' ).block( {
					message: null,
					overlayCSS: { background: '#fff', opacity: 0.6 },
				} );

				$.ajax( {
					url: easyreservations_admin_meta_boxes.ajax_url,
					data: {
						object_id: $( '#object_id' ).val(),
						object_type: easyreservations_admin_meta_boxes.order ? 'order' : 'reservation',
						receipt_item_ids: $( this ).attr( 'data-receipt_item_id' ),
						action: 'easyreservations_remove_custom',
						security: easyreservations_admin_meta_boxes.receipt_item_nonce,
					},
					dataType: 'json',
					type: 'POST',
					success: function( response ) {
						$( '.custom_data_container' ).closest( '.order_data_column' ).find( 'a:not(.delete_custom)' ).toggle();

						// Update notes.
						if ( response.data.notes_html ) {
							$( 'ul.order_notes' ).empty();
							$( 'ul.order_notes' ).append( $( response.data.notes_html ).find( 'li' ) );
						}

						$( '.custom_data_container' ).empty();
						$( '.custom_data_container' ).append( response.data.html );
						$( '.custom_data_container' ).trigger( 'er_generated_custom_field' ).unblock();
						erMetaBoxesCustom.init_redraw();
					},
				} );
			}
			return false;
		},

		backbone: {
			init: function( e, target ) {
				if ( 'er-modal-add-custom' === target ) {
					$( this ).on( 'change', '.er-custom-field', function() {
						const field = $( this ).val();
						if ( field ) {
							$( '#custom_field_value' ).html( $( '#prototype-custom-' + field ).clone() );
							$( document.body ).trigger( 'er_generated_custom_field' );
							$( '#custom_field_data' ).show();
						} else {
							$( '#custom_field_data' ).hide();
						}
					} );
				}
			},

			response: function( e, target, data ) {
				if ( 'er-modal-add-custom' === target ) {
					erMetaBoxesCustom.backbone.add_custom( data );
				}
			},

			add_custom: function( data ) {
				if ( data.custom_field_fee ) {
					$( '#easyreservations-order-items' ).block();
				}

				$( '.custom_data_container' ).block( {
					message: null,
					overlayCSS: { background: '#fff', opacity: 0.6 },
				} );

				data.action = 'easyreservations_add_custom';
				data.security = easyreservations_admin_meta_boxes.custom_nonce;
				data.object_id = easyreservations_admin_meta_boxes.post_id;
				data.object_type = easyreservations_admin_meta_boxes.order ? 'order' : 'reservation';

				if ( data.custom_field && data.custom_field !== '' ) {
					$.ajax( {
						url: easyreservations_admin_meta_boxes.ajax_url,
						data: data,
						dataType: 'json',
						type: 'POST',
						success: function( response ) {
							$( '.custom_data_container' ).closest( '.order_data_column' ).find( 'a:not(.delete_custom)' ).toggle();

							if ( response.data.html ) {
								$( '#easyreservations-order-items' ).find( '.inside' ).empty();
								$( '#easyreservations-order-items' ).find( '.inside' ).append( response.data.html );
								$( '#easyreservations-order-items' ).trigger( 'er_receipt_items_reloaded' ).unblock();
							}

							$( '.custom_data_container' ).empty();
							$( '.custom_data_container' ).append( response.data.custom_html );
							$( '.custom_data_container' ).trigger( 'er_generated_custom_field' ).unblock();
							erMetaBoxesCustom.init_redraw();
						},
					} );
				}
			},
		},
	};

	erMetaBoxesCustom.init();
} );
