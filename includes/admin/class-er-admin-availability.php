<?php
/**
 * Availability settings screen
 */

//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Admin_Availability {

	/**
	 * ER_Admin_Availability constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Save filter
	 */
	public function init() {
		if ( isset( $_POST['filter_form_name_field'] ) ) {
			$all_filter = get_option( 'reservations_availability_filters', array() );

			$filter = array(
				'name' => sanitize_title( $_POST['filter_form_name_field'] ),
				'type' => 'unavail',
				'imp'  => intval( $_POST['price_filter_imp'] ),
			);

			if ( isset( $_POST['price_filter_cond_range'] ) ) {
				$filter['cond'] = 'range';

				if ( isset( $_POST['price_filter_range_from'] ) && ! empty( $_POST['price_filter_range_from'] ) ) {
					$from = new ER_DateTime( sanitize_text_field( $_POST['price_filter_range_from'] ) );
					$from->setTime( intval( $_POST['filter_range_from_hour'] ), intval( $_POST['filter_range_from_minute'] ) );
					$filter['from'] = $from->format( 'Y-m-d H:i:s' );
				} else {
					ER_Admin_Notices::add_temporary_error( __( 'Enter a starting date for the filter.', 'easyReservations' ) );
				}

				if ( isset( $_POST['price_filter_range_to'] ) && ! empty( $_POST['price_filter_range_to'] ) ) {
					$from = new ER_DateTime( sanitize_text_field( $_POST['price_filter_range_to'] ) );
					$from->setTime( intval( $_POST['filter_range_to_hour'] ), intval( $_POST['filter_range_to_minute'] ) );
					$filter['to'] = $from->format( 'Y-m-d H:i:s' );
				} else {
					ER_Admin_Notices::add_temporary_error( __( 'Enter an ending date for the filter.', 'easyReservations' ) );
				}

				if ( isset( $_POST['price_filter_range_every'] ) ) {
					$filter['every'] = 1;
				}
			}

			if ( isset( $_POST['price_filter_cond_unit'] ) ) {
				$filter['cond'] = 'unit';
				if ( isset( $_POST['price_filter_unit_year'] ) ) {
					$filter['year'] = implode( ',', array_map( 'intval', $_POST['price_filter_unit_year'] ) );
				}
				if ( isset( $_POST['price_filter_unit_quarter'] ) ) {
					$filter['quarter'] = implode( ',', array_map( 'intval', $_POST['price_filter_unit_quarter'] ) );
				}
				if ( isset( $_POST['price_filter_unit_month'] ) ) {
					$filter['month'] = implode( ',', array_map( 'intval', $_POST['price_filter_unit_month'] ) );
				}
				if ( isset( $_POST['price_filter_unit_cw'] ) ) {
					$filter['cw'] = implode( ',', array_map( 'intval', $_POST['price_filter_unit_cw'] ) );
				}
				if ( isset( $_POST['price_filter_unit_days'] ) ) {
					$filter['day'] = implode( ',', array_map( 'intval', $_POST['price_filter_unit_days'] ) );
				}
				if ( isset( $_POST['price_filter_unit_hour'] ) ) {
					$filter['hour'] = implode( ',', array_map( 'intval', $_POST['price_filter_unit_hour'] ) );
				}
			}

			if ( ! isset( $_POST['price_filter_cond_range'] ) && ! isset( $_POST['price_filter_cond_unit'] ) ) {
				/*
				 * translators: One condition
				 */
				ER_Admin_Notices::add_temporary_error( sprintf( __( 'Select %s', 'easyReservations' ), _x( 'condition', 'one condition', 'easyReservations' ) ) );
			}

			if ( isset( $_POST['filter_form_arrival_checkbox'] ) ) {
				$filter['arrival'] = 1;
			}

			if ( isset( $_POST['filter_form_departure_checkbox'] ) ) {
				$filter['departure'] = 1;
			}

			if ( ! ER_Admin_Notices::has_errors() ) {
				if ( isset( $_POST['price_filter_edit'] ) && isset( $all_filter[ intval( $_POST['price_filter_edit'] ) ] ) ) {
					//Remove existing filter
					unset( $all_filter[ intval( $_POST['price_filter_edit'] ) ] );
				}

				$all_filter[] = $filter;

				usort( $all_filter, function ( $a, $b ) {
					return $a['imp'] - $b['imp'];
				} );

				update_option( 'reservations_availability_filters', $all_filter );

				ER_Admin_Notices::add_temporary_success( sprintf( __( 'Filter %s added.', 'easyReservations' ), $filter['name'] ) );
			}
		} elseif ( isset( $_GET['delete_filter'] ) && check_admin_referer( 'easy-resource-delete-filter' ) ) {
			$filters = get_option( 'reservations_availability_filters', array() );
			unset( $filters[ intval( $_GET['delete_filter'] ) ] );
			update_option( 'reservations_availability_filters', $filters );

			ER_Admin_Notices::add_temporary_success( __( 'Filter deleted.', 'easyReservations' ) );
		}
	}

	/**
	 * Output availability settings
	 */
	public static function output() {
		wp_enqueue_script( 'er-datepicker' );
		wp_enqueue_script( 'er-enhanced-select' );
		wp_enqueue_script( 'er-admin-availability' );
		wp_enqueue_style( 'er-datepicker' );

		$all_filter = get_option( 'reservations_availability_filters', array() );
		$url        = 'admin.php?page=reservation-availability';

		if ( $all_filter && ! empty( $all_filter ) ) {
			foreach ( $all_filter as $key => $filter ) {
				$filter['name'] = addslashes( $filter['name'] );

				if ( isset( $filter['from'] ) ) {
					if ( is_numeric( $filter['from'] ) ) {
						$filter['from_str'] = date( "F d, Y G:i:s", $filter['from'] );
					} else {
						$to                 = new ER_DateTime( $filter['from'] );
						$filter['from_str'] = $to->format( "F d, Y G:i:s" );
					}
				}

				if ( isset( $filter['to'] ) ) {
					if ( is_numeric( $filter['to'] ) ) {
						$filter['to_str'] = date( "F d, Y G:i:s", $filter['to'] );
					} else {
						$to               = new ER_DateTime( $filter['to'] );
						$filter['to_str'] = $to->format( "F d, Y G:i:s" );
					}
				}

				if ( isset( $filter['date'] ) ) {
					$filter['date_str'] = date( "F d, Y G:i:s", $filter['date'] );
				}

				$all_filter[ $key ] = $filter;
			}
		} else {
			$all_filter = array();
		}

		echo '<div class="wrap">';
		include 'views/html-admin-resource-filters.php';
		include 'views/html-admin-resource-filter-add.php';
		echo '</div>';
	}
}

return new ER_Admin_Availability();