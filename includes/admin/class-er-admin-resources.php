<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Admin_Resources {

	public function __construct() {
		add_filter( 'parent_file', array( $this, 'highlight_admin_menu' ) );
		add_action( 'wp_loaded', array( $this, 'init' ) );
	}

	/**
	 * Save posted data
	 */
	public function init() {
		$resource_id = false;
		if ( isset( $_GET['resource'] ) ) {
			$resource_id = intval( $_GET['resource'] );
		}

		if ( isset( $_GET['delete'] ) && check_admin_referer( 'easy-resource-delete' ) ) {
			$this->delete_resource( intval( $_GET['delete'] ) );
		} elseif ( isset( $_POST['add_resource_title'] ) && check_admin_referer( 'easy-resource-add', 'easy-resource-add' ) ) {
			$this->add_resource();
		} elseif ( isset( $_POST['resource_spaces'] ) && $resource_id ) {
			$this->save_resource_spaces_names( $resource_id );
		} elseif ( isset( $_POST['base_price'] ) && $resource_id && check_admin_referer( 'easy-resource-settings', 'easy-resource-settings' ) ) {
			$this->save_resource_settings( $resource_id );
		} elseif ( isset( $_POST['filter_form_name_field'] ) && $resource_id && check_admin_referer( 'easy-resource-filter', 'easy-resource-filter' ) ) {
			$this->save_resource_filter( $resource_id );
		} elseif ( isset( $_GET['delete_filter'] ) && $resource_id && check_admin_referer( 'easy-resource-delete-filter' ) ) {
			$this->delete_resource_filter( $resource_id );
		} elseif ( isset( $_POST['slot_range_from'] ) && $resource_id && check_admin_referer( 'easy-resource-slot' ) ) {
			$this->save_resource_slot( $resource_id );
		} elseif ( isset( $_GET['delete_slot'] ) && $resource_id && check_admin_referer( 'easy-resource-delete-slot' ) ) {
			$this->delete_resource_slot( $resource_id );
		}
	}

	/**
	 * Highlight correct submenu item
	 *
	 * @param string $parent_file
	 *
	 * @return string
	 */
	public function highlight_admin_menu( $parent_file ) {
		global $submenu_file;

		$submenu_file = 'edit.php?post_type=easy-rooms';

		return $parent_file;
	}

	/**
	 * Output resource settings
	 */
	public static function output() {
		if ( isset( $_GET['add_resource'] ) ) {
			//Add new resource
			include 'views/html-admin-resources-header.php';

			include 'views/html-admin-resource-add.php';
		} elseif ( isset( $_GET['resource'] ) ) {
			//Edit resource
			self::output_resource_page( intval( $_GET['resource'] ) );
		}
	}

	/**
	 * Output resource page
	 */
	public static function output_resource_page( $id ) {
		$resource = ER()->resources()->get( $id );

		wp_enqueue_script( 'er-datepicker' );
		wp_enqueue_script( 'er-enhanced-select' );
		wp_enqueue_style( 'er-datepicker' );

		$thumbnail    = get_the_post_thumbnail( $resource->get_id(), array( 150, 150 ) );
		$spaces_names = get_post_meta( $resource->get_id(), 'easy-resource-roomnames', true );
		$slots        = get_post_meta( $id, 'easy-resource-slots', true );
		if ( $slots && ! empty( $slots ) ) {
			foreach ( $slots as $key => $slot ) {
				if ( is_numeric( $slot['range-from'] ) ) {
					$slot['from_str'] = date( er_date_format(), $slot['range-from'] );
				} else {
					$from             = new ER_DateTime( $slot['range-from'] );
					$slot['from_str'] = $from->format( er_date_format() );
				}

				if ( is_numeric( $slot['range-to'] ) ) {
					$slot['to_str'] = date( er_date_format(), $slot['range-to'] );
				} else {
					$to             = new ER_DateTime( $slot['range-to'] );
					$slot['to_str'] = $to->format( er_date_format() );
				}

				$slots[ $key ] = $slot;
			}
		}

		$url        = admin_url( 'admin.php?page=resource&resource=' . $resource->get_id() );
		$all_filter = get_post_meta( $resource->get_id(), 'easy_res_filter', true );

		if ( $all_filter && ! empty( $all_filter ) ) {
			foreach ( $all_filter as $key => $filter ) {
				$filter['name'] = addslashes( $filter['name'] );

				if ( isset( $filter['from'] ) ) {
					if ( is_numeric( $filter['from'] ) ) {
						$filter['from_str'] = date( "F d, Y G:i:s", $filter['from'] );
					} else {
						$from               = new ER_DateTime( $filter['from'] );
						$filter['from_str'] = $from->format( "F d, Y G:i:s" );
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

		include 'views/html-admin-resource-index.php';
	}

	/**
	 * Delete resource
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	private function delete_resource( $id ) {
		global $wpdb;

		if ( is_integer( $id ) && wp_delete_post( $id ) ) {
			$delete_reservations = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix . "reservations WHERE resource=%s", $id ) );
			if ( $delete_reservations ) {
				ER_Admin_Notices::add_temporary_success( __( 'Resource and all its reservations deleted', 'easyReservations' ) );

				return true;
			} else {
				ER_Admin_Notices::add_temporary_error( __( 'Reservations could not be deleted', 'easyReservations' ) );

				return false;
			}
		} else {
			ER_Admin_Notices::add_temporary_error( __( 'Resource could not be deleted', 'easyReservations' ) );

			return false;
		}
	}

	/**
	 * Add resource
	 */
	public function add_resource() {
		$title   = sanitize_text_field( $_POST['add_resource_title'] );
		$content = wp_kses_post( $_POST['add_resource_content'] );

		if ( isset( $_POST['dopy'] ) ) {
			$resource = new ER_Resource( get_post( intval( $_POST['dopy'] ) ) );
			try {
				$resource->set_title( $title );
				$resource->set_content( $content );
				$resource->add();

				ER_Admin_Notices::add_temporary_success( sprintf( __( 'Resource #%d added', 'easyReservations' ), $resource->get_id() ) );
				wp_redirect( admin_url() . 'admin.php?page=resource&resource=' . $resource->get_id() );
				exit();
			} catch ( Exception $e ) {
				ER_Admin_Notices::add_temporary_error( $e->getMessage() );
			}
		} elseif ( ! empty( $title ) ) {
			$resource = array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'private',
				'post_type'    => 'easy-rooms'
			);

			$new_id = wp_insert_post( $resource );
			add_post_meta( $new_id, 'roomcount', '1', true );
			add_post_meta( $new_id, 'reservations_groundprice', 0, true );
			add_post_meta( $new_id, 'easy-resource-interval', DAY_IN_SECONDS, true );

			$filename = esc_url( $_POST['upload_image'] );

			if ( ! empty( $filename ) ) {
				$wp_filetype = wp_check_filetype( basename( $filename ), null );
				$attachment  = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				);
				$attach_id   = wp_insert_attachment( $attachment, $filename, $new_id );
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				add_post_meta( $new_id, '_thumbnail_id', $attach_id, true );
			}

			ER_Admin_Notices::add_temporary_success( sprintf( __( 'Resource #%d added', 'easyReservations' ), $new_id ) );
			wp_redirect( admin_url() . 'admin.php?page=resource&resource=' . $new_id );
			exit();
		} else {
			ER_Admin_Notices::add_temporary_error( sprintf( __( 'Please enter a title', 'easyReservations' ) ) );
		}
	}

	/**
	 * Save resource spaces names
	 */
	private function save_resource_spaces_names( $id ) {
		update_post_meta( $id, 'easy-resource-roomnames', array_map( 'sanitize_text_field', $_POST['resource_spaces'] ) );
		ER_Admin_Notices::add_temporary_success( sprintf( __( '%s saved', 'easyReservations' ), __( 'Resource spaces titles', 'easyReservations' ) ) );
	}

	/**
	 * Save resource settings
	 */
	private function save_resource_settings( $id ) {
		check_admin_referer( 'easyreservations-resource-settings' );

		ER_Admin_Notices::add_temporary_success( sprintf( __( '%s settings saved', 'easyReservations' ), __( 'Resource', 'easyReservations' ) ) );

		$reservations_current_req = get_post_meta( $id, 'easy-resource-req', true );

		$arrival_possible_on   = array_map( 'intval', $_POST['arrival_on'] );
		$departure_possible_on = array_map( 'intval', $_POST['departure_on'] );
		if ( count( $arrival_possible_on ) == 7 ) {
			$arrival_possible_on = 0;
		}

		if ( count( $departure_possible_on ) == 7 ) {
			$departure_possible_on = 0;
		}

		$req = array(
			'nights-min' => intval( $_POST['units_minimum'] ),
			'nights-max' => intval( $_POST['units_max'] ),
			'pers-min'   => intval( $_POST['person_minimum'] ),
			'pers-max'   => intval( $_POST['person_max'] ),
			'start-on'   => $arrival_possible_on,
			'end-on'     => $departure_possible_on
		);

		if ( $_POST['arrival_between_from'] !== '0' || $_POST['arrival_between_to'] !== '23' ) {
			$req['start-h'] = array( intval( $_POST['arrival_between_from'] ), intval( $_POST['arrival_between_to'] ) );
		}

		if ( $_POST['departure_between_from'] !== '0' || $_POST['departure_between_to'] !== '23' ) {
			$req['end-h'] = array( intval( $_POST['departure_between_from'] ), intval( $_POST['departure_between_to'] ) );
		}

		if ( ( empty( $arrival_possible_on ) && $arrival_possible_on !== 0 ) || ( isset( $req['start-h'] ) && $req['start-h'][1] < $req['start-h'][0] ) ) {
			ER_Admin_Notices::add_temporary_error( __( 'No arrival possible', 'easyReservations' ) );
		}

		if ( ( empty( $departure_possible_on ) && $departure_possible_on !== 0 ) || ( isset( $req['end-h'] ) && $req['end-h'][1] < $req['end-h'][0] ) ) {
			ER_Admin_Notices::add_temporary_error( __( 'No departure possible', 'easyReservations' ) );
		}

		if ( $reservations_current_req !== $req && ! ER_Admin_Notices::has_errors() ) {
			update_post_meta( $id, 'easy-resource-req', $req );
		}

		$base_price = er_sanitize_price( $_POST['base_price'] );
		do_action( 'edit_resource', $id, $base_price );

		if ( $base_price !== get_post_meta( $id, 'reservations_groundprice', true ) ) {
			if ( is_numeric( $base_price ) && $base_price > 0 ) {
				update_post_meta( $id, 'reservations_groundprice', $base_price );
			} else {
				ER_Admin_Notices::add_temporary_error( __( 'Insert correct money format', 'easyReservations' ) );
			}
		}

		$quantity = intval( $_POST['quantity'] );

		if ( isset( $_POST['availability_by'] ) && $_POST['availability_by'] !== 'unit' ) {
			$quantity = array( $quantity, sanitize_text_field( $_POST['availability_by'] ) );
		}

		if ( $quantity !== get_post_meta( $id, 'roomcount', true ) ) {
			if ( is_numeric( $_POST['quantity'] ) ) {
				update_post_meta( $id, 'roomcount', $quantity );
			} else {
				ER_Admin_Notices::add_temporary_error( sprintf( __( '%s has to be numeric', 'easyReservations' ), __( 'Resource space', 'easyReservations' ) ) );
			}
		}

		update_post_meta( $id, 'er_resource_frequency', intval( $_POST['frequency'] ) );

		$billing_method = intval( $_POST['billing_method'] );
		if ( $billing_method !== get_post_meta( $id, 'easy-resource-billing-method', true ) ) {
			update_post_meta( $id, 'easy-resource-billing-method', $billing_method );
		}

		$price_array                 = get_post_meta( $id, 'easy-resource-price', true );
		$reservations_res_price_set  = isset( $_POST['billing_per_person'] ) ? 1 : 0;
		$reservations_res_price_once = isset( $_POST['billing_once'] ) ? 1 : 0;

		if ( ! isset( $price_array[0] ) || $price_array[0] !== $reservations_res_price_set || $price_array[1] !== $reservations_res_price_once ) {/* SET PRICE SETTINGS */
			update_post_meta( $id, 'easy-resource-price', array(
				$reservations_res_price_set,
				$reservations_res_price_once
			) );
		}

		$resource_interval = intval( $_POST['billing_interval'] );
		if ( $billing_method == 3 ) {
			$resource_interval = DAY_IN_SECONDS;
		}

		if ( $resource_interval !== get_post_meta( $id, 'easy-resource-interval', true ) ) {
			update_post_meta( $id, 'easy-resource-interval', $resource_interval );
		}

		$form_template = sanitize_key( $_POST['resource_form_template'] );
		if ( $form_template !== get_post_meta( $id, 'form_template', true ) ) {
			update_post_meta( $id, 'form_template', $form_template );
		}

		$children_price = er_sanitize_price( $_POST['children_price'] );
		if ( $children_price !== get_post_meta( $id, 'reservations_child_price', true ) ) {
			if ( $children_price !== 'error' ) {
				update_post_meta( $id, 'reservations_child_price', $children_price );
			} else {
				ER_Admin_Notices::add_temporary_error( sprintf( __( '%s has to be numeric', 'easyReservations' ), __( 'Children price', 'easyReservations' ) ) );
			}
		}

		do_action( 'easy_resource_save', $id );
	}

	/**
	 * Save filter
	 *
	 * @param $id
	 */
	private function save_resource_filter( $id ) {
		if ( ! empty( $_POST['filter_form_name_field'] ) ) {
			$type   = sanitize_key( $_POST['filter_type'] );
			$filter = array(
				'name' => sanitize_text_field( $_POST['filter_form_name_field'] )
			);

			if ( $type == 'price' ) {
				$filter['type'] = 'price';

				if ( isset( $_POST['filter-price-field'] ) ) {
					$filter['price'] = floatval( $_POST['filter-price-field'] );
				}

				if ( isset( $_POST['filter-children-price'] ) && ! empty( $_POST['filter-children-price'] ) ) {
					$filter['children-price'] = floatval( $_POST['filter-children-price'] );
				}

				if ( isset( $_POST['price_filter_imp'] ) ) {
					$filter['imp'] = intval( $_POST['price_filter_imp'] );
				}

				if ( $_POST['filter-price-mode'] == 'baseprice' ) {
					$time_condition_id = 'cond';
					$condition_id      = 'basecond';
					$type_id           = 'condtype';
					$filter['type']    = 'price';
				} else {
					$time_condition_id = 'timecond';
					$condition_id      = 'cond';
					$type_id           = 'type';

					if ( ! isset( $_POST['filter_form_condition_checkbox'] ) ) {
						if ( $filter['price'] >= 0 ) {
							$filter['type'] = 'charge';
						} else {
							$filter['type'] = 'discount';
						}
					}
				}
				if ( isset( $_POST['filter_form_condition_checkbox'] ) ) {
					$filter[ $type_id ]      = sanitize_key( $_POST['filter_form_discount_type'] );
					$filter[ $condition_id ] = sanitize_key( $_POST['filter_form_discount_cond'] );
					$filter['modus']         = sanitize_key( $_POST['filter_form_discount_mode'] );
				}
			} elseif ( $type == 'unavail' ) {
				$time_condition_id = 'cond';
				$filter['type']    = 'unavail';

				if ( isset( $_POST['filter_form_arrival_checkbox'] ) ) {
					$filter['arrival'] = 1;
				}

				if ( isset( $_POST['filter_form_departure_checkbox'] ) ) {
					$filter['departure'] = 1;
				}
			} elseif ( $type == 'req' ) {
				$time_condition_id = 'cond';
				$filter['type']    = 'req';
			}

			if ( ( $type == 'price' && isset( $_POST['filter_form_usetime_checkbox'] ) ) || $type == 'unavail' || $type == 'req' ) {
				if ( isset( $_POST['price_filter_cond_range'] ) ) {
					$filter[ $time_condition_id ] = 'range';

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
					$filter[ $time_condition_id ] = 'unit';
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
					ER_Admin_Notices::add_temporary_error( sprintf( __( 'Select %s', 'easyReservations' ), _x( 'condition', 'one condition', 'easyReservations' ) ) );
				}
			}

			if ( $type == 'req' ) {
				$arrival_possible_on = 8;

				if ( isset( $_POST['req_filter_start_on'] ) ) {
					$arrival_possible_on = array_map( 'intval', $_POST['req_filter_start_on'] );
					if ( count( $arrival_possible_on ) == 7 ) {
						$arrival_possible_on = 0;
					}
				}

				$departure_possible_on = 8;

				if ( isset( $_POST['req_filter_end_on'] ) ) {
					$departure_possible_on = array_map( 'intval', $_POST['req_filter_end_on'] );
					if ( count( $departure_possible_on ) == 7 ) {
						$departure_possible_on = 0;
					}
				}

				$filter['req'] = array(
					'pers-min'   => intval( $_POST['req_filter_min_pers'] ),
					'pers-max'   => intval( $_POST['req_filter_max_pers'] ),
					'nights-min' => intval( $_POST['req_filter_min_nights'] ),
					'nights-max' => intval( $_POST['req_filter_max_nights'] ),
					'start-on'   => $arrival_possible_on,
					'end-on'     => $departure_possible_on
				);

				if ( $_POST['filter-start-h0'] !== '0' || $_POST['filter-start-h1'] !== '23' ) {
					$filter['req']['start-h'] = array(
						intval( $_POST['filter-start-h0'] ),
						intval( $_POST['filter-start-h1'] )
					);
				}
				if ( $_POST['filter-end-h0'] !== '0' || $_POST['filter-end-h1'] !== '23' ) {
					$filter['req']['end-h'] = array(
						intval( $_POST['filter-end-h0'] ),
						intval( $_POST['filter-end-h1'] )
					);
				}
			}

			$filters = get_post_meta( $id, 'easy_res_filter', true );
			if ( ! isset( $filters ) || empty( $filters ) || ! $filters ) {
				$filters = array();
			}

			if ( isset( $_POST['price_filter_edit'] ) && isset( $filters[ intval( $_POST['price_filter_edit'] ) ] ) ) {
				//Remove existing filter
				unset( $filters[ intval( $_POST['price_filter_edit'] ) ] );
			}

			//Add new filter
			$filters[] = $filter;

			$p1filters = array();
			$pfilters  = array();
			$d1filters = array();
			$d2filters = array();
			$dfilters  = array();
			$ufilters  = array();

			//Pre sort all filters so we can just iterate in calculation
			foreach ( $filters as $key => $filter ) {
				if ( $filter['type'] == 'unavail' || $filter['type'] == 'req' ) {
					$ufilters[]     = $filter;
					$ufiltersSort[] = isset( $filter['imp'] ) ? $filter['imp'] : 1;
				} else {
					if ( $filter['type'] == 'price' && isset( $filter['basecond'] ) ) {
						$p1filters[]           = $filter;
						$p1sortArray[ $key ]   = $filter['imp'];
						$p1dsortArray[ $key ]  = $filter['basecond'];
						$p1dtsortArray[ $key ] = $filter['condtype'];
					} elseif ( $filter['type'] == 'price' ) {
						$pfilters[]         = $filter;
						$psortArray[ $key ] = $filter['imp'];
					} elseif ( isset( $filter['timecond'] ) && isset( $filter['cond'] ) ) {
						$d1filters[]          = $filter;
						$d1isortArray[ $key ] = $filter['imp'];
						$d1sortArray[ $key ]  = $filter['cond'];
						$d1tsortArray[ $key ] = $filter['type'];
					} elseif ( isset( $filter['timecond'] ) ) {
						$d2filters[]          = $filter;
						$d2isortArray[ $key ] = $filter['imp'];
					} else {
						$dfilters[]          = $filter;
						$dsortArray[ $key ]  = $filter['cond'];
						$dtsortArray[ $key ] = $filter['type'];
					}
				}
			}

			if ( isset( $p1sortArray ) ) {
				array_multisort( $p1sortArray, SORT_ASC, SORT_NUMERIC, $p1dtsortArray, SORT_ASC, $p1dsortArray, SORT_DESC, SORT_NUMERIC, $p1filters );
			}

			if ( isset( $psortArray ) ) {
				array_multisort( $psortArray, SORT_ASC, SORT_NUMERIC, $pfilters );
			}

			if ( isset( $d1tsortArray ) ) {
				array_multisort( $d1isortArray, SORT_ASC, $d1tsortArray, SORT_ASC, $d1sortArray, SORT_DESC, SORT_NUMERIC, $d1filters );
			}

			if ( isset( $d2isortArray ) ) {
				array_multisort( $d2isortArray, SORT_DESC, SORT_NUMERIC, $d2filters );
			}

			if ( isset( $dtsortArray ) ) {
				array_multisort( $dtsortArray, SORT_ASC, $dsortArray, SORT_DESC, SORT_NUMERIC, $dfilters );
			}

			if ( isset( $ufiltersSort ) ) {
				array_multisort( $ufiltersSort, SORT_ASC, $ufilters );
			}

			$filters = array_merge( $p1filters, $pfilters, $d1filters, $d2filters, $dfilters, $ufilters );

			if ( ! ER_Admin_Notices::has_errors() ) {
				update_post_meta( $id, 'easy_res_filter', $filters );

				ER_Admin_Notices::add_temporary_success( sprintf( __( 'Filter %s added.', 'easyReservations' ), $filter['name'] ) );
			}
		} else {
			ER_Admin_Notices::add_temporary_error( sprintf( __( 'Please enter a name', 'easyReservations' ) ) );
		}
	}

	/**
	 * Delete's certain filter
	 */
	private function delete_resource_filter( $id ) {
		$filters = get_post_meta( $id, 'easy_res_filter', true );
		unset( $filters[ intval( $_GET['delete_filter'] ) ] );
		update_post_meta( $id, 'easy_res_filter', $filters );
	}

	/**
	 * Save resource slot
	 *
	 * @param $id
	 */
	private function save_resource_slot( $id ) {
		if ( empty( $_POST['slot_range_from'] ) || empty( $_POST['slot_range_to'] ) ) {
			ER_Admin_Notices::add_temporary_error( sprintf( __( 'Please enter arrival and departure as valid date format', 'easyReservations' ) ) );

			return;
		}

		if ( ! isset( $_POST['slot_days'] ) || empty( $_POST['slot_days'] ) ) {
			ER_Admin_Notices::add_temporary_error( sprintf( __( 'Please select days for arrival', 'easyReservations' ) ) );

			return;
		}

		$slot = array(
			'name'           => sanitize_text_field( $_POST['slot_name'] ),
			'range-from'     => ER_DateTime::createFromFormat( er_date_format() . ' H:i:s', sanitize_text_field( $_POST['slot_range_from'] ) . ' 00:00:00' )->format( 'Y-m-d H:i:s' ),
			'range-to'       => ER_DateTime::createFromFormat( er_date_format() . ' H:i:s', sanitize_text_field( $_POST['slot_range_to'] ) . ' 23:59:00' )->format( 'Y-m-d H:i:s' ),
			'repeat'         => intval( $_POST['slot_repeat_amount'] ),
			'repeat-break'   => intval( $_POST['slot_repeat_break'] ),
			'days'           => array_map( 'intval', $_POST['slot_days'] ),
			'from'           => intval( $_POST['slot-from-hour'] ) * 60 + intval( $_POST['slot-from-minute'] ),
			'to'             => intval( $_POST['slot-to-hour'] ) * 60 + intval( $_POST['slot-to-minute'] ),
			'duration'       => intval( $_POST['slot_duration'] ),
			'adults-min'     => isset( $_POST['slot_min_adults'] ) ? intval( $_POST['slot_min_adults'] ) : 0,
			'adults-max'     => isset( $_POST['slot_max_adults'] ) ? intval( $_POST['slot_max_adults'] ) : 0,
			'children-min'   => isset( $_POST['slot_min_children'] ) ? intval( $_POST['slot_min_children'] ) : 0,
			'children-max'   => isset( $_POST['slot_max_children'] ) ? intval( $_POST['slot_max_children'] ) : 0,
			'base-price'     => floatval( $_POST['slot_base_price'] ),
			'children-price' => floatval( $_POST['slot_children_price'] ),
		);

		if ( empty( $slot['name'] ) ) {
			ER_Admin_Notices::add_temporary_error( sprintf( __( 'Please enter a name', 'easyReservations' ) ) );
		}

		if ( ! ER_Admin_Notices::has_errors() ) {
			$slots = get_post_meta( $id, 'easy-resource-slots', true );
			if ( ! empty( $_POST['slot_edit'] ) ) {
				unset( $slots[ intval( $_POST['slot_edit'] ) - 1 ] );
				ER_Admin_Notices::add_temporary_success( sprintf( __( '%s edited', 'easyReservations' ), __( 'Slot', 'easyReservations' ) ) );
			} else {
				ER_Admin_Notices::add_temporary_success( sprintf( __( '%s added', 'easyReservations' ), __( 'Slot', 'easyReservations' ) ) );
			}

			if ( empty( $slots ) ) {
				$slots = array();
			}

			$slots[] = $slot;
			update_post_meta( $id, 'easy-resource-slots', $slots );
		}
	}

	/**
	 * Delete slot
	 *
	 * @param $id
	 */
	private function delete_resource_slot( $id ) {
		$slots = get_post_meta( $id, 'easy-resource-slots', true );
		unset( $slots[ intval( $_GET['delete_slot'] ) ] );
		update_post_meta( $id, 'easy-resource-slots', $slots );
		ER_Admin_Notices::add_temporary_success( sprintf( __( '%s deleted', 'easyReservations' ), __( 'Slot', 'easyReservations' ) ) );
	}
}

return new ER_Admin_Resources();