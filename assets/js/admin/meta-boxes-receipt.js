/*global easyreservations_admin_meta_boxes, er_admin_params, accounting, er_admin_meta_boxes_order_params */
jQuery( function( $ ) {

	// Stand-in erTracks.recordEvent in case tracks is not available (for any reason).
	window.erTracks = window.erTracks || {};
	window.erTracks.recordEvent = window.erTracks.recordEvent || function() {
	};

	/**
	 * Receipt Items Panel
	 */
	var er_meta_boxes_receipt_items = {
		init: function() {
			this.stupidtable.init();

			$( '#easyreservations-order-items' )
				.on( 'click', 'button.add-line-item', this.add_line_item )
				.on( 'click', 'button.add-coupon', this.add_coupon )
				.on( 'click', 'a.remove-coupon', this.remove_coupon )
				.on( 'click', 'button.refund-items', this.refund_items )
				.on( 'click', '.cancel-action', this.cancel )
				.on( 'click', '.refund-actions .cancel-action', this.track_cancel )
				.on( 'click', '.reservation-preview', this.preview_reservation )
				.on( 'click', 'button.add-receipt-reservation', this.add_item )
				.on( 'click', 'button.add-receipt-fee', this.add_fee )
				.on( 'click', 'button.add-receipt-tax', this.add_tax )
				.on( 'click', 'button.save-action', this.save_line_items )
				.on( 'click', 'a.delete-receipt-tax', this.delete_tax )
				.on( 'click', 'button.calculate-action', this.recalculate )
				.on( 'click', 'a.edit-receipt-item', this.edit_item )
				.on( 'click', 'a.recalculate-receipt-item', this.recalculate_item )
				.on( 'click', 'a.delete-receipt-item', this.delete_item )

				// Refunds
				.on( 'click', '.delete_refund', this.refunds.delete_refund )
				.on( 'click', 'button.do-api-refund, button.do-manual-refund', this.refunds.do_refund )
				.on( 'change', '.refund input.refund_line_total, .refund input.refund_line_tax', this.refunds.input_changed )
				.on( 'change keyup', '.er-receipt-refund-items #refund_amount', this.refunds.amount_changed )

				// Subtotal/total
				.on( 'keyup change', '.split-input :input', function() {
					var $subtotal = $( this ).parent().prev().find( ':input' );
					if ( $subtotal && ( $subtotal.val() === '' || $subtotal.is( '.match-total' ) ) ) {
						$subtotal.val( $( this ).val() ).addClass( 'match-total' );
					}
				} )

				.on( 'keyup', '.split-input :input', function() {
					$( this ).removeClass( 'match-total' );
				} )

				// Meta
				.on( 'click', 'button.add_receipt_item_meta', this.item_meta.add )
				.on( 'click', 'button.remove_receipt_item_meta', this.item_meta.remove )

				// Reload items
				.on( 'er_receipt_items_reload', this.reload_items )
				.on( 'er_receipt_items_reloaded', this.reloaded_items );

			$( document.body )
				.on( 'er_backbone_modal_loaded', this.backbone.init )
				.on( 'er_backbone_modal_response', this.backbone.response );
		},

		block: function() {
			$( '#easyreservations-order-items' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} );
		},

		unblock: function() {
			$( '#easyreservations-order-items' ).unblock();
		},

		reload_items: function() {
			const data = {
				object_id: $( '#object_id' ).val(),
				object_type: easyreservations_admin_meta_boxes.order ? 'order' : 'reservation',
				action: 'easyreservations_load_receipt_items',
				security: easyreservations_admin_meta_boxes.receipt_item_nonce
			};

			er_meta_boxes_receipt_items.block();

			$.ajax( {
				url: easyreservations_admin_meta_boxes.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					$( '#easyreservations-order-items' ).find( '.inside' ).empty();
					$( '#easyreservations-order-items' ).find( '.inside' ).append( response );
					er_meta_boxes_receipt_items.reloaded_items();
					er_meta_boxes_receipt_items.unblock();

					$( document.body ).trigger( 'init_tooltips' );
				},
			} );
		},

		reloaded_items: function() {
			er_meta_boxes_receipt_items.stupidtable.init();
		},

		add_line_item: function() {
			$( 'div.er-receipt-add-item' ).slideDown();
			$( 'div.er-receipt-data-row-toggle' ).not( 'div.er-receipt-add-item' ).slideUp();

			window.erTracks.recordEvent( 'receipt_edit_add_items_click', {
				object_id: easyreservations_admin_meta_boxes.post_id,
				object_type: easyreservations_admin_meta_boxes.post_type,
				status: $( '#order_status,#reservation_status' ).val(),
			} );

			return false;
		},

		add_coupon: function() {
			window.erTracks.recordEvent( 'receipt_edit_add_coupon_click', {
				object_id: easyreservations_admin_meta_boxes.post_id,
				object_type: easyreservations_admin_meta_boxes.post_type,
				status: $( '#order_status,#reservation_status' ).val(),
			} );

			const value = window.prompt( easyreservations_admin_meta_boxes.i18n_apply_coupon );

			if ( null == value ) {
				window.erTracks.recordEvent( 'receipt_edit_add_coupon_cancel', {
					object_id: easyreservations_admin_meta_boxes.post_id,
					object_type: easyreservations_admin_meta_boxes.post_type,
					status: $( '#order_status,#reservation_status' ).val(),
				} );
			} else {
				er_meta_boxes_receipt_items.block();

				var data = {
					action: 'easyreservations_add_order_coupon',
					dataType: 'json',
					order_id: easyreservations_admin_meta_boxes.post_id,
					security: easyreservations_admin_meta_boxes.receipt_item_nonce,
					coupon: value,
					user_id: $( '#customer_user' ).val(),
					user_email: $( '#_billing_email' ).val(),
				};

				$.ajax( {
					url: easyreservations_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						if ( response.success ) {
							$( '#easyreservations-order-items' ).find( '.inside' ).empty();
							$( '#easyreservations-order-items' ).find( '.inside' ).append( response.data.html );
							er_meta_boxes_receipt_items.reloaded_items();
							er_meta_boxes_receipt_items.unblock();

							$( document.body ).trigger( 'init_tooltips' );
						} else {
							window.alert( response.data.error );
						}
						er_meta_boxes_receipt_items.unblock();
					},
					complete: function() {
						window.erTracks.recordEvent( 'receipt_edit_added_coupon', {
							object_id: easyreservations_admin_meta_boxes.post_id,
							object_type: easyreservations_admin_meta_boxes.post_type,
							status: $( '#order_status,#reservation_status' ).val(),
						} );
					},
				} );
			}
			return false;
		},

		remove_coupon: function() {
			var $this = $( this );
			er_meta_boxes_receipt_items.block();

			var data = {
				action: 'easyreservations_remove_order_coupon',
				dataType: 'json',
				order_id: easyreservations_admin_meta_boxes.post_id,
				security: easyreservations_admin_meta_boxes.receipt_item_nonce,
				coupon: $this.data( 'code' )
			};

			$.post( easyreservations_admin_meta_boxes.ajax_url, data, function( response ) {
				if ( response.success ) {
					$( '#easyreservations-order-items' ).find( '.inside' ).empty();
					$( '#easyreservations-order-items' ).find( '.inside' ).append( response.data.html );
					er_meta_boxes_receipt_items.reloaded_items();
					er_meta_boxes_receipt_items.unblock();

					$( document.body ).trigger( 'init_tooltips' );
				} else {
					window.alert( response.data.error );
				}
				er_meta_boxes_receipt_items.unblock();
			} );
		},

		refund_items: function() {
			$( 'div.er-receipt-refund-items' ).slideDown();
			$( 'div.er-receipt-data-row-toggle' ).not( 'div.er-receipt-refund-items' ).slideUp();
			$( 'div.er-receipt-totals-items' ).slideUp();
			$( '#easyreservations-order-items' ).find( 'div.refund' ).show();
			$( '.er-receipt-edit-line-item .er-receipt-edit-line-item-actions' ).hide();

			window.erTracks.recordEvent( 'receipt_edit_refund_button_click', {
				object_id: easyreservations_admin_meta_boxes.post_id,
				object_type: easyreservations_admin_meta_boxes.post_type,
				status: $( '#order_status,#reservation_status' ).val(),
			} );

			return false;
		},

		cancel: function() {
			$( 'div.er-receipt-data-row-toggle' ).not( 'div.er-receipt-bulk-actions' ).slideUp();
			$( 'div.er-receipt-bulk-actions' ).slideDown();
			$( 'div.er-receipt-totals-items' ).slideDown();
			$( '#easyreservations-order-items' ).find( 'div.refund' ).hide();
			$( '.er-receipt-edit-line-item .er-receipt-edit-line-item-actions' ).show();

			// Reload the items
			if ( 'true' === $( this ).attr( 'data-reload' ) ) {
				er_meta_boxes_receipt_items.reload_items();
			}

			window.erTracks.recordEvent( 'receipt_edit_add_items_cancelled', {
				object_id: easyreservations_admin_meta_boxes.post_id,
				object_type: easyreservations_admin_meta_boxes.post_type,
				status: $( '#order_status,#reservation_status' ).val(),
			} );

			return false;
		},

		track_cancel: function() {
			window.erTracks.recordEvent( 'receipt_edit_refund_cancel', {
				object_id: easyreservations_admin_meta_boxes.post_id,
				object_type: easyreservations_admin_meta_boxes.post_type,
				status: $( '#order_status,#reservation_status' ).val(),
			} );
		},

		preview_reservation: function() {
			var $previewButton = $( this ),
				$reservation_id = $previewButton.data( 'reservation-id' );

			if ( $previewButton.data( 'reservation-data' ) ) {
				$( this ).ERBackboneModal( {
					template: 'er-modal-view-reservation',
					variable: $previewButton.data( 'reservation-data' ),
				} );
			} else {
				$previewButton.addClass( 'disabled' );

				$.ajax( {
					url: easyreservations_admin_meta_boxes.ajax_url,
					data: {
						reservation_id: $reservation_id,
						action: 'easyreservations_get_reservation_details',
						security: easyreservations_admin_meta_boxes.preview_nonce,
					},
					type: 'GET',
					success: function( response ) {
						$previewButton.removeClass( 'disabled' );

						if ( response.success ) {
							$previewButton.data( 'reservation-data', response.data );

							$( this ).ERBackboneModal( {
								template: 'er-modal-view-reservation',
								variable: response.data,
							} );
						}
					},
				} );
			}
			return false;
		},

		add_item: function() {
			//$(this).ERBackboneModal({
			//    template: 'er-modal-add-reservation'
			//});
			var value = window.prompt( easyreservations_admin_meta_boxes.i18n_add_reservation );

			if ( null !== value ) {
				er_meta_boxes_receipt_items.block();

				var data = {
					action: 'easyreservations_add_reservation_to_order',
					dataType: 'json',
					order_id: $( '#object_id' ).val(),
					reservation_id: value,
					security: easyreservations_admin_meta_boxes.receipt_item_nonce
				};

				$.post( easyreservations_admin_meta_boxes.ajax_url, data, function( response ) {
					if ( response.success ) {
						$( '#easyreservations-order-items' ).find( '.inside' ).empty();
						$( '#easyreservations-order-items' ).find( '.inside' ).append( response.data.html );
						er_meta_boxes_receipt_items.reloaded_items();
						er_meta_boxes_receipt_items.unblock();

						$( document.body ).trigger( 'init_tooltips' );
					} else {
						window.alert( response.data.error );
					}
					er_meta_boxes_receipt_items.unblock();
				} );
			}
			return false;
		},

		add_fee: function() {
			window.erTracks.recordEvent( 'receipt_edit_add_fee_click', {
				object_id: easyreservations_admin_meta_boxes.post_id,
				object_type: easyreservations_admin_meta_boxes.post_type,
				status: $( '#order_status,#reservation_status' ).val(),
			} );

			var value = window.prompt( easyreservations_admin_meta_boxes.i18n_add_fee );

			if ( null == value ) {
				window.erTracks.recordEvent( 'receipt_edit_add_fee_cancel', {
					object_id: easyreservations_admin_meta_boxes.post_id,
					object_type: easyreservations_admin_meta_boxes.post_type,
					status: $( '#order_status,#reservation_status' ).val(),
				} );
			} else {
				er_meta_boxes_receipt_items.block();

				var data = {
					action: 'easyreservations_add_receipt_fee',
					dataType: 'json',
					object_id: $( '#object_id' ).val(),
					object_type: easyreservations_admin_meta_boxes.order ? 'order' : 'reservation',
					security: easyreservations_admin_meta_boxes.receipt_item_nonce,
					amount: value
				};

				$.post( easyreservations_admin_meta_boxes.ajax_url, data, function( response ) {
					if ( response.success ) {
						$( '#easyreservations-order-items' ).find( '.inside' ).empty();
						$( '#easyreservations-order-items' ).find( '.inside' ).append( response.data.html );
						er_meta_boxes_receipt_items.reloaded_items();
						er_meta_boxes_receipt_items.unblock();

						$( document.body ).trigger( 'init_tooltips' );

						window.erTracks.recordEvent( 'receipt_edit_added_fee', {
							object_id: easyreservations_admin_meta_boxes.post_id,
							object_type: easyreservations_admin_meta_boxes.post_type,
							status: $( '#order_status,#reservation_status' ).val(),
						} );
					} else {
						window.alert( response.data.error );
					}
					er_meta_boxes_receipt_items.unblock();
				} );
			}
			return false;
		},

		add_tax: function() {
			$( this ).ERBackboneModal( {
				template: 'er-modal-add-tax'
			} );
			return false;
		},

		edit_item: function() {
			$( this ).closest( 'tr' ).find( '.view' ).hide();
			$( this ).closest( 'tr' ).find( '.edit' ).show();
			$( this ).hide();
			$( 'button.add-line-item' ).trigger( 'click' );
			$( 'button.cancel-action' ).attr( 'data-reload', true );

			window.erTracks.recordEvent( 'receipt_edit_edit_item_click', {
				object_id: easyreservations_admin_meta_boxes.post_id,
				object_type: easyreservations_admin_meta_boxes.post_type,
				status: $( '#order_status,#reservation_status' ).val(),
			} );

			return false;
		},

		delete_item: function() {
			var answer = window.confirm( easyreservations_admin_meta_boxes.remove_item_notice );

			if ( answer ) {
				var $item = $( this ).closest( 'tr.item, tr.fee, tr.shipping' );
				var receipt_item_id = $item.attr( 'data-receipt_item_id' );

				er_meta_boxes_receipt_items.block();

				var data = {
					object_id: $( '#object_id' ).val(),
					object_type: easyreservations_admin_meta_boxes.order ? 'order' : 'reservation',
					receipt_item_ids: receipt_item_id,
					action: 'easyreservations_remove_receipt_item',
					security: easyreservations_admin_meta_boxes.receipt_item_nonce
				};

				// Check if items have changed, if so pass them through so we can save them before deleting.
				if ( 'true' === $( 'button.cancel-action' ).attr( 'data-reload' ) ) {
					data.items = $( 'table.easyreservations_receipt_items :input[name], .er-receipt-totals-items :input[name]' ).serialize();
				}

				$.ajax( {
					url: easyreservations_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						if ( response.success ) {
							$( '#easyreservations-order-items' ).find( '.inside' ).empty();
							$( '#easyreservations-order-items' ).find( '.inside' ).append( response.data.html );

							// Update notes.
							if ( response.data.notes_html ) {
								$( 'ul.order_notes' ).empty();
								$( 'ul.order_notes' ).append( $( response.data.notes_html ).find( 'li' ) );
							}

							er_meta_boxes_receipt_items.reloaded_items();
							er_meta_boxes_receipt_items.unblock();

							$( document.body ).trigger( 'init_tooltips' );
						} else {
							window.alert( response.data.error );
						}
						er_meta_boxes_receipt_items.unblock();
					},
					complete: function() {
						window.erTracks.recordEvent( 'receipt_edit_remove_item', {
							object_id: easyreservations_admin_meta_boxes.post_id,
							object_type: easyreservations_admin_meta_boxes.post_type,
							status: $( '#order_status,#reservation_status' ).val(),
						} );
					},
				} );
			}
			return false;
		},

		delete_tax: function() {
			if ( window.confirm( easyreservations_admin_meta_boxes.i18n_delete_tax ) ) {
				er_meta_boxes_receipt_items.block();

				var data = {
					object_id: $( '#object_id' ).val(),
					object_type: easyreservations_admin_meta_boxes.order ? 'order' : 'reservation',
					action: 'easyreservations_remove_receipt_tax',
					rate_id: $( this ).attr( 'data-rate_id' ),
					security: easyreservations_admin_meta_boxes.receipt_item_nonce
				};

				$.ajax( {
					url: easyreservations_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						if ( response.success ) {
							$( '#easyreservations-order-items' ).find( '.inside' ).empty();
							$( '#easyreservations-order-items' ).find( '.inside' ).append( response.data.html );
							er_meta_boxes_receipt_items.reloaded_items();
							er_meta_boxes_receipt_items.unblock();

							$( document.body ).trigger( 'init_tooltips' );
						} else {
							window.alert( response.data.error );
						}
						er_meta_boxes_receipt_items.unblock();
					},
					complete: function() {
						window.erTracks.recordEvent( 'receipt_edit_delete_tax', {
							object_id: easyreservations_admin_meta_boxes.post_id,
							object_type: easyreservations_admin_meta_boxes.post_type,
							status: $( '#order_status,#reservation_status' ).val(),
						} );
					},
				} );
			} else {
				window.erTracks.recordEvent( 'receipt_edit_delete_tax_cancel', {
					object_id: easyreservations_admin_meta_boxes.post_id,
					object_type: easyreservations_admin_meta_boxes.post_type,
					status: $( '#order_status,#reservation_status' ).val(),
				} );
			}

			return false;
		},

		recalculate_item: function( e ) {
			e.preventDefault();
			er_meta_boxes_receipt_items.block();

			var data = {
				object_id: $( '#object_id' ).val(),
				object_type: easyreservations_admin_meta_boxes.order ? 'order' : 'reservation',
				action: 'easyreservations_recalc_line',
				items_id: $( this ).attr( 'data-item-id' ),
				security: easyreservations_admin_meta_boxes.calc_totals_nonce,
			};

			$.ajax( {
				url: easyreservations_admin_meta_boxes.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					$( '#easyreservations-order-items' ).find( '.inside' ).empty();
					$( '#easyreservations-order-items' ).find( '.inside' ).append( response );
					er_meta_boxes_receipt_items.reloaded_items();
					er_meta_boxes_receipt_items.unblock();

					$( document.body ).trigger( 'init_tooltips' );
				},
				complete: function( response ) {
					window.erTracks.recordEvent( 'receipt_edit_recalc_line', {
						object_id: easyreservations_admin_meta_boxes.post_id,
						object_type: easyreservations_admin_meta_boxes.post_type,
						status: $( '#order_status,#reservation_status' ).val(),
					} );
				},
			} );
		},

		recalculate: function() {
			if ( window.confirm( easyreservations_admin_meta_boxes.calc_totals ) ) {
				er_meta_boxes_receipt_items.block();

				var data = {
					object_id: $( '#object_id' ).val(),
					object_type: easyreservations_admin_meta_boxes.order ? 'order' : 'reservation',
					action: 'easyreservations_calc_line_taxes',
					items: $( 'table.easyreservations_receipt_items :input[name], .er-receipt-totals-items :input[name]' ).serialize(),
					security: easyreservations_admin_meta_boxes.calc_totals_nonce,
				};

				$( document.body ).trigger( 'receipt-totals-recalculate-before', data );

				$.ajax( {
					url: easyreservations_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						$( '#easyreservations-order-items' ).find( '.inside' ).empty();
						$( '#easyreservations-order-items' ).find( '.inside' ).append( response );
						er_meta_boxes_receipt_items.reloaded_items();
						er_meta_boxes_receipt_items.unblock();

						$( document.body ).trigger( 'init_tooltips' );

						$( document.body ).trigger( 'receipt-totals-recalculate-success', response );
					},
					complete: function( response ) {
						$( document.body ).trigger( 'receipt-totals-recalculate-complete', response );

						window.erTracks.recordEvent( 'receipt_edit_recalc_totals', {
							object_id: easyreservations_admin_meta_boxes.post_id,
							object_type: easyreservations_admin_meta_boxes.post_type,
							status: $( '#order_status,#reservation_status' ).val(),
						} );
					},
				} );
			} else {
				window.erTracks.recordEvent( 'receipt_edit_recalc_totals', {
					object_id: easyreservations_admin_meta_boxes.post_id,
					object_type: easyreservations_admin_meta_boxes.post_type,
					status: $( '#order_status,#reservation_status' ).val(),
				} );
			}

			return false;
		},

		save_line_items: function() {
			var data = {
				object_id: $( '#object_id' ).val(),
				object_type: easyreservations_admin_meta_boxes.order ? 'order' : 'reservation',
				items: $( 'table.easyreservations_receipt_items :input[name], .er-receipt-totals-items :input[name]' ).serialize(),
				action: 'easyreservations_save_receipt_items',
				security: easyreservations_admin_meta_boxes.receipt_item_nonce
			};

			er_meta_boxes_receipt_items.block();

			$.ajax( {
				url: easyreservations_admin_meta_boxes.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					if ( response.success ) {
						$( '#easyreservations-order-items' ).find( '.inside' ).empty();
						$( '#easyreservations-order-items' ).find( '.inside' ).append( response.data.html );

						// Update notes.
						if ( response.data.notes_html ) {
							$( 'ul.order_notes' ).empty();
							$( 'ul.order_notes' ).append( $( response.data.notes_html ).find( 'li' ) );
						}

						er_meta_boxes_receipt_items.reloaded_items();
						er_meta_boxes_receipt_items.unblock();
					} else {
						er_meta_boxes_receipt_items.unblock();
						window.alert( response.data.error );
					}
				},
				complete: function() {
					window.erTracks.recordEvent( 'receipt_edit_save_line_items', {
						object_id: easyreservations_admin_meta_boxes.post_id,
						object_type: easyreservations_admin_meta_boxes.post_type,
						status: $( '#order_status,#reservation_status' ).val(),
					} );
				},
			} );

			$( this ).trigger( 'items_saved' );

			return false;
		},

		refunds: {

			do_refund: function() {
				er_meta_boxes_receipt_items.block();

				if ( window.confirm( easyreservations_admin_meta_boxes.i18n_do_refund ) ) {
					var refund_amount = $( 'input#refund_amount' ).val();
					var refund_reason = $( 'input#refund_reason' ).val();
					var refunded_amount = $( 'input#refunded_amount' ).val();

					// Get line item refunds
					var line_item_totals = {};
					var line_item_tax_totals = {};

					$( '.refund input.refund_line_total' ).each( function( index, item ) {
						if ( $( item ).closest( 'tr' ).data( 'receipt_item_id' ) ) {
							line_item_totals[ $( item ).closest( 'tr' ).data( 'receipt_item_id' ) ] = accounting.unformat( item.value, er_admin_params.mon_decimal_point );
						}
					} );

					$( '.refund input.refund_line_tax' ).each( function( index, item ) {
						if ( $( item ).closest( 'tr' ).data( 'receipt_item_id' ) ) {
							var tax_id = $( item ).data( 'tax_id' );

							if ( ! line_item_tax_totals[ $( item ).closest( 'tr' ).data( 'receipt_item_id' ) ] ) {
								line_item_tax_totals[ $( item ).closest( 'tr' ).data( 'receipt_item_id' ) ] = {};
							}

							line_item_tax_totals[ $( item ).closest( 'tr' ).data( 'receipt_item_id' ) ][ tax_id ] = accounting.unformat( item.value, er_admin_params.mon_decimal_point );
						}
					} );

					var data = {
						action: 'easyreservations_refund_line_items',
						order_id: easyreservations_admin_meta_boxes.post_id,
						refund_amount: refund_amount,
						refunded_amount: refunded_amount,
						refund_reason: refund_reason,
						line_item_totals: JSON.stringify( line_item_totals, null, '' ),
						line_item_tax_totals: JSON.stringify( line_item_tax_totals, null, '' ),
						api_refund: $( this ).is( '.do-api-refund' ),
						security: easyreservations_admin_meta_boxes.receipt_item_nonce
					};

					$.ajax( {
						url: easyreservations_admin_meta_boxes.ajax_url,
						data: data,
						type: 'POST',
						success: function( response ) {
							if ( true === response.success ) {
								// Redirect to same page for show the refunded status
								window.location.reload();
							} else {
								window.alert( response.data.error );
								er_meta_boxes_receipt_items.reload_items();
								er_meta_boxes_receipt_items.unblock();
							}
						},
						complete: function() {
							window.erTracks.recordEvent( 'receipt_edit_save_line_items', {
								object_id: easyreservations_admin_meta_boxes.post_id,
								object_type: easyreservations_admin_meta_boxes.post_type,
								status: $( '#order_status,#reservation_status' ).val(),
								api_refund: data.api_refund,
								has_reason: Boolean( data.refund_reason.length ),
							} );
						},
					} );
				} else {
					er_meta_boxes_receipt_items.unblock();
				}
			},

			delete_refund: function() {
				if ( window.confirm( easyreservations_admin_meta_boxes.i18n_delete_refund ) ) {
					var $refund = $( this ).closest( 'tr.refund' );
					var refund_id = $refund.attr( 'data-order_refund_id' );

					er_meta_boxes_receipt_items.block();

					var data = {
						action: 'easyreservations_delete_refund',
						refund_id: refund_id,
						security: easyreservations_admin_meta_boxes.receipt_item_nonce
					};

					$.ajax( {
						url: easyreservations_admin_meta_boxes.ajax_url,
						data: data,
						type: 'POST',
						success: function() {
							er_meta_boxes_receipt_items.reload_items();
						},
					} );
				}
				return false;
			},

			input_changed: function() {
				var refund_amount = 0;
				var $items = $( '.easyreservations_receipt_items' ).find( 'tr.item, tr.fee, tr.resource' );

				$items.each( function() {
					var $row = $( this );
					var refund_cost_fields = $row.find( '.refund input' );

					refund_cost_fields.each( function( index, el ) {
						refund_amount += parseFloat( accounting.unformat( $( el ).val() || 0, er_admin_params.mon_decimal_point ) );
					} );
				} );

				$( '#refund_amount' )
					.val( accounting.formatNumber(
						refund_amount,
						easyreservations_admin_meta_boxes.currency_format_num_decimals,
						'',
						er_admin_params.mon_decimal_point
					) )
					.trigger( 'change' );
			},

			amount_changed: function() {
				var total = accounting.unformat( $( this ).val(), er_admin_params.mon_decimal_point );

				$( 'button .er-order-refund-amount .amount' ).text( accounting.formatMoney( total, {
					symbol: easyreservations_admin_meta_boxes.currency_format_symbol,
					decimal: easyreservations_admin_meta_boxes.currency_format_decimal_sep,
					thousand: easyreservations_admin_meta_boxes.currency_format_thousand_sep,
					precision: easyreservations_admin_meta_boxes.currency_format_num_decimals,
					format: easyreservations_admin_meta_boxes.currency_format,
				} ) );
			},
		},

		item_meta: {

			add: function() {
				var $button = $( this );
				var $item = $button.closest( 'tr.item' );
				var $items = $item.find( 'tbody.meta_items' );
				var index = $items.find( 'tr' ).length + 1;
				var $row = '<tr data-meta_id="0">' +
					'<td>' +
					'<input type="text" maxlength="255" placeholder="' + er_admin_meta_boxes_order_params.placeholder_name + '" name="meta_key[' + $item.attr( 'data-receipt_item_id' ) + '][new-' + index + ']" />' +
					'<textarea placeholder="' + er_admin_meta_boxes_order_params.placeholder_value + '" name="meta_value[' + $item.attr( 'data-receipt_item_id' ) + '][new-' + index + ']"></textarea>' +
					'</td>' +
					'<td width="1%"><button class="remove_receipt_item_meta button">&times;</button></td>' +
					'</tr>';
				$items.append( $row );

				return false;
			},

			remove: function() {
				if ( window.confirm( easyreservations_admin_meta_boxes.remove_item_meta ) ) {
					const $row = $( this ).closest( 'tr' );
					$row.find( ':input' ).val( '' );
					$row.hide();
				}
				return false;
			}
		},

		backbone: {

			init: function( e, target ) {
				if ( 'wc-modal-add-products' === target ) {
					$( document.body ).trigger( 'er-enhanced-select-init' );

					$( this ).on( 'change', '.wc-product-search', function() {
						if ( ! $( this ).closest( 'tr' ).is( ':last-child' ) ) {
							return;
						}
						var item_table = $( this ).closest( 'table.widefat' ),
							item_table_body = item_table.find( 'tbody' ),
							index = item_table_body.find( 'tr' ).length,
							row = item_table_body.data( 'row' ).replace( /\[0\]/g, '[' + index + ']' );

						item_table_body.append( '<tr>' + row + '</tr>' );
						$( document.body ).trigger( 'er-enhanced-select-init' );
					} );
				}
			},

			response: function( e, target, data ) {
				if ( 'er-modal-add-tax' === target ) {
					er_meta_boxes_receipt_items.backbone.add_tax( data.add_order_tax );
				}
				if ( 'wc-modal-add-products' === target ) {
					// Build array of data.
					var item_table = $( this ).find( 'table.widefat' ),
						item_table_body = item_table.find( 'tbody' ),
						rows = item_table_body.find( 'tr' ),
						add_items = [];

					$( rows ).each( function() {
						var item_id = $( this ).find( ':input[name="item_id"]' ).val(),
							item_qty = $( this ).find( ':input[name="item_qty"]' ).val();

						add_items.push( {
							'id': item_id,
							'qty': item_qty ? item_qty : 1
						} );
					} );

					return er_meta_boxes_receipt_items.backbone.add_items( add_items );
				}
			},

			add_items: function( add_items ) {
				er_meta_boxes_receipt_items.block();

				var data = {
					action: 'easyreservations_add_order_item',
					order_id: easyreservations_admin_meta_boxes.post_id,
					security: easyreservations_admin_meta_boxes.receipt_item_nonce,
					data: add_items
				};

				// Check if items have changed, if so pass them through so we can save them before adding a new item.
				if ( 'true' === $( 'button.cancel-action' ).attr( 'data-reload' ) ) {
					data.items = $( 'table.easyreservations_receipt_items :input[name], .er-receipt-totals-items :input[name]' ).serialize();
				}

				$.ajax( {
					type: 'POST',
					url: easyreservations_admin_meta_boxes.ajax_url,
					data: data,
					success: function( response ) {
						if ( response.success ) {
							$( '#easyreservations-order-items' ).find( '.inside' ).empty();
							$( '#easyreservations-order-items' ).find( '.inside' ).append( response.data.html );

							// Update notes.
							if ( response.data.notes_html ) {
								$( 'ul.order_notes' ).empty();
								$( 'ul.order_notes' ).append( $( response.data.notes_html ).find( 'li' ) );
							}

							er_meta_boxes_receipt_items.reloaded_items();
							er_meta_boxes_receipt_items.unblock();
						} else {
							er_meta_boxes_receipt_items.unblock();
							window.alert( response.data.error );
						}
					},
					complete: function() {
						window.erTracks.recordEvent( 'receipt_edit_add_resource', {
							object_id: easyreservations_admin_meta_boxes.post_id,
							object_type: easyreservations_admin_meta_boxes.post_type,
							status: $( '#order_status,#reservation_status' ).val(),
						} );
					},
					dataType: 'json',
				} );
			},

			add_tax: function( rate_id ) {
				if ( ! rate_id ) {
					return false;
				}

				var rates = $( '.receipt-tax-id' ).map( function() {
					return $( this ).val();
				} ).get();

				// Test if already exists
				if ( -1 === $.inArray( rate_id, rates ) ) {
					er_meta_boxes_receipt_items.block();

					var data = {
						action: 'easyreservations_add_receipt_tax',
						rate_id: rate_id,
						object_id: $( '#object_id' ).val(),
						object_type: easyreservations_admin_meta_boxes.order ? 'order' : 'reservation',
						security: easyreservations_admin_meta_boxes.receipt_item_nonce
					};

					$.ajax( {
						url: easyreservations_admin_meta_boxes.ajax_url,
						data: data,
						dataType: 'json',
						type: 'POST',
						success: function( response ) {
							if ( response.success ) {
								$( '#easyreservations-order-items' ).find( '.inside' ).empty();
								$( '#easyreservations-order-items' ).find( '.inside' ).append( response.data.html );
								er_meta_boxes_receipt_items.reloaded_items();
							} else {
								window.alert( response.data.error );
							}
							er_meta_boxes_receipt_items.unblock();
						},
						complete: function() {
							window.erTracks.recordEvent( 'receipt_edit_add_tax', {
								object_id: easyreservations_admin_meta_boxes.post_id,
								object_type: easyreservations_admin_meta_boxes.post_type,
								status: $( '#order_status,#reservation_status' ).val(),
							} );
						},
					} );
				} else {
					window.alert( easyreservations_admin_meta_boxes.i18n_tax_rate_already_exists );
				}
			}
		},

		stupidtable: {
			init: function() {
				$( '.easyreservations_receipt_items' ).stupidtable();
				$( '.easyreservations_receipt_items' ).on( 'aftertablesort', this.add_arrows );
			},

			add_arrows: function( event, data ) {
				var th = $( this ).find( 'th' );
				var arrow = data.direction === 'asc' ? '&uarr;' : '&darr;';
				var index = data.column;
				th.find( '.er-arrow' ).remove();
				th.eq( index ).append( '<span class="er-arrow">' + arrow + '</span>' );
			}
		}
	};

	er_meta_boxes_receipt_items.init();
} );

