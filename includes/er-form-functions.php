<?php
defined( 'ABSPATH' ) || exit;

/**
 * Generate single form field
 *
 * @param array $value
 * @param bool  $return
 *
 * @return string
 */
function er_form_get_field( $value, $return = false ) {
	if ( ! isset( $value['type'] ) ) {
		return '';
	}

	if ( $return ) {
		ob_start();
	}

	// Custom attribute handling.
	$custom_attributes = array();

	if ( ! isset( $value['placeholder'] ) ) {
		$value['placeholder'] = '';
	}
	if ( ! isset( $value['class'] ) ) {
		$value['class'] = '';
	}
	if ( ! isset( $value['css'] ) ) {
		$value['css'] = '';
	}

	if ( ! empty( $value['attributes'] ) && is_array( $value['attributes'] ) ) {
		foreach ( $value['attributes'] as $attribute => $attribute_value ) {
			if ( $attribute === 'disabled' ) {
				if ( ! empty( $attribute_value ) ) {
					$custom_attributes[] = 'disabled';
				}
			} else {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}
	}

	if ( ! isset( $value['input-group'] ) || $value['input-group'] == 'start' ) {
		if ( isset( $value['together'] ) ) {
			?><span class="input-wrapper"><?php
		}
	}

	if ( isset( $value['icon'] ) ) {
		?>
        <span class="input-box <?php echo isset( $value['icon-class'] ) ? esc_attr( $value['icon-class'] ) : ''; ?>"><?php esc_attr_e( $value['icon'] ); ?></span><?php
	}

	switch ( $value['type'] ) {
		case 'text':
		case 'password':
		case 'datetime':
		case 'datetime-local':
		case 'date':
		case 'month':
		case 'time':
		case 'week':
		case 'number':
		case 'email':
		case 'url':
		case 'tel':
			?><input
            name="<?php echo esc_attr( $value['id'] ); ?>"
            id="<?php echo esc_attr( $value['id'] ); ?>"
            type="<?php echo esc_attr( $value['type'] ); ?>"
            style="<?php echo esc_attr( $value['css'] ); ?>"
            value="<?php echo esc_attr( $value['value'] ); ?>"
            class="<?php echo esc_attr( $value['class'] ); ?>"
            placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
			<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok.
			?>
            />
			<?php
			break;

		case 'price':
			?><input
            name="<?php echo esc_attr( $value['id'] ); ?>"
            id="<?php echo esc_attr( $value['id'] ); ?>"
            type="text"
            style="<?php echo esc_attr( $value['css'] ); ?>"
            value="<?php echo esc_attr( $value['value'] ); ?>"
            class="<?php echo esc_attr( $value['class'] ); ?> er_input_price"
            placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
			<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok.
			?>
            />
			<?php
			break;

		case 'hidden':
			?><input
            name="<?php echo esc_attr( $value['id'] ); ?>"
            id="<?php echo esc_attr( $value['id'] ); ?>"
            type="hidden"
            value="<?php echo esc_attr( $value['value'] ); ?>"
			<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok.
			?>
            />
			<?php
			break;

		case 'color':
			?><input
            name="<?php echo esc_attr( $value['id'] ); ?>"
            id="<?php echo esc_attr( $value['id'] ); ?>"
            type="text"
            dir="ltr"
            style="<?php echo esc_attr( $value['css'] ); ?>"
            value="<?php echo esc_attr( $value['value'] ); ?>"
            class="<?php echo esc_attr( $value['class'] ); ?>colorpick"
            placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
			<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok.
			?>
            />
			<?php
			break;

		case 'textarea':
			?><textarea
            name="<?php echo esc_attr( $value['id'] ); ?>"
            id="<?php echo esc_attr( $value['id'] ); ?>"
            style="<?php echo esc_attr( $value['css'] ); ?>"
            class="<?php echo esc_attr( $value['class'] ); ?>"
            placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
			<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok.
			?>
            ><?php echo esc_textarea( $value['value'] ); // WPCS: XSS ok.
			?></textarea>
			<?php
			break;

		case 'select':
		case 'multiselect':
			?><select
            name="<?php echo esc_attr( $value['id'] ); ?><?php echo ( 'multiselect' === $value['type'] ) ? '[]' : ''; ?>"
            id="<?php echo esc_attr( $value['id'] ); ?>"
            style="<?php echo esc_attr( $value['css'] ); ?>"
            class="<?php echo esc_attr( $value['class'] ); ?>"
			<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok.
			?>
			<?php echo 'multiselect' === $value['type'] ? 'multiple="multiple"' : ''; ?>
            >
			<?php
			foreach ( $value['options'] as $key => $val ) {
				?>
                <option value="<?php echo esc_attr( $key ); ?>"
                    <?php

                    if ( is_array( $value['value'] ) ) {
	                    selected( in_array( (string) $key, $value['value'], true ), true );
                    } else {
	                    selected( $value['value'], (string) $key );
                    }

                    ?>
                ><?php echo esc_html( $val ); ?></option>
				<?php
			}
			?>
            </select>
			<?php
			break;

		case 'radio':
			?>
            <ul>
                <?php
                foreach ( $value['options'] as $key => $val ) {
	                ?>
                    <li>
                        <label><input
                                name="<?php echo esc_attr( $value['id'] ); ?>"
                                value="<?php echo esc_attr( $key ); ?>"
                                type="radio"
                                style="<?php echo esc_attr( $value['css'] ); ?>"
                                class="<?php echo esc_attr( $value['class'] ); ?>"
                                <?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
		                        <?php checked( $key, $value['value'] ); ?>
                            /> <?php echo esc_html( $val ); ?></label>
                    </li>
	                <?php
                }
                ?>
                </ul>
			<?php
			break;

		case 'checkbox':
			?><input
            name="<?php echo esc_attr( isset( $value['name'] ) ? $value['name'] : $value['id'] ); ?>"
            id="<?php echo esc_attr( $value['id'] ); ?>"
            type="checkbox"
            class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
            value="<?php echo esc_attr( $value['default'] === 'no' ? 'yes' : $value['default'] ); ?>"
			<?php checked( true, $value['value'] === 1 || $value['value'] === 'yes' || ( $value['value'] === $value['default'] && $value['default'] !== 'no' ) ); ?>
			<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok.
			?>
            />
			<?php
			break;
	}

	if ( isset( $value['icon-after'] ) ) {
		?>
        <span class="input-box <?php echo isset( $value['icon-class'] ) ? esc_attr( $value['icon-class'] ) : ''; ?>"><?php esc_attr_e( $value['icon-after'] ); ?></span><?php
	}

	if ( ! isset( $value['input-group'] ) || $value['input-group'] == 'end' ) {
		if ( isset( $value['together'] ) ) {
			?></span><?php
		}
	}

	if ( $return ) {
		return ob_get_clean();
	}
}

/**
 * Generate from id from title and sanitize them
 *
 * @param string $title
 *
 * @return string
 */
function er_sanitize_form_id( $title ) {
	return apply_filters( 'easyreservations_form_id', preg_replace( '/[^A-Za-z0-9\-]/', '', str_replace( ' ', '-', $title ) ) );
}

/**
 * Parse form templates
 *
 * @param string      $content form template
 * @param bool        $use_pattern whether to use default pattern
 * @param bool|string $define only return defined tags
 *
 * @return array
 */
function er_form_template_parser( $content, $use_pattern = false, $define = false ) {
	if ( $use_pattern ) {
		$pattern = '\\[';                         // Opening bracket
		if ( $define ) {
			$pattern .= '(\\[?)'                     // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
			            . '(' . $define . ')';                     // 2: Shortcode name
		}
		$pattern .= '\\b'                        // Word boundary
		            . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
		            . '[^\\]\\/]*'                   // Not a closing bracket or forward slash
		            . '(?:'
		            . '\\/(?!\\])'               // A forward slash not followed by a closing bracket
		            . '[^\\]\\/]*'               // Not a closing bracket or forward slash
		            . ')*?'
		            . ')'
		            . '(?:'
		            . '(\\/)'                        // 4: Self closing tag ...
		            . '\\]'                          // ... and closing bracket
		            . '|'
		            . '\\]'                          // Closing bracket
		            . '(?:'
		            . '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
		            . '[^\\[]*+'             // Not an opening bracket
		            . '(?:'
		            . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
		            . '[^\\[]*+'         // Not an opening bracket
		            . ')*+'
		            . ')'
		            . '\\[\\/\\2\\]'             // Closing shortcode tag
		            . ')?'
		            . ')'
		            . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
		preg_match_all( '/' . $pattern . '/s', $content, $match );
		if ( $define ) {
			$return = $match[3];
		} else {
			$return = $match[1];
		}
		$return = array_merge( $return, array() );
	} else {
		preg_match_all( '/\[.*\]/U', $content, $match );
		$return = $match[0];
	}
	$return = str_replace( array( '[', ']' ), '', $return );

	return $return;
}

/**
 * Get options for resources select
 *
 * @param bool       $check whether to check resources permission if current user can edit it
 * @param bool|array $exclude array of resource ids to exclude
 * @param bool|array $include array of resource ids to include
 * @param bool       $addslashes
 *
 * @return array
 */
function er_form_resources_options( $check = false, $exclude = false, $include = false, $addslashes = false ) {
	$resource_options = array();
	if ( $check ) {
		$resources = ER()->resources()->get_visible();
	} else {
		$resources = ER()->resources()->get();
	}

	foreach ( $resources as $resource ) {
		if ( ( ! $exclude || ! in_array( $resource->get_id(), $exclude ) ) && ( ! $include || in_array( $resource->get_id(), $include ) ) ) {
			if ( $addslashes ) {
				$title = addslashes( $resource->get_title() );
			} else {
				$title = $resource->get_title();
			}

			$resource_options[ $resource->get_id() ] = __( stripslashes( $title ) );
		}
	}

	return $resource_options;
}

/**
 * Get options for form template select
 *
 * @return array
 */
function er_form_template_options() {
	global $wpdb;

	$return  = array( '' => __( "Default form", "easyReservations" ) );
	$results = $wpdb->get_results( "SELECT option_name FROM " . $wpdb->prefix . "options WHERE option_name like 'reservations_form_%'" ); // Get User made Forms

	foreach ( $results as $result ) {
		$cute = str_replace( 'reservations_form_', '', $result->option_name );
		if ( ! empty( $cute ) ) {
			$return[ $cute ] = ucfirst( $cute );
		}
	}

	return $return;
}

/**
 * Return numbered options for selects
 *
 * @param           $min
 * @param           $max
 * @param int|float $increment
 *
 * @return array
 */

function er_form_number_options( $min, $max, $increment = 1 ) {
	$return = array();
	if ( is_array( $min ) ) {
		$plus = $min[1];
		$min  = $min[0];
	}
	for ( $num = $min; $num <= $max; $num = $num + ( $increment ? $increment : 1 ) ) {
		$num_display = $num;
		$num_option  = $num;

		if ( strlen( $min ) == strlen( $max ) && $min < 10 && $max > 9 && $num < 10 && $num > $min ) {
			$num_display = '0' . $num;
		} elseif ( isset( $plus ) ) {
			$num_option += $plus;
		}

		if ( $num_option === '00' ) {
			$num_option = 0;
		}

		$return[ $num_option ] = $num_display;
	}

	return $return;
}

/**
 * Get options for time
 *
 * @param bool $increment
 * @param bool $range
 *
 * @return array
 */
function er_form_time_options( $increment = false, $range = false ) {
	$minmax = false;
	if ( $range ) {
		$minmax = array();
		$ranges = explode( ';', $range );
		foreach ( $ranges as $range ) {
			if ( ! empty( $range ) ) {
				$range    = explode( '-', $range );
				$minmax[] = array( intval( $range[0] ), intval( $range[1] ) );
			}
		}
	}
	$zero   = strtotime( '20.10.2010 00:00:00' );
	$return = array();
	$max    = $increment ? ( 60 / $increment * 23 ) : 23;
	for ( $i = 0; $i <= $max; $i ++ ) {
		$value = $i;
		$time  = $zero + ( $i * HOUR_IN_SECONDS );
		if ( $increment ) {
			$time  = $zero + ( $i * $increment * 60 );
			$value = date( 'H-i', $time );
		}
		if ( $minmax ) {
			$passed = false;
			$hour   = date( 'H', $time );
			foreach ( $minmax as $m ) {
				if ( $hour >= $m[0] && $hour <= $m[1] ) {
					$passed = true;
					break;
				}
			}
			if ( ! $passed ) {
				continue;
			}
		}
		$return[ $value ] = date( er_time_format(), $time );
	}

	return $return;
}

/**
 * Get checkboxes for each day of the week
 *
 * @param string $name
 * @param int    $selected 0 = all
 *
 * @return string
 */
function er_form_days_options( $name, $selected ) {
	$return = '';
	for ( $i = 1; $i < 8; $i ++ ) {
		$return .= '<label class="wrapper days-option">';
		$return .= '<input type="checkbox" name="' . esc_attr( $name ) . '" value = "' . esc_attr( $i ) . '" ';
		$return .= checked( ( $selected === 0 || ( is_array( $selected ) && in_array( $i, $selected ) ) ) ? true : false, true, false ) . '>';
		$return .= er_date_get_label( 0, 0, $i - 1 ) . '</label>';
	}

	return $return;
}

/**
 * Get options for roles
 *
 * @return array
 */
function er_form_roles_options() {
	$roles   = get_editable_roles();
	$options = array();

	foreach ( $roles as $key => $role ) {
		if ( isset( $role['capabilities'] ) ) {
			$da = key( $role['capabilities'] );
			if ( is_numeric( $da ) ) {
				$value = $role['capabilities'][0];
			} else {
				$value = $da;
			}
			$options[ $value ] = ucfirst( $key );
		}
	}

	return $options;
}

/**
 * @param               $line
 * @param               $form_hash
 * @param ER_Order|bool $order
 *
 * @return array|string
 */
function er_form_generate_checkout_field( $line, $form_hash, $order = false ) {
	$tag          = shortcode_parse_atts( $line );
	$value        = isset( $tag['value'] ) ? $tag['value'] : '';
	$title        = isset( $tag['title'] ) ? $tag['title'] : '';
	$form_element = '';

	$type = sanitize_key( $tag[0] );

	switch ( $type ) {
		case "default":
			if ( $order ) {
				$value = $order->get_address_prop( 'country' );
			}

			return array(
				'id'          => $type,
				'type'        => 'select',
				'value'       => isset( $_POST['country'] ) ? sanitize_text_field( $_POST['country'] ) : ( $value ? $value : ER()->customer->get_address_country() ),
				'class'       => 'country_select',
				'title'       => $title,
				'options'     => ER()->countries->get_countries(),
				'css'         => isset( $tag['style'] ) ? $tag['style'] : '',
				'placeholder' => isset( $tag['placeholder'] ) ? $tag['placeholder'] : ''
			);
			break;
	}

	return $form_element;
}

/**
 * @param                     $line
 * @param                     $input_id_prefix
 * @param                     $form_hash
 * @param bool|ER_Reservation $reservation
 *
 * @return array|string
 */
function er_form_generate_reservation_field( $line, $input_id_prefix, $form_hash, $reservation = false ) {
	$tag               = shortcode_parse_atts( $line );
	$value             = isset( $tag['value'] ) ? $tag['value'] : '';
	$title             = isset( $tag['title'] ) ? esc_attr( $tag['title'] ) : '';
	$custom_attributes = array();

	if ( isset( $tag['disabled'] ) ) {
		$array = array( 'units', 'nights', 'times', 'persons', 'adults', 'children', 'country', 'resources', 'rooms' );
		if ( in_array( $tag[0], $array ) ) {
			$custom_attributes['disabled'] = 'disabled';
		} else {
			$custom_attributes['readonly'] = 'readonly';
		}
	}

	switch ( $tag[0] ) {
		case "date":
			$uid = uniqid();
			$opt = array(
				'resource'        => isset( $tag['resource'] ) ? intval( $tag['resource'] ) : 0,
				'arrivalHour'     => isset( $tag['arrivalhour'] ) ? intval( $tag['arrivalhour'] ) : false,
				'arrivalMinute'   => isset( $tag['arrivalminute'] ) ? intval( $tag['arrivalminute'] ) : 0,
				'departureHour'   => isset( $tag['departurehour'] ) ? intval( $tag['departurehour'] ) : false,
				'departureMinute' => isset( $tag['departureminute'] ) ? intval( $tag['departureminute'] ) : 0,
				'departure'       => isset( $tag['departure'] ) ? 1 : 0,
				'init'            => isset( $_POST['arrival'] ) ? 0 : 1,
				'form'            => $form_hash,
				'time'            => isset( $tag['time'] ) ? 1 : 0,
				'price'           => isset( $tag['price'] ) ? 1 : 0,
			);

			wp_enqueue_script( 'er-date-selection' );
			er_enqueue_js( 'jQuery(document).ready(function(){jQuery("#easy_selection_' . esc_attr( $uid ) . '").dateSelection(' . wp_json_encode( $opt ) . ');});' );

			return er_get_template_html( 'form/date-selection.php', array(
				'uid'               => $uid,
				'time_selection'    => 'bla',
				'display_departure' => $opt['departure'],
			) );

			break;

		case "arrival":
		case "date-from":
			if ( $reservation ) {
				$value = date( er_date_format(), $reservation->get_arrival() );
			}

			if ( empty( $value ) ) {
				$value = date( er_date_format(), er_get_time() + DAY_IN_SECONDS );
			} elseif ( preg_match( '/\+{1}[0-9]+/i', $value ) ) {
				$cutplus = str_replace( '+', '', $value );
				$value   = date( er_date_format(), er_get_time() + ( $cutplus * DAY_IN_SECONDS ) );
			}

			$custom_attributes = '';
			$custom_attributes .= isset( $tag["days"] ) ? ' data-days="' . esc_attr( $tag["days"] ) . '"' : '';
			$custom_attributes .= isset( $tag["min"] ) ? ' data-min="' . esc_attr( $tag["min"] ) . '"' : '';
			$custom_attributes .= isset( $tag["max"] ) ? ' data-max="' . esc_attr( $tag["max"] ) . '"' : '';

			return '<span class="input-wrapper"><input id="' . esc_attr( $input_id_prefix ) . 'arrival" type="text" data-target="' . esc_attr( $input_id_prefix ) . 'departure" name="arrival" value="' . esc_attr( $value ) . '" class="er-datepicker validate validate-required" ' . $custom_attributes . ' title="' . esc_attr( $title ) . '" autocomplete="off"><span class="input-box clickable"><span class="dashicons dashicons-calendar-alt"></span></span></span>';
			break;

		case "departure":
		case "date-to":
			if ( $reservation ) {
				$value = date( er_date_format(), $reservation->get_departure() );
			}
			if ( empty( $value ) ) {
				$value = date( er_date_format(), er_get_time() + 172800 );
			} elseif ( preg_match( '/\+{1}[0-9]+/i', $value ) ) {
				$cutplus = str_replace( '+', '', $value );
				$value   = date( er_date_format(), er_get_time() + ( (int) $cutplus * DAY_IN_SECONDS ) );
			}

			$custom_attributes = '';
			$custom_attributes .= isset( $tag["days"] ) ? ' data-days="' . esc_attr( $tag["days"] ) . '"' : '';
			$custom_attributes .= isset( $tag["min"] ) ? ' data-min="' . esc_attr( $tag["min"] ) . '"' : '';
			$custom_attributes .= isset( $tag["max"] ) ? ' data-max="' . esc_attr( $tag["max"] ) . '"' : '';

			return '<span class="input-wrapper"><input id="' . esc_attr( $input_id_prefix ) . 'departure" type="text" name="departure" value="' . esc_attr( $value ) . '" class="er-datepicker validate" ' . $custom_attributes . ' title="' . esc_attr( $title ) . '" autocomplete="off"><span class="input-box clickable"><span class="dashicons dashicons-calendar-alt"></span></span></span>';
			break;

		case "arrival-time":
		case "departure-time":
			if ( $reservation ) {
				if ( $tag[0] == 'arrival-time' ) {
					$value = date( 'H-i', $reservation->get_arrival() );
				} else {
					$value = date( 'H-i', $reservation->get_departure() );
				}
			}

			$increment = isset( $tag["increment"] ) ? $tag["increment"] : 15;
			$range     = isset( $tag['range'] ) ? $tag['range'] : false;

			return array(
				'id'          => $tag[0],
				'type'        => 'select',
				'title'       => $title,
				'options'     => er_form_time_options( $increment, $range ),
				'value'       => $value ? $value : ( isset( $_POST[ $tag[0] ] ) ? sanitize_text_field( $_POST[ $tag[0] ] ) : '' ),
				'attributes'  => $custom_attributes,
				'css'         => isset( $tag['style'] ) ? $tag['style'] : '',
				'placeholder' => isset( $tag['placeholder'] ) ? $tag['placeholder'] : '',
				'class'       => 'validate default-disabled'
			);
			break;

		case "arrival_hour":
		case "departure_hour":
			if ( $reservation ) {
				if ( $tag[0] == 'arrival_hour' ) {
					$value = date( 'h', $reservation->get_arrival() );
				} else {
					$value = date( 'h', $reservation->get_departure() );
				}
			}

			return array(
				'id'          => $tag[0],
				'type'        => 'select',
				'title'       => $title,
				'options'     => er_form_time_options( false, isset( $tag['range'] ) ? $tag['range'] : false ),
				'value'       => $value ? $value : ( isset( $_POST[ $tag[0] ] ) ? sanitize_key( $_POST[ $tag[0] ] ) : '' ),
				'attributes'  => $custom_attributes,
				'css'         => isset( $tag['style'] ) ? $tag['style'] : '',
				'placeholder' => isset( $tag['placeholder'] ) ? $tag['placeholder'] : '',
				'class'       => 'validate default-disabled'
			);

			break;

		case "arrival_minute":
		case "departure_minute":
			if ( $reservation ) {
				if ( $tag[0] == 'arrival_hour' ) {
					$value = date( 'i', $reservation->get_arrival() );
				} else {
					$value = date( 'i', $reservation->get_departure() );
				}
			} else {
				$value = 0;
			}

			return array(
				'id'          => $tag[0],
				'type'        => 'select',
				'title'       => $title,
				'class'       => 'together validate default-disabled',
				'options'     => er_form_number_options( "00", 59, isset( $tag["increment"] ) ? $tag["increment"] : 1 ),
				'value'       => $value ? $value : ( isset( $_POST[ $tag[0] ] ) ? sanitize_key( $_POST[ $tag[0] ] ) : '' ),
				'attributes'  => $custom_attributes,
				'css'         => isset( $tag['style'] ) ? $tag['style'] : '',
				'placeholder' => isset( $tag['placeholder'] ) ? $tag['placeholder'] : '',
			);
			break;

		case "units":
		case "nights":
		case "times":
			if ( $reservation ) {
				$value = $reservation->get_billing_units();
			}

			$start = isset( $tag[1] ) ? intval( $tag[1] ) : 1;
			$end   = isset( $tag[2] ) ? intval( $tag[2] ) : 6;

			$array = array(
				'id'          => 'units',
				'type'        => 'select',
				'title'       => $title,
				'options'     => er_form_number_options( $start, $end, isset( $tag['increment'] ) ? floatval( $tag['increment'] ) : 1 ),
				'value'       => $value ? $value : ( isset( $_POST['units'] ) ? sanitize_key( $_POST['units'] ) : '' ),
				'attributes'  => $custom_attributes,
				'css'         => isset( $tag['style'] ) ? $tag['style'] : '',
				'placeholder' => isset( $tag['placeholder'] ) ? $tag['placeholder'] : '',
				'class'       => 'validate default-disabled'
			);

			if ( isset( $tag['interval'] ) ) {
				$array['hidden']                      = array( 'interval' => intval( $tag['interval'] ) );
				$array['attributes']['data-interval'] = intval( $tag['interval'] );
			}

			return $array;
			break;

		case "persons":
		case "adults":
			if ( $reservation ) {
				$value = $reservation->get_adults();
			}

			$start = isset( $tag[1] ) ? intval( $tag[1] ) : 1;
			$end   = isset( $tag[2] ) ? intval( $tag[2] ) : 6;

			return array(
				'id'          => 'adults',
				'type'        => 'select',
				'title'       => $title,
				'options'     => er_form_number_options( $start, $end ),
				'value'       => $value ? $value : ( isset( $_POST['adults'] ) ? intval( $_POST['adults'] ) : '' ),
				'attributes'  => $custom_attributes,
				'css'         => isset( $tag['style'] ) ? $tag['style'] : '',
				'placeholder' => isset( $tag['placeholder'] ) ? $tag['placeholder'] : '',
				'class'       => 'validate default-disabled'
			);
			break;

		case "childs":
		case "children":
			if ( $reservation ) {
				$value = $reservation->get_children();
			}

			$start = isset( $tag[1] ) ? intval( $tag[1] ) : 1;
			$end   = isset( $tag[2] ) ? intval( $tag[2] ) : 6;

			return array(
				'id'          => 'children',
				'type'        => 'select',
				'title'       => $title,
				'options'     => er_form_number_options( $start, $end ),
				'value'       => $value ? $value : ( isset( $_POST['children'] ) ? intval( $_POST['children'] ) : '' ),
				'attributes'  => $custom_attributes,
				'css'         => isset( $tag['style'] ) ? $tag['style'] : '',
				'placeholder' => isset( $tag['placeholder'] ) ? $tag['placeholder'] : '',
				'class'       => 'validate default-disabled'
			);
			break;

		case "hidden":
			if ( $tag[1] == "room" || $tag[1] == "resource" ) {
				$resource = ER()->resources()->get( isset( $_POST['resource'] ) ? intval( $_POST['resource'] ) : intval( $tag[2] ) );

				if ( ! $resource ) {
					break;
				}

				$form_element = '<input type="hidden" name="resource" class="validate"  value="' . esc_attr( $resource->get_id() ) . '">';
				if ( isset( $tag['display'] ) && $resource ) {
					$form_element .= '<strong>' . __( stripslashes( $resource->get_title() ) ) . '</strong>';
				}
			} elseif ( $tag[1] == "from" || $tag[1] == "arrival" ) {
				$form_element = '<input type="hidden" name="arrival" class="validate" value="' . esc_attr( $tag[2] ) . '">';
				if ( isset( $tag['display'] ) ) {
					$form_element .= '<strong>' . sanitize_text_field( isset( $_POST['from'] ) ? $_POST['from'] : $tag[2] ) . '</strong>';
				}
			} elseif ( $tag[1] == "to" || $tag[1] == "departure" ) {
				$form_element = '<input type="hidden" name="departure" class="validate"  value="' . esc_attr( $tag[2] ) . '">';
				if ( isset( $tag['display'] ) ) {
					$form_element .= '<strong>' . sanitize_text_field( isset( $_POST['to'] ) ? $_POST['to'] : $tag[2] ) . '</strong>';
				}
			} elseif ( $tag[1] == "units" || $tag[1] == "times" ) {
				$form_element = '<input type="hidden" id="' . esc_attr( $input_id_prefix ) . 'units" name="units" class="validate" value="' . esc_attr( $tag[2] ) . '">';
				if ( isset( $tag['display'] ) ) {
					$form_element .= '<strong>' . floatval( isset( $_POST['units'] ) ? $_POST['units'] : $tag[2] ) . '</strong>';
				}
			} elseif ( $tag[1] == "persons" || $tag[1] == "adults" ) {
				$form_element = '<input type="hidden" name="adults" class="validate" value="' . esc_attr( $tag[2] ) . '">';
				if ( isset( $tag['display'] ) ) {
					$form_element .= '<strong>' . intval( isset( $_POST['adults'] ) ? $_POST['adults'] : $tag[2] ) . '</strong>';
				}
			} elseif ( $tag[1] == "childs" || $tag[1] == "children" ) {
				$form_element = '<input type="hidden" name="children" class="validate" value="' . esc_attr( $tag[2] ) . '">';
				if ( isset( $tag['display'] ) ) {
					$form_element .= '<strong>' . intval( isset( $_POST['children'] ) ? $_POST['children'] : $tag[2] ) . '</strong>';
				}
			} else {
				$form_element = '<input type="hidden" name="' . $tag[1] . '" id="' . $tag[1] . '" value="' . esc_attr( $tag[2] ) . '">';
				if ( isset( $tag['display'] ) ) {
					$value = sanitize_text_field( isset( $_POST[ $tag[1] ] ) ? $_POST[ $tag[1] ] : $tag[2] );
					if ( $value !== '' && is_numeric( $value ) && $value >= 0 && $value < 10 ) {
						$value = '0' . $value;
					}
					$form_element .= '<strong>' . $value . '</strong>';
				}
			}

			return $form_element;
			break;

		case "rooms":
		case "resources":
			if ( $reservation ) {
				$value = $reservation->get_resource_id();
			}

			$exclude = isset( $tag['exclude'] ) ? array_map( 'intval', explode( ',', $tag['exclude'] ) ) : '';
			$include = isset( $tag['include'] ) ? array_map( 'intval', explode( ',', $tag['include'] ) ) : '';

			return array(
				'id'          => 'resource',
				'type'        => 'select',
				'title'       => $title,
				'options'     => er_form_resources_options( true, $exclude, $include, false ),
				'value'       => $value,
				'attributes'  => $custom_attributes,
				'css'         => isset( $tag['style'] ) ? $tag['style'] : '',
				'placeholder' => isset( $tag['placeholder'] ) ? $tag['placeholder'] : '',
				'class'       => 'validate default-disabled'
			);
			break;

		default:
			return apply_filters( 'easyreservations_form_field', $tag, $form_hash );
			break;
	}
}

/**
 * Get default form content
 *
 * @param string $form_type
 *
 * @return string
 */
function er_form_get_default( $form_type = '' ) {
	switch ( $form_type ) {
		case 'checkout':
			return '<label>Custom</label>' . "\n" .
			       '<div class="content">[custom id="1"]' . "\n" .
			       '<small>Here you can additional fields to checkout.</small>' . "\n" .
			       '</div>';
			break;

		default:
			return '<label>Resource</label>' . "\n" .
			       '<div class="content">' . "\n" .
			       '[resources]' . "\n" .
			       '<small>All reservation fields can be replaced by hidden fields to either permanently set their value or take the value from the widget or search form - without letting the guest change it.</small>' . "\n" .
			       '</div>' . "\n\n" .
			       '<label>Date</label>' . "\n" .
			       '<div class="content">' . "\n" .
			       '<div class="row">' . "\n" .
			       '[date departure="true" time="true" arrivalHour="12" arrivalMinute="0" departureHour="12" departureMinute="0"]' . "\n" .
			       '</div><small>There are two different ways for your guests to select the reservation period. Either this guided date selection.</small>' . "\n" .
			       '</div>' . "\n\n" .
			       '<label>Arrival Date</label>' . "\n" .
			       '<div class="content">' . "\n" .
			       '<div class="row">' . "\n" .
			       '[date-from min="0" style="width:100px"] [arrival_hour value="12"][arrival_minute value="0" increment="10"]' . "\n" .
			       '</div><small>Or simple date fields. Delete either as they wont work together. The hour and minute fields can be removed. You can also edit them to set which hours and minutes can be selected.</small>' . "\n" .
			       '</div>' . "\n\n" .
			       '<label>Departure Date</label>' . "\n" .
			       '<div class="content">' . "\n" .
			       '<div class="row">' . "\n" .
			       '[date-to min="0" style="width:100px"] [departure_hour value="12"][departure_minute value="0" increment="10"]' . "\n" .
			       '</div><small>The departure field can also be replaced by a billing unit select. Your guests would only have to select how many hours/days/nights they want to stay.</small>' . "\n" .
			       '</div>' . "\n\n" .
			       '<label>Adults</label>' . "\n" .
			       '<div class="content">' . "\n" .
			       '[adults 1 10]' . "\n" .
			       '<small>Many options like restricting the amount of adults can be set directly at the form field.</small>' . "\n" .
			       '</div>' . "\n\n" .
			       '<label>Children</label>' . "\n" .
			       '<div class="content">' . "\n" .
			       '[children 0 10]' . "\n" .
			       '<small>While called adults and children in the plugin you can use them for anything. You can replace the label at any place the guest can see.</small>' . "\n" .
			       '</div>' . "\n\n" .
			       '<label>Custom</label>' . "\n" .
			       '<div class="content">' . "\n" .
			       '[custom id="1"]' . "\n" .
			       '</div>';
			break;
	}
}
