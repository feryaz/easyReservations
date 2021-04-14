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
					$( this ).val( $( this ).data( 'original-value' ) ).trigger( 'change' );
				} else {
					$( this ).val( '' ).trigger( 'change' );
				}
			}
		} );

	$( 'body' ).on( 'click', function() {
		$( '.iris-picker' ).hide();
	} ).on( 'click', '.easyreservations-upl', function( e ) {
		// on upload button click

		e.preventDefault();

		const isImage = $( this ).hasClass( 'image' ),
			button = $( this ),
			uploader = wp.media( {
				library: {
					type: isImage ? 'image' : '',
				},
				multiple: false,
			} ).on( 'select', function() { // it also has "open" and "close" events
				const attachment = uploader.state().get( 'selection' ).first().toJSON();
				button.removeClass( 'button' ).css( 'display', 'inline-block' ).trigger( 'blur' ).next().show().next().val( attachment.id );

				if ( isImage ) {
					button.html( '<img src="' + attachment.url + '">' );
				} else {
					button.children().val( attachment.filename );
				}
			} ).open();
	} ).on( 'click', '.easyreservations-rmv', function( e ) {
		// on remove button click

		e.preventDefault();

		const button = $( this ),
			isImage = $( this ).hasClass( 'image' );

		button.next().val( '' ); // emptying the hidden field
		if ( isImage ) {
			button.hide().prev().addClass( 'button' ).css( 'display', 'inline-block' ).html( 'Upload image' );
		} else {
			button.hide().prev().children().val( '' );
		}
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
