function easyFormatDate( date, format ) {
	if ( !format ) format = er_both_params.date_format;

	var year   = date.getYear(),
		month  = easyAddZero( date.getMonth() + 1 ),
		day    = easyAddZero( date.getDate() ),
		hour   = date.getHours(),
		minute = easyAddZero( date.getMinutes() );

	if ( year < 999 ) year += 1900;

	format = format.replace( "Y", year );
	format = format.replace( "m", month );
	format = format.replace( "d", day );
	format = easyFormatTime( hour, minute, format );

	return format
}

function easyFormatTime( hour, minute, format ) {
	if ( !format ) format = er_both_params.time_format;
	if ( !minute ) {
		minute = easyAddZero( hour.getMinutes() );
		hour = hour.getHours();
	}
	format = format.replace( "H", easyAddZero( hour ) );
	format = format.replace( "h", hour % 12 ? easyAddZero( hour % 12 ) : 12 );
	format = format.replace( "a", hour >= 12 ? 'pm' : 'am' );
	format = format.replace( "A", hour >= 12 ? 'PM' : 'AM' );
	format = format.replace( "i", minute );

	return format
}

function easyStringToDate( string ) {
	var regex = "/(?<day>\d{2}).(?<month>\d{2}).(?<year>\d{4})/";
	if ( er_both_params.date_format == 'Y/m/d' ) regex = "/(?<year>\d{4})\/(?<month>\d{2})\/(?<day>\d{2})/";
	else if ( er_both_params.date_format == 'm/d/Y' ) regex = "/(?<month>\d{2})\/(?<day>\d{2})\/(?<year>\d{4})/";
	else if ( er_both_params.date_format == 'Y-m-d' ) regex = "/(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})/";
	else if ( er_both_params.date_format == 'd-m-Y' ) regex = "/(?<day>\d{2})-(?<month>\d{2})-(?<year>\d{4})/";

	var hour = 0;
	var minute = 0;
	var explode = string.split( ' ' );
	var date_object = regex.exec( explode[ 0 ] );

	if ( explode[ 1 ] ) {
		var explode_time = explode[ 1 ].split( ':' );
		hour = parseInt( explode_time[ 0 ], 10 );
		minute = parseInt( explode_time[ 1 ], 10 );

		if ( explode[ 2 ] ) {
			hour = hour * 2;
		}
	}

	return new Date( parseInt( date_object.groups.year, 10 ), parseInt( date_object.groups.month, 10 ) - 1, parseInt( date_object.groups.day, 10 ), hour, minute, 0, 0 );
}

function easyAddZero( nr ) {
	nr = parseInt( nr, 10 );
	if ( nr < 10 ) nr = '0' + nr;
	return nr;
}
