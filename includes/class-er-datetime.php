<?php
/**
 * Wrapper for DateTie to keep time consistent.
 * User: feryaz
 * Date: 02.09.2018
 * Time: 14:49
 */

//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_DateTime extends DateTime {

	/**
	 * UTC Offset, if needed. Only used when a timezone is not set. When
	 * timezones are used this will equal 0.
	 *
	 * @var integer
	 */
	protected $utc_offset = 0;

	/**
	 * Output an ISO 8601 date string in local (WordPress) timezone.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->format( DATE_ATOM );
	}

	public function addSeconds( $seconds ) {
		if ( ! is_integer( $seconds ) ) {
			return $this;
		}

		$this->add( new DateInterval( 'PT' . $seconds . 'S' ) );

		return $this;
	}

	public function getTimestamp() {
		if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
			return parent::getTimestamp();
		}

		return $this->format( 'U' );
	}

	/**
	 * Set UTC offset - this is a fixed offset instead of a timezone.
	 *
	 * @param int $offset Offset.
	 */
	public function set_utc_offset( $offset ) {
		$this->utc_offset = intval( $offset );
	}

	/**
	 * Get UTC offset if set, or default to the DateTime object's offset.
	 */
	public function getOffset() {
		return $this->utc_offset ? $this->utc_offset : parent::getOffset();
	}

	/**
	 * Set timezone.
	 *
	 * @param DateTimeZone $timezone DateTimeZone instance.
	 *
	 * @return DateTime
	 */
	public function setTimezone( $timezone ) {
		$this->utc_offset = 0;

		return parent::setTimezone( $timezone );
	}

	/**
	 * Get the timestamp with the WordPress timezone offset added or subtracted.
	 *
	 * @return int
	 */
	public function getOffsetTimestamp() {
		return $this->getTimestamp() + $this->getOffset();
	}

	/**
	 * Format a date based on the offset timestamp.
	 *
	 * @param string $format Date format.
	 *
	 * @return string
	 */
	public function date( $format ) {
		return gmdate( $format, $this->getOffsetTimestamp() );
	}

	/**
	 * Return a localised date based on offset timestamp. Wrapper for date_i18n function.
	 *
	 * @param string $format Date format.
	 *
	 * @return string
	 */
	public function date_i18n( $format = 'Y-m-d' ) {
		return date_i18n( $format, $this->getOffsetTimestamp() );
	}
}
