<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param array       $filter
 * @param ER_Resource $resource
 *
 * @return array
 */
function easyreservations_get_filter_description( $filter, $resource ) {
	$price = 0;
	if ( isset( $filter['price'] ) ) {
		$price = er_price( $filter['price'], true );
		if ( isset( $filter['children-price'] ) ) {
			$price .= '<br>' . er_price( $filter['children-price'], true );
		}
	}

	$interval_label = er_date_get_interval_label( isset( $resource ) ? $resource->get_billing_interval() : DAY_IN_SECONDS, 1, true );

	if ( $filter['type'] == 'price' ) {
		if ( isset( $filter['cond'] ) ) {
			$timecond = 'cond';
		}
		if ( isset( $filter['basecond'] ) ) {
			$condcond = 'basecond';
		}
		if ( isset( $filter['condtype'] ) ) {
			$condtype = 'condtype';
		}

		$explain = __( 'the base price changes to', 'easyReservations' );
	} elseif ( $filter['type'] == 'req' || $filter['type'] == 'unavail' ) {
		$timecond = 'cond';
		$explain  = '';
	} else {
		if ( isset( $filter['timecond'] ) ) {
			$timecond = 'timecond';
		}
		if ( isset( $filter['cond'] ) ) {
			$condcond = 'cond';
		}
		if ( isset( $filter['type'] ) ) {
			$condtype = 'type';
		}
		if ( isset( $filter['modus'] ) ) {
			$price_add = ' ' . esc_html__( 'each', 'easyReservations' ) . ' <br>';

			if ( $filter['modus'] == '%' ) {
				$price_add = esc_html( $filter['price'] ) . ' %';
			} elseif ( $filter['modus'] == 'price_res' ) {
				$price_add .= esc_html__( 'Reservation', 'easyReservations' );
			} elseif ( $filter['modus'] == 'price_day' ) {
				$price_add .= er_date_get_interval_label( $resource->get_billing_interval(), 1, true );
			} elseif ( $filter['modus'] == 'price_pers' ) {
				$price_add .= esc_html( __( 'Person', 'easyReservations' ) );
			} elseif ( $filter['modus'] == 'price_both' ) {
				$price_add .= esc_html__( 'Person', 'easyReservations' ) . ' ' . esc_html__( 'and', 'easyReservations' ) . ' ' . $interval_label;
			} elseif ( $filter['modus'] == 'price_adul' ) {
				$price_add .= esc_html__( 'Adult', 'easyReservations' );
			} elseif ( $filter['modus'] == 'price_day_adult' ) {
				$price_add .= esc_html__( 'Adult', 'easyReservations' ) . ' ' . esc_html__( 'and', 'easyReservations' ) . ' ' . $interval_label;
			} elseif ( $filter['modus'] == 'price_day_child' ) {
				$price_add .= esc_html__( 'Children', 'easyReservations' ) . ' ' . esc_html__( 'and', 'easyReservations' ) . ' ' . $interval_label;
			} elseif ( $filter['modus'] == 'price_child' ) {
				$price_add .= esc_html__( 'Children', 'easyReservations' );
			}

			$price .= $price_add;
		}

		if ( $filter['price'] >= 0 ) {
			$explain = esc_html__( 'the price increases by', 'easyReservations' );
		} else {
			$explain = esc_html__( 'the price decreases by', 'easyReservations' );
		}
	}

	if ( isset( $timecond ) ) {
		$full = false;
		if ( $filter['type'] == 'price' ) {
			$the_condition = sprintf( esc_html__( "If %s to calculate is ", "easyReservations" ), strtolower( $interval_label ) );
		} elseif ( $filter['type'] == 'unavail' ) {
			if ( isset( $filter['arrival'], $filter['departure'] ) ) {
				$the_condition = esc_html__( "Arrival and departure is not possible ", "easyReservations" );
			} elseif ( isset( $filter['arrival'] ) ) {
				$the_condition = esc_html__( "Arrival is not possible ", "easyReservations" );
			} elseif ( isset( $filter['departure'] ) ) {
				$the_condition = esc_html__( "Departure is not possible ", "easyReservations" );
			} else {
				$the_condition = esc_html__( "Resource is unavailable ", "easyReservations" );
			}
		} else {
			$the_condition = esc_html__( "If date is ", "easyReservations" );
		}

		if ( isset( $filter['from'] ) ) {
			$from          = new ER_DateTime( is_numeric( $filter['from'] ) ? '@' . $filter['from'] : $filter['from'] );
			$to            = new ER_DateTime( is_numeric( $filter['to'] ) ? '@' . $filter['to'] : $filter['to'] );
			$the_condition .= ' ' . sprintf( __( 'between %1$s and %2$s', 'easyReservations' ), '<b>' . esc_html( $from->format( er_datetime_format() ) ) . '</b>', '<b>' . esc_html( $to->format( er_datetime_format() ) ) . '</b>' );
			$full          = true;
		}

		if ( $filter[ $timecond ] == 'unit' ) {
			if ( isset( $filter['hour'] ) && ! empty( $filter['hour'] ) ) {
				$timecondition = '';
				$times         = explode( ',', $filter['hour'] );
				foreach ( $times as $time ) {
					$timecondition .= esc_html( $time ) . 'h, ';
				}
			}
			if ( ! empty( $filter['day'] ) ) {
				$daycondition = '';
				$days         = explode( ',', $filter['day'] );
				$daynames     = er_date_get_label( 0, 3 );
				foreach ( $days as $day ) {
					$daycondition .= $daynames[ $day - 1 ] . ', ';
				}
			}
			if ( ! empty( $filter['cw'] ) ) {
				$cwcondition = esc_html( $filter['cw'] );
			}
			if ( ! empty( $filter['month'] ) ) {
				$monthcondition = '';
				$months         = explode( ',', $filter['month'] );
				$monthesnames   = er_date_get_label( 1, 3 );
				foreach ( $months as $month ) {
					$monthcondition .= $monthesnames[ $month - 1 ] . ', ';
				}
			}
			if ( ! empty( $filter['quarter'] ) ) {
				$qcondition = esc_html( $filter['quarter'] );
			}
			if ( ! empty( $filter['year'] ) ) {
				$ycondition = esc_html( $filter['year'] );
			}

			$itcondtion = '';
			if ( isset( $timecondition ) && $timecondition !== '' ) {
				$itcondtion .= sprintf( __( 'at %s', 'easyReservations' ), " <b>" . substr( $timecondition, 0, - 2 ) . '</b> ' ) . esc_html__( 'and', 'easyReservations' ) . ' ';
			}
			if ( isset( $daycondition ) && $daycondition !== '' ) {
				$itcondtion .= '<b>' . substr( $daycondition, 0, - 2 ) . '</b> ' . esc_html__( 'and', 'easyReservations' ) . ' ';
			}
			if ( isset( $cwcondition ) && $cwcondition !== '' ) {
				$itcondtion .= sprintf( esc_html__( 'in %s', 'easyReservations' ), esc_html__( 'calendar week', 'easyReservations' ) ) . " <b>" . $cwcondition . '</b> ' . esc_html__( 'and', 'easyReservations' ) . ' ';
			}
			if ( isset( $monthcondition ) && $monthcondition !== '' ) {
				$itcondtion .= sprintf( esc_html__( 'in %s', 'easyReservations' ), " <b>" . substr( $monthcondition, 0, - 2 ) . '</b> ' ) . esc_html__( 'and', 'easyReservations' ) . ' ';
			}
			if ( isset( $qcondition ) && $qcondition !== '' ) {
				$itcondtion .= sprintf( esc_html__( 'in %s', 'easyReservations' ), esc_html__( 'quarter', 'easyReservations' ) ) . " <b>" . $qcondition . '</b> ' . esc_html__( 'and', 'easyReservations' ) . ' ';
			}
			if ( isset( $ycondition ) && $ycondition !== '' ) {
				$itcondtion .= sprintf( esc_html__( 'in %s', 'easyReservations' ), " <b>" . $ycondition . '</b> ' ) . esc_html__( 'and', 'easyReservations' ) . ' ';
			}
			if ( $full ) {
				$the_condition .= ' ' . esc_html__( 'and', 'easyReservations' );
			}
			$the_condition .= ' ' . substr( $itcondtion, 0, - 4 );
		}
	}

	$bg_color = '#F4AA33';

	if ( isset( $condcond ) ) {
		if ( $filter[ $condtype ] == "stay" ) {
			$type             = esc_html__( 'Duration', 'easyReservations' );
			$bg_color         = '#1CA0E1';
			$condition_string = sprintf( esc_html__( 'guest stays %s days or more', 'easyReservations' ), '<b>' . esc_html( $filter[ $condcond ] ) . '</b>' );
		} elseif ( $filter[ $condtype ] == "pers" ) {
			$type             = esc_html__( 'Person', 'easyReservations' );
			$bg_color         = '#3059C1';
			$condition_string = sprintf( esc_html__( '%s or more persons reserve', 'easyReservations' ), '<b>' . esc_html( $filter[ $condcond ] ) . '</b>' );
		} elseif ( $filter[ $condtype ] == "adul" ) {
			$type             = esc_html__( 'Adult', 'easyReservations' );
			$bg_color         = '#3059C1';
			$condition_string = sprintf( esc_html__( '%s or more adults reserve', 'easyReservations' ), '<b>' . esc_html( $filter[ $condcond ] ) . '</b>' );
		} elseif ( $filter[ $condtype ] == "child" ) {
			$type             = esc_html__( 'Children', 'easyReservations' );
			$bg_color         = '#3059C1';
			$condition_string = sprintf( esc_html__( '%s or more children reserve', 'easyReservations' ), '<b>' . esc_html( $filter[ $condcond ] ) . '</b>' );
		} elseif ( $filter[ $condtype ] == "early" ) {
			$type             = esc_html__( 'Early bird', 'easyReservations' );
			$bg_color         = '#F4AA33';
			$condition_string = sprintf( esc_html__( 'the guest reserves %s before his arrival', 'easyReservations' ), '<b>' . esc_html( $filter[ $condcond ] ) . '</b> ' . $interval_label );
		} else {
			$type = __( 'Deprecated please delete', 'easyReservations' );
		}

		if ( isset( $condition_string ) ) {
			if ( ! empty( $the_condition ) ) {
				$the_condition = $the_condition . ' ' . __( 'and', 'easyReservations' ) . '<br>' . strtolower( $condition_string );
			} else {
				$the_condition = __( 'If', 'easyReservations' ) . ' ' . $condition_string;
			}
		}
	}

	if ( $filter['type'] == 'price' ) {
		$type     = esc_html__( 'Price', 'easyReservations' );
		$bg_color = '#30B24A';
	} elseif ( $filter['type'] == "discount" ) {
		$bg_color = '#F4AA33';
		$type     = esc_html__( 'Discount', 'easyReservations' );
	} elseif ( $filter['type'] == "charge" ) {
		$bg_color = '#F4AA33';
		$type     = esc_html__( 'Extra charge', 'easyReservations' );
	} elseif ( $filter['type'] == "unavail" ) {
		$bg_color = '#F4AA33';
		$type     = esc_html__( 'Availability', 'easyReservations' );
	}

	return array(
		'<code style="color:' . esc_attr( $bg_color ) . ';font-weight:bold;display:inline-block">' . $type . '</code>',
		$the_condition . ' ' . $explain,
		$price
	);
}

