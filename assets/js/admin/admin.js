( function( $, params, wp ) {
	$( function() {
		// Re-order buttons.
		$( '.er-item-reorder-nav' ).find( '.er-move-up, .er-move-down' ).on( 'click', function() {
			var moveBtn = $( this ),
				$row    = moveBtn.closest( 'tr' );

			moveBtn.focus();

			var isMoveUp   = moveBtn.is( '.er-move-up' ),
				isMoveDown = moveBtn.is( '.er-move-down' );

			if ( isMoveUp ) {
				var $previewRow = $row.prev( 'tr' );

				if ( $previewRow && $previewRow.length ) {
					$previewRow.before( $row );
					wp.a11y.speak( params.i18n_moved_up );
				}
			} else if ( isMoveDown ) {
				var $nextRow = $row.next( 'tr' );

				if ( $nextRow && $nextRow.length ) {
					$nextRow.after( $row );
					wp.a11y.speak( params.i18n_moved_down );
				}
			}

			moveBtn.focus(); // Re-focus after the container was moved.
			moveBtn.closest( 'table' ).trigger( 'updateMoveButtons' );
		} );

		$( document.body )

			.on( 'er_add_error_tip', function( e, element, error_type ) {
				var offset = element.position();

				if ( element.parent().find( '.er_error_tip' ).length === 0 ) {
					element.after( '<div class="er_error_tip ' + error_type + '">' + er_admin_params[ error_type ] + '</div>' );
					element.parent().find( '.er_error_tip' )
						.css( 'left', offset.left + element.width() - ( element.width() / 2 ) - ( $( '.er_error_tip' ).width() / 2 ) )
						.css( 'top', offset.top + element.height() )
						.fadeIn( '100' );
				}
			} )

			.on( 'er_remove_error_tip', function( e, element, error_type ) {
				element.parent().find( '.er_error_tip.' + error_type ).fadeOut( '100', function() {
					$( this ).remove();
				} );
			} )

			.on( 'click', function() {
				$( '.er_error_tip' ).fadeOut( '100', function() {
					$( this ).remove();
				} );
			} )

			.on( 'blur', '.er_input_decimal[type=text], .er_input_price[type=text], .er_input_country_iso[type=text]', function() {
				$( '.er_error_tip' ).fadeOut( '100', function() {
					$( this ).remove();
				} );
			} )

			.on( 'change', '.er_input_price[type=text], .er_input_decimal[type=text], .er-order-totals #refund_amount[type=text]', function() {
				var regex, decimalRegex,
					decimailPoint = er_admin_params.decimal_point;

				if ( $( this ).is( '.er_input_price' ) || $( this ).is( '#refund_amount' ) ) {
					decimailPoint = er_admin_params.mon_decimal_point;
				}

				regex = new RegExp( '[^\-0-9\%\\' + decimailPoint + ']+', 'gi' );
				decimalRegex = new RegExp( '\\' + decimailPoint + '+', 'gi' );

				var value = $( this ).val();
				var newvalue = value.replace( regex, '' ).replace( decimalRegex, decimailPoint );

				if ( value !== newvalue ) {
					$( this ).val( newvalue );
				}
			} )

			.on( 'keyup', '.er_input_price[type=text], .er_input_decimal[type=text], .er_input_country_iso[type=text], .er-order-totals #refund_amount[type=text]', function() {
				var regex, error, decimalRegex;
				var checkDecimalNumbers = false;

				if ( $( this ).is( '.er_input_price' ) || $( this ).is( '#refund_amount' ) ) {
					checkDecimalNumbers = true;
					regex = new RegExp( '[^\-0-9\%\\' + er_admin_params.mon_decimal_point + ']+', 'gi' );
					decimalRegex = new RegExp( '[^\\' + er_admin_params.mon_decimal_point + ']', 'gi' );
					error = 'i18n_mon_decimal_error';
				} else if ( $( this ).is( '.er_input_country_iso' ) ) {
					regex = new RegExp( '([^A-Z])+|(.){3,}', 'im' );
					error = 'i18n_country_iso_error';
				} else {
					checkDecimalNumbers = true;
					regex = new RegExp( '[^\-0-9\%\\' + er_admin_params.decimal_point + ']+', 'gi' );
					decimalRegex = new RegExp( '[^\\' + er_admin_params.decimal_point + ']', 'gi' );
					error = 'i18n_decimal_error';
				}

				var value = $( this ).val();
				var newvalue = value.replace( regex, '' );

				// Check if newvalue have more than one decimal point.
				if ( checkDecimalNumbers && 1 < newvalue.replace( decimalRegex, '' ).length ) {
					newvalue = newvalue.replace( decimalRegex, '' );
				}

				if ( value !== newvalue ) {
					$( document.body ).triggerHandler( 'er_add_error_tip', [$( this ), error] );
				} else {
					$( document.body ).triggerHandler( 'er_remove_error_tip', [$( this ), error] );
				}
			} )
			.on( 'init_tooltips', function() {
				$( '.tips, .help_tip, .easyreservations-help-tip' ).tipTip( {
					'attribute': 'data-tip',
					'fadeIn':    50,
					'fadeOut':   50,
					'delay':     200
				} );

				$( '.column-er_actions .er-action-button' ).tipTip( {
					'fadeIn':  50,
					'fadeOut': 50,
					'delay':   200
				} );

				// Add tiptip to parent element for widefat tables
				$( '.parent-tips' ).each( function() {
					$( this ).closest( 'a, th' ).attr( 'data-tip', $( this ).data( 'tip' ) ).tipTip( {
						'attribute': 'data-tip',
						'fadeIn':    50,
						'fadeOut':   50,
						'delay':     200
					} ).css( 'cursor', 'help' );
				} );
			} );

		$( document.body ).trigger( 'init_tooltips' );

		$( '.er_input_table.sortable tbody' ).sortable( {
			items:                'tr',
			cursor:               'move',
			axis:                 'y',
			scrollSensitivity:    40,
			forcePlaceholderSize: true,
			helper:               'clone',
			opacity:              0.65,
			placeholder:          'er-metabox-sortable-placeholder',
			start:                function( event, ui ) {
				ui.item.css( 'background-color', '#f6f6f6' );
			},
			stop:                 function( event, ui ) {
				ui.item.removeAttr( 'style' );
			}
		} );

		// Focus on inputs within the table if clicked instead of trying to sort.
		$( '.er_input_table.sortable tbody input' ).on( 'click', function() {
			$( this ).focus();
		} );

		$( '.er_input_table .remove_rows' ).click( function() {
			var $tbody = $( this ).closest( '.er_input_table' ).find( 'tbody' );
			if ( $tbody.find( 'tr.current' ).length > 0 ) {
				var $current = $tbody.find( 'tr.current' );
				$current.each( function() {
					$( this ).remove();
				} );
			}
			return false;
		} );

		$( '.er-item-reorder-nav' ).closest( 'table' ).on( 'updateMoveButtons', function() {
			var table    = $( this ),
				lastRow  = $( this ).find( 'tbody tr:last' ),
				firstRow = $( this ).find( 'tbody tr:first' );

			table.find( '.er-item-reorder-nav .er-move-disabled' ).removeClass( 'er-move-disabled' )
				.attr( { 'tabindex': '0', 'aria-hidden': 'false' } );
			firstRow.find( '.er-item-reorder-nav .er-move-up' ).addClass( 'er-move-disabled' )
				.attr( { 'tabindex': '-1', 'aria-hidden': 'true' } );
			lastRow.find( '.er-item-reorder-nav .er-move-down' ).addClass( 'er-move-disabled' )
				.attr( { 'tabindex': '-1', 'aria-hidden': 'true' } );
		} );

		$( '.er-item-reorder-nav' ).closest( 'table' ).trigger( 'updateMoveButtons' );

		var controlled = false;
		var shifted = false;
		var hasFocus = false;

		$( document.body ).bind( 'keyup keydown', function( e ) {
			shifted = e.shiftKey;
			controlled = e.ctrlKey || e.metaKey;
		} );

		$( '.er_input_table' ).on( 'focus click', 'input, select', function( e ) {
			var $this_table = $( this ).closest( 'table, tbody' );
			var $this_row = $( this ).closest( 'tr' );

			if ( ( e.type === 'focus' && hasFocus !== $this_row.index() ) || ( e.type === 'click' && $( this ).is( ':focus' ) ) ) {
				hasFocus = $this_row.index();
				if ( !shifted && !controlled ) {
					$( 'tr', $this_table ).removeClass( 'current' ).removeClass( 'last_selected' );
					$this_row.addClass( 'current' ).addClass( 'last_selected' );
				} else if ( shifted ) {
					$( 'tr', $this_table ).removeClass( 'current' );
					$this_row.addClass( 'selected_now' ).addClass( 'current' );

					if ( $( 'tr.last_selected', $this_table ).length > 0 ) {
						if ( $this_row.index() > $( 'tr.last_selected', $this_table ).index() ) {
							$( 'tr', $this_table ).slice( $( 'tr.last_selected', $this_table ).index(), $this_row.index() ).addClass( 'current' );
						} else {
							$( 'tr', $this_table ).slice( $this_row.index(), $( 'tr.last_selected', $this_table ).index() + 1 ).addClass( 'current' );
						}
					}

					$( 'tr', $this_table ).removeClass( 'last_selected' );
					$this_row.addClass( 'last_selected' );
				} else {
					$( 'tr', $this_table ).removeClass( 'last_selected' );
					if ( controlled && $( this ).closest( 'tr' ).is( '.current' ) ) {
						$this_row.removeClass( 'current' );
					} else {
						$this_row.addClass( 'current' ).addClass( 'last_selected' );
					}
				}

				$( 'tr', $this_table ).removeClass( 'selected_now' );
			}
		} ).on( 'blur', 'input', function() {
			hasFocus = false;
		} );

		$( 'table.widefat tbody tr' ).each( function() {
			var i = $( 'table.widefat tbody tr' ).index( this );
			if ( i % 2 === 0 ) {
				$( this ).addClass( 'alternate' );
			} else {
				$( this ).removeClass( 'alternate' );
			}
		} );

	} );
} )( jQuery, er_admin_params, wp );

function generateOptions( options, sel ) {
	var value = '';
	if ( typeof options == "string" ) {
		var split = options.split( '-' );
		for ( var k = split[ 0 ]; k <= split[ 1 ]; k++ ) {
			var selected = '';
			if ( sel && sel == k ) {
				selected = 'selected="selected"';
			}
			value += '<option value="' + k + '" ' + selected + '>' + k + '</option>';
		}
	} else {
		jQuery.each( options, function( ok, ov ) {
			var selected = '';
			if ( sel && sel == ok ) {
				selected = ' selected="selected"';
			}
			value += '<option value="' + ok + '"' + selected + '>' + ov + '</option>';
		} );
	}
	return value;
}