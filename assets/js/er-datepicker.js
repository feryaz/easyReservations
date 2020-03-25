/*global er_date_picker_params */
jQuery( function( $ ) {
	// er_date_picker_params is required to continue, ensure the object exists
	if ( typeof er_date_picker_params === 'undefined' ) {
		return false;
	}

	const defaultArgs = erDatepickerArgs();

	function init() {
		$( '.er-datepicker' ).each( function() {
			$( this ).attr( 'autocomplete', 'off' );

			const dataTarget = $( this ).attr( 'data-target' ),
				dataFormat = $( this ).attr( 'data-format' ),
				args = $.extend( {
					changeMonth: true,
					changeYear: true,
					showAnim: 'slideDown',
					beforeShow: function( _, inst ) {
						inst.dpDiv.removeClass( 'ui-datepicker' ).addClass( 'easy-datepicker' ).addClass( 'easy-ui' );
					},
				}, defaultArgs );

			if ( $( this ).is( 'div' ) ) {
				$( this ).removeClass( 'ui-datepicker' ).addClass( 'easy-datepicker' ).addClass( 'easy-ui' );
			}

			if ( dataFormat && typeof dataFormat !== "undefined" ) {
				args.dateFormat = dataFormat;
			}

			if ( dataTarget && typeof dataTarget !== "undefined" ) {
				args.onSelect = function( selectedDate ) {
					const instance = $( this ).data( 'datepicker' );
					const date = $.datepicker.parseDate( instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings );
					$( '#' + dataTarget ).datepicker( 'option', 'minDate', date );
				};
			}

			$( this ).datepicker( args );
		} );
	}

	init();

	$( document ).on( 'er-init-datepicker', function() {
		init();
	} );
} );

function erDatepickerArgs() {
	let dateFormat = er_date_picker_params.date_format;

	switch ( dateFormat ) {
		case 'Y/m/d':
			dateFormat = 'yy/mm/dd';
			break;
		case 'm/d/Y':
			dateFormat = 'mm/dd/yy';
			break;
		case 'd-m-Y':
			dateFormat = 'dd-mm-yy';
			break;
		case 'Y-m-d':
			dateFormat = 'yy-mm-dd';
			break;
		default:
			dateFormat = 'dd.mm.yy';
			break;
	}

	const dayNames = er_date_picker_params.day_names.slice();
	dayNames.unshift( dayNames[ 6 ] );
	dayNames.length = 7;

	const dayNamesShort = er_date_picker_params.day_names_short.slice();
	dayNamesShort.unshift( dayNamesShort[ 6 ] );
	dayNamesShort.length = 7;

	const dayNamesMin = er_date_picker_params.day_names_min.slice();
	dayNamesMin.unshift( dayNamesMin[ 6 ] );
	dayNamesMin.length = 7;

	const args = {
		dateFormat: dateFormat,
		dayNames: dayNames,
		dayNamesShort: dayNamesShort,
		dayNamesMin: dayNamesMin,
		monthNames: er_date_picker_params.month_names,
		monthNamesShort: er_date_picker_params.month_names_short,
		prevText: '',
		nextText: '',
	};

	if ( er_date_picker_params.is_frontend_request === 'yes' ) {
		const earliestPossible = parseInt( er_date_picker_params.earliest_possible, 10 ) / 86400;
		if ( earliestPossible >= 1 ) {
			args.minDate = earliestPossible;
		}
	}

	args.firstDay = parseInt( er_date_picker_params.start_of_week, 10 );

	return args;
}
