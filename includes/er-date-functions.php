<?php
defined( 'ABSPATH' ) || exit;

/**
 * Get matching date and/or time pattern from based on interval
 *
 * @param int  $interval
 * @param bool $only_time if we only want the time pattern
 *
 * @return string date pattern
 */
function er_date_get_interval_pattern( $interval, $only_time = false ) {
	$time_pattern = '00:00';

	if ( $interval <= HOUR_IN_SECONDS ) {
		$minute = ':00';

		if ( $interval < HOUR_IN_SECONDS ) {
			$minute = ':i';
		}

		$time_format = explode( ' ', er_time_format() );

		if ( isset( $time_format[1] ) && ! empty( $time_format[1] ) ) {
			$time_pattern = "h" . $minute . ' ' . $time_format[1];
		} else {
			$time_pattern = "H" . $minute;
		}
	}

	if ( $only_time ) {
		return $time_pattern;
	} else {
		return er_date_format() . $time_pattern;
	}
}

/**
 * Get interval label
 *
 * @param int  $interval
 * @param int  $singular
 * @param bool $ucfirst wether to capitalize the first char
 *
 * @return string
 */
function er_date_get_interval_label( $interval = 0, $singular = 0, $ucfirst = false ) {
	if ( $interval == 1800 ) {
		$string = _n( 'half hour', 'half hours', $singular, 'easyReservations' );
	} elseif ( $interval == HOUR_IN_SECONDS ) {
		$string = _n( 'hour', 'hours', $singular, 'easyReservations' );
	} elseif ( $interval == DAY_IN_SECONDS ) {
		$string = _n( 'day', 'days', $singular, 'easyReservations' );
	} elseif ( $interval == 86401 ) {
		$string = _n( 'night', 'nights', $singular, 'easyReservations' );
	} elseif ( $interval == WEEK_IN_SECONDS ) {
		$string = _n( 'week', 'weeks', $singular, 'easyReservations' );
	} elseif ( $interval == MONTH_IN_SECONDS ) {
		$string = _n( 'month', 'months', $singular, 'easyReservations' );
	} else {
		$string = _n( 'time', 'times', $singular, 'easyReservations' );
	}

	$string = esc_html( $string );

	if ( $ucfirst ) {
		return ucfirst( $string );
	}

	return $string;
}

/**
 * Get day or month names
 *
 * @param int      $interval 0 for days, 1 for months
 * @param int      $substr number of characters to display 0=full
 * @param int|bool $single (optional) number of day/month
 *
 * @return array|string with name of date
 */
function er_date_get_label( $interval = 0, $substr = 0, $single = false, $addslashes = false ) {
	$name = array();
	if ( $interval == 0 ) {
		$name[] = esc_html__( 'Monday', 'easyReservations' );
		$name[] = esc_html__( 'Tuesday', 'easyReservations' );
		$name[] = esc_html__( 'Wednesday', 'easyReservations' );
		$name[] = esc_html__( 'Thursday', 'easyReservations' );
		$name[] = esc_html__( 'Friday', 'easyReservations' );
		$name[] = esc_html__( 'Saturday', 'easyReservations' );
		$name[] = esc_html__( 'Sunday', 'easyReservations' );
	} else {
		$name[] = esc_html__( 'January', 'easyReservations' );
		$name[] = esc_html__( 'February', 'easyReservations' );
		$name[] = esc_html__( 'March', 'easyReservations' );
		$name[] = esc_html__( 'April', 'easyReservations' );
		$name[] = esc_html__( 'May', 'easyReservations' );
		$name[] = esc_html__( 'June', 'easyReservations' );
		$name[] = esc_html__( 'July', 'easyReservations' );
		$name[] = esc_html__( 'August', 'easyReservations' );
		$name[] = esc_html__( 'September', 'easyReservations' );
		$name[] = esc_html__( 'October', 'easyReservations' );
		$name[] = esc_html__( 'November', 'easyReservations' );
		$name[] = esc_html__( 'December', 'easyReservations' );
	}

	foreach ( $name as $key => $day ) {
		if ( $substr > 0 ) {
			$name[ $key ] = er_trim_string( $day, $substr, '' );
		}

		if ( $addslashes ) {
			$name[ $key ] = addslashes( $name[ $key ] );
		}
	}

	if ( $single !== false ) {
		return $name[ $single ];
	}

	return $name;
}

/**
 * Get date format without year
 */
function er_date_get_format_without_year() {
	switch ( er_date_format() ) {
		case 'd.m.Y':
			return 'd.m';
			break;
		case 'Y-m-d':
			return 'm-d';
			break;
		case 'd-m-Y':
			return 'd-m';
			break;
		case 'm/d/Y':
		case 'Y/m/d':
			return 'm/d';
			break;
	}

	return er_date_format();
}

/**
 * Get current time based on website settings
 */
function er_get_time() {
	return (int) wp_date( 'U' );
}

/**
 * Get current time as ER_DateTime based on website settings
 */
function er_get_datetime() {
	return new ER_DateTime( wp_date( 'd.m.Y H:i' ) );
}

/**
 * @param ER_DateTime|string $date
 * @param int                $seconds
 *
 * @return ER_DateTime
 */
function er_date_add_seconds( $date, $seconds ) {
	if ( ! is_integer( $seconds ) ) {
		return $date;
	}

	if ( is_string( $date ) ) {
		$new_date = new ER_DateTime( $date );
	} else {
		$new_date = clone $date;
	}

	$new_date->add( new DateInterval( 'PT' . $seconds . 'S' ) );

	return $new_date;
}

/**
 * @param ER_DateTime|string $date
 * @param int                $seconds
 *
 * @return ER_DateTime
 */
function er_date_sub_seconds( $date, $seconds ) {
	if ( ! is_integer( $seconds ) ) {
		return $date;
	}

	if ( is_string( $date ) ) {
		$new_date = new ER_DateTime( $date );
	} else {
		$new_date = clone $date;
	}

	$new_date->sub( new DateInterval( 'PT' . $seconds . 'S' ) );

	return $new_date;
}

/**
 * @param string $date_string
 * @param int    $seconds
 *
 * @return ER_DateTime
 */
function er_date_create_and_add_seconds( $date_string, $seconds ) {
	if ( ! is_integer( $seconds ) ) {
		return new ER_DateTime( $date_string );
	}

	$date = new ER_DateTime( $date_string );
	$date->add( new DateInterval( 'PT' . $seconds . 'S' ) );

	return $date;
}