<?php
defined( 'ABSPATH' ) || exit;

/**
 * Initialize and load the cart functionality.
 *
 * @return void
 */
function er_load_cart() {
    ER()->initialize_session();
    ER()->initialize_cart();
}

/**
 * Clears the cart session when called.
 */
function er_empty_cart() {
    if ( !isset( ER()->cart ) || '' === ER()->cart ) {
        ER()->cart = new ER_Cart();
    }
    ER()->cart->empty_cart( false );
}

/**
 * Empties cart and session
 */
function er_empty_cart_and_session() {
    ER()->cart->empty_cart( true );
    ER()->session->destroy_session();
}

/**
 * Retrieves unvalidated referer from '_wp_http_referer' or HTTP referer.
 *
 * Do not use for redirects, use {@see wp_get_referer()} instead.
 *
 * @return string|false Referer URL on success, false on failure.
 */
function er_get_raw_referer() {
    if ( function_exists( 'wp_get_raw_referer' ) ) {
        return wp_get_raw_referer();
    }

    if ( !empty( $_REQUEST['_wp_http_referer'] ) ) { // WPCS: input var ok, CSRF ok.
        return wp_unslash( $_REQUEST['_wp_http_referer'] ); // WPCS: input var ok, CSRF ok, sanitization ok.
    } elseif ( !empty( $_SERVER['HTTP_REFERER'] ) ) { // WPCS: input var ok, CSRF ok.
        return wp_unslash( $_SERVER['HTTP_REFERER'] ); // WPCS: input var ok, CSRF ok, sanitization ok.
    }

    return false;
}

/**
 * Clear cart after payment.
 */
function er_clear_cart_after_payment() {
    global $wp;

    if ( !empty( $wp->query_vars['order-received'] ) ) {

        $order_id  = absint( $wp->query_vars['order-received'] );
        $order_key = isset( $_GET['key'] ) ? er_clean( wp_unslash( $_GET['key'] ) ) : ''; // WPCS: input var ok, CSRF ok.

        if ( $order_id > 0 ) {
            $order = er_get_order( $order_id );

            if ( $order && hash_equals( $order->get_order_key(), $order_key ) ) {
                ER()->cart->empty_cart();
            }
        }
    }

    if ( ER()->session && ER()->session->order_awaiting_payment > 0 ) {
        $order = er_get_order( ER()->session->order_awaiting_payment );

        if ( $order && $order->get_id() > 0 ) {
            // If the order has not failed, or is not pending, the order must have gone through.
            if ( !$order->has_status( array( 'failed', 'pending', 'cancelled' ) ) ) {
                ER()->cart->empty_cart();
            }
        }
    }
}

add_action( 'get_header', 'er_clear_cart_after_payment' );

/**
 * Get the fee value.
 *
 * @param ER_Receipt_Item_Fee $fee Fee data.
 */
function er_cart_totals_fee_html( $fee ) {
    $cart_totals_fee_html = ER()->cart->display_prices_including_tax() ? er_price( $fee->get_total() + $fee->get_total_tax() ) : er_price( $fee->get_total() );

    echo apply_filters( 'easyreservations_cart_totals_fee_html', $cart_totals_fee_html, $fee ); // WPCS: XSS ok.
}

/**
 * Get the subtotal.
 */
function er_cart_totals_subtotal_html() {
    echo ER()->cart->get_cart_subtotal(); // WPCS: XSS ok.
}

/**
 * Get taxes total.
 */
function er_cart_totals_taxes_total_html() {
    echo apply_filters( 'easyreservations_cart_totals_taxes_total_html', er_price( ER()->cart->get_taxes_total(), true ) ); // WPCS: XSS ok.
}

/**
 * Get order total html including inc tax if needed.
 */
function er_cart_totals_order_total_html() {
    $value = '<strong>' . ER()->cart->get_total() . '</strong> ';

    // If prices are tax inclusive, show taxes here.
    if ( er_tax_enabled() && ER()->cart->display_prices_including_tax() ) {
        $tax_string_array = array();
        $cart_tax_totals  = ER()->cart->get_tax_totals();

        if ( get_option( 'reservations_tax_total_display' ) === 'itemized' ) {
            foreach ( $cart_tax_totals as $code => $tax ) {
                $tax_string_array[] = sprintf( '%s %s', $tax->formatted_amount, $tax->label );
            }
        } elseif ( !empty( $cart_tax_totals ) ) {
            $tax_string_array[] = sprintf( '%s %s', er_price( ER()->cart->get_taxes_total( true, true ) ), ER()->countries->tax_or_vat() );
        }
    }

    echo apply_filters( 'easyreservations_cart_totals_order_total_html', $value ); // WPCS: XSS ok.
}

/**
 * Add to cart messages.
 *
 * @param ER_Reservation|ER_Custom[] $items Resource ID list or single resources ID.
 * @param bool      $return Return message rather than add it.
 *
 * @return string
 */
function er_add_to_cart_message( $items, $return = false ) {
    $titles   = array();
    $url      = esc_url_raw( $_POST['redirect'] );
    $cart_url = esc_url( er_get_cart_url() );

    if ( !is_array( $items ) ) {
        $items = array( $items );
    }

    foreach ( $items as $item ) {
        /* translators: %s: item name */
        $titles[] = apply_filters( 'easyreservations_add_to_cart_item_name_in_quotes', sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'easyReservations' ), strip_tags( $item->get_name() ) ), $item );
    }

    $titles      = array_filter( $titles );
    $item_string = '';

    foreach ( $titles as $key => $item ) {
        $item_string .= $item;

        if ( count( $items ) === $key + 2 ) {
            $item_string .= ' ' . __( 'and', 'easyReservations' ) . ' ';
        } elseif ( count( $items ) !== $key + 1 ) {
            $item_string .= ', ';
        }
    }

    /* translators: %s: item name */
    $added_text = sprintf( _n( '%s has been added to your cart.', '%s have been added to your cart.', count( $items ), 'easyReservations' ), $item_string );

    // Output success messages.
    if ( 'yes' === get_option( 'reservations_cart_redirect_after_add' ) ) {
        if( $url === $cart_url ){
            $message = sprintf( '<a href="%s" tabindex="1" class="button er-forward">%s</a> %s', esc_url( er_get_page_permalink( 'shop' ) ), esc_html__( 'Continue', 'easyReservations' ), esc_html( $added_text ) );
        } else {
            $message = sprintf( '<a href="%s" tabindex="1" class="button er-forward">%s</a> %s', esc_url( $cart_url ), esc_html__( 'View cart', 'easyReservations' ), esc_html( $added_text ) );
        }
    } else {
        if(!$url){
            $url = $cart_url;
        }

        $message = sprintf( '<a href="%s" tabindex="1" class="button er-forward">%s</a> %s', esc_url( $url ), esc_html__( 'Continue', 'easyReservations' ), esc_html( $added_text ) );
    }

    $message = apply_filters( 'easyreservations_add_to_cart_message_html', $message, $items );

    if ( $return ) {
        return $message;
    } else {
        er_add_notice( $message,  'success' );
    }
}
