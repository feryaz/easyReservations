<?php
//Prevent direct access to file
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class ER_Form {

    /**
     * Get form template
     *
     * @param string $id form template identifier
     * @return string form template HTML
     */
    protected function get_form_template( $id = '' ) {
        if ( empty( $id ) ) {
            $template = get_option( "reservations_form", '' );
        } else {
            $template = get_option( 'reservations_form_' . sanitize_key( $id ), '' );
        }

        return apply_filters( 'easyreservations_form_content', wp_kses_post( stripslashes( $template ) ), $id );
    }

    /**
     * Generate form from template
     *
     * @param string $form_id
     * @param string $form_hash
     * @param bool $object
     * @return string
     */
    public function generate( $form_id, $form_hash, $object = false ) {
        $form_template = $this->get_form_template( $form_id );
        $fields        = er_form_template_parser( $form_template, true );

        foreach ( $fields as $field ) {
            $tags = shortcode_parse_atts( $field );

            switch ( $tags[0] ) {
                case "easy_receipt":
                    $form_template = str_replace( '[' . $field . ']', do_shortcode( '[' . $field . ']' ), $form_template );
                    break;

                case "custom":
                    $form_field = '';

                    if ( isset( $tags['id'] ) && ER_Custom_Data::exists( $tags['id'] ) ) {
                        //Try to get value for checkout data from user meta
                        if( $form_id === 'checkout' ) {
                            $value = ER()->customer->get_meta( $tags['id'] );

                            if( $value ){
                                $tags['value'] = $value;
                            }
                        }

                        $form_field = ER_Custom_Data::generate( $tags['id'], $tags );
                    }

                    $form_template = str_replace( '[' . $field . ']', $form_field, $form_template );
                    break;

                default:
                    if ( $form_id == 'checkout' ) {
                        $form_field = apply_filters(
                            'easyreservations_form_field',
                            er_form_generate_checkout_field( $field, $form_hash, $object ),
                            $tags
                        );
                    } else {
                        $form_field = apply_filters(
                            'easyreservations_form_field',
                            er_form_generate_reservation_field( $field, 'easy-form-', $form_hash, $object ),
                            $tags
                        );
                    }

                    if ( is_array( $form_field ) ) {
                        $form_field = er_form_get_field( $form_field, true );
                    }

                    $form_template = str_replace( '[' . $field . ']', $form_field, $form_template );
                    break;

            }
        }

        return apply_filters( 'easyreservations_form_template', $form_template );
    }

    /**
     * If checkout failed during an AJAX call, send failure response.
     */
    protected function send_ajax_failure_response() {
        if ( is_easyreservations_ajax() ) {
            // Only print notices if not reloading the checkout, otherwise they're lost in the page reload.
            if ( !isset( ER()->session->reload_checkout ) ) {
                $messages = er_print_notices( true );
            }

            $response = array(
                'result'   => 'failure',
                'messages' => isset( $messages ) ? $messages : '',
                'refresh'  => isset( ER()->session->refresh_totals ),
                'reload'   => isset( ER()->session->reload_checkout ),
            );

            unset( ER()->session->refresh_totals, ER()->session->reload_checkout );

            wp_send_json( $response );
            exit;
        }
    }

    /**
     * Retrieve custom data from posted data
     *
     * @param WP_Error                $errors
     * @param ER_Reservation|ER_Order $object
     * @param string|bool             $template_id
     * @return array
     */
    public function get_form_data_custom( &$errors, $object, $template_id = false ) {
        $template_id   = $template_id ? $template_id : sanitize_key( $_POST['easy_form_id'] );
        $template      = $this->get_form_template( $template_id );
        $tags          = er_form_template_parser( $template, true );
        $customs       = array();
        $custom_fields = ER_Custom_Data::get_settings();

        foreach ( $tags as $fields ) {
            $field = shortcode_parse_atts( $fields );

            if ( $field[0] == "custom" && isset( $field["id"] ) ) {
                $id = absint( $field["id"] );

                if ( isset( $custom_fields[$id] ) ) {
                    if ( isset( $_POST['er-custom-' . $id] ) ) {
                        $custom = ER_Custom_Data::get_data( $id );

                        if ( $custom ) {
                            $customs[$id] = $custom;
                        }
                    } elseif ( isset( $custom_fields['required'] ) ) {
                        $errors->add( 'er-custom-' . $id, sprintf( __( '%s is required', 'easyReservations' ), $custom_fields[$id]['title'] ) );
                    }
                }
            }
        }

        foreach ( $customs as $id => $custom ) {
            if ( isset( $custom_fields[$id], $custom_fields[$id]['price'] ) ) {
                $customs[$id]['custom_total'] = ER_Custom_Data::calculate( $custom['custom_id'], $custom['custom_value'], $customs, $object );
            }
        }

        return $customs;
    }

    /**
     * Enqueue form scripts and styles
     */
    protected function enqueue() {
        wp_enqueue_script( 'er-datepicker' );
        wp_enqueue_script( 'er-form' );
        wp_enqueue_style( 'er-datepicker' );
    }
}
