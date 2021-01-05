<?php
defined( 'ABSPATH' ) || exit;

/**
 * Outputs all queued notices on ER pages.
 */
function easyreservations_output_all_notices() {
	echo '<div class="easyreservations-notices-wrapper">';
	er_print_notices();
	echo '</div>';
}

/**
 * Get the count of notices added, either for all notices (default) or for one.
 * particular notice type specified by $notice_type.
 *
 * @param string $notice_type Optional. The name of the notice type - either error, success or notice.
 *
 * @return int
 */
function er_notice_count( $notice_type = '' ) {
	if ( ! did_action( 'easyreservations_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before easyreservations_init.', 'easyReservations' ), '6.0' );

		return false;
	}

	$notice_count = 0;
	$all_notices  = ER()->session->get( 'er_notices', array() );

	if ( isset( $all_notices[ $notice_type ] ) ) {
		$notice_count = count( $all_notices[ $notice_type ] );
	} elseif ( empty( $notice_type ) ) {
		foreach ( $all_notices as $notices ) {
			$notice_count += count( $notices );
		}
	}

	return $notice_count;
}

/**
 * Check if a notice has already been added.
 *
 * @param string $message The text to display in the notice.
 * @param string $notice_type Optional. The name of the notice type - either error, success or notice.
 *
 * @return bool
 */
function er_has_notice( $message, $notice_type = 'success' ) {
	if ( ! did_action( 'easyreservations_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before easyreservations_init.', 'easyReservations' ), '6.0' );

		return false;
	}

	$notices = ER()->session->get( 'er_notices', array() );
	$notices = isset( $notices[ $notice_type ] ) ? $notices[ $notice_type ] : array();

	return array_search( $message, wp_list_pluck( $notices, 'notice' ), true ) !== false;
}

/**
 * Add and store a notice.
 *
 * @param string $message The text to display in the notice.
 * @param string $notice_type Optional. The name of the notice type - either error, success or notice.
 * @param array  $data Optional notice data.
 */
function er_add_notice( $message, $notice_type = 'success', $data = array() ) {
	if ( ! did_action( 'easyreservations_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before easyreservations_init.', 'easyReservations' ), '6.0' );

		return;
	}

	$notices = ER()->session->get( 'er_notices', array() );

	$message = apply_filters( 'easyreservations_add_' . $notice_type, $message );

	if ( ! empty( $message ) ) {
		$notices[ $notice_type ][] = array(
			'notice' => $message,
			'data'   => $data,
		);
	}

	ER()->session->set( 'er_notices', $notices );
}

/**
 * Set all notices at once.
 *
 * @param array[] $notices Array of notices.
 */
function er_set_notices( $notices ) {
	if ( ! did_action( 'easyreservations_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before easyreservations_init.', 'easyReservations' ), '6.0' );

		return;
	}

	ER()->session->set( 'er_notices', $notices );
}

/**
 * Unset all notices.
 */
function er_clear_notices() {
	if ( ! did_action( 'easyreservations_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before easyreservations_init.', 'easyReservations' ), '6.0' );

		return;
	}

	ER()->session->set( 'er_notices', null );
}

/**
 * Prints messages and errors which are stored in the session, then clears them.
 *
 * @param bool $return true to return rather than echo.
 *
 * @return string|null
 */
function er_print_notices( $return = false ) {
	if ( ! did_action( 'easyreservations_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before easyreservations_init.', 'easyReservations' ), '6.0' );

		return;
	}

	$all_notices  = ER()->session->get( 'er_notices', array() );
	$notice_types = apply_filters( 'easyreservations_notice_types', array( 'error', 'success', 'notice' ) );

	// Buffer output.
	ob_start();

	foreach ( $notice_types as $notice_type ) {
		if ( er_notice_count( $notice_type ) > 0 ) {
			er_get_template(
				"notices/{$notice_type}.php",
				array(
					'notices' => array_filter( $all_notices[ $notice_type ] ),
				)
			);
		}
	}

	er_clear_notices();

	$notices = er_kses_notice( ob_get_clean() );

	if ( $return ) {
		return $notices;
	}

	echo $notices; // WPCS: XSS ok.
}

/**
 * Print a single notice immediately.
 *
 * @param string $message The text to display in the notice.
 * @param string $notice_type Optional. The singular name of the notice type - either error, success or notice.
 * @param array  $data Optional notice data.
 */
function er_print_notice( $message, $notice_type = 'success', $data = array() ) {
	if ( 'success' === $notice_type ) {
		$message = apply_filters( 'easyreservations_add_message', $message );
	}

	$message = apply_filters( 'easyreservations_add_' . $notice_type, $message );

	er_get_template(
		"notices/{$notice_type}.php",
		array(
			'notices' => array(
				array(
					'notice' => $message,
					'data'   => $data,
				),
			),
		)
	);
}

/**
 * Returns all queued notices, optionally filtered by a notice type.
 *
 * @param string $notice_type Optional. The singular name of the notice type - either error, success or notice.
 *
 * @return array[]
 */
function er_get_notices( $notice_type = '' ) {
	if ( ! did_action( 'easyreservations_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before easyreservations_init.', 'easyReservations' ), '6.0' );

		return;
	}

	$all_notices = ER()->session->get( 'er_notices', array() );

	if ( empty( $notice_type ) ) {
		$notices = $all_notices;
	} elseif ( isset( $all_notices[ $notice_type ] ) ) {
		$notices = $all_notices[ $notice_type ];
	} else {
		$notices = array();
	}

	return $notices;
}

/**
 * Add notices for WP Errors.
 *
 * @param WP_Error $errors Errors.
 */
function er_add_wp_error_notices( $errors ) {
	if ( is_wp_error( $errors ) && $errors->get_error_messages() ) {
		foreach ( $errors->get_error_messages() as $error ) {
			er_add_notice( $error, 'error' );
		}
	}
}

/**
 * Filters out the same tags as wp_kses_post, but allows tabindex for <a> element.
 *
 * @param string $message Content to filter through kses.
 *
 * @return string
 */
function er_kses_notice( $message ) {
	$allowed_tags = array_replace_recursive(
		wp_kses_allowed_html( 'post' ),
		array(
			'a' => array(
				'tabindex' => true,
			),
		)
	);

	/**
	 * Kses notice allowed tags.
	 *
	 * @param array[]|string $allowed_tags An array of allowed HTML elements and attributes, or a context name such as 'post'.
	 */
	return wp_kses( $message, apply_filters( 'easyreservations_kses_notice_allowed_tags', $allowed_tags ) );
}

/**
 * Get notice data attribute.
 *
 * @param array $notice Notice data.
 *
 * @return string
 */
function er_get_notice_data_attr( $notice ) {
	if ( empty( $notice['data'] ) ) {
		return;
	}

	$attr = '';

	foreach ( $notice['data'] as $key => $value ) {
		$attr .= sprintf(
			' data-%1$s="%2$s"',
			sanitize_title( $key ),
			esc_attr( $value )
		);
	}

	return $attr;
}