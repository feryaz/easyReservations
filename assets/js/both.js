/* global er_both_params */

function easyFormatDate( date, format ) {
	if ( ! format ) {
		format = er_both_params.date_format;
	}
	if ( format === 'full' ) {
		format = er_both_params.date_format;

		if ( er_both_params.use_time ) {
			format += ' ' + er_both_params.time_format;
		}
	}

	var year = date instanceof Date ? date.getYear() : date.year(),
		month = date instanceof Date ? date.getMonth() + 1 : date.month() + 1,
		day = date instanceof Date ? date.getDate() : date.date(),
		hour = date instanceof Date ? date.getHours() : date.hour(),
		minute = date instanceof Date ? date.getMinutes() : date.minute();

	if ( year < 999 ) {
		year += 1900;
	}

	format = format.replace( "Y", year );
	format = format.replace( "m", easyAddZero( month ) );
	format = format.replace( "d", easyAddZero( day ) );
	format = easyFormatTime( hour, easyAddZero( minute ), format );

	return format
}

function easyFormatTime( hour, minute, format ) {
	if ( ! format ) {
		format = er_both_params.time_format;
	}
	if ( ! minute ) {
		minute = easyAddZero( hour instanceof Date ? hour.getMinutes() : hour.minute() );
		hour = hour instanceof Date ? hour.getHours() : hour.hour();
	}

	format = format.replace( "H", easyAddZero( hour ) );
	format = format.replace( "h", hour % 12 ? easyAddZero( hour % 12 ) : 12 );
	format = format.replace( "a", hour >= 12 ? 'pm' : 'am' );
	format = format.replace( "A", hour >= 12 ? 'PM' : 'AM' );
	format = format.replace( "i", minute );

	return format
}

function easyStringToDate( string ) {
	const explode = string.split( ' ' );

	let dateObject,
		year,
		month,
		day,
		hour = 0,
		minute = 0;

	if ( er_both_params.date_format === 'Y/m/d' ) {
		dateObject = /(\d{4})\/(\d{2})\/(\d{2})/.exec( explode[ 0 ] );
		day = dateObject[ 3 ];
		month = dateObject[ 2 ];
		year = dateObject[ 1 ];
	} else if ( er_both_params.date_format === 'm/d/Y' ) {
		dateObject = /(\d{2})\/(\d{2})\/(\d{4})/.exec( explode[ 0 ] );
		day = dateObject[ 2 ];
		month = dateObject[ 1 ];
		year = dateObject[ 3 ];
	} else if ( er_both_params.date_format === 'Y-m-d' ) {
		dateObject = /(\d{4})-(\d{2})-(\d{2})/.exec( explode[ 0 ] );
		day = dateObject[ 3 ];
		month = dateObject[ 2 ];
		year = dateObject[ 1 ];
	} else if ( er_both_params.date_format === 'd-m-Y' ) {
		dateObject = /(\d{2})-(\d{2})-(\d{4})/.exec( explode[ 0 ] );
		day = dateObject[ 1 ];
		month = dateObject[ 2 ];
		year = dateObject[ 3 ];
	} else {
		dateObject = /(\d{2}).(\d{2}).(\d{4})/.exec( explode[ 0 ] );
		day = dateObject[ 1 ];
		month = dateObject[ 2 ];
		year = dateObject[ 3 ];
	}

	if ( explode[ 1 ] ) {
		const explodeTime = explode[ 1 ].split( ':' );
		hour = parseInt( explodeTime[ 0 ], 10 );
		minute = parseInt( explodeTime[ 1 ], 10 );

		if ( explode[ 2 ] ) {
			hour = hour * 2;
		}
	}

	return new Date( parseInt( year, 10 ), parseInt( month, 10 ) - 1, parseInt( day, 10 ), hour, minute, 0, 0 );
}

function easyAddZero( nr ) {
	nr = parseInt( nr, 10 );
	if ( nr < 10 ) {
		nr = '0' + nr;
	}
	return nr;
}