$count = 0;
?>
<h1><?php esc_html_e( 'Filter', 'easyReservations' ); ?></h1>
<p><?php esc_html_e( 'With filter you can change the price, availability and requirements by flexible conditions.', 'easyReservations' ); ?></p>
<table class="widefat" style="width: 100%">
    <thead>
    <tr>
        <th><?php esc_html_e( 'Filter', 'easyReservations' ); ?></th>
        <th style="text-align:center;"><?php esc_html_e( 'Priority', 'easyReservations' ); ?></th>
        <th><?php esc_html_e( 'Time', 'easyReservations' ); ?></th>
        <th><?php esc_html_e( 'Price', 'easyReservations' ); ?></th>
        <th></th>
    </tr>
    </thead>
    <tbody id="sortable">
    <script>var filter = new Array();</script>
	<?php
	foreach ( $all_filter as $key => $filter ):
		if ( $filter['type'] == 'unavail' || $filter['type'] == 'req' ) {
			continue;
		}

		$count ++;
		$filter_info = easyreservations_get_filter_description( $filter, $resource );
		?>
        <tr>
            <script>
				filter[<?php esc_attr_e( $key ); ?>] = new Object();
				filter[<?php esc_attr_e( $key ); ?>] = <?php echo wp_json_encode( $filter ); ?>;
            </script>
            <td>
				<?php echo $filter_info[0]; ?><?php echo esc_html( stripslashes( $filter['name'] ) ); ?>
            </td>
            <td style="text-align:center;width:40px">
				<?php if ( isset( $filter['imp'] ) ) {
					echo esc_html( $filter['imp'] );
				} ?>
            </td>
            <td><?php echo $filter_info[1]; ?></td>
            <td><?php echo wp_kses_post( $filter_info[2] ); ?></td>
            <td style="vertical-align:middle;text-align:center">
                <a href="javascript:filter_edit(<?php echo esc_attr( $key ); ?>);" class="dashicons dashicons-edit tips" data-tip="<?php echo sprintf( esc_attr__( 'Edit %s', 'easyReservations' ), esc_attr__( 'filter', 'easyReservations' ) ); ?>"></a>
                <a href="javascript:filter_copy(<?php echo esc_attr( $key ); ?>" class="dashicons dashicons-admin-page tips" data-tip="<?php echo sprintf( esc_attr__( 'Copy %s', 'easyReservations' ), esc_attr__( 'filter', 'easyReservations' ) ); ?>"></a>
                <a href="<?php echo esc_url( wp_nonce_url( $url . '&delete_filter=' . $key, 'easy-resource-delete-filter' ) ); ?>#filters" class="dashicons dashicons-trash tips" data-tip="<?php echo sprintf( esc_attr__( 'Delete %s', 'easyReservations' ), esc_attr__( 'filter', 'easyReservations' ) ); ?>"></a>
            </td>
        </tr>
		<?php unset( $all_filter[ $key ] ); ?>
	<?php endforeach; ?>
	<?php if ( $count == 0 ): ?>
        <td colspan="5"><?php esc_html_e( 'No price filter defined', 'easyReservations' ); ?></td>
	<?php endif; ?>
    </tbody>
    <thead>
    <tr>
        <th class="<?php echo 'tmiddle'; ?>" style="border-top-width: 1px"><?php esc_html_e( 'Filter', 'easyReservations' ); ?></th>
        <th class="<?php echo 'tmiddle'; ?>" style="text-align:center;"><?php esc_html_e( 'Priority', 'easyReservations' ); ?></th>
        <th class="<?php echo 'tmiddle'; ?>" colspan="2"><?php esc_html_e( 'Condition', 'easyReservations' ); ?></th>
        <th class="<?php echo 'tmiddle'; ?>"></th>
    </tr>
    </thead>
    <tbody>
	<?php
	if ( count( $all_filter ) > 0 ):
		$day_names = er_date_get_label( 0, 2 );
		$hour_string = esc_html__( 'From %1$s till %2$s', 'easyReservations' );

		foreach ( $all_filter as $key => $filter ): //foreach filter array
			$description = easyreservations_get_filter_description( $filter, isset( $resource ) ? $resource : null, 1 );

			if ( $filter['type'] == "unavail" ) {
				$bg_color         = '#D8211E';
				$condition_string = $description[1];
			} elseif ( $filter['type'] == "req" ) {
				$bg_color         = '#F4AA33';
				$condition_string = $description[1] . ' ' . esc_html__( 'resources requirements change to', 'easyReservations' );
				$max_nights       = ( $filter['req']['nights-max'] == 0 ) ? '&infin;' : esc_html( $filter['req']['nights-max'] );
				$max_pers         = ( $filter['req']['pers-max'] == 0 ) ? '&infin;' : esc_html( $filter['req']['pers-max'] );
				$condition_string .=
					'<br>' . esc_html__( 'Persons', 'easyReservations' ) . ': <b>' . esc_html( $filter['req']['pers-min'] ) . '</b> - ' .
					'<b>' . $max_pers . '</b>, ' . er_date_get_interval_label( $resource->get_billing_interval(), 2, true ) . ': <b>' . esc_html( $filter['req']['nights-min'] ) . '</b> - <b>' . $max_nights . '</b><br>';
				$start_on         = '';
				$end_on           = '';

				if ( $filter['req']['start-on'] == 0 ) {
					$start_on = esc_html__( "All", 'easyReservations' ) . ', ';
				} elseif ( $filter['req']['start-on'] == 8 ) {
					$start_on = esc_html__( "None", 'easyReservations' ) . ', ';
				} else {
					for ( $i = 1; $i < 8; $i ++ ) {
						if ( in_array( $i, $filter['req']['start-on'] ) ) {
							$start_on .= '<b>' . $day_names[ $i - 1 ] . '</b>, ';
						}
					}
				}
				if ( isset( $filter['req']['start-h'] ) ) {
					$start_on = substr( $start_on, 0, - 2 );
					$start_on .= ' ' . strtolower( sprintf( $hour_string, '<b>' . esc_html( $filter['req']['start-h'][0] ) . 'h</b>', '<b>' . esc_html( $filter['req']['start-h'][1] ) ) ) . 'h</b>, ';
				}
				if ( $filter['req']['end-on'] == 0 ) {
					$end_on = __( "All", 'easyReservations' ) . ', ';
				} elseif ( $filter['req']['end-on'] == 8 ) {
					$end_on = __( "None", 'easyReservations' ) . ', ';
				} else {
					for ( $i = 1; $i < 8; $i ++ ) {
						if ( in_array( $i, $filter['req']['end-on'] ) ) {
							$end_on .= '<b>' . $day_names[ $i - 1 ] . '</b>, ';
						}
					}
				}
				if ( isset( $filter['req']['end-h'] ) ) {
					$end_on = substr( $end_on, 0, - 2 );
					$end_on .= ' ' . strtolower( sprintf( $hour_string, '<b>' . esc_html( $filter['req']['end-h'][0] ) . 'h</b>', '<b>' . esc_html( $filter['req']['end-h'][1] ) . 'h</b>' ) );
				}
				$condition_string .= 'Arrival: ' . $start_on . 'Departure: ' . substr( $end_on, 0, - 2 );
			} ?>
            <tr name="notsort">
                <script>
					filter[<?php esc_attr_e( $key ); ?>] = new Object();
					filter[<?php esc_attr_e( $key ); ?>] = <?php echo wp_json_encode( $filter ); ?>;
                </script>
                <td>
                    <code style="color:<?php echo $bg_color; ?>;font-weight:bold;display:inline-block">
						<?php echo esc_html( ucfirst( $filter['type'] ) ); ?>
                    </code>
					<?php echo esc_html( $filter['name'] ); ?>
                </td>
                <td style="middle;text-align:center;width:40px">
					<?php if ( isset( $filter['imp'] ) ) {
						echo esc_html( $filter['imp'] );
					} ?>
                </td>
                <td colspan="2">
					<?php echo wp_kses_post( $condition_string ); ?>
                </td>
                <td style="vertical-align:middle;text-align:center">
                    <a href="javascript:filter_edit(<?php esc_attr_e( $key ); ?>);" class="dashicons dashicons-edit tips" data-tip="<?php echo sprintf( esc_attr__( 'Edit %s', 'easyReservations' ), esc_attr__( 'filter', 'easyReservations' ) ); ?>"></a>
                    <a href="javascript:filter_copy(<?php esc_attr_e( $key ); ?>);" class="dashicons dashicons-admin-page tips" data-tip="<?php echo sprintf( esc_attr__( 'Copy %s', 'easyReservations' ), esc_attr__( 'filter', 'easyReservations' ) ); ?>"></a>
                    <a href="<?php echo esc_url( wp_nonce_url( $url . '&delete_filter=' . $key, 'easy-resource-delete-filter' ) ); ?>" class="dashicons dashicons-trash tips" data-tip="<?php echo sprintf( esc_attr__( 'Delete %s', 'easyReservations' ), esc_attr__( 'filter', 'easyReservations' ) ); ?>"></a>
                </td>
            </tr>
		<?php endforeach; ?>
	<?php else: ?>
        <tr>
            <td colspan="5"><?php esc_html_e( 'No filter defined', 'easyReservations' ); ?></td>
        </tr>
	<?php endif; ?>
    </tbody>
</table>