/*global easyreservations_admin_meta_boxes, er_admin_params, accounting, er_admin_meta_boxes_order_params */
jQuery( function( $ ) {
	// Stand-in erTracks.recordEvent in case tracks is not available (for any reason).
	window.erTracks = window.erTracks || {};
	window.erTracks.recordEvent = window.erTracks.recordEvent || function() {};

	/**
	 * Order Data Panel
	 */
	var erMetaBoxesOrder = {
		states: null,

		init: function() {
			this.states = JSON.parse( er_admin_meta_boxes_order_params.countries.replace( /&quot;/g, '"' ) );

			$( '.js_field-country' ).selectWoo().on( 'change', this.change_country );
			$( '.js_field-country' ).trigger( 'change', [ true ] );
			$( document.body ).on( 'change', 'select.js_field-state', this.change_state );
			$( '#easyreservations-order-actions input, #easyreservations-order-actions a' ).on( 'click', function() {
				window.onbeforeunload = '';
			} );
			$( 'a.edit_address' ).on( 'click', this.edit_address );
			$( 'a.load_customer' ).on( 'click', this.load_address );
			$( '#customer_user' ).on( 'change', this.change_customer_user );
		},

		change_country: function( e, stickValue ) {
			// Check for stickValue before using it
			if ( typeof stickValue === 'undefined' ) {
				stickValue = false;
			}

			// Prevent if we don't have the metabox data
			if ( erMetaBoxesOrder.states === null ) {
				return;
			}

			const $this = $( this ),
				country = $this.val(),
				$state = $this.parents( 'div.edit_address' ).find( ':input.js_field-state' ),
				$parent = $state.parent(),
				stateValue = $state.val(),
				inputName = $state.attr( 'name' ),
				inputId = $state.attr( 'id' ),
				value = $this.data( 'easyreservations.stickState-' + country ) ? $this.data( 'easyreservations.stickState-' + country ) : stateValue,
				placeholder = $state.attr( 'placeholder' );

			let $newstate;

			if ( stickValue ) {
				$this.data( 'easyreservations.stickState-' + country, value );
			}

			// Remove the previous DOM element
			$parent.show().find( '.select2-container' ).remove();

			if ( ! $.isEmptyObject( erMetaBoxesOrder.states[ country ] ) ) {
				const state = erMetaBoxesOrder.states[ country ],
					$defaultOption = $( '<option value=""></option>' ).text( er_admin_meta_boxes_order_params.i18n_select_state_text );

				$newstate = $( '<select></select>' )
					.prop( 'id', inputId )
					.prop( 'name', inputName )
					.prop( 'placeholder', placeholder )
					.addClass( 'js_field-state select short' )
					.append( $defaultOption );

				$.each( state, function( index ) {
					const $option = $( '<option></option>' )
						.prop( 'value', index )
						.text( state[ index ] );

					if ( index === stateValue ) {
						$option.prop( 'selected' );
					}

					$newstate.append( $option );
				} );

				$newstate.val( value );

				$state.replaceWith( $newstate );

				$newstate.show().selectWoo().hide().trigger( 'change' );
			} else {
				$newstate = $( '<input type="text" />' )
					.prop( 'id', inputId )
					.prop( 'name', inputName )
					.prop( 'placeholder', placeholder )
					.addClass( 'js_field-state' )
					.val( stateValue );
				$state.replaceWith( $newstate );
			}

			$( document.body ).trigger( 'country-change.easyreservations', [ country, $( this ).closest( 'div' ) ] );
		},

		change_state: function() {
			// Here we will find if state value on a select has changed and stick it to the country data
			const $this = $( this ),
				state = $this.val(),
				$country = $this.parents( 'div.edit_address' ).find( ':input.js_field-country' ),
				country = $country.val();

			$country.data( 'easyreservations.stickState-' + country, state );
		},

		edit_address: function( e ) {
			e.preventDefault();

			const $this = $( this ),
				$wrapper = $this.closest( '.order_data_column' ),
				$editAddress = $wrapper.find( 'div.edit_address' ),
				$address = $wrapper.find( 'div.address' ),
				$countryInput = $editAddress.find( '.js_field-country' ),
				$stateInput = $editAddress.find( '.js_field-state' );

			$address.hide();
			$this.parent().find( 'a' ).toggle();

			if ( ! $countryInput.val() ) {
				$countryInput.val( er_admin_meta_boxes_order_params.default_country ).trigger( 'change' );
				$stateInput.val( er_admin_meta_boxes_order_params.default_state ).trigger( 'change' );
			}

			$editAddress.show();

			window.erTracks.recordEvent( 'order_edit_address_click', {
				order_id: easyreservations_admin_meta_boxes.post_id,
				status: $( '#order_status' ).val(),
			} );
		},

		change_customer_user: function() {
			if ( ! $( '#_billing_country' ).val() ) {
				$( 'a.edit_address' ).trigger( 'click' );
				erMetaBoxesOrder.load_address( true );
			}
		},

		load_address: function( force ) {
			if ( true === force || window.confirm( easyreservations_admin_meta_boxes.load_address ) ) {
				// Get user ID to load data for
				const userId = $( '#customer_user' ).val();

				if ( ! userId ) {
					window.alert( easyreservations_admin_meta_boxes.no_customer_selected );
					return false;
				}

				const data = {
					user_id: userId,
					action: 'easyreservations_get_customer_details',
					security: easyreservations_admin_meta_boxes.get_customer_details_nonce
				};

				$( 'div.edit_address' ).block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6,
					},
				} );

				$.ajax( {
					url: easyreservations_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						if ( response && response.billing ) {
							$.each( response.billing, function( key, value ) {
								$( ':input#_' + key ).val( value ).trigger( 'change' );
							} );
						}
						$( 'div.edit_address' ).unblock();
					},
				} );
			}
			return false;
		},
	};

	/**
	 * Order Notes Panel
	 */
	const erMetaBoxesOrderNotes = {
		init: function() {
			$( '#easyreservations-order-notes' )
				.on( 'click', 'button.add_note', this.add_order_note )
				.on( 'click', 'a.delete_note', this.delete_order_note );
		},

		add_order_note: function() {
			if ( ! $( 'textarea#add_order_note' ).val() ) {
				return;
			}

			$( '#easyreservations-order-notes' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6,
				},
			} );

			const data = {
				action: 'easyreservations_add_order_note',
				post_id: easyreservations_admin_meta_boxes.post_id,
				note: $( 'textarea#add_order_note' ).val(),
				note_type: $( 'select#order_note_type' ).val(),
				security: easyreservations_admin_meta_boxes.add_order_note_nonce,
			};

			$.post( easyreservations_admin_meta_boxes.ajax_url, data, function( response ) {
				$( 'ul.order_notes .no-items' ).remove();
				$( 'ul.order_notes' ).prepend( response );
				$( '#easyreservations-order-notes' ).unblock();
				$( '#add_order_note' ).val( '' );

				window.erTracks.recordEvent( 'order_edit_add_order_note', {
					order_id: data.post_id,
					note_type: data.note_type || 'private',
					status: $( '#order_status' ).val(),
				} );
			} );

			return false;
		},

		delete_order_note: function() {
			if ( window.confirm( easyreservations_admin_meta_boxes.i18n_delete_note ) ) {
				const note = $( this ).closest( 'li.note' );

				$( note ).block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6,
					},
				} );

				const data = {
					action: 'easyreservations_delete_order_note',
					note_id: $( note ).attr( 'rel' ),
					security: easyreservations_admin_meta_boxes.delete_order_note_nonce,
				};

				$.post( easyreservations_admin_meta_boxes.ajax_url, data, function() {
					$( note ).remove();
				} );
			}

			return false;
		},
	};

	erMetaBoxesOrder.init();
	erMetaBoxesOrderNotes.init();
} );
