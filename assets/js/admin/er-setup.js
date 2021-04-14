/*global er_setup_params, er_setup_currencies, er_base_state */
jQuery( function( $ ) {
	function blockWizardUI() {
		$('.er-setup-content').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
	}

	$( '.button-next' ).on( 'click', function() {
		var form = $( this ).parents( 'form' ).get( 0 );

		if ( ( 'function' !== typeof form.checkValidity ) || form.checkValidity() ) {
			blockWizardUI();
		}

		return true;
	} );

	$( 'form.address-step' ).on( 'submit', function( e ) {
		var form = $( this );
		if ( ( 'function' !== typeof form.checkValidity ) || form.checkValidity() ) {
			blockWizardUI();
		}

		e.preventDefault();
		$('.er-setup-content').unblock();

		$( this ).ERBackboneModal( {
			template: 'er-modal-tracking-setup'
		} );

		$( document.body ).on( 'er_backbone_modal_response', function() {
			form.unbind( 'submit' ).trigger( 'submit' );
		} );

		$( '#er_tracker_checkbox_dialog' ).on( 'change', function( e ) {
			var eventTarget = $( e.target );
			$( '#er_tracker_checkbox' ).prop( 'checked', eventTarget.prop( 'checked' ) );
		} );

		$( '#er_tracker_submit' ).on( 'click', function () {
			form.unbind( 'submit' ).trigger( 'submit' );
		} );

		return true;
	} );

	$( '#store_country' ).on( 'change', function() {
		// Prevent if we don't have the metabox data
		if ( er_setup_params.states === null ){
			return;
		}

		var $this         = $( this ),
			country       = $this.val(),
			$state_select = $( '#store_state' );

		if ( ! $.isEmptyObject( er_setup_params.states[ country ] ) ) {
			var states = er_setup_params.states[ country ];

			$state_select.empty();

			$.each( states, function( index ) {
				$state_select.append( $( '<option value="' + index + '">' + states[ index ] + '</option>' ) );
			} );

			$( '.store-state-container' ).show();
			$state_select.selectWoo().val( er_base_state ).trigger( 'change' ).prop( 'required', true );
		} else {
			$( '.store-state-container' ).hide();
			$state_select.empty().val( '' ).trigger( 'change' ).prop( 'required', false );
		}

		$( '#currency_code' ).val( er_setup_currencies[ country ] ).trigger( 'change' );
	} );

	/* Setup postcode field and validations */
	$( '#store_country' ).on( 'change', function() {
		if ( ! er_setup_params.postcodes ) {
			return;
		}

		var $this                 = $( this ),
			country               = $this.val(),
			$store_postcode_input = $( '#store_postcode' ),
			country_postcode_obj  = er_setup_params.postcodes[ country ];

		// Default to required, if its unknown whether postcode is required or not.
		if ( $.isEmptyObject( country_postcode_obj ) || country_postcode_obj.required  ) {
			$store_postcode_input.attr( 'required', 'true' );
		} else {
			$store_postcode_input.removeAttr( 'required' );
		}
	} );

	$( '#store_country' ).trigger( 'change' );

	$( document.body ).on( 'init_tooltips', function() {
		$( '.help_tip' ).tipTip( {
			'attribute': 'data-tip',
			'fadeIn': 50,
			'fadeOut': 50,
			'delay': 200,
			'defaultPosition': 'top'
		} );
	} ).trigger( 'init_tooltips' );
} );
