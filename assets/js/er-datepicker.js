/*global er_date_picker_params */
jQuery( function ( $ ) {
	// er_date_picker_params is required to continue, ensure the object exists
	if ( typeof er_date_picker_params === 'undefined' ) {
		return false;
	}

	var default_args = er_datepicker_get_args();

	function init() {
		$( '.er-datepicker' ).each( function () {
			$( this ).attr( 'autocomplete', 'off' );

			var args = $.extend( {
				changeMonth: true,
				changeYear: true,
				showAnim: 'slideDown',
				beforeShow: function ( _, inst ) {
					console.log( 13 );

					inst.dpDiv.removeClass( 'ui-datepicker' ).addClass( 'easy-datepicker' ).addClass( 'easy-ui' );
				},
			}, default_args );

			if( $( this ).is('div') ){
				$( this ).removeClass( 'ui-datepicker' ).addClass( 'easy-datepicker' ).addClass( 'easy-ui' );
			}

			var data_format = $( this ).data( 'format' );
			if ( data_format && typeof data_format !== "undefined" ) {
				args.dateFormat = data_format;
			}

			var data_target = $( this ).data( 'target' );
			if ( data_target && typeof data_target !== "undefined" ) {
				args.onSelect = function ( selectedDate ) {
					var instance = $( this ).data( "datepicker" );
					var date = $.datepicker.parseDate( instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings );
					$( '#' + data_target ).datepicker( "option", "minDate", date );
				}
			}

			$( this ).datepicker( args );
		} );
	}

	init();

	$( document ).on( 'er-init-datepicker', function () {
		init();
	} )
} );

function er_datepicker_get_args() {
	var date_format = er_date_picker_params.date_format;
	switch ( date_format ) {
		case 'Y/m/d':
			date_format = 'yy/mm/dd';
			break;
		case 'm/d/Y':
			date_format = 'mm/dd/yy';
			break;
		case 'd-m-Y':
			date_format = 'dd-mm-yy';
			break;
		case 'Y-m-d':
			date_format = 'yy-mm-dd';
			break;
		default:
			date_format = 'dd.mm.yy';
			break;
	}

	var day_names = er_date_picker_params.day_names.slice();
	day_names.unshift( day_names[ 6 ] );
	day_names.length = 7;

	var day_names_short = er_date_picker_params.day_names_short.slice();
	day_names_short.unshift( day_names_short[ 6 ] );
	day_names_short.length = 7;

	var day_names_min = er_date_picker_params.day_names_min.slice();
	day_names_min.unshift( day_names_min[ 6 ] );
	day_names_min.length = 7;

	var args = {
		dateFormat: date_format,
		dayNames: day_names,
		dayNamesShort: day_names_short,
		dayNamesMin: day_names_min,
		monthNames: er_date_picker_params.month_names,
		monthNamesShort: er_date_picker_params.month_names_short,
		prevText: '',
		nextText: '',
	};

	if ( er_date_picker_params.is_frontend_request === 'yes' ) {
		var earliest_possible = parseInt( er_date_picker_params.earliest_possible, 10 ) / 86400;
		if( earliest_possible >= 1 ){
			args.minDate = earliest_possible;
		}
	}

	args.firstDay = parseInt( er_date_picker_params.start_of_week, 10 );

	return args;
}