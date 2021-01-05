/*global er_country_select_params */
jQuery( function( $ ) {

	// er_country_select_params is required to continue, ensure the object exists
	if ( typeof er_country_select_params === 'undefined' ) {
		return false;
	}

	// Select2 Enhancement if it exists
	if ( $().selectWoo ) {
		var getEnhancedSelectFormatString = function() {
			return {
				'language': {
					errorLoading: function() {
						// Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
						return er_country_select_params.i18n_searching;
					},
					inputTooLong: function( args ) {
						var overChars = args.input.length - args.maximum;

						if ( 1 === overChars ) {
							return er_country_select_params.i18n_input_too_long_1;
						}

						return er_country_select_params.i18n_input_too_long_n.replace( '%qty%', overChars );
					},
					inputTooShort: function( args ) {
						var remainingChars = args.minimum - args.input.length;

						if ( 1 === remainingChars ) {
							return er_country_select_params.i18n_input_too_short_1;
						}

						return er_country_select_params.i18n_input_too_short_n.replace( '%qty%', remainingChars );
					},
					loadingMore: function() {
						return er_country_select_params.i18n_load_more;
					},
					maximumSelected: function( args ) {
						if ( args.maximum === 1 ) {
							return er_country_select_params.i18n_selection_too_long_1;
						}

						return er_country_select_params.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
					},
					noResults: function() {
						return er_country_select_params.i18n_no_matches;
					},
					searching: function() {
						return er_country_select_params.i18n_searching;
					}
				}
			};
		};

		var er_country_select_select2 = function() {
			$( 'select.country_select:visible, select.state_select:visible' ).each( function() {
				var select2_args = $.extend( {
					placeholder: $( this ).attr( 'data-placeholder' ) || $( this ).attr( 'placeholder' ) || '',
					width: '100%',
				}, getEnhancedSelectFormatString() );

				$( this )
					.on( 'select2:select', function() {
						$( this ).focus(); // Maintain focus after select https://github.com/select2/select2/issues/4384
					} )
					.selectWoo( select2_args );
			} );
		};

		er_country_select_select2();

		$( document.body ).on( 'country_to_state_changed', function() {
			er_country_select_select2();
		} );
	}

	/* State/Country select boxes */
	var states_json = er_country_select_params.countries.replace( /&quot;/g, '"' ),
		states = JSON.parse( states_json ),
		wrapper_selectors = '.easyreservations-address-fields';

	$( document.body ).on( 'change refresh', 'select.country_select, input.country_select', function() {
		// Grab wrapping element to target only stateboxes in same 'group'
		var $wrapper = $( this ).closest( wrapper_selectors );

		if ( ! $wrapper.length ) {
			$wrapper = $( this ).closest( '.form-row' ).parent();
		}

		var country = $( this ).val(),
			$statebox = $wrapper.find( '#state' ),
			$parent = $statebox.closest( '.form-row' ),
			input_name = $statebox.attr( 'name' ),
			input_id = $statebox.attr( 'id' ),
			inputClasses = $statebox.attr( 'data-input-classes' ),
			value = $statebox.val(),
			placeholder = $statebox.attr( 'placeholder' ) || $statebox.attr( 'data-placeholder' ) || '',
			$newstate;

		if ( states[ country ] ) {
			if ( $.isEmptyObject( states[ country ] ) ) {

				$newstate = $( '<input type="hidden" />' )
					.prop( 'id', input_id )
					.prop( 'name', input_name )
					.prop( 'placeholder', placeholder )
					.attr( 'data-input-classes', inputClasses )
					.addClass( 'hidden ' + inputClasses );

				$parent.hide().find( '.select2-container' ).remove();
				$statebox.replaceWith( $newstate );

				$( document.body ).trigger( 'country_to_state_changed', [ country, $wrapper ] );
			} else {
				var state = states[ country ],
					$defaultOption = $( '<option value=""></option>' ).text( er_country_select_params.i18n_select_state_text );

				if ( ! placeholder ) {
					placeholder = er_country_select_params.i18n_select_state_text;
				}

				$parent.show();

				if ( $statebox.is( 'input' ) ) {

					$newstate = $( '<select></select>' )
						.prop( 'id', input_id )
						.prop( 'name', input_name )
						.data( 'placeholder', placeholder )
						.attr( 'data-input-classes', inputClasses )
						.addClass( 'state_select ' + inputClasses );
					$statebox.replaceWith( $newstate );
					$statebox = $wrapper.find( '#state' );
				}

				$statebox.empty().append( $defaultOption );

				$.each( state, function( index ) {
					var $option = $( '<option></option>' )
						.prop( 'value', index )
						.text( state[ index ] );
					$statebox.append( $option );
				} );

				$statebox.val( value ).trigger( 'change' );

				$( document.body ).trigger( 'country_to_state_changed', [ country, $wrapper ] );
			}
		} else {
			if ( $statebox.is( 'select, input[type="hidden"]' ) ) {
				$newstate = $( '<input type="text" />' )
					.prop( 'id', input_id )
					.prop( 'name', input_name )
					.prop( 'placeholder', placeholder )
					.attr( 'data-input-classes', inputClasses )
					.addClass( 'input-text  ' + inputClasses );
				$parent.show().find( '.select2-container' ).remove();
				$statebox.replaceWith( $newstate );
				$( document.body ).trigger( 'country_to_state_changed', [ country, $wrapper ] );
			}
		}

		$( document.body ).trigger( 'er_country_to_state_changing', [ country, $wrapper ] );
	} );

	$( document.body ).on( 'er_address_i18n_ready', function() {
		// Init country selects with their default value once the page loads.
		$( wrapper_selectors ).each( function() {
			var $country_input = $( this ).find( '#country' );

			if ( 0 === $country_input.length || 0 === $country_input.val().length ) {
				return;
			}

			$country_input.trigger( 'refresh' );
		} );
	} );
} );
