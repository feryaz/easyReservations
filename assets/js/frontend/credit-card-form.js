jQuery( function( $ ) {
	$( '.er-credit-card-form-card-number' ).payment( 'formatCardNumber' );
	$( '.er-credit-card-form-card-expiry' ).payment( 'formatCardExpiry' );
	$( '.er-credit-card-form-card-cvc' ).payment( 'formatCardCVC' );

	$( document.body )
		.on( 'updated_checkout er-credit-card-form-init', function() {
			$( '.er-credit-card-form-card-number' ).payment( 'formatCardNumber' );
			$( '.er-credit-card-form-card-expiry' ).payment( 'formatCardExpiry' );
			$( '.er-credit-card-form-card-cvc' ).payment( 'formatCardCVC' );
		} )
		.trigger( 'er-credit-card-form-init' );
} );