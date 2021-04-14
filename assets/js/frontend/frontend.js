/* global Cookies */
jQuery( function( $ ) {
	// Orderby
	$( '.easyreservations-ordering' ).on( 'change', 'select.orderby', function() {
		$( this ).closest( 'form' ).trigger( 'submit' );
	} );

	// Make form field descriptions toggle on focus.
	if ( $( '.easyreservations-input-wrapper span.description' ).length ) {
		$( document.body ).on( 'click', function() {
			$( '.easyreservations-input-wrapper span.description:visible' ).prop( 'aria-hidden', true ).slideUp( 250 );
		} );
	}

	$( '.easyreservations-input-wrapper' ).on( 'click', function( event ) {
		event.stopPropagation();
	} );

	$( '.easyreservations-input-wrapper :input' )
		.on( 'keydown', function( event ) {
			const input = $( this ),
				parent = input.parent(),
				description = parent.find( 'span.description' );

			if ( 27 === event.which && description.length && description.is( ':visible' ) ) {
				description.prop( 'aria-hidden', true ).slideUp( 250 );
				event.preventDefault();
				return false;
			}
		} )
		.on( 'click focus', function() {
			const input = $( this ),
				parent = input.parent(),
				description = parent.find( 'span.description' );

			parent.addClass( 'currentTarget' );

			$( '.easyreservations-input-wrapper:not(.currentTarget) span.description:visible' ).prop( 'aria-hidden', true ).slideUp( 250 );

			if ( description.length && description.is( ':hidden' ) ) {
				description.prop( 'aria-hidden', false ).slideDown( 250 );
			}

			parent.removeClass( 'currentTarget' );
		} );

	// Show password visiblity hover icon on easyreservations forms
	$( '.easyreservations form .easyreservations-Input[type="password"]' ).wrap( '<span class="er-password-input"></span>' );
	// Add 'er-password-input' class to the password wrapper in checkout page.
	$( '.easyreservations form input' ).filter( ':password' ).parent( 'span' ).addClass( 'er-password-input' );
	$( '.er-password-input' ).append( '<span class="er-show-password-input"></span>' );

	$( '.er-show-password-input' ).on( 'click',
		function() {
			$( this ).toggleClass( 'display-password' );
			if ( $( this ).hasClass( 'display-password' ) ) {
				$( this ).siblings( [ 'input[name="password"]', 'input[type="password"]' ] ).prop( 'type', 'text' );
			} else {
				$( this ).siblings( 'input[type="text"]' ).prop( 'type', 'password' );
			}
		}
	);
} );