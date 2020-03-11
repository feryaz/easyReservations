<?php

defined( 'ABSPATH' ) || exit;

/**
 * ER_Ajax class.
 */
class ER_AJAX {

    /**
     * Hook in ajax handlers.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
        add_action( 'template_redirect', array( __CLASS__, 'do_er_ajax' ), 0 );
        self::add_ajax_events();
    }

    /**
     * Get ER Ajax Endpoint.
     *
     * @param string $request Optional.
     *
     * @return string
     */
    public static function get_endpoint( $request = '' ) {
        return esc_url_raw( apply_filters( 'easyreservations_ajax_get_endpoint', add_query_arg( 'er-ajax', $request, remove_query_arg( array(
            'remove_item',
            'add-to-cart',
            'added-to-cart',
            '_wpnonce'
        ), home_url( '/', 'relative' ) ) ), $request ) );
    }

    /**
     * Send headers for ER Ajax Requests.
     */
    private static function er_ajax_headers() {
        if ( !headers_sent() ) {
            send_origin_headers();
            send_nosniff_header();
            nocache_headers();
            header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
            header( 'X-Robots-Tag: noindex' );
            status_header( 200 );
        } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            headers_sent( $file, $line );
            trigger_error( "er_ajax_headers cannot set headers - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // @codingStandardsIgnoreLine
        }
    }

    /**
     * Set ER AJAX constant and headers.
     */
    public static function define_ajax() {
        // phpcs:disable
        if ( !empty( $_GET['er-ajax'] ) ) {
            er_maybe_define_constant( 'DOING_AJAX', true );
            er_maybe_define_constant( 'ER_DOING_AJAX', true );
            if ( !WP_DEBUG || ( WP_DEBUG && !WP_DEBUG_DISPLAY ) ) {
                @ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON.
            }
            $GLOBALS['wpdb']->hide_errors();
        }
        // phpcs:enable
    }

    /**
     * Check for ER Ajax request and fire action.
     */
    public static function do_er_ajax() {
        global $wp_query;

        // phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification
        if ( !empty( $_GET['er-ajax'] ) ) {
            $wp_query->set( 'er-ajax', sanitize_text_field( wp_unslash( $_GET['er-ajax'] ) ) );
        }

        $action = $wp_query->get( 'er-ajax' );

        if ( $action ) {
            self::er_ajax_headers();
            $action = sanitize_text_field( $action );
            do_action( 'er_ajax_' . $action );

            wp_die();
        }
        // phpcs:enable
    }

