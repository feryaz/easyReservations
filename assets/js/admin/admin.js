/* global er_admin_params */

( function( $, params, wp ) {
	$( function() {
		// Re-order buttons.
		$( '.er-item-reorder-nav' ).find( '.er-move-up, .er-move-down' ).on( 'click', function() {
			const moveBtn = $( this ),
				$row = moveBtn.closest( 'tr' );

			moveBtn.focus();

			const isMoveUp = moveBtn.is( '.er-move-up' ),
				isMoveDown = moveBtn.is( '.er-move-down' );

			if ( isMoveUp ) {
				const $previewRow = $row.prev( 'tr' );

				if ( $previewRow && $previewRow.length ) {
					$previewRow.before( $row );
					wp.a11y.speak( params.i18n_moved_up );
				}
			} else if ( isMoveDown ) {
				const $nextRow = $row.next( 'tr' );

				if ( $nextRow && $nextRow.length ) {
					$nextRow.after( $row );
					wp.a11y.speak( params.i18n_moved_down );
				}
			}

			moveBtn.focus(); // Re-focus after the container was moved.
			moveBtn.closest( 'table' ).trigger( 'updateMoveButtons' );
		} );

		$( document.body )

			.on( 'er_add_error_tip', function( e, element, errorType ) {
				const offset = element.position();

				if ( element.parent().find( '.er_error_tip' ).length === 0 ) {
					element.after( '<div class="er_error_tip ' + errorType + '">' + er_admin_params[ errorType ] + '</div>' );
					element.parent().find( '.er_error_tip' )
						.css( 'left', offset.left + element.width() - ( element.width() / 2 ) - ( $( '.er_error_tip' ).width() / 2 ) )
						.css( 'top', offset.top + element.height() )
						.fadeIn( '100' );
				}
			} )

			.on( 'er_remove_error_tip', function( e, element, errorType ) {
				element.parent().find( '.er_error_tip.' + errorType ).fadeOut( '100', function() {
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
				let decimailPoint = er_admin_params.decimal_point;

				if ( $( this ).is( '.er_input_price' ) || $( this ).is( '#refund_amount' ) ) {
					decimailPoint = er_admin_params.mon_decimal_point;
				}

				const regex = new RegExp( '[^\-0-9\%\\' + decimailPoint + ']+', 'gi' );
				const decimalRegex = new RegExp( '\\' + decimailPoint + '+', 'gi' );
				const value = $( this ).val();
				const fixedValue = value.replace( regex, '' ).replace( decimalRegex, decimailPoint );

				if ( value !== fixedValue ) {
					$( this ).val( fixedValue );
				}
			} )

			.on( 'keyup', '.er_input_price[type=text], .er_input_decimal[type=text], .er_input_country_iso[type=text], .er-order-totals #refund_amount[type=text]', function() {
				let regex,
					error,
					decimalRegex,
					checkDecimalNumbers = false;

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

				const value = $( this ).val();
				let fixedValue = value.replace( regex, '' );

				// Check if fixedValue have more than one decimal point.
				if ( checkDecimalNumbers && 1 < fixedValue.replace( decimalRegex, '' ).length ) {
					fixedValue = fixedValue.replace( decimalRegex, '' );
				}

				if ( value !== fixedValue ) {
					$( document.body ).triggerHandler( 'er_add_error_tip', [ $( this ), error ] );
				} else {
					$( document.body ).triggerHandler( 'er_remove_error_tip', [ $( this ), error ] );
				}
			} )
			.on( 'init_tooltips', function() {
				$( '.tips, .help_tip, .easyreservations-help-tip' ).tipTip( {
					'attribute': 'data-tip',
					'fadeIn': 50,
					'fadeOut': 50,
					'delay': 200,
				} );

				$( '.column-er_actions .er-action-button' ).tipTip( {
					'fadeIn': 50,
					'fadeOut': 50,
					'delay': 200,
				} );

				// Add tiptip to parent element for widefat tables
				$( '.parent-tips' ).each( function() {
					$( this ).closest( 'a, th' ).attr( 'data-tip', $( this ).data( 'tip' ) ).tipTip( {
						'attribute': 'data-tip',
						'fadeIn': 50,
						'fadeOut': 50,
						'delay': 200,
					} ).css( 'cursor', 'help' );
				} );
			} );

		$( document.body ).trigger( 'init_tooltips' );

		$( '.er_input_table.sortable tbody' ).sortable( {
			items: 'tr',
			cursor: 'move',
			axis: 'y',
			scrollSensitivity: 40,
			forcePlaceholderSize: true,
			helper: 'clone',
			opacity: 0.65,
			placeholder: 'er-metabox-sortable-placeholder',
			start: function( event, ui ) {
				ui.item.css( 'background-color', '#f6f6f6' );
			},
			stop: function( event, ui ) {
				ui.item.removeAttr( 'style' );
			},
		} );

		// Focus on inputs within the table if clicked instead of trying to sort.
		$( '.er_input_table.sortable tbody input' ).on( 'click', function() {
			$( this ).focus();
		} );

		$( '.er_input_table .remove_rows' ).on( 'click', function() {
			const current = $( this ).closest( '.er_input_table' ).find( 'tbody' ).find( 'tr.current' );

			if ( current.length > 0 ) {
				current.each( function() {
					$( this ).remove();
				} );
			}

			return false;
		} );

		$( '.er-item-reorder-nav' ).closest( 'table' ).on( 'updateMoveButtons', function() {
			const table = $( this ),
				lastRow = table.find( 'tbody tr:last' ),
				firstRow = table.find( 'tbody tr:first' );

			table.find( '.er-item-reorder-nav .er-move-disabled' ).removeClass( 'er-move-disabled' )
				.attr( { 'tabindex': '0', 'aria-hidden': 'false' } );
			firstRow.find( '.er-item-reorder-nav .er-move-up' ).addClass( 'er-move-disabled' )
				.attr( { 'tabindex': '-1', 'aria-hidden': 'true' } );
			lastRow.find( '.er-item-reorder-nav .er-move-down' ).addClass( 'er-move-disabled' )
				.attr( { 'tabindex': '-1', 'aria-hidden': 'true' } );
		} );

		$( '.er-item-reorder-nav' ).closest( 'table' ).trigger( 'updateMoveButtons' );

		let controlled = false,
			shifted = false,
			hasFocus = false;

		$( document.body ).on( 'keyup keydown', function( e ) {
			shifted = e.shiftKey;
			controlled = e.ctrlKey || e.metaKey;
		} );

		$( '.er_input_table' ).on( 'focus click', 'input, select', function( e ) {
			const thisTable = $( this ).closest( 'table, tbody' );
			const thisRow = $( this ).closest( 'tr' );

			if ( ( e.type === 'focus' && hasFocus !== thisRow.index() ) || ( e.type === 'click' && $( this ).is( ':focus' ) ) ) {
				hasFocus = thisRow.index();
				if ( ! shifted && ! controlled ) {
					$( 'tr', thisTable ).removeClass( 'current' ).removeClass( 'last_selected' );
					thisRow.addClass( 'current' ).addClass( 'last_selected' );
				} else if ( shifted ) {
					$( 'tr', thisTable ).removeClass( 'current' );
					thisRow.addClass( 'selected_now' ).addClass( 'current' );

					if ( $( 'tr.last_selected', thisTable ).length > 0 ) {
						if ( thisRow.index() > $( 'tr.last_selected', thisTable ).index() ) {
							$( 'tr', thisTable ).slice( $( 'tr.last_selected', thisTable ).index(), thisRow.index() ).addClass( 'current' );
						} else {
							$( 'tr', thisTable ).slice( thisRow.index(), $( 'tr.last_selected', thisTable ).index() + 1 ).addClass( 'current' );
						}
					}

					$( 'tr', thisTable ).removeClass( 'last_selected' );
					thisRow.addClass( 'last_selected' );
				} else {
					$( 'tr', thisTable ).removeClass( 'last_selected' );
					if ( controlled && $( this ).closest( 'tr' ).is( '.current' ) ) {
						thisRow.removeClass( 'current' );
					} else {
						thisRow.addClass( 'current' ).addClass( 'last_selected' );
					}
				}

				$( 'tr', thisTable ).removeClass( 'selected_now' );
			}
		} ).on( 'blur', 'input', function() {
			hasFocus = false;
		} );

		$( 'table.widefat tbody tr' ).each( function() {
			if ( $( 'table.widefat tbody tr' ).index( this ) % 2 === 0 ) {
				$( this ).addClass( 'alternate' );
			} else {
				$( this ).removeClass( 'alternate' );
			}
		} );
	} );
} )( jQuery, er_admin_params, wp );

function generateOptions( options, sel ) {
	let value = '';
	if ( typeof options === 'string' ) {
		const split = options.split( '-' );
		for ( let k = split[ 0 ]; k <= split[ 1 ]; k++ ) {
			const selected = sel && sel === k ? 'selected="selected"' : '';

			value += '<option value="' + k + '" ' + selected + '>' + k + '</option>';
		}
	} else {
		jQuery.each( options, function( ok, ov ) {
			const selected = sel && sel === ok ? 'selected="selected"' : '';

			value += '<option value="' + ok + '"' + selected + '>' + ov + '</option>';
		} );
	}

	return value;
}