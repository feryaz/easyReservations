/* global  */
( function( $, wp ) {
	// Color picker
	$( '.colorpick' )
		.iris( {
			change: function( event, ui ) {
				$( this ).parent().find( '.colorpickpreview' ).css( { backgroundColor: ui.color.toString() } );
			},
			hide: true,
			border: true,
		} )

		.on( 'click focus', function( event ) {
			event.stopPropagation();
			$( '.iris-picker' ).hide();
			$( this ).closest( 'td' ).find( '.iris-picker' ).show();
			$( this ).data( 'original-value', $( this ).val() );
		} )

		.on( 'change', function() {
			if ( $( this ).is( '.iris-error' ) ) {
				if ( $( this ).data( 'original-value' ).match( /^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/ ) ) {
					$( this ).val( $( this ).data( 'original-value' ) ).change();
				} else {
					$( this ).val( '' ).change();
				}
			}
		} );

	$( 'body' ).on( 'click', function() {
		$( '.iris-picker' ).hide();
	} );

	// Sorting
	$( 'table.er_gateways tbody' ).sortable( {
		items: 'tr',
		cursor: 'move',
		axis: 'y',
		handle: 'td.sort',
		scrollSensitivity: 40,
		helper: function( event, ui ) {
			ui.children().each( function() {
				$( this ).width( $( this ).width() );
			} );
			ui.css( 'left', '0' );
			return ui;
		},
		start: function( event, ui ) {
			ui.item.css( 'background-color', '#f6f6f6' );
		},
		stop: function( event, ui ) {
			ui.item.removeAttr( 'style' );
			ui.item.trigger( 'updateMoveButtons' );
		},
	} );

	$( 'code[data-tag]' ).on( 'click', function() {
		const $txt = $( '#' + $( this ).data( 'target' ) );
		const caretPos = $txt[ 0 ].selectionStart;
		const textAreaTxt = $txt.val();
		const txtToAdd = '[' + $( this ).data( 'tag' ) + ']';
		$txt.val( textAreaTxt.substring( 0, caretPos ) + txtToAdd + textAreaTxt.substring( caretPos ) );
	} );
}( jQuery, wp ) );
