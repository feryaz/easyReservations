/*global easyreservations_admin_meta_boxes, er_admin_params, accounting, easyreservations_admin_meta_boxes_order */
jQuery( function ( $ ) {

	/**
	 * Order Data Panel
	 */
	var er_meta_boxes_order = {
		states: null,

		init: function() {
			if ( ! ( typeof easyreservations_admin_meta_boxes_order === 'undefined' || typeof easyreservations_admin_meta_boxes_order.countries === 'undefined' ) ) {
				/* State/Country select boxes */
				this.states = $.parseJSON( easyreservations_admin_meta_boxes_order.countries.replace( /&quot;/g, '"' ) );
			}

			$( '.js_field-country' ).selectWoo().change( this.change_country );
			$( '.js_field-country' ).trigger( 'change', [ true ] );
			$( document.body ).on( 'change', 'select.js_field-state', this.change_state );
			$( '#easyreservations-order-actions input, #easyreservations-order-actions a' ).click(function() {
				window.onbeforeunload = '';
			});
			$( 'a.edit_address' ).click( this.edit_address );
			$( 'a.load_customer' ).on( 'click', this.load_address );
			$( '#customer_user' ).on( 'change', this.change_customer_user );
		},

		change_country: function( e, stickValue ) {
			// Check for stickValue before using it
			if ( typeof stickValue === 'undefined' ){
				stickValue = false;
			}

			// Prevent if we don't have the metabox data
			if ( er_meta_boxes_order.states === null ){
				return;
			}

			var $this = $( this ),
				country = $this.val(),
				$state = $this.parents( 'div.edit_address' ).find( ':input.js_field-state' ),
				$parent = $state.parent(),
				stateValue = $state.val(),
				input_name = $state.attr( 'name' ),
				input_id = $state.attr( 'id' ),
				value = $this.data( 'easyreservations.stickState-' + country ) ? $this.data( 'easyreservations.stickState-' + country ) : stateValue,
				placeholder = $state.attr( 'placeholder' ),
				$newstate;

			if ( stickValue ){
				$this.data( 'easyreservations.stickState-' + country, value );
			}

			// Remove the previous DOM element
			$parent.show().find( '.select2-container' ).remove();

			if ( ! $.isEmptyObject( er_meta_boxes_order.states[ country ] ) ) {
				var state = er_meta_boxes_order.states[ country ],
					$defaultOption = $( '<option value=""></option>' )
						.text( easyreservations_admin_meta_boxes_order.i18n_select_state_text );

				$newstate = $( '<select></select>' )
					.prop( 'id', input_id )
					.prop( 'name', input_name )
					.prop( 'placeholder', placeholder )
					.addClass( 'js_field-state select short' )
					.append( $defaultOption );

				$.each( state, function( index ) {
					var $option = $( '<option></option>' )
						.prop( 'value', index )
						.text( state[ index ] );
					if ( index === stateValue ) {
						$option.prop( 'selected' );
					}
					$newstate.append( $option );
				} );

				$newstate.val( value );

				$state.replaceWith( $newstate );

				$newstate.show().selectWoo().hide().change();
			} else {
				$newstate = $( '<input type="text" />' )
					.prop( 'id', input_id )
					.prop( 'name', input_name )
					.prop( 'placeholder', placeholder )
					.addClass( 'js_field-state' )
					.val( stateValue );
				$state.replaceWith( $newstate );
			}

			// This event has a typo - deprecated in 2.5.0
			$( document.body ).trigger( 'contry-change.easyreservations', [country, $( this ).closest( 'div' )] );
			$( document.body ).trigger( 'country-change.easyreservations', [country, $( this ).closest( 'div' )] );
		},

		change_state: function() {
			// Here we will find if state value on a select has changed and stick it to the country data
			var $this = $( this ),
				state = $this.val(),
				$country = $this.parents( 'div.edit_address' ).find( ':input.js_field-country' ),
				country = $country.val();

			$country.data( 'easyreservations.stickState-' + country, state );
		},

		edit_address: function(e) {
			e.preventDefault();

			var $this          = $( this ),
				$wrapper       = $this.closest( '.order_data_column' ),
				$edit_address  = $wrapper.find( 'div.edit_address' ),
				$address       = $wrapper.find( 'div.address' ),
				$country_input = $edit_address.find( '.js_field-country' ),
				$state_input   = $edit_address.find( '.js_field-state' );

			$address.hide();
			$this.parent().find( 'a' ).toggle();

			if ( ! $country_input.val() ) {
				$country_input.val( easyreservations_admin_meta_boxes_order.default_country ).change();
				$state_input.val( easyreservations_admin_meta_boxes_order.default_state ).change();
			}

			$edit_address.show();
		},

		change_customer_user: function() {
			if ( ! $( '#_billing_country' ).val() ) {
				$( 'a.edit_address' ).click();
				er_meta_boxes_order.load_address( true );
			}
		},

		load_address: function( force ) {
			if ( true === force || window.confirm( easyreservations_admin_meta_boxes.load_address ) ) {

				// Get user ID to load data for
				var user_id = $( '#customer_user' ).val();

				if ( ! user_id ) {
					window.alert( easyreservations_admin_meta_boxes.no_customer_selected );
					return false;
				}

				var data = {
					user_id : user_id,
					action  : 'easyreservations_get_customer_details',
					security: easyreservations_admin_meta_boxes.get_customer_details_nonce
				};

				$( 'div.edit_address' ).block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				$.ajax({
					url: easyreservations_admin_meta_boxes.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						if ( response && response.billing ) {
							$.each( response.billing, function( key, data ) {
								$( ':input#_' + key ).val( data ).change();
							});
						}
						$( 'div.edit_address' ).unblock();
					}
				});
			}
			return false;
		},
	};

	/**
	 * Order Notes Panel
	 */
	var er_meta_boxes_order_notes = {
		init: function() {
			$( '#easyreservations-order-notes' )
				.on( 'click', 'button.add_note', this.add_order_note )
				.on( 'click', 'a.delete_note', this.delete_order_note );

		},

		add_order_note: function() {
			if ( ! $( 'textarea#add_order_note' ).val() ) {
				return;
			}

			$( '#easyreservations-order-notes' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			var data = {
				action:    'easyreservations_add_order_note',
				post_id:   easyreservations_admin_meta_boxes.post_id,
				note:      $( 'textarea#add_order_note' ).val(),
				note_type: $( 'select#order_note_type' ).val(),
				security:  easyreservations_admin_meta_boxes.add_order_note_nonce
			};

			$.post( easyreservations_admin_meta_boxes.ajax_url, data, function( response ) {
				$( 'ul.order_notes' ).prepend( response );
				$( '#easyreservations-order-notes' ).unblock();
				$( '#add_order_note' ).val( '' );
			});

			return false;
		},

		delete_order_note: function() {
			if ( window.confirm( easyreservations_admin_meta_boxes.i18n_delete_note ) ) {
				var note = $( this ).closest( 'li.note' );

				$( note ).block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				var data = {
					action:   'easyreservations_delete_order_note',
					note_id:  $( note ).attr( 'rel' ),
					security: easyreservations_admin_meta_boxes.delete_order_note_nonce
				};

				$.post( easyreservations_admin_meta_boxes.ajax_url, data, function() {
					$( note ).remove();
				});
			}

			return false;
		}
	};

	er_meta_boxes_order.init();
	er_meta_boxes_order_notes.init();
});