    /**
     * Hook in methods - uses WordPress ajax handlers (admin-ajax).
     */
    public static function add_ajax_events() {
        $ajax_events = array(
            'calendar'                      => true,
            'form'                          => true,
            'send_calendar'                 => true,
            'apply_coupon'                  => true,
            'update_order_review'           => true,
            'remove_coupon'                 => true,
            'timeline_data'                 => false,
            'timeline_update_reservation'   => false,
            'json_search_order'             => false,
            'mark_order_status'             => false,
            'mark_reservation_status'       => false,
            'get_order_details'             => false,
            'get_reservation_details'       => false,
            'refund_line_items'             => false,
            'delete_refund'                 => false,
            'load_receipt_items'            => false,
            'save_receipt_items'            => false,
            'calc_line_taxes'               => false,
            'add_custom'                    => false,
            'add_order_coupon'              => false,
            'add_receipt_fee'               => false,
            'add_receipt_tax'               => false,
            'add_order_note'                => false,
            'add_reservation_to_order'      => false,
            'delete_order_note'             => false,
            'remove_custom'                 => false,
            'remove_receipt_item'           => false,
            'remove_receipt_tax'            => false,
            'remove_order_coupon'           => false,
            'remove_reservation_from_order' => false,
            'get_customer_details'          => false,
            'json_search_customers'         => false,
        );

        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( 'wp_ajax_easyreservations_' . $ajax_event, array( __CLASS__, $ajax_event ) );

            if ( $nopriv ) {
                add_action( 'wp_ajax_nopriv_easyreservations_' . $ajax_event, array( __CLASS__, $ajax_event ) );
                add_action( 'er_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            }
        }
    }

    /**
     * Frontend form submit
     */
    public static function form() {
        check_ajax_referer( 'easyreservations-form', 'easy-form-nonce' );

        $form = ER()->form_handler();
        $form->process_reservation_and_checkout();

        wp_send_json( array(
            'result'   => 'success'
        ) );

        exit;
    }

    /**
     * [date] calendar
     * @throws Exception
     */
    public static function calendar() {
        check_ajax_referer( 'er-date-selection', 'security' );

        $adults       = isset( $_POST['adults'] ) ? absint( $_POST['adults'] ) : 1;
        $children     = isset( $_POST['children'] ) ? absint( $_POST['children'] ) : 0;
        $resource     = ER()->resources()->get( absint( $_POST['resource'] ) );
        $req          = $resource->get_requirements();
        $quantity     = $resource->get_quantity();
        $availability = new ER_Resource_Availability( $resource, 0, $adults, $children, ER()->cart->get_reservations() );
        $now          = er_get_datetime();
        $now_string   = $now->format( er_date_format() );

        if( !is_numeric( $_POST['date'] ) ){
            $date = new ER_DateTime( sanitize_text_field( $_POST['date'] ) );
        } else {
            $date = clone $now;

            if ( !empty( $_POST['minDate'] ) ) {
                if( is_numeric( $_POST['minDate'] ) ){
                    $date->add( new DateInterval( 'PT' . intval( $_POST['minDate'] ) * 86400 . 'S' ) );
                } else {
                    $date = new ER_DateTime( sanitize_text_field( $_POST['minDate'] ) );
                }
            }
        }

        $arrival = false;

        if ( intval( $_POST['arrival'] ) !== 0 ) {
            $arrival = new ER_DateTime( sanitize_text_field( $_POST['arrival'] ) );

            if ( !empty( $_POST['arrivalTime'] ) ) {
                $arrival_time = explode( ':', sanitize_text_field( $_POST['arrivalTime'] ));
                $arrival->setTime( $arrival_time[0], $arrival_time[1] );
            }
        }

        if( !empty( $_POST['minDate'] ) ){
            if ( $arrival === false && $date->format( 'm.Y' ) === $now->format( 'm.Y' ) ) {
                $date = clone $now;
            } elseif ( $arrival === false && $date->format( 'm.Y' ) !== $now->format( 'm.Y' ) ) {
                $date->modify( 'first day of this month' );
            }
        }

        $date->setTime( 0, 0, 0 );

        $end = clone $date;
        $end = $end->modify( 'first day of next month' );

        if ( absint( $_POST['months'] ) > 1 ) {
            $end->modify( '+' . ( absint( $_POST['months'] ) - 1 ) . ' month' );
        }

        $days                      = array();
        $was_unavailable           = false;
        $earliest_possible_arrival = new DateTimeImmutable( wp_date( 'd.m.Y H:i' ) );
        $earliest_possible_arrival = $earliest_possible_arrival->add( new DateInterval( 'PT' . er_earliest_arrival() . 'S' ) );

        if ( $date < $earliest_possible_arrival ){
            $date->setTimestamp( $earliest_possible_arrival->setTime( 0, 0, 0 )->getTimestamp() );
        }

        $last_departure = $arrival;

        while ( $date <= $end ) {

            $date_string = $date->format( er_date_format() );

            if( $was_unavailable ){
                $days[$date_string] = array(
                    'availability' => 0,
                    'time'         => array(0,23),
                );

                $date->modify( '+1 day' );
            }

            if ( $resource->get_slots() ) {
                $matrix = er_resource_get_slot_matrix( $availability, $resource, $date, true, $adults, $children );

                $days[$date_string] = empty( $matrix ) ? array( 0 ) : $matrix;

                $date->modify( '+1 day' );

                continue;
            }

            $left = false;

            if ( $arrival && empty( $_POST['arrivalTime'] ) ) {
                $arrival->setTime( 0, 0 );
            }

            if ( $resource->get_filter() && !empty( $resource->get_filter() ) ) {
                foreach ( $resource->get_filter() as $filter ) {
                    if ( $filter['type'] == 'req' ) {
                        if ( $resource->time_condition(
                            $filter, $arrival ? $arrival : $date
                        ) ) {
                            $req = $filter['req'];
                            break;
                        }
                    }
                }
            }

            $latest_possible_arrival     = isset( $req['start-h'] ) ? $req['start-h'][1] : 23;
            $earliest_possible_departure = isset( $req['end-h'] ) ? $req['end-h'][0] : 0;

            if ( !$arrival ) {
                $time = isset( $req['start-h'] ) ? $req['start-h'] : array( 0, 23 );
            } else {
                $time = isset( $req['end-h'] ) ? $req['end-h'] : array( 0, 23 );
            }

            if( $now_string === $date_string ){
                $current_hour = $now->format( 'G' );
                $time[0] = max( $time[0], $current_hour );
                $time[1] = min( $time[1], $current_hour );
            }

            //Check for possible arrival
            if ( !$arrival ) {
                $avail = 0;

                if ( isset( $req['start-on'] ) && $req['start-on'] !== 0 && ( $req['start-on'] == 8 || !in_array( $date->format( "N" ), $req['start-on']  ) ) ) {
                    $avail = $quantity + 1;
                } else {
                    if ( $resource->get_frequency() < DAY_IN_SECONDS ) {
                        $left          = array();
                        $date_to_check = er_date_add_seconds( $date, $time[0] * HOUR_IN_SECONDS );
                        $until         = er_date_add_seconds( $date, $time[1] * HOUR_IN_SECONDS + 3599 );

                        while( $earliest_possible_arrival > $date_to_check ){
                            $left[$date_to_check->format( 'H:i' )] = 0;

                            $date_to_check->add( new DateInterval( 'PT' . $resource->get_frequency() . 'S' ) );
                        }

                        while ( $date_to_check < $until ) {
                            $avail = $quantity;

                            if ( $date_to_check >= $now ) {
                                $avail = $availability->check_whole_period(
                                    $date_to_check, er_date_add_seconds( $date, $resource->get_frequency() )
                                );

                                if( !is_numeric( $avail ) ){
                                    if( $avail === 'departure' ){
                                        $avail = 0;
                                    } else {
                                        $avail = $quantity + 1;
                                    }
                                }
                            }

                            $left[$date_to_check->format( 'H:i' )] = $quantity - $avail;

                            $date_to_check->add( new DateInterval( 'PT' . $resource->get_frequency() . 'S' ) );
                        }
                    } else {
                        $avail = $availability->check_arrivals_and_departures(
                            er_date_add_seconds( $date, $latest_possible_arrival * HOUR_IN_SECONDS ),
                            er_date_add_seconds( $date, $earliest_possible_departure * HOUR_IN_SECONDS + $req['nights-min'] * $resource->get_billing_interval() ),
                            'arrival'
                        );

                        if ( !is_object( $avail ) ) {
                            //If numeric day is unavailable else only arrival is not possible
                            $avail = is_numeric( $avail ) ? $quantity : $quantity + 1;
                        } else {
                            if ( $avail->count_all >= $quantity ) {
                                $avail->count_all = $avail->count_all + $avail->arrival - $avail->departure;

                                if ( !empty( $avail->max_arrival ) ) {
                                    $hour    = date( 'G', strtotime( $avail->max_arrival ) );
                                    $time[1] = $hour < $time[1] ? $hour : $time[1];
                                }

                                if ( !empty( $avail->min_departure ) ) {
                                    $hour    = date( 'G', strtotime( $avail->min_departure ) );
                                    $time[0] = $hour > $time[0] ? $hour : $time[0];
                                }

                                if ( $time[0] === $time[1] ) {
                                    $avail->count_all = $quantity;
                                }
                            }

                            $avail = $avail->count_all;
                        }
                    }
                }

                if ( !$left ) {
                    $left = $quantity - $avail;
                }

            } else {
                //Check for possible departure

                if ( empty( $_POST['arrivalTime'] ) ) {
                    $arrival->setTime( $latest_possible_arrival, 0);
                }

                if ( isset( $req['end-on'] ) && $req['end-on'] !== 0 && ( $req['end-on'] == 8 || !in_array( $date->format( "N" ), $req['end-on'] ) ) ) {
                    $left = -1;
                } else {
                    if ( $resource->get_frequency() < DAY_IN_SECONDS ) {
                        $left      = array();
                        $departure = er_date_add_seconds( $date, $time[0] * HOUR_IN_SECONDS );
                        $until     = er_date_add_seconds( $date, $time[1] * HOUR_IN_SECONDS + 3599 );
                        while ( $departure < $until ) {
                            $billing_units = $resource->get_billing_units( $arrival, $departure );

                            if ( !$was_unavailable && $req['nights-min'] <= $billing_units ) {
                                if ( ( $req['nights-max'] > 0 && $req['nights-max'] < $billing_units ) && !$was_unavailable ) {
                                    $was_unavailable = $date_string;
                                }

                                $avail = $quantity;

                                if ( !$was_unavailable && $departure >= $now && $departure > $arrival ) {
                                    $avail = $availability->check_whole_period( $resource->availability_by('unit') ? $arrival : $last_departure, $departure );

                                    if ( !is_numeric( $avail ) ) {
                                        if ( $avail === 'arrival' ) {
                                            $avail = 0;
                                        } else {
                                            $avail = $quantity + 1;
                                        }
                                    } elseif ( $quantity - $avail < 1 ) {
                                        $was_unavailable = $date_string;
                                    }
                                }

                                $left[$departure->format( 'H:i' )] = $quantity - $avail;
                            } else {
                                $left[$departure->format( 'H:i' )] = 0;
                            }

                            $departure->add( new DateInterval( 'PT' . $resource->get_frequency() . 'S' ) );
                            $last_departure = $departure;
                        }
                    } else {
                        //We check latest possible departure on that day as the availability query returns us the latest departure, but only until
                        $departure = er_date_add_seconds( $date, $latest_possible_arrival * HOUR_IN_SECONDS );

                        $billing_units = $resource->get_billing_units(
                            $arrival, $departure
                        );

                        if ( ( $req['nights-max'] > 0 && $req['nights-max'] < $billing_units ) && !$was_unavailable ) {
                            $was_unavailable = $date_string;
                        }

                        if ( !$was_unavailable  && $req['nights-min'] <= $billing_units ) {
                            $avail = $availability->check_arrivals_and_departures( $resource->availability_by( 'unit' ) ? $arrival : $last_departure, $departure, 'departure' );

                            //If an availability filter is matched the check returns a numeric value instead
                            if ( !is_object( $avail ) ) {
                                //If numeric day is unavailable else only departure is not possible
                                $avail = is_numeric( $avail ) ? $quantity : $quantity + 1;
                            } else {
                                if ( $avail->count_all >= $quantity ) {
                                    $avail->count_all = $avail->count_all + $avail->arrival - $avail->departure;

                                    //[0] Minimum departure time
                                    //[1] Maximum departure time
                                    if ( !empty( $avail->max_arrival ) ) {
                                        $hour    = date( 'G', strtotime( $avail->max_arrival ) );
                                        $time[1] = $hour < $time[1] ? $hour : $time[1];
                                    }

                                    if ( !empty( $avail->min_departure ) ) {
                                        $hour    = date( 'G', strtotime( $avail->min_departure ) );
                                        $time[0] = $hour > $time[0] ? $hour : $time[0];
                                    }

                                    if ( $time[0] === $time[1] ) {
                                        $avail->count_all = $quantity;
                                    }
                                }

                                $avail = $avail->count_all;
                            }

                            if ( $quantity - $avail === 0 ) {
                                $was_unavailable = $date_string;
                            }

                            $left = $quantity - $avail;
                        } else {
                            $left = -1;
                        }

                        $last_departure = $departure;
                    }
                }
            }

            $days[$date_string] = array(
                'availability'             => $left,
                'time'                     => $time,
            );

            $date->modify( '+1 day' );
        }

        if ( $resource->get_slots() ) {
            $days['slots'] = true;
            wp_send_json( $days );
        } else {
            $days['max'] = '25.03.2020';
            $days['first_possible'] =
                $arrival ? er_date_add_seconds( $arrival, $req['nights-min'] * $resource->get_billing_interval() )->format( er_date_format() . ' H:i' )
                    : $earliest_possible_arrival->format( er_date_format() . ' H:i' );
            wp_send_json( $days );
        }
    }

    /**
     * AJAX update order review on checkout.
     */
    public static function update_order_review() {
        check_ajax_referer( 'update-order-review', 'security' );

        er_maybe_define_constant( 'RESERVATIONS_CHECKOUT', true );

        if ( ER()->cart->is_empty() && !is_customize_preview() && apply_filters( 'easyreservations_checkout_update_order_review_expired', true ) ) {
            wp_send_json(
                array(
                    'fragments' => apply_filters(
                        'easyreservations_update_order_review_fragments',
                        array(
                            'form.easyreservations-checkout' => '<div class="easyreservations-error">' . __( 'Sorry, your session has expired.', 'easyReservations' ) . ' <a href="' . esc_url( er_get_page_permalink( 'shop' ) ) . '" class="er-backward">' . __( 'Return to shop', 'easyReservations' ) . '</a></div>',
                        )
                    ),
                )
            );
        }

        do_action( 'easyreservations_checkout_update_order_review' );

        ER()->session->set( 'chosen_payment_method', empty( $_POST['payment_method'] ) ? '' : er_clean( wp_unslash( $_POST['payment_method'] ) ) );
        ER()->customer->set_props(
            array(
                'address_country'   => isset( $_POST['country'] ) ? er_clean( wp_unslash( $_POST['country'] ) ) : null,
                'address_state'     => isset( $_POST['state'] ) ? er_clean( wp_unslash( $_POST['state'] ) ) : null,
                'address_postcode'  => isset( $_POST['postcode'] ) ? er_clean( wp_unslash( $_POST['postcode'] ) ) : null,
                'address_city'      => isset( $_POST['city'] ) ? er_clean( wp_unslash( $_POST['city'] ) ) : null,
                'address_address_1' => isset( $_POST['address'] ) ? er_clean( wp_unslash( $_POST['address'] ) ) : null,
                'address_address_2' => isset( $_POST['address_2'] ) ? er_clean( wp_unslash( $_POST['address_2'] ) ) : null,
            )
        );

        ER()->customer->save();

        $order   = ER()->cart->get_order();
        $errors  = new WP_Error();
        $customs = ER()->checkout()->get_form_data_custom( $errors, ER()->cart->get_order(), 'checkout' );

        foreach( $customs as $custom ){
            $order->add_custom( $custom );
        }

        ER()->cart->calculate_totals();

        // Get order review fragment.
        ob_start();
        easyreservations_order_review();
        $order_review = ob_get_clean();

        $checkout_payment = '';
        if(function_exists('easyreservations_checkout_payment')){
            // Get checkout payment fragment.
            ob_start();
            easyreservations_checkout_payment();
            $checkout_payment = ob_get_clean();
        }

        $checkout_deposit = '';
        if(function_exists('easyreservations_checkout_deposit_form')){
            // Get checkout payment fragment.
            ob_start();
            easyreservations_checkout_deposit_form();
            $checkout_deposit = ob_get_clean();
        }

        // Get messages if reload checkout is not true.
        $reload_checkout = isset( ER()->session->reload_checkout ) ? true : false;
        if ( !$reload_checkout ) {
            $messages = er_print_notices( true );
        } else {
            $messages = '';
        }

        unset( ER()->session->refresh_totals, ER()->session->reload_checkout );

        wp_send_json(
            array(
                'result'    => empty( $messages ) ? 'success' : 'failure',
                'messages'  => $messages,
                'reload'    => $reload_checkout,
                'fragments' => apply_filters(
                    'easyreservations_update_order_review_fragments',
                    array(
                        '.easyreservations-checkout-review-order-table' => $order_review,
                        '.easyreservations-checkout-payment'            => $checkout_payment,
                        '.easyreservations-checkout-deposit'            => $checkout_deposit,
                    )
                ),
            )
        );
    }

    /**
     * AJAX apply coupon on checkout page.
     */
    public static function apply_coupon() {
        check_ajax_referer( 'apply-coupon', 'security' );

        if ( !empty( $_POST['coupon_code'] ) ) {
            ER()->cart->apply_coupon( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        } else {
            er_add_notice( __( 'Please enter a coupon code.', 'easyReservations' ), 'error' );
        }

        er_print_notices();
        wp_die();
    }

    /**
     * AJAX remove coupon on cart and checkout page.
     */
    public static function remove_coupon() {
        check_ajax_referer( 'remove-coupon', 'security' );

        $coupon = isset( $_POST['coupon'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon'] ) ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        if ( empty( $coupon ) ) {
            er_add_notice( __( 'Sorry there was a problem removing this coupon.', 'easyReservations' ), 'error' );
        } else {
            ER()->cart->remove_coupon( $coupon );
            er_add_notice( __( 'Coupon has been removed.', 'easyReservations' ) );
        }

        er_print_notices();
        wp_die();
    }

    /**
     * Mark an order with a status.
     */
    public static function mark_order_status() {
        if ( current_user_can( 'edit_easy_orders' ) && check_admin_referer( 'easyreservations-mark-order-status' ) && isset( $_GET['status'], $_GET['order_id'] ) ) {
            $status = sanitize_text_field( wp_unslash( $_GET['status'] ) );
            $order  = er_get_order( absint( wp_unslash( $_GET['order_id'] ) ) );

            if ( ER_Order_Status::get_title( $status ) && $order ) {
                // Initialize payment gateways in case order has hooked status transition actions.
                ER()->payment_gateways();

                $order->update_status( $status, '', true );
                do_action( 'easyreservations_order_edit_status', $order->get_id(), $status );
            }
        }

        wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=easy_order' ) );
        exit;
    }

    /**
     * Mark an order with a status.
     */
    public static function mark_reservation_status() {
        if ( current_user_can( 'edit_easy_orders' ) && check_admin_referer( 'easyreservations-mark-reservation-status' ) && isset( $_GET['status'], $_GET['reservation_id'] ) ) {
            $status = sanitize_text_field( wp_unslash( $_GET['status'] ) );
            $reservation  = er_get_reservation( absint( wp_unslash( $_GET['reservation_id'] ) ) );

            if ( ER_Reservation_Status::get_title( $status ) && $reservation ) {
                $reservation->update_status( $status, '', true );
                do_action( 'easyreservations_reservation_edit_status', $reservation->get_id(), $status );
            }
        }

        wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=easy_order' ) );
        exit;
    }

    /**
     * Get order details.
     */
    public static function get_order_details() {
        check_admin_referer( 'easyreservations-preview-order', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_GET['order_id'] ) ) {
            wp_die( -1 );
        }

        $order = er_get_order( absint( $_GET['order_id'] ) ); // WPCS: sanitization ok.

        if ( $order ) {
            include_once 'admin/list-tables/class-er-admin-list-table-orders.php';

            wp_send_json_success( ER_Admin_List_Table_Orders::order_preview_get_order_details( $order ) );
        }

        wp_die();
    }

    /**
     * Get order details.
     */
    public static function get_reservation_details() {
        check_admin_referer( 'easyreservations-preview-reservation', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_GET['reservation_id'] ) ) {
            wp_die( -1 );
        }

        $reservation = er_get_reservation( absint( $_GET['reservation_id'] ) ); // WPCS: sanitization ok.

        if ( $reservation ) {
            include_once 'admin/list-tables/class-er-admin-list-table-reservations.php';

            wp_send_json_success( ER_Admin_List_Table_Reservations::reservation_preview_get_reservation_details( $reservation ) );
        }

        wp_die();
    }

    /**
     * Search for customers and return json.
     */
    public static function json_search_customers() {
        ob_start();

        check_ajax_referer( 'search-customers', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_GET['term'] ) ) {
            wp_die( -1 );
        }

        $term  = isset( $_GET['term'] ) ? (string) er_clean( wp_unslash( $_GET['term'] ) ) : '';
        $limit = 0;

        if ( empty( $term ) ) {
            wp_die();
        }

        $ids = array();
        // Search by ID.
        if ( is_numeric( $term ) ) {
            $customer = new ER_Customer( intval( $term ) );

            // Customer does not exists.
            if ( 0 !== $customer->get_id() ) {
                $ids = array( $customer->get_id() );
            }
        }

        // Usernames can be numeric so we first check that no users was found by ID before searching for numeric username, this prevents performance issues with ID lookups.
        if ( empty( $ids ) ) {
            // If search is smaller than 3 characters, limit result set to avoid
            // too many rows being returned.
            if ( 3 > strlen( $term ) ) {
                $limit = 20;
            }
            $ids = er_search_customers( $term, $limit );
        }

        $found_customers = array();

        if ( !empty( $_GET['exclude'] ) ) {
            $ids = array_diff( $ids, array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) ) );
        }

        foreach ( $ids as $id ) {
            $customer = new ER_Customer( $id );
            /* translators: 1: user display name 2: user ID 3: user email */
            $found_customers[$id] = sprintf(
            /* translators: $1: customer name, $2 customer id, $3: customer email */
                esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'easyReservations' ),
                $customer->get_first_name() . ' ' . $customer->get_last_name(),
                $customer->get_id(),
                $customer->get_email()
            );
        }

        wp_send_json( apply_filters( 'easyreservations_json_search_found_customers', $found_customers ) );
    }

    /**
     * Get customer details via ajax.
     */
    public static function get_customer_details() {
        check_ajax_referer( 'get-customer-details', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['user_id'] ) ) {
            wp_die( -1 );
        }

        $user_id  = absint( $_POST['user_id'] );
        $customer = new ER_Customer( $user_id );

        $customer_data = apply_filters( 'easyreservations_ajax_get_customer_details', $customer->get_data(), $customer, $user_id );

        wp_send_json( $customer_data );
    }

    /**
     * Add order fee via ajax.
     *
     * @throws Exception If order is invalid.
     */
    public static function add_receipt_fee() {
        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['object_id'] ) ) {
            wp_die( -1 );
        }

        $response = array();

        try {
            $object_id   = absint( $_POST['object_id'] );
            $object_type = sanitize_key( $_POST['object_type'] );

            if ( $object_type === 'order' ) {
                $object = er_get_order( $object_id );
            } else {
                $object = er_get_reservation( $object_id );
            }

            if ( !$object ) {
                throw new Exception( __( 'Invalid object', 'easyReservations' ) );
            }

            $amount = isset( $_POST['amount'] ) ? er_clean( wp_unslash( $_POST['amount'] ) ) : 0;

            if ( strstr( $amount, '%' ) ) {
                $formatted_amount = $amount;
                $percent          = floatval( trim( $amount, '%' ) );
                $amount           = $object->get_total() * ( $percent / 100 );
            } else {
                $amount           = floatval( $amount );
                $formatted_amount = er_price( $amount, true );
            }

            $fee = new ER_Receipt_Item_Fee();
            $fee->set_subtotal( $amount );
            $fee->set_total( $amount );
            /* translators: %s fee amount */
            $fee->set_name( sprintf( __( '%s fee', 'easyReservations' ), er_clean( $formatted_amount ) ) );

            $object->add_item( $fee );
            $object->calculate_taxes( false );
            $object->calculate_totals( false );
            $object->save();

            ob_start();
            include 'admin/meta-boxes/views/html-receipt-items.php';
            $response['html'] = ob_get_clean();
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
        }

        // wp_send_json_success must be outside the try block not to break phpunit tests.
        wp_send_json_success( $response );
    }

    /**
     * Add order tax column via ajax.
     *
     * @throws Exception If order or tax rate is invalid.
     */
    public static function add_receipt_tax() {
        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['object_id'] ) ) {
            wp_die( -1 );
        }

        $response = array();

        try {
            $object_id   = absint( $_POST['object_id'] );
            $object_type = sanitize_key( $_POST['object_type'] );

            if ( $object_type === 'order' ) {
                $object = er_get_order( $object_id );
            } else {
                $object = er_get_reservation( $object_id );
            }

            if ( !$object ) {
                throw new Exception( __( 'Invalid object', 'easyReservations' ) );
            }

            $rate_id = isset( $_POST['rate_id'] ) ? absint( $_POST['rate_id'] ) : '';

            if ( !$rate_id ) {
                throw new Exception( __( 'Invalid rate', 'easyReservations' ) );
            }

            // Add new tax.
            $item = new ER_Receipt_Item_Tax();
            $item->set_rate_id( $rate_id );
            $item->set_object_id( $object_id );
            $item->set_object_type( $object_type );
            $item->save();

            ob_start();
            include 'admin/meta-boxes/views/html-receipt-items.php';
            $response['html'] = ob_get_clean();
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
        }

        // wp_send_json_success must be outside the try block not to break phpunit tests.
        wp_send_json_success( $response );
    }

    /**
     * Add order discount via ajax.
     *
     * @throws Exception If order or coupon is invalid.
     */
    public static function add_order_coupon() {
        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['order_id'] ) ) {
            wp_die( -1 );
        }

        $response = array();

        try {
            $order_id           = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
            $order              = er_get_order( $order_id );

            if ( !$order ) {
                throw new Exception( __( 'Invalid order', 'easyReservations' ) );
            }

            if ( empty( $_POST['coupon'] ) ) {
                throw new Exception( __( 'Invalid coupon', 'easyReservations' ) );
            }

            // Add user ID and/or email so validation for coupon limits works.
            $user_id_arg    = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
            $user_email_arg = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';

            if ( $user_id_arg ) {
                $order->set_customer_id( $user_id_arg );
            }

            if ( $user_email_arg ) {
                $order->set_email( $user_email_arg );
            }

            $result = apply_filters( 'easyreservations_add_order_coupon', wp_unslash( $_POST['coupon'] ), $order, false );

            if ( is_wp_error( $result ) ) {
                throw new Exception( html_entity_decode( wp_strip_all_tags( $result->get_error_message() ) ) );
            }

            $order->calculate_taxes();
            $order->calculate_totals( false );
            $order->save();

            ob_start();
            $object = $order;
            include 'admin/meta-boxes/views/html-receipt-items.php';
            $response['html'] = ob_get_clean();
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
        }

        // wp_send_json_success must be outside the try block not to break phpunit tests.
        wp_send_json_success( $response );
    }

    /**
     * Remove coupon from an order via ajax.
     *
     * @throws Exception If order or coupon is invalid.
     */
    public static function remove_order_coupon() {
        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['order_id'] ) ) {
            wp_die( -1 );
        }

        $response = array();

        try {
            $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
            $order    = er_get_order( $order_id );

            if ( !$order ) {
                throw new Exception( __( 'Invalid order', 'easyReservations' ) );
            }

            if ( empty( $_POST['coupon'] ) ) {
                throw new Exception( __( 'Invalid coupon', 'easyReservations' ) );
            }

            do_action('easyreservations_remove_order_coupon', wp_unslash( $_POST['coupon'] ), $order);

            $order->calculate_taxes();
            $order->calculate_totals( false );
            $order->save();

            ob_start();
            $object = $order;
            include 'admin/meta-boxes/views/html-receipt-items.php';
            $response['html'] = ob_get_clean();
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
        }

        // wp_send_json_success must be outside the try block not to break phpunit tests.
        wp_send_json_success( $response );
    }

    /**
     * Remove an order item.
     *
     * @throws Exception If order is invalid.
     */
    public static function remove_receipt_item() {
        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['object_id'], $_POST['receipt_item_ids'] ) ) {
            wp_die( -1 );
        }

        $response = array();

        try {
            $object_id   = absint( $_POST['object_id'] );
            $object_type = sanitize_key( $_POST['object_type'] );

            if ( $object_type === 'order' ) {
                $object = er_get_order( $object_id );
            } else {
                $object = er_get_reservation( $object_id );
            }

            if ( !$object ) {
                throw new Exception( __( 'Invalid object', 'easyReservations' ) );
            }

            if ( !isset( $_POST['receipt_item_ids'] ) ) {
                throw new Exception( __( 'Invalid items', 'easyReservations' ) );
            }

            $receipt_item_ids     = wp_unslash( $_POST['receipt_item_ids'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $items              = ( !empty( $_POST['items'] ) ) ? wp_unslash( $_POST['items'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            if ( !is_array( $receipt_item_ids ) && is_numeric( $receipt_item_ids ) ) {
                $receipt_item_ids = array( $receipt_item_ids );
            }

            // If we passed through items it means we need to save first before deleting.
            if ( !empty( $items ) ) {
                $save_items = array();
                parse_str( $items, $save_items );
                er_save_receipt_items( $object, $save_items );
            }

            if ( !empty( $receipt_item_ids ) ) {
                foreach ( $receipt_item_ids as $item_id ) {
                    $item_id = absint( $item_id );
                    $item    = $object->get_item( $item_id );

                    $object->add_note( sprintf( __( 'Deleted %s', 'easyReservations' ), $item->get_name() ), false, true );

                    er_receipt_delete_item( $item_id, $object_id );
                }
            }

            $object->calculate_taxes();
            $object->calculate_totals( false );
            $object->save();

            // Get HTML to return.
            ob_start();
            include 'admin/meta-boxes/views/html-receipt-items.php';
            $response['html'] = ob_get_clean();
            $notes_html = '';

            if ( $object_type === 'order' ) {
                ob_start();
                $notes = er_get_order_notes( array( 'order_id' => $object_id ) );
                include 'admin/meta-boxes/views/html-order-notes.php';
                $response['notes_html'] = ob_get_clean();
            }
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
        }

        // wp_send_json_success must be outside the try block not to break phpunit tests.
        wp_send_json_success( $response );
    }

    /**
     * Remove an custom item.
     *
     * @throws Exception If order is invalid.
     */
    public static function remove_custom() {
        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['object_id'], $_POST['receipt_item_ids'] ) ) {
            wp_die( -1 );
        }

        $response = array();

        try {
            $object_id = absint( $_POST['object_id'] );
            $object_type = sanitize_key( $_POST['object_type'] );

            if ( $object_type === 'order' ) {
                $object = er_get_order( $object_id );

                if ( !$object ) {
                    throw new Exception( __( 'Invalid order', 'easyReservations' ) );
                }
            } else {
                $object = er_get_reservation( $object_id );

                if ( !$object ) {
                    throw new Exception( __( 'Invalid reservation', 'easyReservations' ) );
                }
            }

            if ( !isset( $_POST['receipt_item_ids'] ) ) {
                throw new Exception( __( 'Invalid items', 'easyReservations' ) );
            }

            $receipt_item_ids     = wp_unslash( $_POST['receipt_item_ids'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            if ( !is_array( $receipt_item_ids ) && is_numeric( $receipt_item_ids ) ) {
                $receipt_item_ids = array( $receipt_item_ids );
            }

            if ( !empty( $receipt_item_ids ) ) {
                foreach ( $receipt_item_ids as $item_id ) {
                    $item_id = absint( $item_id );
                    $item    = $object->get_item( $item_id );

                    $object->add_note( sprintf( __( 'Deleted custom data %s', 'easyReservations' ), $item->get_name() ), false, true );

                    er_receipt_delete_item( $item_id, $object_id );
                }
            }

            // Get HTML to return.
            ob_start();
            include 'admin/meta-boxes/views/html-custom-data.php';
            $items_html = ob_get_clean();
            $notes_html = '';

            if ( $object_type === 'order' ) {
                ob_start();
                $notes = er_get_order_notes( array( 'order_id' => $object_id ) );
                include 'admin/meta-boxes/views/html-order-notes.php';
                $notes_html = ob_get_clean();
            }

            wp_send_json_success(
                array(
                    'html'       => $items_html,
                    'notes_html' => $notes_html,
                )
            );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
        }

        // wp_send_json_success must be outside the try block not to break phpunit tests.
        wp_send_json_success( $response );
    }

    /**
     * Remove an order tax.
     *
     * @throws Exception If there is an error whilst deleting the rate.
     */
    public static function remove_receipt_tax() {
        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['order_id'], $_POST['rate_id'] ) ) {
            wp_die( -1 );
        }

        $response = array();

        try {
            $object_id   = absint( $_POST['object_id'] );
            $object_type = sanitize_key( $_POST['object_type'] );
            $rate_id  = absint( $_POST['rate_id'] );

            if ( $object_type === 'order' ) {
                $object = er_get_order( $object_id );
            } else {
                $object = er_get_reservation( $object_id );
            }

            if ( !$object->is_editable() ) {
                throw new Exception( __( 'Object not editable', 'easyReservations' ) );
            }

            er_receipt_delete_item( $rate_id, $object->get_id() );

            // Need to load order again after deleting to have latest items before calculating.
            if ( $object_type === 'order' ) {
                $object = er_get_order( $object_id );
            } else {
                $object = er_get_reservation( $object_id );
            }

            $object->calculate_totals( false );
            $object->save();

            ob_start();
            include 'admin/meta-boxes/views/html-receipt-items.php';
            $response['html'] = ob_get_clean();
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
        }

        // wp_send_json_success must be outside the try block not to break phpunit tests.
        wp_send_json_success( $response );
    }

    /**
     * Calc line tax.
     */
    public static function calc_line_taxes() {
        check_ajax_referer( 'calc-totals', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['object_id'], $_POST['items'] ) ) {
            wp_die( -1 );
        }

        $object_id   = absint( $_POST['object_id'] );
        $object_type = sanitize_key( $_POST['object_type'] );

        if ( $object_type === 'order' ) {
            $object = er_get_order( $object_id );
        } else {
            $object = er_get_reservation( $object_id );
        }

        // Parse the jQuery serialized items.
        $items = array();
        parse_str( wp_unslash( $_POST['items'] ), $items ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        // Save order items first.
        er_save_receipt_items( $object, $items );

        if ( $object_type === 'order' ) {
            $object = er_get_order( $object_id );
        } else {
            $object = er_get_reservation( $object_id );
        }

        // Grab the order and recalculate taxes.
        $object->calculate_taxes();
        $object->calculate_totals( false );
        $object->save();

        include 'admin/meta-boxes/views/html-receipt-items.php';
        wp_die();
    }

    /**
     * Save order items via ajax.
     */
    public static function save_receipt_items() {
        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['object_id'], $_POST['items'] ) ) {
            wp_die( -1 );
        }

        $object_id     = absint( $_POST['object_id'] );
        $object_type   = sanitize_key( $_POST['object_type'] );

        if ( $object_type === 'order' ) {
            $object = er_get_order( $object_id );
        } else {
            $object = er_get_reservation( $object_id );
        }

        // Parse the jQuery serialized items.
        $items = array();
        parse_str( wp_unslash( $_POST['items'] ), $items ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        // Save order items.
        er_save_receipt_items( $object, $items );

        $object->save();

        // Get HTML to return.
        ob_start();
        include 'admin/meta-boxes/views/html-receipt-items.php';
        $items_html = ob_get_clean();
        $notes_html = '';

        if ( $object_type === 'order' ) {
            ob_start();
            $notes = er_get_order_notes( array( 'order_id' => $object_id ) );
            include 'admin/meta-boxes/views/html-order-notes.php';
            $notes_html = ob_get_clean();
        }

        wp_send_json_success(
            array(
                'html'       => $items_html,
                'notes_html' => $notes_html,
            )
        );

        wp_die();
    }

    /**
     * Add a reservation to an order
     */
    public static function add_reservation_to_order(){
        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['reservation_id'], $_POST['order_id'] ) ) {
            wp_die( -1 );
        }

        $response = array();

        try {
            $reservation_id = absint( $_POST['reservation_id'] );
            $reservation    = er_get_reservation( $reservation_id );

            if ( !$reservation ) {
                throw new Exception( __( 'Invalid reservation', 'easyReservations' ) );
            }

            if ( $reservation->get_order_id() ) {
                throw new Exception( sprintf( __( 'Reservation already attached to order #%d', 'easyReservations' ), $reservation->get_order_id() ) );
            }

            $order_id = absint( $_POST['order_id'] );
            $order    = er_get_order( $order_id );

            if ( !$order ) {
                throw new Exception( __( 'Invalid order', 'easyReservations' ) );
            }

            if( $order->find_reservation( $reservation_id ) ){
                throw new Exception( __( 'Reservation already attached to order', 'easyReservations' ) );
            }

            $order->add_reservation( $reservation_id, false );

            $order->calculate_taxes();
            $order->calculate_totals( false );
            $order->save();

            $reservation->set_order_id( $order_id );
            $reservation->save();

            if( isset( $_POST['reservation'] ) ){
                ob_start();
                ER_Meta_Box_Reservation_Order::output( $reservation );
                $response['html'] = ob_get_clean();
            } else {
                ob_start();
                $object = $order;
                include 'admin/meta-boxes/views/html-receipt-items.php';
                $response['html'] = ob_get_clean();
            }
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
        }

        wp_send_json_success( $response );
    }

    /**
     * Remove a reservation from an order
     */
    public static function remove_reservation_from_order(){
        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['reservation_id'], $_POST['order_id'] ) ) {
            wp_die( -1 );
        }

        $response = array();

        try {
            $reservation_id = absint( $_POST['reservation_id'] );
            $order_id = absint( $_POST['order_id'] );
            $order = er_get_order( $order_id );

            if ( !$order ) {
                throw new Exception( __( 'Invalid order', 'easyReservations' ) );
            }

            $reservation_item = $order->find_reservation( $reservation_id );

            if( !$reservation_item ){
                throw new Exception( __( 'Reservation not attached to order', 'easyReservations' ) );
            }

            $reservation_item->delete();

            //Load order again after deleting item
            $order = er_get_order( $order_id );

            $order->calculate_taxes();
            $order->calculate_totals( false );
            $order->save();

            $reservation = er_get_reservation( $reservation_id );

            if( !$reservation ){
                throw new Exception( __( 'Invalid reservation', 'easyReservations' ) );
            }

            ob_start();
            ER_Meta_Box_Reservation_Order::output( $reservation );
            $response['html'] = ob_get_clean();
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
        }

        wp_send_json_success( $response );
    }

    //Add custom data to order or reservation
    public static function add_custom(){
        check_ajax_referer( 'custom', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['object_id'], $_POST['custom_field'] ) ) {
            wp_die( -1 );
        }

        $custom_id     = absint( $_POST['custom_field'] );
        $object_id     = absint( $_POST['object_id'] );
        $object_type   = sanitize_key( $_POST['object_type'] );
        $custom_fields = ER_Custom_Data::get_settings();

        if( $object_type === 'order' ){
            $object = er_get_order( $object_id );
        } else {
            $object = er_get_reservation( $object_id );
        }

        if ( $object && isset( $custom_fields[$custom_id], $_POST['er-custom-' . $custom_id] ) ) {
            $custom = ER_Custom_Data::get_data( $custom_id );
            $items_html = '';

            if ( isset( $custom_fields[$custom_id]['price'] ) ) {
                $custom['custom_total'] = ER_Custom_Data::calculate( $custom['custom_id'], $custom['custom_value'], $object->get_custom_data(), $object );
            } else {
                $custom['custom_total'] = 0;
            }

            $object->add_custom( $custom, false );

            if ( isset( $_POST['custom_field_fee'] ) ) {
                $item = new ER_Receipt_Item_Fee( 0 );
                $item->set_custom_id( $custom['custom_id'] );
                $item->set_name( $custom['custom_title'] );
                $item->set_value( $custom['custom_display'] );
                $item->set_subtotal( $custom['custom_total'] );
                $item->set_total( $custom['custom_total'] );

                $object->add_item( $item );
                $object->calculate_taxes( false );
                $object->calculate_totals( false );
                $object->save();

                // Get HTML to return.
                ob_start();
                include 'admin/meta-boxes/views/html-receipt-items.php';
                $items_html = ob_get_clean();
            }

            if ( $object_id  > 0 ) {
                $object->save();
            }

            ob_start();
            include 'admin/meta-boxes/views/html-custom-data.php';
            $custom_html = ob_get_clean();

            wp_send_json_success(
                array(
                    'html'       => $items_html,
                    'custom_html' => $custom_html,
                )
            );
        }

        wp_die();
    }

    /**
     * Load order items via ajax.
     */
    public static function load_receipt_items() {
        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['order_id'] ) ) {
            wp_die( -1 );
        }

        // Return HTML items.

        $object_id   = absint( $_POST['object_id'] );
        $object_type = sanitize_key( $_POST['object_type'] );

        if ( $object_type === 'order' ) {
            $object = er_get_order( $object_id );
        } else {
            $object = er_get_reservation( $object_id );
        }
        include 'admin/meta-boxes/views/html-receipt-items.php';
        wp_die();
    }

    /**
     * Add order note via ajax.
     */
    public static function add_order_note() {
        check_ajax_referer( 'add-order-note', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['post_id'], $_POST['note'], $_POST['note_type'] ) ) {
            wp_die( -1 );
        }

        $post_id   = absint( $_POST['post_id'] );
        $note      = wp_kses_post( trim( wp_unslash( $_POST['note'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $note_type = er_clean( wp_unslash( $_POST['note_type'] ) );

        $is_customer_note = ( 'customer' === $note_type ) ? 1 : 0;

        if ( $post_id > 0 ) {
            $order      = er_get_order( $post_id );
            $comment_id = $order->add_order_note( $note, $is_customer_note, true );
            $note       = er_get_order_note( $comment_id );

            $note_classes   = array( 'note' );
            $note_classes[] = $is_customer_note ? 'customer-note' : '';
            $note_classes   = apply_filters( 'easyreservations_order_note_class', array_filter( $note_classes ), $note );
            ?>
            <li rel="<?php echo absint( $note->id ); ?>" class="<?php echo esc_attr( implode( ' ', $note_classes ) ); ?>">
                <div class="note_content">
                    <?php echo wp_kses_post( wpautop( wptexturize( make_clickable( $note->content ) ) ) ); ?>
                </div>
                <p class="meta">
                    <abbr class="exact-date" title="<?php echo esc_attr( $note->date_created->date( 'y-m-d h:i:s' ) ); ?>">
                        <?php
                        /* translators: $1: Date created, $2 Time created */
                        printf( esc_html__( '%1$s at %2$s', 'easyReservations' ), esc_html( $note->date_created->date_i18n( er_date_format() ) ), esc_html( $note->date_created->date_i18n( er_time_format() ) ) );
                        ?>
                    </abbr>
                    <?php
                    if ( 'system' !== $note->added_by ) :
                        /* translators: %s: note author */
                        printf( ' ' . esc_html__( 'by %s', 'easyReservations' ), esc_html( $note->added_by ) );
                    endif;
                    ?>
                    <a href="#" class="delete_note" role="button"><?php esc_html_e( 'Delete note', 'easyReservations' ); ?></a>
                </p>
            </li>
            <?php
        }
        wp_die();
    }

    /**
     * Delete order note via ajax.
     */
    public static function delete_order_note() {
        check_ajax_referer( 'delete-order-note', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['note_id'] ) ) {
            wp_die( -1 );
        }

        $note_id = (int) $_POST['note_id'];

        if ( $note_id > 0 ) {
            er_delete_order_note( $note_id );
        }
        wp_die();
    }

    /**
     * Data for timeline column
     */
    public static function timeline_data() {
        global $wpdb;

        check_ajax_referer( 'easyreservations-timeline', 'security' );
	    define( 'RESERVATIONS_ADMIN_REQUEST', true );

	    $start         = new ER_DateTime( sanitize_text_field( $_POST['start'] ) );
	    $end           = new ER_DateTime( sanitize_text_field( $_POST['end'] ) );
	    $interval      = absint( $_POST['interval'] );
	    $date_interval = new DateInterval( 'PT' . $interval . 'S' );
	    $return        = array();
	    $add           = isset( $_POST['add'] ) ? sanitize_text_field( $_POST['add'] ) : false;
	    $resource      = isset( $_POST['resource'] ) ? absint( $_POST['resource'] ) : false;
	    $space         = isset( $_POST['space'] ) ? absint( $_POST['space'] ) : false;

	    if( $add ){
	        $arrival = new ER_DateTime( sanitize_text_field( $_POST['arrival'] ) );
	        $departure = new ER_DateTime( sanitize_text_field( $_POST['departure'] ) );

		    if( $add === 'reservation' ){
	            $reservation = new ER_Reservation(0);
	            $reservation->set_resource_id( $resource );
	            $reservation->set_space( $space );
	            $reservation->set_arrival( $arrival );
	            $reservation->set_departure( $departure );
	            $reservation->set_status( 'approved' );
			    $reservation->set_title( __( 'No title', 'easyReservations' ) );

			    $availability = $reservation->check_availability();

	            if( !$availability ){
		            $reservation->calculate_taxes( false );
		            $reservation->calculate_totals( false );

		            $reservation->save();
                } else {
	                var_dump('TODO');
                }
            } else {
		        if( $add === 'resource' ){
			        $availability_filter = array();
			        $all_filter          = get_post_meta( $resource, 'easy_res_filter', true );

			        if( $all_filter && !empty( $all_filter ) ){
			            foreach( $all_filter as $key => $filter ){
			                if( $filter['type'] === 'unavail' ){
				                $availability_filter[] = $filter;
				                unset( $all_filter[$key] );
			                }
                        }
                    } else {
				        $all_filter = array();
                    }
                } else {
			        $availability_filter = get_option( 'reservations_availability_filters', array() );
		        }

		        $filter = array(
			        'name' => 'Timeline',
			        'type' => 'unavail',
			        'imp'  => 1,
			        'cond' => 'range',
			        'from' => $arrival->format( 'Y-m-d H:i:s' ),
			        'to'   => $departure->format( 'Y-m-d H:i:s' )
		        );

			    $availability_filter[] = $filter;

			    usort( $availability_filter, function ( $a, $b ) {
				    return $a['imp'] - $b['imp'];
			    } );

			    if ( $add === 'resource' ) {
				    update_post_meta( $resource, 'easy_res_filter', $all_filter + $availability_filter );
			    } else {
				    update_option( 'reservations_availability_filters', $availability_filter );
			    }
		    }
        }

	    foreach ( ER()->resources()->get_accessible() as $resource ) {
            $return[$resource->get_id()] = array();

            if ( $interval == DAY_IN_SECONDS ) {
                $hour = $resource->get_default_arrival_time();
                $start->setTime( $hour, 0 );
                $end->setTime( $hour, 0 );
                $add_to_interval = ( $resource->get_default_departure_time() * 3600 ) - ( $hour * 3600 );
            } else {
                $hour = 0;
                $start->setTime( intval( $_POST['start_hour'] ), 0 );
                $end->setTime( intval( $_POST['end_hour'] ), 0 );
                $add_to_interval = 0;
            }

            $availability = new ER_Resource_Availability( $resource, 0, 1, 0, false, $interval );
            $date         = clone $start;

            while ( $date < $end ) {
                $return[$resource->get_id()][( $date->getOffsetTimestamp() - ( $hour * 3600 ) )] = $availability->check_whole_period( $date, $interval + $add_to_interval, false, true );
                $date->add( $date_interval );
            }
        }

        if ( $interval == DAY_IN_SECONDS ) {
            $start->setTime( 0, 0 );
            $end->setTime( 23, 59 );
        } else {
            $start->setTime( intval( $_POST['start_hour'] ), 0 );
            $end->setTime( intval( $_POST['end_hour'] ), 0 );
        }

        $reservations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.id as id, r.arrival arrival, r.departure as departure, r.resource as resource, r.space as space, r.adults as adults, r.children as children, r.status as status, r.order_id as order_id, m.meta_value as title " .
                "FROM {$wpdb->prefix}reservations as r " .
                "LEFT JOIN {$wpdb->prefix}reservationmeta as m ON m.reservation_id = r.id AND m.meta_key = %s " .
                "WHERE %s <= r.departure AND %s >= r.arrival AND status IN ('" . implode( "', '", er_reservation_get_approved_statuses() ) . "') " .
                "ORDER BY arrival",
                '_title',
                $start->format( 'Y-m-d H:i:s' ),
                $end->format( 'Y-m-d H:i:s' )
            )
        );

        wp_send_json( array(
            'data'         => $return,
            'reservations' => $reservations
        ) );

        exit;
    }

    /**
     * Update reservation from timeline
     */
    public static function timeline_update_reservation() {
        global $wpdb;

        check_ajax_referer( 'easyreservations-timeline', 'security' );

	    $id        = absint( $_POST['id'] );
	    $arrival   = new ER_DateTime( sanitize_text_field( $_POST['arrival'] ) );
	    $departure = new ER_DateTime( sanitize_text_field( $_POST['departure'] ) );
	    $resource  = absint( $_POST['resource'] );
	    $space     = absint( $_POST['space'] );
	    $title     = sanitize_text_field( $_POST['title'] );
	    $status    = sanitize_key( $_POST['status'] );

	    $reservation = er_get_reservation( $id );

	    if( $reservation ){
		    $reservation->set_arrival( $arrival );
		    $reservation->set_departure( $departure );
		    $reservation->set_resource_id( $resource );
		    $reservation->set_space( $space );
		    $reservation->set_title( $title );

		    if( $status !== $reservation->get_status() ){
		        $reservation->update_status( $status, __( 'Reservation status changed in timeline:', 'easyReservations' ), true );
            }

		    $reservation->save();
        }

        exit;
    }

    /**
     * Search for orders and echo json.
     *
     * @param string $term (default: '') Term to search for.
     */
    public static function json_search_order( $term = '' ) {
        check_ajax_referer( 'search-order', 'security' );

        if ( empty( $term ) && isset( $_GET['term'] ) ) {
            $term = (string) er_clean( wp_unslash( $_GET['term'] ) );
        }

        if ( empty( $term ) ) {
            wp_die();
        }

        $found_orders = array();

        $query_args_meta = array(
            'posts_per_page' => -1,
            'post_type'      => 'easy_order',
            'post_status' => array(array_keys(ER_Order_Status::get_statuses())),
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_first_name',
                    'value'   => $term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key'     => '_last_name',
                    'value'   => $term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key'     => '_company',
                    'value'   => $term,
                    'compare' => 'LIKE'
                ),
            )
        );

        $posts = get_posts( $query_args_meta );

        if ( is_numeric( $term ) ){
            $found = false;

            foreach( $posts as $post ){
                if( $post->ID === $term ){
                    $found = true;
                }
            }

            if( !$found ){
                $post = get_post( absint( $term ) );

                if( $post ){
                    $posts[] = $post;
                }
            }
        }

        foreach ( $posts as $post ) {
            $first_name = get_post_meta( $post->ID, '_first_name', true );
            $last_name = get_post_meta( $post->ID, '_last_name', true );
            $company = get_post_meta( $post->ID, '_company', true );

            $label = '#' . $post->ID;
            $label .= $first_name ? ' ' . $first_name : '';
            $label .= $last_name ? ' ' . $last_name : '';
            $label .= $company ? ' (' . $company . ')' : '';

            $found_orders[$post->ID] = $label;
        }

        wp_send_json( $found_orders );
    }

    /**
     * Handle a refund via the edit order screen.
     *
     * @throws Exception To return errors.
     */
    public static function refund_line_items() {
        ob_start();

        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) ) {
            wp_die( -1 );
        }

        $order_id               = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $refund_amount          = isset( $_POST['refund_amount'] ) ? er_format_decimal( sanitize_text_field( wp_unslash( $_POST['refund_amount'] ) ), er_get_price_decimals() ) : 0;
        $refunded_amount        = isset( $_POST['refunded_amount'] ) ? er_format_decimal( sanitize_text_field( wp_unslash( $_POST['refunded_amount'] ) ), er_get_price_decimals() ) : 0;
        $refund_reason          = isset( $_POST['refund_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['refund_reason'] ) ) : '';
        $line_item_totals       = isset( $_POST['line_item_totals'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_totals'] ) ), true ) : array();
        $line_item_tax_totals   = isset( $_POST['line_item_tax_totals'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_tax_totals'] ) ), true ) : array();
        $api_refund             = isset( $_POST['api_refund'] ) && 'true' === $_POST['api_refund'];
        $restock_refunded_items = isset( $_POST['restock_refunded_items'] ) && 'true' === $_POST['restock_refunded_items'];
        $refund                 = false;
        $response               = array();

        try {
            $order      = er_get_order( $order_id );
            $max_refund = er_format_decimal( $order->get_total() - $order->get_total_refunded(), er_get_price_decimals() );

            if ( !$refund_amount || $max_refund < $refund_amount || 0 > $refund_amount ) {
                throw new Exception( __( 'Invalid refund amount', 'easyReservations' ) );
            }

            if ( er_format_decimal( $order->get_total_refunded(), er_get_price_decimals() ) !== $refunded_amount ) {
                throw new Exception( __( 'Error processing refund. Please try again.', 'easyReservations' ) );
            }

            // Prepare line items which we are refunding.
            $line_items = array();
            $item_ids   = array_unique( $line_item_totals );

            foreach ( $item_ids as $item_id ) {
                $line_items[$item_id] = array(
                    'qty'          => 0,
                    'refund_total' => 0,
                    'refund_tax'   => array(),
                );
            }
            foreach ( $line_item_totals as $item_id => $total ) {
                $line_items[$item_id]['refund_total'] = er_format_decimal( $total );
            }
            foreach ( $line_item_tax_totals as $item_id => $tax_totals ) {
                $line_items[$item_id]['refund_tax'] = array_filter( array_map( 'er_format_decimal', $tax_totals ) );
            }

            // Create the refund object.
            $refund = er_create_refund(
                array(
                    'amount'         => $refund_amount,
                    'reason'         => $refund_reason,
                    'order_id'       => $order_id,
                    'line_items'     => $line_items,
                    'refund_payment' => $api_refund,
                    'restock_items'  => $restock_refunded_items,
                )
            );

            if ( is_wp_error( $refund ) ) {
                throw new Exception( $refund->get_error_message() );
            }

            if ( did_action( 'easyreservations_order_fully_refunded' ) ) {
                $response['status'] = 'fully_refunded';
            }
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
        }

        // wp_send_json_success must be outside the try block not to break phpunit tests.
        wp_send_json_success( $response );
    }

    /**
     * Delete a refund.
     */
    public static function delete_refund() {
        check_ajax_referer( 'receipt-item', 'security' );

        if ( !current_user_can( 'edit_easy_orders' ) || !isset( $_POST['refund_id'] ) ) {
            wp_die( -1 );
        }

        $refund_ids = array_map( 'absint', is_array( $_POST['refund_id'] ) ? wp_unslash( $_POST['refund_id'] ) : array( wp_unslash( $_POST['refund_id'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        foreach ( $refund_ids as $refund_id ) {
            if ( $refund_id && 'easy_order_refund' === get_post_type( $refund_id ) ) {
                $refund   = er_get_order_refund( $refund_id );
                $order_id = $refund->get_parent_id();
                $refund->delete( true );
                do_action( 'easyreservations_refund_deleted', $refund_id, $order_id );
            }
        }
        wp_die();
    }
}

ER_AJAX::init();
